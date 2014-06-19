<?php

class UserEmailPreferences extends DBCollection
{

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

    /**
     * Gets the default preferences for a given user account
     *
     * @param UserAccount $user_account
     *
     * @return UserEmailPreferences
     */
    public static function getDefault(UserAccount $user_account) {

        $preferences = new self;
        foreach (UserEmailPreference::$defaults as $name => $data) {
            $preferences->add(UserEmailPreference::defaultPreference($name,$user_account->user_id));
        }

        return $preferences;
    }
    /**
     * Will take the list of preferences and draw on that to queue up emails
     * It assumes that other emails have already been removed and will duplicate them
     *
     * only for repeating emails
     *
     * @author  Will
     */
    public function queueEmails()
    {
        $user_ids_user_emails_maps = new UserEmailUserIds();
        $email_addresses           = new UserEmailAddresses();

        foreach ($this->models as $email_preference) {

            /** @var $email_preference UserEmailPreference */
            switch ($email_preference->name) {
                default:
                    $add_email = false;
                    $time      = false;
                    break;
                case UserEmail::DAILY_SUMMARY:
                    $add_email = true;
                    $time      = strtotime('tomorrow ' . $email_preference->hour_to_send);

                    break;

                case UserEmail::WEEKLY_SUMMARY:
                    $add_email = true;
                    $time      = strtotime('next monday ' . $email_preference->hour_to_send);

                    break;

                case UserEmail::MONTHLY_SUMMARY:
                case UserEmail::MONTHLY_STATEMENT:
                    $add_email = true;

                    $curMonth = date('n');
                    $curYear  = date('Y');

                    if ($curMonth == 12) {
                        $time = strtotime('01/01/'.($curYear+1));
                    } else {
                        $time = strtotime(($curMonth+1).'/01/'.$curYear);
                    }

                    break;
            }

            /*
             * We sort of have to do the saves for email in a loop here to get the
             * email id for the mapping tables. The mapping tables themselves can be
             * bulk saved, though.
             */
            if ($add_email AND
                $email_preference->frequency != UserEmailPreference::OFF AND
                $email_preference->frequency != UserEmailPreference::NEVER
            ) {

                $user_email             = new UserEmail();
                $user_email->email_name = $email_preference->name;
                $user_email->repeat     = UserEmail::REPEATING_YES;
                $user_email->cust_id    = $email_preference->cust_id;
                $user_email->type       = UserEmail::AUTOMATED;
                $user_email->status     = UserEmail::STATUS_QUEUED;
                $user_email->send_at    = $time;
                $user_email->insertUpdateDB();
                $user_email->id = $this->DBH->lastInsertId();

                /*
                 * Add the pinterest account(s) in question
                 */
                $user_id_map           = new UserEmailUserId();
                $user_id_map->user_id  = $email_preference->user_id;
                $user_id_map->email_id = $user_email->id;

                $user_ids_user_emails_maps->add($user_id_map);

                /*
                 * Add the email address(es) of the customer
                 */
                $user_email_address           = new UserEmailAddress();
                $user_email_address->email_id = $user_email->id;
                $user_email_address->address  = $user_email->customer()->email;
                $user_email_address->type     = UserEmailAddress::TYPE_CUSTOMER;

                $email_addresses->add($user_email_address);

            }
        }

        if ($user_ids_user_emails_maps->count() > 0) {
            $user_ids_user_emails_maps->insertUpdateDB();
        }

        if ($email_addresses->count() > 0) {
            $email_addresses->insertUpdateDB();
        }
    }

    /**
     * @author  Will
     *
     * @param string $insert_type
     * @param bool   $appended
     *
     * @return bool
     */
    public function saveModelsToDB($insert_type = 'INSERT INTO', $appended = false)
    {
        if (!$appended) {
            $appended = '
                ON DUPLICATE KEY UPDATE
				frequency = VALUES(frequency),
				last_sent = VALUES(last_sent),
				hour_to_send = VALUES(hour_to_send),
				updated_at = VALUES(updated_at)
				';
        }

        return parent::saveModelsToDB($insert_type, $appended);
    }
}