<?php

use Pinleague\Pinterest;

/**
 * Map Traffic Pins User model
 *
 * @author Alex
 *
 */
class MapTrafficPinsUser extends PDODatabaseModel
{
    public $table = 'map_traffic_pins_users',
    $columns =
        array(
            'pin_id'
            , 'user_id'
            , 'timestamp'
            , 'calced_flag'
        ),
    $primary_keys = array('pin_id', 'user_id');

    public $pin_id
    , $user_id
    , $timestamp
    , $calced_flag;


    public function __construct(){
        parent::__construct();
        $this->timestamp = time();
    }

}

class MapTrafficPinsUserException extends DBModelException {}
