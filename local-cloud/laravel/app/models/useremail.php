<?php

use Mailgun\Mailgun;

/**
 * Class UserEmail
 *
 * @author  Will
 */
class UserEmail extends PDODatabaseModel
{
    /*
    |--------------------------------------------------------------------------
    | Constant
    |--------------------------------------------------------------------------
    */

    /*
     * Names / templates of the emails that we send
     */
    const DAILY_SUMMARY     = 'daily_summary';
    const WEEKLY_SUMMARY    = 'weekly_summary';
    const MONTHLY_SUMMARY   = 'monthly_summary';
    const MONTHLY_STATEMENT = 'monthly_statement';
    const ALERTS            = 'alerts';
    const DOWNGRADE_FREE    = 'downgrade_free';
    const DOMAIN_ALERTS     = 'domain_alerts';

    /*
     * Names of external mailing lists and preferences that we track
     */
    const MAILCHIMP_BLOG_RSS = 'mailchimp_blog_rss';
    const INTERCOM_EMAIL     = 'intercom_email';

    /*
     * Status for the email in the queue.
     * Processing is when the engine grabs it, but hasn't finished sending
     * Queued is the default.
     * Sent is...well sent
     */
    const STATUS_SENT       = 'S';
    const STATUS_PROCESSING = 'P';
    const STATUS_QUEUED     = 'Q';
    const STATUS_CANCELLED  = 'C';
    const STATUS_FAILED     = 'F';

    /*
     * This is pretty self explanitory
     */
    const REPEATING_NO  = 'N';
    const REPEATING_YES = 'Y';

    /*
     * Event based emails are different than automated, daily/weekly/monthly mailers
     */
    const AUTOMATED = 'A';
    const EVENT     = 'E';
    const PERSONAL  = 'P';

    /*
    |--------------------------------------------------------------------------
    | Static Fields
    |--------------------------------------------------------------------------
    */
    public static $repeating = array(
        self::DAILY_SUMMARY,
        self::WEEKLY_SUMMARY,
        self::MONTHLY_SUMMARY
    );

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
    | Fields
    |--------------------------------------------------------------------------
    */
    public
        $created_at,
        $cust_id,
        $email_name,
        $id,
        $repeat,
        $send_at,
        $status,
        $type,
        $updated_at;
    public
        $from,
        $subject,
        $to;
    /*
    |--------------------------------------------------------------------------
    | Cached Fields
    |--------------------------------------------------------------------------
    */
    protected
        $_customer,
        $_email_addresses,
        $_profiles;

    /*
    |--------------------------------------------------------------------------
    | Properties
    |--------------------------------------------------------------------------
    */
    protected $attachements = array();

    /*
    |--------------------------------------------------------------------------
    | Construct
    |--------------------------------------------------------------------------
    */

    /**
     * Set some sane defaults
     *
     * @author  Will
     */
    public function __construct()
    {
        parent::__construct();

        $this->from    = 'Tailwind <please_reply@tailwindapp.com>';

        $this->created_at = time();
        $this->updated_at = time();
    }

    /*
    |--------------------------------------------------------------------------
    | Instance methods
    |--------------------------------------------------------------------------
    */

