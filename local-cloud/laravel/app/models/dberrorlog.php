<?php

use Pinleague\Pinterest;

/**
 * DBErrorLog Model
 *
 * @author Yesh
 */
class DBErrorLog extends PDODatabaseModel
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */

    public
        $table = 'db_error_log',
        $columns =
        array(
          'id'
          , 'script_name'
          , 'line_number'
          , 'sqlstate_error_code'
          , 'driver_specific_error_code'
          , 'driver_specific_error_message'
          , 'timestamp'
        ),
        $primary_keys = array();

    public
      $id,
      $script_name,
      $line_number,
      $sqlstate_error_code,
      $driver_specific_error_code,
      $driver_specific_error_message,
      $timestamp;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    /**
     * @author Yesh
     */
    public function __construct()
    {
        parent::__construct();
        $this->timestamp = time();
    }

    /*
    |--------------------------------------------------------------------------
    | Instance Methods
    |--------------------------------------------------------------------------
    */

    /** Load DB error_info, parse and load it in
     *  a db_error_log model
     *
     * @author Yesh
     */
    public function loadErrorData($error){
      $this->sqlstate_error_code = $error[0];
      $this->driver_specific_error_code = $error[1];
      $this->driver_specific_error_message = $error[2];

      return $this;
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/
class DBErrorLogException extends DBModelException {}
