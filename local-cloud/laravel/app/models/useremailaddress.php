<?php

/**
 * Maps an email address to emails so we can send a specific email to multiple
 * people
 *
 * @author  Will
 */
class UserEmailAddress extends PDODatabaseModel
{
    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */
    const TYPE_CUSTOMER = 'customer';

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
    | Fields
    |--------------------------------------------------------------------------
    */
    public
        $email_id,
        $address,
        $type,
        $created_at,
        $updated_at;

    /*
    |--------------------------------------------------------------------------
    | Cached Fields
    |--------------------------------------------------------------------------
    */
    protected $_email;

    /*
    |--------------------------------------------------------------------------
    | Construct
    |--------------------------------------------------------------------------
    */

    public function __construct()
    {
        parent::__construct();
        $this->created_at = time();
        $this->updated_at = time();
    }

    /*
    |--------------------------------------------------------------------------
    | Instance methods
    |--------------------------------------------------------------------------
    */

    /**
     * @author  Will
     */
    public function removeFromDB()
    {
        $STH = $this->DBH->prepare('
            delete from user_emails_addresses
            where email_id = :email_id
            AND address =:address
        ');

        return $STH->execute(
                   array(
                        ':email_id' => $this->email_id,
                        ':address'  => $this->address
                   )
        );
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/

class UserEmailAddressException extends DBModelException {}
