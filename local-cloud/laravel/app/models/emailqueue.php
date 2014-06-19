<?php

/**
 * Email Queue Model
 * Represents an item in the email queue
 *
 * @author Will
 */
class EmailQueue extends PDODatabaseModel
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    public
        $cust_id,
        $email_name,
        $type,
        $send_at,
        $created_at,
        $updated_at;
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

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    /**
     * @author  Will
     */
    public function __construct()
    {
        parent::__construct();
        $this->created_at = time();
        $this->updated_at = time();
    }

    /*
    |--------------------------------------------------------------------------
    | Static Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Add an email to the queue
     *
     * @author  Will
     *
     * @param User $user
     * @param      $email_name
     * @param      $type
     * @param      $send_at
     *
     * @return EmailQueue
     */
    public static function add(User $user, $email_name, $type, $send_at)
    {
        $email_queue             = new EmailQueue();
        $email_queue->cust_id    = $user->cust_id;
        $email_queue->email_name = $email_name;
        $email_queue->type       = $type;
        $email_queue->send_at    = $send_at;

        $email_queue->saveToDB();

        return $email_queue;
    }

    /**
     * @param string $statement_type
     * @param bool   $append
     *
     * @return $this
     */
    public function saveToDB($statement_type = 'INSERT INTO', $append = false)
    {
        $this->updated_at = time();

        if (!$append) {
            $append = '
            ON DUPLICATE KEY UPDATE
			type = VALUES(type),
			send_at = VALUES(send_at),
			updated_at = VALUES(updated_at)';
        }

        return parent::saveToDB($statement_type, $append);
    }
}

class EmailQueueException extends DBModelException {}
