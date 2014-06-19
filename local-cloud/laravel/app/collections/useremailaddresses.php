<?php

class UserEmailAddresses extends DBCollection
{
    /*
    |--------------------------------------------------------------------------
    | Table Data
    |--------------------------------------------------------------------------
    */
    public $columns = array(
        'email_id',
        'address',
        'type',
        'created_at',
        'updated_at'
    ), $table = 'user_emails_addresses';

    /*
    |--------------------------------------------------------------------------
    | Magic
    |--------------------------------------------------------------------------
    */
    /**
     * @author  Will
     * @return string
     */
    public function __toString()
    {
        return $this->stringifyField('address', ', ', '');
    }
}