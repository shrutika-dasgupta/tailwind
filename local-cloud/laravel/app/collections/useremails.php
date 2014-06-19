<?php

/**
 * Class UserEmails
 *
 * @author  Will
 */
class UserEmails extends DBCollection
{
    /*
    |--------------------------------------------------------------------------
    | Table Meta Data
    |--------------------------------------------------------------------------
    */
    public $columns = array(
        'id',
        'cust_id',
        'email_name',
        'type',
        'status',
        'repeat',
        'send_at',
        'created_at',
        'updated_at'
    ), $table = 'user_email_queue';

    /*
    |--------------------------------------------------------------------------
    | Static Methods
    |--------------------------------------------------------------------------
    */
    /**
     * Find emails on the queue that have a send at
     * earlier than the time given
     *
     * @param        $limit
     * @param string $time
     *
     * @return UserEmails
     */
    public static function findByTime($time = 'now', $limit = false)
    {
        /*
         * Unless we dicate a time, we use right now
         */
        if ($time == 'now') {
            $time = time();
        }

        /*
         * If we want to set a limit for the call we need to build the sql
         */
        if ($limit) {
            $limit_statement = "LIMIT $limit";
        } else {
            $limit_statement = '';
        }

        $DBH = DatabaseInstance::DBO();

        $STH = $DBH->prepare("
            select * from user_email_queue
            where send_at < :time
            AND `status` != :processing
            AND `status` != :cancelled
            AND `status` != :sent
            AND `status` != :failed
            ORDER BY `send_at` DESC
            $limit_statement
        ");

        $STH->execute(
            array(
                 ':time'       => $time,
                 ':cancelled'  => UserEmail::STATUS_CANCELLED,
                 ':processing' => UserEmail::STATUS_PROCESSING,
                 ':sent'       => UserEmail::STATUS_SENT,
                 ':failed'     => UserEmail::STATUS_FAILED
            )
        );

        $emails = new UserEmails();

        if ($STH->rowCount() > 0) {
            foreach ($STH->fetchAll() as $key => $row) {

                $email = UserEmail::createFromDBData($row);
                $emails->add($email);

            }
        }

        return $emails;
    }

    /*
    |--------------------------------------------------------------------------
    | Instance methods
    |--------------------------------------------------------------------------
    */

    /**
     * @author  Will
     * @param array $dont_update_these_columns
     * @depreciated
     *
     * @return $this
     */
    public function insertUpdateDB($dont_update_these_columns=array())
    {
        array_push($dont_update_these_columns, 'id', 'created_at');

        $append = "ON DUPLICATE KEY UPDATE ";

        foreach ($this->columns as $column) {
            if (!in_array($column, $dont_update_these_columns)) {
                $append .= "`$column` = VALUES(`$column`),";
            }
        }

        $append = rtrim($append, ',');

        return parent::saveModelsToDB('INSERT INTO', $append);
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/
class UserEmailsException extends CollectionException {}

class NoUserEmailsException extends UserEmailsException {}
