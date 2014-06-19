<?php
/**
 * DB Interface
 * What a database model should be able to do
 *
 * @author  Will
 */
interface DBModel
{
    public static function createFromDBData($data);

    public function insertUpdateDB($ignore_these_columns);

    public function loadDBData($data);

    public function saveToDB($statement_type, $appendedSQL);
}

/**
 * Class DatabaseModel
 *
 * @author  Will
 */
class PDODatabaseModel extends Model implements DBModel, JsonSerializable
{
    /**
     * The columns int he table
     *
     * @var array
     */
    public $columns;

    /**
     * The name of the table
     *
     * @var string
     */
    public $table;

    /**
     * The columns that are primary keys in the table
     *
     * @var array
     */
    public $primary_keys = array();

    /**
     * A database connection
     *
     * @var PDO
     */
    protected $DBH;

    /**
     * New constructor
     * @author  Will
     */
    public function __construct()
    {
        $this->DBH = DatabaseInstance::DBO();
    }

    /**
     * JsonSerializable lets you configure which class members are
     * output when an instance is passed into json_encode.
     *
     * This method is run when you run json_encode($model);
     *
     * So when we are encoding models we don't get the rest of the crap.
     *
     * @return array|mixed
     *
     * @author  Will
     */
    public function jsonSerialize()
    {
        $json = [];

        foreach ($this->columns as $column) {
            $json[$column] = $this->$column;
        }

        return array_merge($json, $this->misc);
    }

    /**
     * Finds db results based on $data.
     * 
     * @author Daniel
     * 
     * @param int|array $data ID or array of key value pairs to build query from.
     * 
     * @return array Query results.
     */
    public static function find($data)
    {
        $class  = get_called_class();
        $object = self::getObjectWithoutCallingConstructor($class);


        $table = $object->table;

        $query = "SELECT *
                  FROM $table";

        $find_one = false;
        if (!empty($data) && !is_array($data)) {
            $find_one = true;

            $primary_key = array_get($object->primary_keys, 0);
            if ($primary_key) {
                $data = array($primary_key => $data);
            }
        }

        $reserved_words = array('limit', 'offset');

        $query_args = $wheres = array();
        foreach ($data as $field => $value) {
            $field = strtolower($field);
            if (in_array($field, $reserved_words)) {
                continue;
            }

            $wheres[] = "$field = ?";
            $query_args[] = $value;
        }

        if (!empty($wheres)) {
            $query .= ' WHERE ' . implode(' AND ', $wheres);
        }

        if ($limit = array_get($data, 'limit')) {
            if ($limit == 1) {
                $find_one = true;
            }

            $offset = array_get($data, 'offset', 0);
            
            $query .= " LIMIT $offset, $limit";
        }

        $results = DB::select($query, $query_args);

        $items = array();
        foreach ($results as $result) {
            $item = new $class();
            $items[] = $item->loadDBData($result);
        }

        return ($find_one) ? array_get($items, 0) : $items;
    }

    /**
     * Finds a single db result based on $data.
     * 
     * @author Daniel
     * 
     * @param int|array $data ID or array of key value pairs to build query from.
     * 
     * @return DBModel Query result.
     */
    public static function find_one(array $data)
    {
        $class  = get_called_class();
        $data['limit'] = 1;

        return $class::find($data);
    }

    /**
     * Load from DB result
     *
     * @author  Will
     */
    public static function createFromDBData($data,$prefix='')
    {
        $class = get_called_class();

        if (empty($data)) {
            $exception_class = $class . 'Exception';
            throw new $exception_class('The dataset is empty to create a ' . $class);
        }
        /** @var $model PDODatabaseModel */
        $model = new $class();

        $model->loadDBData($data,$prefix);

        return $model;
    }

    /**
     * Insert Update
     * Uses and insert update to save models to database
     *
     * @author  Will
     *
     * @param array $ignore_these_columns
     *
     * @returns $this
     */
    public function insertUpdateDB($ignore_these_columns = array())
    {
        $ignore_these_columns = array_merge($ignore_these_columns, $this->primary_keys);

        // Auto update the updated_at value if the property exists for this model.
        if (property_exists($this, 'updated_at') && !in_array('updated_at', $ignore_these_columns)) {
            $this->updated_at = time();
        }

        $appends = array();
        foreach ($this->columns as $column) {
            if (!in_array($column, $ignore_these_columns)) {
                $appends[] = "`$column` = VALUES(`$column`)";
            }
        }

        $append = ' ON DUPLICATE KEY UPDATE ' . implode($appends, ', ');

        return $this->saveToDB('INSERT INTO', $append);
    }

    /**
     * @author  Will
     * @todo    Get the primary key, and save it as the last insert id if its auto incrementing
     */
    public function saveAsNew()
    {
        return $this->saveToDB('INSERT IGNORE INTO');
    }

    /**
     * @return $this
     */
    public function insertIgnore() {
        return $this->saveToDB('INSERT IGNORE INTO');
    }

    /**
     * Load in DB data
     *
     * @author  Will
     */
    public function loadDBData($data,$prefix='')
    {
        foreach ($this->columns as $column) {
            $data_name = $prefix.$column;

            if (is_object($data)) {
                if (isset($data->$data_name)) {
                    $this->$column = $data->$data_name;
                }
            } else {
                if (isset($data[$data_name])) {
                    $this->$column = $data[$data_name];
                }
            }
        }

        return $this;
    }

