<?php namespace Collections\Tailwind;

use
    CollectionException,
    DB,
    Illuminate\Database\Eloquent\Collection,
    InvalidArgumentException,
    LogicException,
    Models\Tailwind\EloquentModel,
    PDO;

/**
 * Class EloquentCollection
 *
 * @package Collections\Tailwind
 */
class EloquentCollection extends Collection implements DatabaseInterface
{

    /**
     * The underlying model of the collection, if there is one
     *
     * @var $related_model \Models\Tailwind\EloquentModel
     */
    protected $related_model;
    /**
     * A list of columns that should not be able to be saved to the db
     *
     * @var $guarded array
     */
    protected $guarded = [];

    /**
     * @param array $items
     */
    public function __construct($items = array())
    {
        $this->related_model = $this->getRelatedModel();

        /** @var $model EloquentModel */
        foreach ($items as $model) {
            $this->items[$model->uniqueIndex()] = $model;
        }
    }

    /**
     * @param EloquentModel $item
     * @param bool          $index
     *
     * @return $this|Collection
     */
    public function add(EloquentModel $item, $index = true)
    {

        if (is_bool($index) && $index === true) {
            $this->items[$item->uniqueIndex()] = $item;
        } else if ($index) {
            $this->items[$index] = $item;
        } else {

            $this->items[] = $item;
        }

        return $this;
    }

    /**
     * Get the models in the collection as an array
     *
     * @return array
     */
    public function asArray()
    {
        return $this->items;
    }

    /**
     * Calculates the average of the numerical columns in the set
     *
     * @param $column
     *
     * @throws \CollectionException
     * @return float
     */
    public function average($column)
    {
        if (!is_int($this->first()->$column)) {
            throw new CollectionException(
                "$column is not an integer [example value: " . $this->first()->$column
            );
        }

        return round($this->sumColumn($column) / $this->count());
    }

    /**
     * This is used in the command to export data from the primary database
     * into vagrant. This is written to a file and then downloaded and interpreted
     * to be run in the vagrant DB.
     *
     * @return string
     */
    public function export()
    {
        $table   = $this->getTable();
        $columns = $this->getColumns();

        /*
         * Create the SQL that will be run
         * and use ? placeholders for the values
         */
        $sql = "INSERT IGNORE INTO $table (`";
        $sql .= implode('`,`', $columns);
        $sql = rtrim($sql, ',`');
        $sql .= '`) VALUES ';

        foreach ($this->items as $model) {
            $sql .= '(';
            foreach ($model->columns as $column) {
                $sql .= '?,';
            }
            $sql = rtrim($sql, ',');
            $sql .= '), ';
        }

        $sql = rtrim($sql, ', ') . ';';

        $data = array();
        foreach ($this->items as $key => $model) {
            $row = array();
            foreach ($model->columns as $column) {
                $row[$column] = $model->$column;
            }
            $data[$key] = $row;
        }


        return array(
            'query' => $sql,
            'data'  => $data
        );

    }

