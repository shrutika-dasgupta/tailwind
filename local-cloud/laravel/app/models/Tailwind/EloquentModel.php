<?php namespace Models\Tailwind;

use
    Collections\Tailwind\EloquentCollection,
    DB,
    Eloquent,
    LogicException,
    ModelException;

/**
 * Class EloquentModel
 *
 * @property $items array protected
 *
 * @package Models\Tailwind
 */
Class EloquentModel extends Eloquent implements DataBaseInterface
{
    /**
     * Since we do not _always_ have an incrementing value, lets default this
     * to false
     *
     * @var bool
     */
    public $incrementing = false;
    /**
     * We don't use updated_at and created_at the same way laravel does,
     * so we will default this to false as well
     *
     * @var bool
     */
    public $timestamps = false;
    /**
     * The columns in the database that this model maps to
     */
    protected $columns = [];
    /**
     * The primary key (or keys)
     *
     * @var array
     */
    protected $primary_keys = [];

    /**
     * Eloquent Models by default are blacklisted and guarded
     * We are code cowboys so we just let everything roll
     * 
     * @var array
     */
    protected $guarded = [];

    /**
     * This adds a fix for composite primary keys
     */
    public function __construct(array $attributes = array())
    {
        if (!is_array($this->primary_keys)) {
            $this->primaryKey   = $this->primary_keys;
            $this->primary_keys = [$this->primaryKey];
        } elseif (count($this->primary_keys) === 1) {
            $this->primaryKey = $this->primary_keys[0];
        } else {
            $this->incrementing = false;
        }

        return parent::__construct(array_merge($this->items,$attributes));
    }

    /**
     * Attempts to insert a new row, if there is a primary key constraint it
     * will not do... anything....
     *
     * @param array $ignore_columns
     *
     * @return self
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
     * @param array $ignore_columns
     * @param array $rules Special rules for the "ON DUPLICATE KEY UPDATE"
     *                     statement.
     *
     * @return self
     */
    public function insertUpdate(array $ignore_columns = array(), $rules = array())
    {
        $columns = array_diff($this->getColumns(), $ignore_columns);

        return $this->insertUpdateOnly($columns, $rules);

    }

    /**
     * Attempts to insert a row only using the array of columns passed
     * if there is a duplicate, update those columns
     *
     * @param  array $columns An array of columns to update
     * @param array  $rules   Special rules for the "ON DUPLICATE KEY UPDATE"
     *                        statement
     *
     * @return mixed
     */
    public function insertUpdateOnly(array $columns, $rules = array())
    {
        foreach ($columns as $column) {
            if (!array_key_exists($column, $rules)) {
                $rules[$column] = "$column = VALUES($column)";
            }
        }

        $duplicate_key_statement = ' ON DUPLICATE KEY UPDATE ' . implode($rules, ', ');

        if(in_array('updated_at',$this->getColumns())) {
            $this->updated_at = time();
        }

        return $this->writeToDB('INSERT INTO', $columns, $duplicate_key_statement);
    }

    /**
     * Sometimes, you may wish to return a custom Collection object with your
     * own added methods. You may specify this on your Eloquent model by
     * overriding the newCollection method:
     *
     * @param array $models
     *
     * @return \Illuminate\Database\Eloquent\Collection|void
     */
    public function newCollection(array $models = array())
    {
        return new EloquentCollection($models);
    }

    /**
     * If there is an autoincrementing primary key, don't include that in the
     * insert statement
     *
     * @param array $ignore_columns
     *
     * @return mixed
     */
    public function saveAsNew(array $ignore_columns = array())
    {
        if ($this->incrementing) {
            $ignore_columns[] = $this->primaryKey;
        }

        return $this->insertIgnore($ignore_columns);
    }

    /**
     * Gets the list of columns
     *
     * @author  Will
     *
     * @throws \ModelException
     * @return array
     */
    public function getColumns()
    {
        if (empty($this->columns) AND empty($this->attributes)) {
            throw new ModelException(
                'The columns are unknown. Please set them in the model file or' .
                ' make a query to the DB via ::find() to set the attributes'
            );
        }

        if (!empty($this->columns)) {
            return array_keys($this->columns);
        }

        if (!empty($this->attributes)) {
            $columns = [];
            foreach ($this->attributes as $column => $type) {
                $columns[] = $column;
            }

            return $columns;
        }
    }

    /**
     * @return array
     */
    public function getPrimaryKeys() {
        return $this->primary_keys;
    }

    /**
     * @param string $statement_type
     * @param        $columns
     * @param bool   $append
     * @param bool   $use_insert_id
     *
     * @throws \LogicException
     * @return bool
     */
    private function writeToDB(
        $statement_type,
        $columns,
        $append = false,
        $use_insert_id = false
    )
    {

        if (empty($columns)) {
            throw new LogicException(
                'The columns for this collection have been defined incorrectly.'
            );
        }

        foreach ($columns as $column) {
            if ($this->isGuarded($column)) {
                throw new LogicException(
                    "$column is guarded and can't be written to the database. " .
                    "Please unguard or don't include it as a column to save."
                );
            }
        }

        $sql = trim($statement_type);
        $sql .= ' ' . $this->getTable();
        $sql .= ' (`';
        $sql .= implode('`,`', $columns);
        $sql = rtrim($sql, ',`');
        $sql .= '`) VALUES (:';
        $sql .= implode(',:', $columns);
        $sql = rtrim($sql, ',:');
        $sql .= ')';

        if ($append) {
            $sql .= $append;
        }

        $STH = DB::getPdo()->prepare($sql);

        foreach ($columns as $column) {
            $STH->bindValue(':' . $column, $this->$column);
        }

        $result = $STH->execute();

        if ($use_insert_id) {
            $id                        = DB::getPdo()->lastInsertId();
            $this->{$this->primaryKey} = $id;
        }

        return $result;
    }

    /**
     * this is used in the export scripts to send
     * serialized data to the other script
     *
     * Hack city, hack hack city
     *
     * @return array
     */
    public function export(){

        /*
         * Construct SQL statement
         */
        $sql = 'INSERT IGNORE INTO';
        $sql .= ' ' . $this->table;
        $sql .= ' (`';
        $sql .= implode('`,`', $this->getColumns());
        $sql = rtrim($sql, ',`');
        $sql .= '`) VALUES (';
        foreach ($this->getColumns() as $column) {
            $sql.='?,';
        }
        $sql = rtrim($sql, ',');
        $sql .= ');';


        $data = array();
        foreach ($this->getColumns() as $column) {
            $data[$column] = $this->$column;
        }

        return array(
            'query' => $sql,
            'data' => [$data]
        );
    }

    /**
     * Increases a given column by a given amount
     * @author  Will
     *
     * @param       $column
     * @param int   $amount
     * @param array $wheres
     *
     * @return bool
     */
    public function increaseColumn($column, $amount=1, $wheres = array()) {
        return $this->incrementColumn($column, $amount, '+', $wheres);
    }

    /**
     * Decreases a given column by a given amount
     * @author  Will
     *
     * @param       $column
     * @param int   $amount
     * @param array $wheres
     *
     * @return bool
     */
    public function decreaseColumn($column, $amount=1, $wheres = array()) {
        return $this->incrementColumn($column, $amount, '-', $wheres);
    }

    /**
     * @author   Will
     *
     * @param        $column
     * @param        $wheres
     * @param int    $amount
     *
     * @param string $operator
     *
     * @return bool
     */
    private function incrementColumn($column, $amount, $operator, $wheres)
    {
        $table = $this->getTable();

        foreach ($this->primary_keys as $key) {
            $wheres[$key] = $this->$key;
        }

        $where      = '';
        $query_args = array();
        $fields     = array();

        foreach ($wheres as $field => $value) {
            $field        = strtolower($field);
            $fields[]     = "$field = ?";
            $query_args[] = $value;
        }

        if (count($fields) > 0) {
            $where = '' . implode(' AND ', $fields);
        }

        $query = "
             UPDATE $table
             SET `$column`=`$column`" . $operator ." ".$amount."
             WHERE ".$where."
             LIMIT 1
        ";

        return DB::statement($query, $query_args);

    }


    /**
     * @param bool $prepend_ordering_int This is a number that is placed at the
     *                                   start of the key so that you can sort
     *                                   by it. Allows you to uniquely order
     *                                   collections using the primary keys
     *                                   to still keep them unique.
     *
     * @return string
     */
    public function uniqueIndex($prepend_ordering_int = false) {
        $primary_key_values = array();

        if (is_numeric($prepend_ordering_int)) {
            $primary_key_values[] = sprintf("%0100d",$prepend_ordering_int);
        }

        foreach ($this->getPrimaryKeys() as $key) {
            $key = str_replace('@','',$key);
            $primary_key_values[] = $this->$key;
        }

        return implode('@',$primary_key_values);
    }

    /**
     * @return mixed|string
     */
    public function getKey() {
        return $this->uniqueIndex();
    }

    /**
     * Gets the order value from the unique index
     *
     * @param $unique_index
     *
     * @return int|string
     */
    public static function orderFromIndex($unique_index) {
        $hash  = explode('@', $unique_index);
        $value = ltrim($hash[0], 0);

        if (empty($value)) {
            return 0;
        }

        return $value;
    }
}