    /**
     * @author  Will
     *
     * @param string $statement_type
     * @param bool   $append
     *
     *
     * @return $this
     */
    public function saveToDB($statement_type = 'INSERT IGNORE INTO', $append = false)
    {
        /*
         * Construct SQL statement
         */
        $sql = trim($statement_type);
        $sql .= ' ' . $this->table;
        $sql .= ' (`';
        $sql .= implode('`,`', $this->columns);
        $sql = rtrim($sql, ',`');
        $sql .= '`) VALUES (:';
        $sql .= implode(',:', $this->columns);
        $sql = rtrim($sql, ',:');
        $sql .= ')';

        if ($append) {
            $sql .= $append;
        }

        $STH = $this->DBH->prepare($sql);

        foreach ($this->columns as $column) {
            $STH->bindValue(':' . $column, $this->$column);
        }

        $STH->execute();

        $id = $this->DBH->lastInsertId();

        /**
         * Setting the lastInsertId for primary key
         */
        $primary_k = current($this::getPrimaryKeys());
        if (!empty($primary_k) && empty($this->$primary_k)) {
            $this->$primary_k = $id;
        }

        return $this;
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
        $sql .= implode('`,`', $this->columns);
        $sql = rtrim($sql, ',`');
        $sql .= '`) VALUES (';
        foreach ($this->columns as $column) {
            $sql.='?,';
        }
        $sql = rtrim($sql, ',');
        $sql .= ');';


        $data = array();
        foreach ($this->columns as $column) {
            $data[$column] = $this->$column;
        }

        return array(
            'query' => $sql,
            'data' => [$data]
        );
    }

    /**
     * PreLoad
     *
     * If we have the data for an object we would normally make a db call, we "pre load"
     * the data so we don't have to make another call.
     *
     * @param      $object_name
     * @param      $data
     *
     *
     * @param bool $class
     *
     * @return $this
     * @example
     *
     * $user_history->preLoad('user.organization',$some_db_data);
     *
     * This will preload the user object in user_history, and the organization object in that
     * loaded user object
     *
     *
     * @throws DBModelException
     */
    public function preLoad($object_name,$data,$class = false)
    {
        $objects_to_load = explode('.',$object_name,2);

        if(!$class) {
            $class = get_called_class();
        }

        /*
         * The child class is stored in _objectName in the parent class
         * When we get objectName, we first look to see if it's stored in
         * _objectName before doing a DB call.
         *
         */
        $property = '_'.$objects_to_load[0];

        if (!property_exists($class,$property)) {
            throw new DBModelException('Unable to preload '.$objects_to_load[0]);
        }

        $this->$property = new $objects_to_load[0]();
        $this->$property->loadDBData($data);

        if (count($objects_to_load) >1) {
            $this->$property->preLoad($objects_to_load[1],$data,$objects_to_load[0]);
        }

        return $this;
    }

    /**
     * Sometimes we have a cache so we don't make extra DB calls
     * This will explicitly set that cache
     * when we already have the object
     *
     * @author  Will
     *
     * @param $object_name
     * @param $object
     *
     * @throws DBModelException
     * @return $this
     */
    public function setCache($object_name,$object) {
        $cache_name = '_'.$object_name;

        if (!property_exists(get_called_class(),$cache_name)) {
            throw new DBModelException('Unable to preload '.$cache_name);
        }
        $this->$cache_name = $object;

        return $this;

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

        foreach ($this->getPrimaryKeys() as $key) {
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
             SET `$column`=`$column` $operator $amount
             WHERE $where
             LIMIT 1
        ";

        return DB::statement($query, $query_args);

    }

    /**
     * @author  Will
     * @return $this
     */
    private function getTable()
    {
        if(isset($this->table)) {
            return $this->table;
        }

        $class  = get_called_class();
        $object = new $class();

        $table = $object->table;
        unset($object);

        return $table;
    }

    /**
     * @author  Will
     * @return $this
     */
    private function getPrimaryKeys()
    {
        if(isset($this->primary_keys)) {
            return $this->primary_keys;
        }

        $object = self::getObjectWithoutCallingConstructor(get_called_class());

        $primary_keys = $object->primary_keys;

        unset($object);

        return $primary_keys;
    }

    /**
     * Since some models have constructors with arguments,
     * we need to get mock objects without calling the constructor
     * This hacky solution does just that. If we were using 5.4+ we could
     * have used a reflection class, but alas - hack away with 5.3 :/
     *
     * @author  Will
     *
     * @param $class_name
     *
     * @return mixed
     */
    private function getObjectWithoutCallingConstructor($class_name) {
        return $object = unserialize(
            sprintf('O:%d:"%s":0:{}', strlen($class_name), $class_name)
        );
    }

    /**
     * An index based off the primary keys
     *
     * @param bool $prepend_ordering_int
     *
     * @return string
     */
    public function getIndex($prepend_ordering_int = false) {

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
     * Gets all model DB data.
     *
     * @param array $ignored_columns
     *
     * @return array
     */
    public function getDBData($ignored_columns = array())
    {
        $data = array();
        foreach ($this->columns as $column) {
            if (!in_array($column, $ignored_columns)) {
                $data[$column] = $this->$column;
            }
        }

        return $data;
    }

    /**
     * @return bool
     */
    public function fetchDBData(){
        if (empty($this->primary_keys)) {
            return false;
        }
        $query =
            "SELECT * FROM ".
            $this->table.
            " WHERE ";

        foreach ($this->primary_keys as $key) {
            $query = $key.' = ? AND ';
        }

        $query = rtrim($query,' AND ');

        $STH = $this->DBH->prepare($query);

        $xx = 0;
        foreach ($this->primary_keys as $key) {
            $STH->bindParam($xx,$this->$key);
            $xx++;
        }

        $STH->execute();
        if ($STH->rowCount() == 0) {
            return false;
        }

        $this->loadDBData($STH->fetch());

        return true;

    }
}

class DBModelException extends Exception {}