    /**
     * Find based on id
     *
     * @param $id
     *
     * @return bool|UserEmail
     */
    public static function find($id)
    {
        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->prepare("
                select * from user_email_queue where id = :id
            ");
        $STH->execute(
            array(
                 ':id' => $id
            )
        );

        if ($STH->rowCount() == 0) {
            return false;
        } else {
            $email = new UserEmail();
            $email->loadDBData($STH->fetch());

            return $email;
        }
    }

    /**
     * Adds an address to send to
     *
     * @author  Will
     */
    public function addToAddress($email_address,$type = UserEmailAddress::TYPE_CUSTOMER)
    {
        $address = new UserEmailAddress();
        $address->email_id = $this->id;
        $address->address = $email_address;
        $address->type = $type;
        $address->saveAsNew();

        return $this;
    }

    /**
     * Alias for User()
     *
     * @author  Will
     *
     * @see     user()
     * @return User
     */
    public function customer()
    {
        return $this->user();
    }

    /**
     * @author  Will
     *
     * @param bool $force_update will force it not to used cached object
     *
     * @return mixed
     */
    public function emailAddresses($force_update = false)
    {
        if ($this->_email_addresses and !$force_update) {
            return $this->_email_addresses;
        }

        $addresses = new UserEmailAddresses();

        $STH = $this->DBH->prepare("
                    select * from user_emails_addresses
                                 where email_id = :email_id
                ");

        $STH->execute(
            array(
                 ':email_id' => $this->id
            )
        );

        foreach ($STH->fetchAll() as $addressData) {
            $address = new UserEmailAddress();
            $address->loadDBData($addressData);

            $addresses->add($address);
        }

        return $this->_email_addresses = $addresses;
    }

    /**
     * @author  Will
     */
    public function is_repeating()
    {
        if ($this->repeat === 'Y') {
            return true;
        }

        return false;
    }

    /**
     * I realize this is hacky but LONG HAIR DONT CARE
     *
     * Prepares each individual type of email with their respective settings
     *
     * @author  Will
     */
    public function prepareToSend()
    {

        $this->to      = $this->emailAddresses();

        if (empty($this->to)) {
            throw new UserEmailException('There is no email address attached to this email');
        }

        switch ($this->email_name) {

            case self::DAILY_SUMMARY:
            case self::WEEKLY_SUMMARY:
            case self::MONTHLY_SUMMARY:

                $this->subject = 'Tailwind Summary for ' . $this->profiles();

                break;

            case self::DOWNGRADE_FREE:

                $this->from    = 'Alex Topiler <alex@tailwindapp.com>';
                $this->subject = 'Tailwind Account';

                break;

            case self::MONTHLY_STATEMENT:

                if ($this->customer()->hasCreditCardOnFile()) {

                    //Get all the statements
                    $statements = new ChargifyStatement();
                    $statements = $statements->getBySubscriptionID(
                                             $this->customer()
                                                  ->organization()
                                                  ->subscription()
                                                  ->id
                    );

                    ///Get the second one because the first is the current one
                    $last_statement = $statements->statement[1];

                    $month = date('F',strtotime($last_statement->closed_at));
                    $date = !empty($last_statement->closed_at) ? date('F-j-Y', strtotime($last_statement->closed_at)) : 'Current';

                    $save_path = storage_path().'/statements';
                    $filename = "Tailwind-Statement-$date-$last_statement->id.pdf";
                    $full_path = $save_path.'/'.$filename;

                    if (!file_exists($full_path)) {
                        //Go fetch the actual PDF from Chargify
                        $statement_pdf = new ChargifyStatement();
                        $statement_pdf = $statement_pdf->getByID($last_statement->id, 'PDF');

                        if (!file_exists($save_path)) {
                            mkdir($save_path, 0777, true);
                        }
                        //Save it temporarily in the storage path
                        $pdf_file = fopen($full_path, 'w');
                        fwrite($pdf_file, $statement_pdf);
                        fclose($pdf_file);
                    }

                    //Add the path to the list of attachements
                    $this->addAttachment($save_path.'/'.$filename);

                    $this->subject = "Tailwind Statement for $month";
                }

                break;

            case self::ALERTS:
                //todo

                break;

            default:

                throw new UserEmailException('No template found for ' . $this->email_name);

                break;
        }
    }

    /**
     * Deletes temp files
     * @author  Will
     */
    public function cleanUp()
    {
        foreach ($this->attachements as $attachement) {
            unlink($attachement);
        }
    }

    /**
     * Gets the profiles associated with this email, if there are any
     *
     * @author  Will
     */
    public function profiles($force_update = false)
    {
        if ($this->_profiles AND !$force_update) {
            return $this->_profiles;
        }

        $profiles = new Profiles();

        $STH = $this->DBH->prepare("
                    select *
                    from user_emails_user_ids A
                    join data_profiles_new B
                    on A.user_id = B.user_id
                    where A.email_id = :email_id;
                ");


        $STH->execute(
            array(
                 ':email_id' => $this->id
            )
        );

        foreach ($STH->fetchAll() as $profileData) {
            $profile = new Profile();
            $profile->loadDBData($profileData);
            $profiles->add($profile);
        }

        return $this->_profiles = $profiles;
    }

    /**
     * Hard deletes the email from the database
     *
     * @author  Will
     */
    public function removeFromDB()
    {
        $STH = $this->DBH->prepare('
            delete from user_email_queue
            where id = :id
        ');

        return $STH->execute(array(
            ':id' => $this->id
        ));
    }

    /**
     * Renders the HTML of the email based on the template
     *
     * @author  Will
     *
     * @param string $type Either html or plaintext
     *
     * @throws UserEmailException
     * @return string of html
     */
    public function render($type = 'html')
    {
        switch ($this->email_name) {

            case self::DAILY_SUMMARY:
            case self::WEEKLY_SUMMARY:
            case self::MONTHLY_SUMMARY:

                $view = new \Presenters\SummaryEmail($this);

                return $view->render($type);

                break;

            case self::MONTHLY_STATEMENT:

                $body = View::make("shared.emails.html.monthly_statement",
                                   array('first_name' => $this->customer()->first_name));

                return View::make('shared.emails.templates.main',
                                  array(
                                       'main_body' => $body,
                                       'subject'   => $this->subject
                                  ));

                break;

            case self::DOWNGRADE_FREE:

                /*
                 * We use the last two digits of the send at time stamp (vs a random number) so
                 * we can see looking back which email would have sent. Otherwise it'd be well,
                 * kind of random
                 */
                $int = substr((string)$this->send_at, -2);

                $version = $int >= 50 ? 'A' : 'B';

                /*
                 * We want to make sure the name is sane, so we don't have any "hey, __SDfd"
                 * openings on a seemingly personal email
                 */
                $name = preg_replace("/[^A-Za-z0-9 ]/", '', $this->customer()->first_name);
                if ($name == '' OR !$name) {
                    $name = 'there';
                }

                return View::make('shared.emails.plaintext.downgrade_' . $version,
                                  array(
                                       'name' => $name
                                  )
                );

                break;


            case self::ALERTS:
                //todo

                break;

            default:

                throw new UserEmailException('No template found for ' . $this->email_name);

                break;
        }
    }

    /**
     * Creates a new email
     * This is somewhat useful because of the auto incrementing id field
     * save to db isn't a great process when that auto increments
     *
     * @author  Will
     */
    public function saveAsNew()
    {
        $columns = $this->columns;
        unset($columns[0]);

        /*
        * Construct SQL statement
        */
        $sql = 'INSERT INTO';
        $sql .= ' ' . $this->table;
        $sql .= ' (`';
        $sql .= implode('`,`', $columns);
        $sql = rtrim($sql, ',`');
        $sql .= '`) VALUES (:';
        $sql .= implode(',:', $columns);
        $sql = rtrim($sql, ',:');
        $sql .= ')';


        $STH = $this->DBH->prepare($sql);

        foreach ($columns as $column) {
            $STH->bindValue(':' . $column, $this->$column);
        }

        $STH->execute();

        $this->id = $this->DBH->lastInsertId();

        return $this;
    }

    /**
     * @author  Will
     */
    public function saveToDB($type = 'INSERT INTO', $append = false)
    {
        $append = "
            ON DUPLICATE KEY UPDATE
            `cust_id` = VALUES(`cust_id`),
			`email_name` = VALUES(`email_name`),
			`type` = VALUES(`type`),
			`repeat` = VALUES(`repeat`),
			`send_at` = VALUES(`send_at`),
			`updated_at` = VALUES(`updated_at`),
			`status` = VALUES(`status`)
            ";

        parent::saveToDB('INSERT INTO ', $append);
    }

    /**
     * @author  Will
     *
     * @return User
     */
    public function user()
    {
        if (!$this->_customer) {
            $this->_customer = User::find($this->cust_id);
        }

        return $this->_customer;
    }

    /**
     * Send the email using the Mailgun API
     *
     * @author  Will
     */
    public function send()
    {
        $mail_gun_client = new Mailgun(Config::get('mailgun.API_KEY'));
        $domain          = "tailwindapp.com";

        # Next, instantiate a Message Builder object from the SDK.
        $message = $mail_gun_client->MessageBuilder();

        # Define who it is from
        $message->setFromAddress($this->from);

        # Define a to recipient.
        $message->addToRecipient($this->to);

        # Define the subject.
        $message->setSubject($this->subject);

        # Define the body of the message.
        $message->setHtmlBody($this->render('html'));
        $message->setTextBody($this->render('plaintext'));

        # Add attachments
        foreach ($this->attachements as $attachment) {
            $message->addAttachment('@' . $attachment);
        }

        # Finally, send the message.
        return $mail_gun_client->post(
                               "{$domain}/messages",
                               $message->getMessage(),
                               $message->getFiles()
        );
    }


    /**
     * If the email is repeating, requeue the email in the appropriate time frame
     *
     * @author  Will
     */
    public function reQueue()
    {
        /*
         * This is not what we actually want
         * We want to find the preference of this particular set of user_ids
         * And that should depend on the email, not the first user_id of the email
         * it works now since each email only has one profile... but will need to
         * eventually update
         */
        /** @var  $preference UserEmailPreference */
        $preference =
            $this->customer()->
                getEmailPreference(
                 $this->email_name,
                 $this->profiles()->first()->user_id
                );

        if (!$preference) {
            return false;
        }

        if (!$this->is_repeating()
            OR $preference->frequency == UserEmailPreference::OFF
            OR $preference->frequency == UserEmailPreference::NEVER
        ) {
            return false;
        }

        $next_email             = new UserEmail();
        $next_email->cust_id    = $this->cust_id;
        $next_email->email_name = $this->email_name;
        $next_email->type       = $this->type;
        $next_email->repeat     = $this->repeat;
        $next_email->status     = UserEmail::STATUS_QUEUED;
        $next_email->send_at    = $preference->nextSendInEpochTime($this->send_at);
        $next_email->saveAsNew();

        $user_ids_user_emails_maps = new UserEmailUserIds();
        $email_addresses           = new UserEmailAddresses();

        /*
         * Add the pinterest account(s) in question
         */
        foreach ($this->profiles() as $profile) {
            $user_id_map           = new UserEmailUserId();
            $user_id_map->user_id  = $profile->user_id;
            $user_id_map->email_id = $next_email->id;

            $user_ids_user_emails_maps->add($user_id_map);
        }

        /*
         * Add the email address(es) of the customer
         */
        foreach ($this->emailAddresses() as $email_address) {
            /** @var $user_email_address UserEmailAddress */
            $user_email_address           = new UserEmailAddress();
            $user_email_address->email_id = $next_email->id;
            $user_email_address->address  = $email_address->address;
            $user_email_address->type     = $email_address->type;

            $email_addresses->add($user_email_address);
        }

        if ($user_ids_user_emails_maps->count() > 0) {
            $user_ids_user_emails_maps->insertUpdateDB();
        }

        if ($email_addresses->count() > 0) {
            $email_addresses->insertUpdateDB();
        }

        return true;
    }

    /**
     * Adds the attachment that will be sent. Must be a file on this server.
     *
     * @author  Will
     *
     * @param $path_to_file
     *
     * @return $this
     */
    protected function addAttachment($path_to_file)
    {
        $this->attachements[$path_to_file] = $path_to_file;
        return $this;
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/

class UserEmailException extends DBModelException {}
