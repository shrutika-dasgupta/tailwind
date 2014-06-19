<?php

namespace Publisher;

use DatabaseInstance,
    DBModelException,
    PDODatabaseModel,
    PDO,
    Pinleague\CLI,
    Lang,
    Log,
    DateTime,
    DateTimeZone,
    UserAccount;

/**
 * Class TimeSlot
 *
 * @package Publisher
 */
class TimeSlot extends PDODatabaseModel
{
    public $table = 'publisher_time_slots';

    public $columns = array(
        'id',
        'account_id',
        'day_preference',
        'time_preference',
        'timezone',
        'pin_uuid',
        'send_at',
    );

    public $primary_keys = array('id');

    public $id;
    public $account_id;
    public $day_preference;
    public $time_preference;
    public $timezone;
    public $pin_uuid;
    public $send_at;

    protected $days_hashmap;


    public function __construct()
    {
        $this->days_hashmap = Lang::get('dates.days');

        parent::__construct();
    }


    /**
     * @author  Will
     *
     * @param      $id
     *
     * @param \PDO $pdo_object
     *
     * @return bool
     */
    public static function delete($id, PDO $pdo_object = null)
    {

        $DBH = is_null($pdo_object) ? DatabaseInstance::DBO() : $pdo_object;

        $STH = $DBH->prepare(
                   "DELETE FROM publisher_time_slots WHERE id = :id"
        );

        $STH->execute(array(':id' => $id));

        return true;
    }

    /**
     * Get all the time slots from a given UserAccount object
     *
     * @param UserAccount $user_account
     * @param PDO         $DBH
     * @param string      $type (all|auto|manual)
     *
     * @return TimeSlots
     */
    public static function getTimeSlots(UserAccount $user_account, PDO $DBH = null, $type = 'all')
    {
        if (is_null($DBH)) {
            $DBH = DatabaseInstance::DBO();
        }

        $query = 'select * from publisher_time_slots
                  where account_id = :account_id';

        if ($type == 'auto') {
            $query .= ' and (pin_uuid is null OR pin_uuid = 0)';
        } else if ($type == 'manual') {
            $query .= ' and (pin_uuid is not null OR pin_uuid != 0)';
        }

        $STH = $DBH->prepare($query);

        $STH->execute(array(
             ':account_id' => $user_account->account_id
        ));

        return TimeSlots::createFromDBData($STH->fetchAll());
    }


    /** Given data with the account_id, day and time_preference; this method helps calculate
     *  the send_at time by converting to the server's timezone from the user's timezone
     *
     *  If day_scheduled and time_scheduled are set, it means that the send_time has
     *  to be calculated for a pin which has a time_slot that has been manually set
     *  by the user
     *
     * Expected input:
     * For normal cases:
     *  {
     *     "account_id"      : $account_id,
     *     "day_preference"  : 1,
     *     "time_preference" : '11:00 PM',
     *  }
     *
     * @return int
     * @throws TimeSlotException
     *
     * @author Yesh
     */

    public function calculateSendTime()
    {

        $user_tz = $this->timezone;

        if (empty($user_tz)) {
            throw new TimeSlotException('user timezone is not set');
        }

        /*
         * If manual post
         *
         * day_preference  == Y-m-d
         * time == [08:00PM]
         * timezone == America\New_York
         *
         * if auto post
         *
         * day_preference == [0-6]
         * time == [0000 - 2359]
         * timezone == America\New_York
         *
         */

        $day = $this->day_preference;

        /*
         * If the time preference is greater than 6, it's not a day and is a
         * date. Find the epoch time and return it
         */
        if ($this->day_preference < 7) {

            $day = $this->getPrettyDay();

            /*
             * If today is the day preference, we want to see if it's after the time
             * we would have sent it
             */
            if ((int)date('w', time()) == $this->day_preference) {

                $todays_send_time = strtotime("today $this->time_preference $this->timezone");

                if (time() < $todays_send_time) {
                    $day = 'today';
                } else {
                    $day = 'next '.$this->getPrettyDay();
                }
            }
        }

        Log::debug("Calculating $day $this->time_preference $this->timezone");
        $this->send_at = strtotime("$day $this->time_preference $this->timezone");
        Log::debug('New send at: '.$this->send_at.'['.date('d-m-Y g:ia',$this->send_at).']');

        return $this;
    }


    /**
     * Gets a pretty version of the day.
     *
     * @return string
     */
    public function getPrettyDay()
    {
        return $this->days_hashmap[$this->day_preference];
    }


    /**
     * Gets a pretty version of the time.
     *
     * @return string
     */
    public function getPrettyTime()
    {
        return \Carbon\Carbon::createFromTime(
                             substr($this->time_preference, 0, 2),
                             substr($this->time_preference, 2, 2),
                             0,
                             $this->timezone
        )                    ->format('g:i A');
    }


    /**
     * Gets the timezone, if set.
     * Otherwsie, returns the CST default
     *
     * @return string
     */
    public function getTimezone()
    {
        if (is_null($this->timezone)) {
            return 'America/Chicago';
        }

        return $this->timezone;
    }


    /**
     * @author  Will
     * @return $this
     */
    public function saveAsNew()
    {

        $insert = parent::saveAsNew();

        if ($insert) {
            $this->id = $this->DBH->lastInsertId();
        }

        return $insert;
    }


    /**
     * Sets the day.
     *
     * @author Will
     * @author Daniel
     *
     * @param int $day
     *
     * @return TimeSlot
     */
    public function setDay($day)
    {
        $days_keys      = array_keys($this->days_hashmap);
        $day_preference = preg_replace('/[^0-9]/', '', $day);

        if (!in_array($day_preference, $days_keys)) {
            throw new TimeSlotException("$day_preference is not a valid day.");
        }

        $this->day_preference = $day_preference;

        return $this;
    }


    /**
     * Sets the time.
     *
     * @author Will
     * @author Daniel
     *
     * @param int $time
     *
     * @return TimeSlot
     */
    public function setTime($time)
    {
        $this->time_preference = preg_replace('/[^0-9]/', '', $time);
        $this->time_preference = sprintf("%04d", $this->time_preference);

        return $this;
    }

}

class TimeSlotException extends DBModelException {}
