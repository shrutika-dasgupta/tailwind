<?php

/**
 * User email preferences
 * Represents a set of preferences
 *
 * @author  Will
 */
class UserEmailPreference extends PDODatabaseModel
{
    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */
    const NEVER             = 'never';
    const DAILY             = '1 day';
    const WEEKLY            = '1 week';
    const BI_WEEKLY         = '2 weeks';
    const MONTHLY           = '1 month';
    const QUARTERLY         = '3 months';
    const ON                = 'on';
    const OFF               = 'off';
    const DEFAULT_SEND_TIME = '0700';

    /*
    |--------------------------------------------------------------------------
    | Static Fields
    |--------------------------------------------------------------------------
    */
    public static $defaults = array(
        UserEmail::DAILY_SUMMARY   => array(
            'frequency'    => UserEmailPreference::OFF,
            'hour_to_send' => '0700'
        ),
        UserEmail::WEEKLY_SUMMARY  => array(
            'frequency'    => UserEmailPreference::WEEKLY,
            'hour_to_send' => '0700'
        ),
        UserEmail::MONTHLY_SUMMARY => array(
            'frequency'    => UserEmailPreference::OFF,
            'hour_to_send' => '0700'
        ),
        UserEmail::MONTHLY_STATEMENT => array(
            'frequency' => UserEmailPreference::OFF,
            'hour_to_send' => '0900'
        ),
        UserEmail::ALERTS          => array(
            'frequency'    => UserEmailPreference::OFF,
            'hour_to_send' => '0700'
        ),
        UserEmail::DOMAIN_ALERTS   => array(
            'frequency' => UserEmailPreference::OFF,
            'hour_to_send' => '0700'
        ),
        UserEmail::MAILCHIMP_BLOG_RSS => array(
            'frequency' => UserEmailPreference::ON,
        ),
        UserEmail::INTERCOM_EMAIL => array(
            'frequency' => UserEmailPreference::ON,
        ),
    );

    /*
    |--------------------------------------------------------------------------
    | Table meta data
    |--------------------------------------------------------------------------
    */
    public $columns = array(
        'cust_id',
        'username',
        'user_id',
        'name',
        'hour_to_send',
        'frequency',
        'last_sent',
        'created_at',
        'updated_at'
    ), $table = 'user_email_preferences';
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    public
        $cust_id,
        $username,
        $user_id,
        $name,
        $hour_to_send,
        $frequency,
        $last_sent,
        $created_at,
        $updated_at;

    /*
    |--------------------------------------------------------------------------
    | Construct
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
     * @author   Will
     *
     * @param $name
     *
     * @param $user_id
     *
     * @internal param $username
     *
     * @return UserEmailPreference
     */
    public static function defaultPreference($name, $user_id)
    {
        $preference = new self();

        $preference->name         = $name;
        $preference->user_id      = $user_id;
        $preference->frequency    = self::$defaults[$name]['frequency'];
        $preference->hour_to_send = array_get(self::$defaults[$name],'hour_to_send','');

        return $preference;
    }

    /**
     * @author   Will
     *
     * @param $cust_id
     * @param $name
     * @param $user_id
     *
     * @returns UserEmailPreference
     */
    public static function find($cust_id, $name, $user_id)
    {
        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->prepare('
                select * from user_email_preferences
                where cust_id = :cust_id
                AND name = :name
                AND user_id = :user_id
            ');

        $STH->execute(
            array(
                 ':cust_id' => $cust_id,
                 ':name'    => $name,
                 ':user_id' => $user_id
            )
        );

        if ($STH->rowCount() > 0) {
            $preference = self::createFromDBData($STH->fetch());

            return $preference;
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Instance Methods
    |--------------------------------------------------------------------------
    */

    /**
     * @author  Will
     *
     * @return string
     */
    public function checked()
    {
        if ($this->frequency == self::OFF || $this->frequency == self::NEVER) {
            return '';
        } else {
            return 'checked';
        }
    }

    /**
     * @author  Will
     * @return string Y or N
     */
    public function isRepeating()
    {
        if (in_array($this->name, UserEmail::$repeating)) {
            return UserEmail::REPEATING_YES;
        }

        return UserEmail::REPEATING_NO;
    }

    /**
     * @param $base
     *
     * @return int
     * @author   Will
     */
    public function nextSendInEpochTime($base)
    {
        return strtotime($this->frequency,$base);
    }

    /**
     * A prettier way to explain how often
     *
     * @author Will
     */
    public function readableFrequency()
    {
        switch ($this->frequency) {

            case self::NEVER:

                return 'never';

                break;

            case self::DAILY:

                return 'daily';

                break;

            default:
            case self::WEEKLY:

                return 'weekly';

                break;

            case self::BI_WEEKLY:

                return 'bi-weekly';

                break;

            case self::MONTHLY:

                return 'monthly';

                break;

            case self::QUARTERLY:

                return 'quarterly';

                break;
        }
    }

    /**
     * @author  Will
     *
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
                frequency = VALUES(frequency),
                last_sent = VALUES(last_sent),
                hour_to_send = VALUES(hour_to_send),
                updated_at = VALUES(updated_at)
            ';
        }

        return parent::saveToDB($statement_type, $append);
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/

class UserEmailPreferenceException extends DBModelException {}
