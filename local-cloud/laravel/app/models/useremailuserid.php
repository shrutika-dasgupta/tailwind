<?php

/**
 * Maps user ids to emails so we know the context of an email
 *
 * @author  Will
 */

class UserEmailUserId extends PDODatabaseModel
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

    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    public
        $user_id,
        $email_id,
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
            delete from user_emails_user_ids
            where email_id = :email_id
            AND user_id =:user_id
        ');

        return $STH->execute(
            array(
                ':email_id' => $this->email_id,
                ':user_id' => $this->user_id
            )
        );
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/

class UserEmailUserIdException extends DBModelException {}