    /**
     * @param array $columns
     *
     * @throws LogicException
     * @return $this
     */
    public function guard($columns = array())
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }

        foreach ($columns as $column) {
            if (!in_array($column, $this->getColumns())) {
                throw new LogicException("
                $column is not a column in this collection and can't be guarded.
                ");
            }
        }

        $this->guarded = $columns;

        return $this;
    }

    /**
     * Attempts to insert a new row, if there is a primary key constraint it
     * will not do... anything....
     *
     * @param array $ignore_columns
     *
     * @return \Collections\Tailwind\DatabaseInterface|mixed
     */
    public function insertIgnore(array $ignore_columns = array())
    {
        $columns = array_diff($this->getColumns(), $ignore_columns);

        return $this->insertIgnoreOnly($columns);
    }

    /**
     * Attempts to insert a row only using the array of columns passed
     * If the key exists, don't do anything
     *
     * @param  array $columns An array of columns to update
     *
     * @return mixed
     */
    public function insertIgnoreOnly(array $columns)
    {
        return $this->writeToDB('INSERT IGNORE INTO', $columns);
    }

    /**
     * Attempts to insert a new row, if there is a primary key constraint
     * and there is a duplicate then we update the row
     *
     *
     * @param array $ignore_columns
     * @param array $rules
     *
     * @return self
     */
    public function insertUpdate(array $ignore_columns = array(), array $rules = array())
    {
        $columns = array_diff($this->getColumns(), $ignore_columns);

        return $this->insertUpdateOnly($columns, $rules);
    }

    /**
     * Attempts to insert a row only using the array of columns passed
     * if there is a duplicate, update those columns
     *
     * @param  array $columns An array of columns to update
     *
     * @param array  $rules
     *
     * @return mixed
     */
    public function insertUpdateOnly(array $columns, array $rules = array())
    {
        foreach ($columns as $column) {
            if (!array_key_exists($column, $rules)) {
                $rules[$column] = "$column = VALUES($column)";
            }
        }

        $duplicate_key_statement = ' ON DUPLICATE KEY UPDATE ' . implode($rules, ', ');

        return $this->writeToDB('INSERT INTO', $columns, $duplicate_key_statement);
    }

    /**
     * @author  Will
     *
     * @return bool
     */
    public function isNotEmpty()
    {
        return !$this->isEmpty();
    }

    /**
     * Merges a set of models into the existing collection.
     *
     * @param array|\Illuminate\Support\Collection|\Illuminate\Support\Contracts\ArrayableInterface $models
     *
     * @return EloquentCollection
     */
    public function merge($models)
    {
        if ($models instanceof EloquentCollection) {
            $this->items = $this->items + $models->asArray();
            return $this;
        }

        $this->items = array_merge($this->items,$models);

        return $this;
    }

    /**
     * If there is an autoincrementing primary key, don't include that in the
     * insert statement and get the last insert ID for the batch
     *
     * WARNING - this makes a ton of SQL calls. Might not be performant
     *
     * @param array $ignore_columns
     *
     * @return mixed
     */
    public function saveModelsAsNew(array $ignore_columns = array())
    {
        /** @var $model \Models\Tailwind\EloquentModel */
        foreach ($this->items as $key => $model) {
            $model->saveAsNew();
            $this->offsetSet($key, $model);
        }

        return true;
    }

    /**
     * Sets each model's column to that value
     *
     * @param $column
     * @param $value
     *
     * @return bool;
     */
    public function setAll($column, $value)
    {

        if ($this->isEmpty()) {
            return false;
        }

        foreach ($this->items as & $model) {
            $model->$column = $value;
        }

        return true;
    }

    /**
     * @author  Will
     *
     * @return $this
     */
    public function shuffle()
    {
        shuffle($this->items);

        return $this;
    }

    /**
     * Sorts the models by the column name.
     *
     * @author   Will Washburn
     * @example
     *
     *      $this->sortBy('timestamp');
     *      $this->sortBy('timestamp',SORT_ASC,'name',SORT_DESC);
     *      $this->sortBy(array('timestamp',SORT_ASC,'name',SORT_DESC));
     *
     * @throws InvalidArgumentException
     * @return Collection
     */
    public function sortByColumn()
    {
        if (func_num_args() == 0) {
            throw new InvalidArgumentException('You need to pass a parameter to sort by');
        }

        $args = func_get_args();

        if (func_num_args() == 1) {

            if (is_array($args[0])) {
                $args = $args[0];
            } else {
                $args[] = SORT_ASC;
            }
        }

        $data = $this->items;

        foreach ($args as $arg_key => $field) {
            if (is_string($field)) {
                $sort = array();
                foreach ($this->items as $key => $model)
                    $sort[$key] = $model->$field;
                $args[$arg_key] = $sort;
            }
        }
        $args[] = & $data;
        call_user_func_array('array_multisort', $args);
        $this->items = array_pop($args);

        return $this;
    }

    /**
     * @throws \CollectionException
     */
    public function sortByPrimaryKeys($sort_type = SORT_ASC)
    {
        if (empty($this->getPrimaryKeys())) {
            throw new CollectionException('The primary keys are not set');
        }

        $sort_arguments = array();

        foreach ($this->getPrimaryKeys() as $key) {
            $sort_arguments[] = $key;
            $sort_arguments[] = $sort_type;
        }

        $this->sortByColumn($sort_arguments);
    }

    /**
     * Calculates the difference between numerical columns in the set
     * If a set of array keys are given, it will only use those. If not, it will
     * use all of them
     *
     * @param $column
     *
     * @throws \CollectionException
     * @return int
     */
    public function spread($column)
    {

        if (!is_int($this->first()->$column)) {
            throw new CollectionException(
                "$column is not an integer [value is " . $this->first()->$column
            );
        }

        if ($this->count() < 2) {
            return 0;
        }

        return $this->first()->$column - $this->last()->$column;
    }

    /**
     * Take the field name of the model in each model add it to a string
     *
     * @author  Will
     *
     * @param        $field_name
     * @param string $separator
     *
     * @param string $encase_in
     *
     * @return string
     */
    public function stringifyField($field_name, $separator = ',', $encase_in = "'")
    {
        $string = '';
        foreach ($this->items as $model) {
            $string .= $encase_in . $model->$field_name . $encase_in . $separator;
        }
        $string = rtrim($string, $separator);

        return $string;
    }

    /**
     * @param callable $column
     *
     * @throws CollectionException
     * @author  Will
     *
     * @return int
     */
    public function sumColumn($column)
    {

        if (!is_int($this->first()->$column)) {
            throw new CollectionException(
                "$column is not an integer [example value: " . $this->first()->$column
            );
        }

        $total = 0;

        foreach ($this->items as $model) {
            $total += $model->$column;
        }

        return $total;
    }

    /**
     * Get the columns of this collection using the underpinned model
     *
     * @author  Will
     *
     * @return array
     */
    protected function getColumns()
    {
        if (!empty($this->related_model)) {
            return $this->related_model->getColumns();
        }

        return array();
    }

    /**
     * @return array
     */
    protected function getPrimaryKeys()
    {
        if (!empty($this->related_model)) {
            return $this->related_model->getPrimaryKeys();
        }

        return array();
    }

    /**
     * This method can be overwritten if it isn't as simple as what is below
     *
     * @return /Models/Tailwind/EloquentModel
     */
    protected function getRelatedModel()
    {
        $class = get_called_class();
        $model = str_singular($class);

        return $this->related_model = new $model();
    }

    /**
     * @return null|string
     */
    protected function getTable()
    {
        if (!empty($this->related_model)) {
            return $this->related_model->getTable();
        }

        return null;
    }

    /**
     * @return bool
     */
    protected function isIncrementing()
    {
        if (!empty($this->related_model)) {
            return $this->related_model->incrementing;
        }

        return false;
    }

    /**
     * @param string $statement_type
     * @param        $columns
     * @param bool   $append
     * @param bool   $skip_sql_log_bin
     *
     * @throws \LogicException
     * @internal param bool $use_insert_id
     *
     * @return bool
     */
    private function writeToDB(
        $statement_type,
        $columns,
        $append = false,
        $skip_sql_log_bin = false
    )
    {
        if (empty($columns)) {
            throw new LogicException(
                'The columns for this collection have been defined incorrectly.'
            );
        }

        foreach ($columns as $column) {
            if (!in_array($column, $this->guarded)) {
                throw new LogicException(
                    "$column is guarded and can't be written to the database. " .
                    "Please unguard or don't include it as a column to save."
                );
            }
        }

        if (!empty($this->getPrimaryKeys())) {
            $this->sortByPrimaryKeys();
        }

        $table = $this->getTable();

        if (empty($this->items)) {
            return false;
        }

        /*
         * Create the SQL that will be run
         * and use ? placeholders for the values
         */
        $sql = "$statement_type $table (`";
        $sql .= implode('`,`', $columns);
        $sql = rtrim($sql, ',`');
        $sql .= '`) VALUES ';

        foreach ($this->items as $model) {
            $sql .= '(';
            foreach ($columns as $column) {
                $sql .= '?,';
            }
            $sql = rtrim($sql, ',');
            $sql .= '), ';
        }

        /*
         * We take of the last added comma and append
         * any SQL that might be needed (for instance if it is an insert update
         * on duplicate key)
         */
        $sql = rtrim($sql, ', ');

        if ($sql != "" && $append) {
            $sql .= $append;
        }

        if ($sql != '') {

            if ($skip_sql_log_bin === true) {
                DB::getPdo()->setAttribute(
                  PDO::MYSQL_ATTR_INIT_COMMAND,
                  "SET sql_log_bin = 0"
                );
            }

            $STH = DB::getPdo()->prepare($sql);
            $xx  = 0;

            foreach ($this->items as $model) {

                foreach ($columns as $column) {
                    $xx++;
                    $STH->bindParam($xx, $model->$column);
                }
            }

            $STH->execute();

            if ($skip_sql_log_bin === true) {
                DB::getPdo()->setAttribute(
                  PDO::MYSQL_ATTR_INIT_COMMAND,
                  "SET sql_log_bin = 1"
                );
            }
        }

        return true;
    }
}


