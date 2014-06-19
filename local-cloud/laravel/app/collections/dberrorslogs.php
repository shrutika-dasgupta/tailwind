<?php

use Pinleague\Pinterest;
use Pinleague\PinterestException;

/**
 * Collection of DBErrorLog
 *
 * @author Yesh
 */
class DBErrorsLogs extends DBCollection
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
        $primary_keys = array('id');

    public
      $id,
      $script_name,
      $line_number,
      $sqlstate_error_code,
      $driver_specific_error_code,
      $driver_specific_error_message,
      $timestamp;
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/
class DBErrorsLogsException extends CollectionException {}
