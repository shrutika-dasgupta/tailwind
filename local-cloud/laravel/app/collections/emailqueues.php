<?php

/**
 * Class EmailQueues
 * 
 * @author  Will
 */
class EmailQueues extends DBCollection
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    public
        $table = 'user_mail_queue',
        $columns =
        array(
            'cust_id',
            'email_name',
            'type',
            'send_at',
            'created_at',
            'updated_at'
        ),
        $primary_keys = array();
}

class EmailQueuesException extends CollectionException {}
