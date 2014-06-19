<?php

/**
 * Class UserHistories
 */
class UserHistories extends DBCollection
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    public $table = 'user_history',
        $columns = array(
        'id',
        'cust_id',
        'type',
        'description',
        'timestamp'
    ),
        $primary_keys = array('id');
}