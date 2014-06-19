<?php

class UserEmailUserIds extends DBCollection
{
    /*
    |--------------------------------------------------------------------------
    | Table Data
    |--------------------------------------------------------------------------
    */
    public $columns = array(
        'user_id',
        'email_id',
        'created_at',
        'updated_at'
    ), $table = 'user_emails_user_ids';
}