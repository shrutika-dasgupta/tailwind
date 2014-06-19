<?php

use Pinleague\Pinterest;

/**
 * Collection Map Traffic Pins Board model
 *
 * @author Alex
 */
class MapTrafficPinsUsers extends DBCollection
{

    public $table = 'map_traffic_pins_users';

    public $columns = array('pin_id'
    , 'user_id'
    , 'timestamp'
    , 'calced_flag');

    public $primary_keys = array('pin_id', 'user_id');

    public $pin_id
    , $user_id
    , $timestamp
    , $calced_flag;
}

class MapTrafficPinsUsersException extends DBModelException {}
