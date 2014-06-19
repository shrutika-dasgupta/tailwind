<?php

class PinsHistories extends DBCollection
{

    /*
    |--------------------------------------------------------------------------
    | Table Meta Data
    |--------------------------------------------------------------------------
    */
    public $columns = array(
        'pin_id',
        'user_id',
        'date',
        'repin_count',
        'like_count',
        'comment_count',
        'timestamp',
    ), $table = 'data_pins_history';

    public $primary_keys = array(
        'pin_id',
        'timestamp'
    );
}

class PinHistoriesException extends CollectionException {}
