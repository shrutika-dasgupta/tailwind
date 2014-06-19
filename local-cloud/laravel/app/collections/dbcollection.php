<?php

/**
 * Basic collections class
 * aka a bunch of models (in the club)
 *
 * @author Will
 */
class DBCollection extends Collection implements \ArrayAccess, \Iterator, \Countable
{
    /**
     * @var $columns array
     * @var $table   string
     */
    public $columns, $table, $primary_keys = array();
    /**
     * @var PDO
     */
    protected $DBH;

    /**
     * @author  Will
     */
    public function __construct($DBH = null)
    {
        $this->DBH = is_null($DBH) ? DatabaseInstance::DBO() : $DBH;
    }

    /**
     * Creates a collection of models based on the given data set
     *
     * @param        $data
     * @param        $model_name
     * @param string $collection_name
     *
     * @return self
     */
    public static function createFromDBData($data, $model_name = null, $collection_name = 'self')
    {
        if ($collection_name == 'self') {
            $collection_name = get_called_class();
        }

        if (empty($model_name)) {
            if (defined("$collection_name::MODEL")) {
                $model_name = $collection_name::MODEL;
            }
        }

        $collection = new $collection_name();
        foreach ($data as $row) {
            $model = new $model_name();
            $model->loadDBData($row);
            $collection->add($model);
        }

        return $collection;
    }

    /**
     * Print columns
     *
     * @author  Will
     */
    public static function __print_columns($table)
    {
        echo '<pre>';

        $DBH    = DatabaseInstance::DBO();
        $result = $DBH->query('SHOW COLUMNS IN ' . $table);

        foreach ($rows =$result->fetchAll() as $field) {
            echo "'$field->Field',\n";
        }
        echo PHP_EOL;
        echo PHP_EOL;
        echo PHP_EOL;
        echo PHP_EOL;

        foreach ($rows as $field) {
            echo "$$field->Field,\n";
        }
        exit;
    }

    /**
     * @author  Will
     *
     * @param bool $dont_log_error
     *
     * @return $this
     */
    public function insertIgnoreDB($dont_log_error = false)
    {
        return $this->saveModelsToDB('INSERT IGNORE INTO', false, $dont_log_error);
    }

    /**
     * Insert Update
     * Uses and insert update to save models to database
     *
     * @author  Will
     *
     * @param array $ignore_these_columns
     * @param bool  $dont_log_error
     *
     * @returns $this
     */
    public function insertUpdateDB($ignore_these_columns = array(), $dont_log_error = false)
    {
        $ignore_these_columns = array_merge($ignore_these_columns, $this->primary_keys);

        $append = "ON DUPLICATE KEY UPDATE ";

        foreach ($this->columns as $column) {
            if (!in_array($column, $ignore_these_columns)) {
                $append .= "`$column` = VALUES(`$column`),";
            }
        }

        $append = rtrim($append, ',');

        return $this->saveModelsToDB('INSERT INTO', $append, $dont_log_error);
    }
    /**
     * Takes all the models and tries to save them to the
     * given table name
     *
     * @author  Will
     * @author  Yesh
     *
     * @param string $insert_type
     * @param bool   $appendedSQL
     * @param bool   $dont_log_error
     *
     * @throws CollectionException
     * @throws Exception
     * @throws PDOException
     * @returns $this
     */
    public function saveModelsToDB(
        $insert_type = 'INSERT IGNORE INTO',
        $appendedSQL = false,
        $dont_log_error = false
    )
    {
        if (empty($this->primary_keys) == false) {
            $this->sortByPrimaryKeys();
        }

        $table       = $this->table;
        $columns     = $this->columns;
        $insert_type = trim($insert_type);

        if (empty($this->models)) {

            throw new CollectionException('There are no models to save');

        } elseif (empty($columns)) {
            throw new CollectionException('There are no columns to insert into');
        }

        /*
         * Create the SQL that will be run
         * and use ? placeholders for the values
         */
        $sql = "$insert_type $table (`";
        $sql .= implode('`,`', $columns);
        $sql = rtrim($sql, ',`');
        $sql .= '`) VALUES ';

        foreach ($this->models as $model) {
            $sql .= '(';
            foreach ($columns as $column) {
                $sql .= '?,';
            }
            $sql = rtrim($sql, ',');
            $sql .= '), ';
        }

        /*
         * We take of the last added comma and append
         * any SQL that might be needed (for instance if it is an insert update on dup key)
         */
        $sql = rtrim($sql, ', ');

        if ($sql != "" && $appendedSQL) {
            $sql .= $appendedSQL;
        }

        if ($sql != '') {

            if ($dont_log_error === true) {
                $this->DBH->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET sql_log_bin = 0");
            }

            $STH = $this->DBH->prepare($sql);
            $xx  = 0;

            foreach ($this->models as $model) {

                foreach ($columns as $column) {
                    $xx++;
                    $STH->bindParam($xx, $model->$column);
                }
            }

            try {
                $STH->execute();
            }
            catch (PDOException $Exception) {
                $db_log              = new DBErrorLog();
                $db_log->script_name = get_called_class();
                $db_log->line_number = __LINE__;
                $db_log->loadErrorData($Exception->errorInfo);

                $db_log->saveToDB();
                throw $Exception;
            }


            if ($dont_log_error === true) {
                $this->DBH->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET sql_log_bin = 1");
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function export()
    {
        $table   = $this->table;
        $columns = $this->columns;

        /*
         * Create the SQL that will be run
         * and use ? placeholders for the values
         */
        $sql = "INSERT IGNORE INTO $table (`";
        $sql .= implode('`,`', $columns);
        $sql = rtrim($sql, ',`');
        $sql .= '`) VALUES ';

        foreach ($this->models as $model) {
            $sql .= '(';
            foreach ($model->columns as $column) {
                $sql .= '?,';
            }
            $sql = rtrim($sql, ',');
            $sql .= '), ';
        }

        $sql = rtrim($sql, ', ') . ';';

        $data = array();
        foreach ($this->models as $key => $model) {
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
     * Sort the collection by the primary keys
     *
     * @author  Will
     * @author  Yesh
     */
    public function sortByPrimaryKeys() {

        if (empty($this->primary_keys)) {
            throw new DBCollectionException('The primary keys are not set');
        }

        $sort_arguments = array();

        foreach($this->primary_keys as $key) {
            $sort_arguments[] = $key;
            $sort_arguments[] = SORT_ASC;
        }

        $this->sortBy($sort_arguments);
    }

    /**
     * @return bool
     */
    public function fetchDBData(){
        if (empty($this->primary_keys)) {
            return false;
        }

        if (count($this->primary_keys) > 1) {

            foreach ($this->models as &$model) {
                $model->fetchDBData();
            }

            return true;
        }
        $primary_key = $this->primary_keys[0];

        $ids = $this->stringifyField($primary_key);

        $STH = $this->DBH->query(
            "SELECT * FROM ".
            $this->table.
            " WHERE $primary_key IN($ids)");

        $STH->execute();

        if ($STH->rowCount() == 0) {
            return false;
        }

        foreach($STH->fetchAll() as $modelData) {
            $model = $this->getModel($modelData->$primary_key);
            $model->loadDBData($modelData);
            $this->removeModel($model->$primary_key);
            $this->add($model);
        }

        return true;
    }

}

/**
 * Class DBCollectionException
 */
class DBCollectionException extends CollectionException{}