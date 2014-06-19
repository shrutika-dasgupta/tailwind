<?php

use
    Carbon\Carbon,
    Collections\Tailwind\Features,
    Models\Tailwind\Feature,
    Pinleague\MailchimpWrapper,
    Pinleague\IntercomWrapper;

/**
 * Class User (customer)
 *
 * Users
 */
class User extends PDODatabaseModel
{
    /**
     * @const status If the user was invited
     */
    const
        PENDING  = 'pending',
        ACCEPTED = 'accepted',
        DELETED  = 'deleted';

    /**
     * @const permissions A basic permission setting
     */
    const
        PERMISSIONS_SUPER  = 'S',
        PERMISSIONS_ADMIN  = 'A',
        PERMISSIONS_VIEWER = 'V';

    /**
     * @const the status of the email address
     */
    const EMAIL_BOUNCED      = 'bounced';
    const EMAIL_UNCONFIRMED  = 'unconfirmed';
    const EMAIL_CONFIRMED    = 'confirmed';

    /**
     * @const the track type of the user
     */
    const TRACK_TYPE_INCOMPLETE = 'incomplete_signup';
    const TRACK_TYPE_FREE = 'free';
    const TRACK_TYPE_USER = 'user';
    const TRACK_TYPE_DEMO = 'DEMO';

    public
        /**
         * Unique, autoincrementing identifier for our customers
         *
         * @var $cust_id int
         */
        $cust_id,

        /**
         * Email address used to signup for the app
         *
         * @var $email string
         */
        $email,
        /**
         * If the email has been verified confirmed or is bouncing
         *
         * @var $email_status string
         */
        $email_status,
        /**
         * Bcrypt'd hash of the user's password.
         *
         * @var $password string
         */
        $password,
        /**
         * Random one time use string of characters used to login
         *
         * @var $tempory_key string
         */
        $temporary_key,
        $first_name,
        $last_name,
        $verified,
        $org_id,
        $is_admin,
        $type,
        $invited_by,
        $source,
        $city,
        $region,
        $country,
        $timezone,
        /**
         * When the user was added to our database
         * @var $added_at
         */
        $added_at,
        /**
         * The last timestamp when the user was edited / updated
         * @var $updated_at
         */
        $updated_at,
        $timestamp,
        /**
         * If set to true, the user is automatically logged out
         * @var $force_logout
         */
        $force_logout,
        /**
         * The IP adress where they last logged in
         * @var $last_seen_ip
         */
        $last_seen_ip;


    public
        /**
         * If this is a demo user, the parent is the one controlling it
         *
         * @var User | bool
         */
        $demo_parent = false;

    public
        $table = 'users',
        $columns = array(
            'cust_id',
            'email',
            'email_status',
            'password',
            'temporary_key',
            'first_name',
            'last_name',
            'verified',
            'org_id',
            'is_admin',
            'type',
            'source',
            'invited_by',
            'city',
            'region',
            'country',
            'last_seen_ip',
            'timezone',
            'added_at',
            'updated_at',
            'timestamp',
            'force_logout'
        ),
        $primary_keys = array('cust_id');

    protected $_email_attachment_preferences = array();
    protected $_organization;

    /**
     * @var Features
     */
    protected $_features;

    /**
     * @author  Will
     */
    public function __construct() {
        $this->_features = new Features;
        parent::__construct();
        $this->updated_at = time();
    }

    /**
     * Check login credentials and "log the user in"
     * Sets session vars
     *
     * @author  Will
     */
    public static function autoLogin($email, $key)
    {
        $email = strtolower($email);

        /*
         * We are using this as a static function so we need
         * to create an instance
         */
        $instance = new self();

        $STH = $instance->DBH->prepare('
                SELECT * FROM users
                WHERE email = :email
                AND temporary_key = :key
            ');
        $STH->execute(array(
                           ':email' => $email,
                           ':key'   => $key
                      ));

        if ($STH->rowCount() != 0) {

            $results = $STH->fetch();
            $instance->loadDBData($results);

            Session::put('cust_id', $results->cust_id);

            $instance->cust_id = $results->cust_id;

            $instance->createTemporaryKey();

            return $instance;
        }

        return false;
    }

    /**
     * Creates a new account
     *
     * @author  Will
     *
     * @param string $email
     * @param string $password
     * @param string $username
     * @param bool   $organization_id
     * @param bool   $add_user_account
     * @param bool   $first_name
     * @param bool   $last_name
     * @param string $is_admin
     * @param string $type
     * @param int    $invited_by
     * @param string $source
     * @param string $timezone
     * @param string $city
     * @param string $region
     * @param string $country
     *
     * @throws UserAlreadyExistsException
     * @return \User
     */
    public static function create(
        $email,
        $password,
        $username,
        $organization_id = false,
        $add_user_account = true,
        $first_name = false,
        $last_name = false,
        $is_admin = 'A',
        $type = 'free',
        $invited_by = 0,
        $source = '',
        $timezone = '',
        $city = '',
        $region = '',
        $country = ''
    )
    {
        /*
         * We don't want to make a new user with the same email. So we check that first
         */
        if (User::findByEmail($email)) {
            throw new UserAlreadyExistsException($email . ' already exists.');
        }

        if ($username) {
            $username = strtolower($username);

            /*
             * The profile *should* be in our database already since we loaded them earlier
             * so lets just find that
             * @todo run a check here to see if it is, and if not, put it in there and do some
             * pulls
             */
            $profile = Profile::findInDB($username);

            /*
             * If we have a profile and if the first name last name is not set
             * we use the profile
             */
            if (!$first_name) {
                $first_name = $profile->first_name;
            }

            if (!$last_name) {
                $last_name = $profile->last_name;
            }
        }

        /*
         * Each customer is linked to a specific organization so if there is no
         * organization id given then we need to create one first
         */
        if (!$organization_id) {
            $organization = Organization::create($username);
        } else {
            $organization = Organization::find($organization_id);
        }

        /*
         * We want to hash the password because plain text is bad and we'd feel bad
         */

        $user        = new User();
        $user->email = $email;
        $user->setPassword($password);
        $user->first_name = $first_name;
        $user->last_name  = $last_name;
        $user->verified   = '';
        $user->org_id     = $organization->org_id;
        $user->is_admin   = $is_admin;
        $user->type       = $type;
        $user->invited_by = $invited_by;
        $user->source     = $source;
        $user->timezone   = $timezone;
        $user->timestamp  = time();
        $user->added_at   = time();
        $user->updated_at = time();
        $user->city       = $city;
        $user->region     = $region;
        $user->country    = $country;

        $user->saveAsNew();

        /*
         * We want to record what we just did in user history
         * (we had to wait to get the cust_id to do this)
         */
        if (!$organization_id) {
            $user->recordAction(
                 UserHistory::CREATE_ORGANIZATION,
                 "Created a new organization with id $organization->org_id");
        }

        if ($add_user_account) {

            $user_account = UserAccount::create($profile, $organization->org_id);
            $user->recordAction(
                 UserHistory::ADD_ACCOUNT,
                 "Created a new user_account with id $user_account->account_id"
            );

            /*
             * Create default preferences for email queue
             */
            $preferences = UserEmailPreferences::getDefault($user_account);
            $preferences->setPropertyOfAllModels('cust_id', $user->cust_id);
            $preferences->insertUpdateDB();

            $user->removeQueuedAutomatedEmails()
                 ->seedAutomatedEmails();

            $user->recordAction(
                UserHistory::ADD_ACCOUNT_DOMAIN,
                    'Added a blank domain'
            );
        }

        // Subscribe our new user to the blog rss newsletter. (Pending users are subscribed on activation.)
        if ($user->type != User::PENDING && $user->type != User::TRACK_TYPE_INCOMPLETE) {
            try {
                MailchimpWrapper::instance()->subscribeToList(
                    Config::get('mailchimp.BLOG_RSS_LIST_ID'),
                    $user
                );
            } catch (Exception $e) {
                Log::error($e);
            }

            try {
                if ($user->type != 'DEMO') {
                    $user->sendConfirmationEmail();
                    $user->recordEvent(UserHistory::SEND_EMAIL_CONFIRMATION);
                }
            }
            catch (Exception $e) {
                Log::error($e);
            }
        }

        // If this user is a viewer, make them a pin scheduling draft editor by default.
        if ($user->is_admin == User::PERMISSIONS_VIEWER && $user->hasFeature('pin_scheduling_enabled')) {
            $user->disableFeature('pin_scheduling_admin');
            $organization->enableFeature('pin_scheduling_approval_queue');
        }

        return $user;
    }

    /**
     * Find user by email
     *
     * @author  Will
     *
     * @param $email
     *
     * @return User
     */

    public static function findByEmail($email)
    {

        $db_user = DB::select("SELECT * FROM users WHERE email = ? LIMIT 1",array($email));

        if ($db_user) {
            return  User::createFromDBData($db_user[0]);
        }

        return false;
    }

    /**
     * Gets the id of the logged in user.
     *
     * @returns int bool
     * @author Will
     *
     * @return int|bool
     */
    public static function getLoggedInId()
    {
        if (Session::has('cust_id')) {
            return Session::get('cust_id');
        }

        return false;
    }


    /**
     * Get the active account
     *
     * @author  Will
     */
    public function getActiveUserAccount()
    {

        if (Session::has('account_index')) {
            $account_index = Session::get('account_index');
        } else {
            $account_index = 0;
        }

        /*
         * If they are on the free plan, we only want to give them access to the first account
         * and no others
         */
        if ($this->plan()->plan_id == Plan::FREE_PLAN_ID) {
            $account_index = 0;
        }

        $accounts = $this->organization()->connectedUserAccounts('active');
        if (!isset($accounts[$account_index])) {
            Log::warning('There is no account at this index:' . $account_index);

            return $accounts->first();
        }

        return $accounts[$account_index];
    }

    /**
     * Gets the logged in user.
     *
     * @author Daniel
     *
     * @return User|bool
     */
    public static function getLoggedInUser()
    {
        if (!$customer_id = self::getLoggedInId()) {
            return false;
        }

        return User::find($customer_id);
    }

    /**
     * Check login credentials and "log the user in"
     * Sets session vars
     *
     * @author  Will
     *
     * @param $email
     * @param $password
     *
     * @return User
     */
    public static function login($email, $password)
    {
        /*
         * We are using this as a static function so we need
         * to create an instance
         */
        $instance = new User();
        $email    = strtolower($email);

        $STH = $instance->DBH->prepare(
                             'select * from users where email = :email and password = :password'
        );
        $STH->execute(array(
                           ':email'    => $email,
                           ':password' => $instance->hashPassword($email, $password)
                      ));
        if ($STH->rowCount() != 0) {

            $instance->loadDBData($STH->fetch());
            $instance->setLogin($password);

            return $instance;
        }

        return false;
    }

    /**
     * Get user attachment preferences
     *
     * @author  Will
     *
     * @param $user_id
     *
     * @return UserEmailAttachmentPreferences
     */
    public function attachmentPreferences($user_id)
    {
        if ($this->_email_attachment_preferences[$user_id]) {
            return $this->_email_attachment_preferences[$user_id];
        }

        /*
         * Start with the defaults
         */
        $preferences = UserEmailAttachmentPreferences::defaultPreferences($user_id);

        /*
         * Look for username preferences
         */
        $STH = $this->DBH->prepare('
                select * from user_email_attachment_preferences
                where cust_id = :cust_id
                and user_id = :user_id
            ');

        $STH->execute(
            array(
                 ':cust_id' => $this->cust_id,
                 ':user_id' => $user_id
            )
        );

        /*
         * If we got any rows back, overwrite the defaults
         */
        if ($STH->rowCount() > 0) {
            foreach ($STH->fetchAll() as $preferenceData) {

                $preference = new UserEmailAttachmentPreference();
                $preference->loadDBData($preferenceData);

                $preferences->add($preference, $user_id . '-' . $preference->name);
            }
        }

        return $this->_email_attachment_preferences[$user_id] = $preferences;
    }

    /**
     * Deletes all emails from the queue that haven't been Processed or Sent
     * so that you can re add the right emails
     *
     * @author  Will
     *
     * @see     seedEmailQueue($username)
     */
    public function cancelFutureAutomatedEmails()
    {
        $STH = $this->DBH->prepare("
               UPDATE user_email_queue
               SET status = :status
               WHERE cust_id = :cust_id
               AND (status is NULL OR
               (status != :sent AND status != :processing ))
               AND TYPE = :automated
            ");

        $STH->execute(
            array(
                 ':cust_id'    => $this->cust_id,
                 ':status'     => UserEmail::STATUS_CANCELLED,
                 ':sent'       => UserEmail::STATUS_SENT,
                 ':processing' => UserEmail::STATUS_PROCESSING,
                 ':automated'  => UserEmail::AUTOMATED
            )
        );
    }

    /**
     * @author  Will
     */
    public function createTemporaryKey()
    {
        $key = random_string(45);
        $STH = $this->DBH->prepare('
                 update users set temporary_key = :key
                 where cust_id = :cust_id
            ');

        $STH->execute(
            array(
                 ':key'     => $key,
                 ':cust_id' => $this->cust_id
            )
        );

        return $key;
    }

    /**
     * @author  Will
     * @return bool
     */
    public function doesNotHaveCreditCardOnFile()
    {
        return !$this->hasCreditCardOnFile();
    }

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
    public function getEmailPreference($name, $user_id)
    {
        $preference = UserEmailPreference::find(
            $this->cust_id,
            $name,
            $user_id
        );

        if (!$preference) {
            switch ($name) {
                case UserEmail::MAILCHIMP_BLOG_RSS:
                    $subscribed = MailchimpWrapper::instance()->isUserSubscribed(
                        Config::get('mailchimp.BLOG_RSS_LIST_ID'),
                        $this
                    );

                    $preference = new UserEmailPreference();
                    $preference->name      = $name;
                    $preference->user_id   = $user_id;
                    $preference->frequency = $subscribed ? UserEmailPreference::ON : UserEmailPreference::OFF;
                    break;

                case UserEmail::INTERCOM_EMAIL:
                    $subscribed = IntercomWrapper::instance()->isUserSubscribed($this);

                    $preference = new UserEmailPreference();
                    $preference->name      = $name;
                    $preference->user_id   = $user_id;
                    $preference->frequency = $subscribed ? UserEmailPreference::ON : UserEmailPreference::OFF;
                    break;

                default:
                    $preference = UserEmailPreference::defaultPreference(
                        $name,
                        $user_id
                    );

                    $preference->cust_id = $this->cust_id;
                    break;
            }
        }

        return $preference;
    }

    /**
     * @author  Will
     * @return UserHistories
     */
    public function getHistory()
    {
        $STH = $this->DBH->prepare(
                         'SELECT * FROM user_history WHERE cust_id = :cust_id'
        );

        $STH->execute(
            array(
                 ':cust_id' => $this->cust_id
            )
        );

        $history = new UserHistories();
        foreach ($STH->fetchAll() as $historyData) {
            $user_history = new UserHistory($this->cust_id);
            $user_history->loadDBData($historyData);
            $history->add($user_history);
        }

        return $history;
    }

    /**
     * @author  Will
     */
    public function getLastLogin($exclude_current_session = false)
    {
        if ($exclude_current_session) {
            $limit = 'LIMIT 1,2';
        } else {
            $limit = '';
        }

        $STH = $this->DBH->prepare("
                SELECT * FROM user_history
                WHERE cust_id = :cust_id
                AND type = :type
                ORDER BY timestamp DESC
                $limit
             ");

        $STH->execute(
            array(
                 ':cust_id' => $this->cust_id,
                 ':type'    => UserHistory::LOGIN
            )
        );

        if ($STH->rowCount() == 0) {
            return false;
        }

        return DateTime::createFromFormat('U', $STH->fetch()->timestamp);

    }

    /**
     * @author  Will
     * @return string
     */
    public function getName()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * @author  Will
     *
     * @param $usernameOrUserId
     *
     * @throws UserAccountException
     * @return bool|\UserAccount
     */
    public function getUserAccount($usernameOrUserId)
    {
        if (is_string($usernameOrUserId)) {
            $field = 'username';
        } else
            if (is_int($usernameOrUserId)) {
                $field = 'user_id';

            } else {
                throw new UserAccountException('User identifier has to be a string or an int');
            }

        $STH = $this->DBH->prepare("
                SELECT * FROM user_accounts
                WHERE $field = :identifier
                AND (
                  track_type != :competitor
                  AND track_type != :orphan
                )
                AND org_id = :org_id
            ");

        $STH->execute(
            array(
                 ':identifier' => $usernameOrUserId,
                 ':competitor' => UserAccount::TRACK_TYPE_COMPETITOR,
                 ':orphan'     => UserAccount::TRACK_TYPE_ORPHAN,
                 ':org_id'     => $this->org_id,
            )
        );

        if ($STH->rowCount() == 0) {
            return false;
        }

        $user_account = new UserAccount();
        $user_account->loadDBData($STH->fetch());

        return $user_account;
    }

    /**
     * @author  Will
     * @return bool
     */
    public function hasCreditCardOnFile()
    {
        return $this->organization()->hasCreditCardOnFile();
    }

    /**
     * @param Models\Tailwind\Feature $feature
     *
     * @param bool    $search_recursively
     *                 If set to true, we'll look for the feature setting in
     *                  the organization and plan
     *
     * @return Models\Tailwind\Feature
     */
    public function getFeature($feature,$search_recursively = false)
    {
        /*
         * If it's already a feature we don't need to know the name
         * and we can search by feature id
         */
        if ($feature instanceof Feature) {
            if ($this->_features->has($feature->feature_id)) {
                return $this->_features->get($feature->feature_id);
            }
        }

        /*
         * If we pass a string name of the feature, before we make a call to the
         * database, lets check the feature cache and see if we already have it
         */
        if (is_string($feature)) {

            $cached_feature = $this->_features->first(function ($feature_id, $model) use ($feature) {
                if ($feature == $model->name) {
                    return true;
                }
                return false;
            });

            if(!empty($cached_feature)) {
                return $cached_feature;
            }
        }

        /*
         * If we don't have it in the cache, and it's not already a feature (aka
         * it's a string) then lets look for it
         */
        if ( empty($cached_feature) AND !($feature instanceof Feature)) {
            $name = $feature;
            $feature = Feature::where('name' ,'=', $name)->get()->first();

            if (!$feature) {
                Log::warning("Looking for a feature that doesn't exist: $name");
                return new Feature();
            }
        }

        $user_feature =
            UserFeature::where('cust_id','=',$this->cust_id)
            ->where('feature_id','=',$feature->feature_id)
            ->get()
            ->first();

        if ($user_feature) {

            $feature->value = $user_feature->value;
            $feature->specificity = Feature::SPECIFICTY_USER;
            $this->_features->add($feature, true);

            return $feature;
        }

        if($search_recursively) {
            return $this->organization()->getFeature($feature,$search_recursively);
        }

        return false;
    }

    /**
     * Determines whether this plan has a given feature.
     *
     * @author Daniel
     * @author Will
     *
     * @param string | Feature $feature
     *
     * @return bool
     */
    public function hasFeature($feature)
    {
        return $this->getFeature($feature,true)->isEnabled();
    }

    /**
     * Determines the limit for a feature of this plan.
     *
     * @author Daniel
     * @author Will
     *
     * @param Feature | string $feature
     *
     * @return int
     */
    public function maxAllowed($feature)
    {
        return $this->getFeature($feature,true)->maxAllowed();
    }

    /**
     * @param $feature
     *
     * @return bool|int
     */
    public function featureValue($feature)
    {
        return $this->getFeature($feature,true)->value;
    }

    /**
     * Enables a feature for this user.
     *
     * @author Janell
     *
     * @param string|Feature $feature
     *
     * @return bool
     */
    public function enableFeature($feature)
    {
        return $this->editFeature($feature, 1);
    }

    /**
     * Disables a feature for this user.
     *
     * @author Janell
     *
     * @param string|Feature $feature
     *
     * @return bool
     */
    public function disableFeature($feature)
    {
        return $this->editFeature($feature, 0);
    }

    /**
     * Changes the value of a feature for this user.
     *
     * @author Janell
     *
     * @param string|Feature $feature
     * @param int|string $value
     *
     * @return bool
     */
    public function editFeature($feature, $value = 0)
    {
        $user_feature = $this->getFeature($feature);

        if ($user_feature instanceof UserFeature) {
            $user_feature->value = $value;
            $user_feature->insertUpdate();

        } else {
            $user_feature = new UserFeature();
            $user_feature->value      = $value;
            $user_feature->feature_id = $feature->feature_id;
            $user_feature->cust_id    = $this->cust_id;
            $user_feature->insertUpdate();

            $this->_features->add($user_feature);
        }

        return true;
    }

    /**
     * @author  Will
     *
     * @todo    really should salt this with email and maybe a random key
     * @todo    really should not be using sha1
     *
     */
    public function  hashPassword($email, $password)
    {
        return sha1(trim($password));
    }

    /**
     * @author  Alex
     *
     * @return bool
     */
    public function isDashboardReady()
    {
        if ($this->organization()
                 ->primaryAccount()
                 ->profile()
                 ->getStatusProfile()
        ) {
            if ($last_calced = $this->organization()
                                    ->primaryAccount()
                                    ->profile()
                                    ->getStatusProfile()->last_calced > 0
            ) {
                return $last_calced;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Get the account associated with this customer
     *
     * @example $customer->account->name; Oldnavy
     * @author  Will
     *
     * @return \Organization
     */
    public function organization()
    {
        if (!$this->_organization) {
            $this->_organization = Organization::find($this->org_id);
        }

        return $this->_organization;
    }

    /**
     * Get the plan of the user
     *
     * @author  Will
     * @return Plan
     */
    public function plan()
    {
        return $this->organization()->plan();
    }

    /**
     * Record some user action in our DB
     * An action is something transaction we want to be able to see happened at a micro level,
     * not necessarily measure on a macro level
     *
     * @author  Will
     *
     * @param      $type
     * @param      $description
     * @param bool $time if no time is set, we'll do it right now
     *
     * @return bool
     */
    public function recordAction($type, $description, $time = false)
    {
        $user_history = new UserHistory($this->cust_id);
        if($time) {
            $user_history->timestamp = $time;
        }

        return $user_history->recordInDB($type, $description);
    }

    /**
     * Record some user action
     * An event is something we want to measure on a higher level and on a micro level
     *
     * @author  Will
     */
    public function recordEvent($type, $parameters=array(), $description=false,$time = false)
    {
        if ($this->demo_parent) {
            $parameters['__demo'] = true;
            return $this->demo_parent->recordEvent($type,$parameters,$description,$time);
        }

        $user_history = new UserHistory($this->cust_id);

        if (array_key_exists('report',$parameters) AND $type === UserHistory::VIEW_REPORT) {

            $view_property = UserProperty::VIEW_REPORT;
            $last_viewed_property = UserProperty::LAST_VIEWED_REPORT_AT;

            if (array_key_exists('__demo',$parameters)) {
                $view_property = 'demo_'.$view_property;
                $last_viewed_property = 'demo_'.$last_viewed_property;

                $this->incrementUserProperty(UserProperty::TOTAL_DEMO_VIEWS,1);
            }

            $this->incrementUserProperty($view_property.$parameters['report'],1);
            $this->setUserProperty($last_viewed_property.$parameters['report'],time());
        }

        return $user_history->record(
            $type,
            $parameters,
            $description,
            $send_external = true,
            $store_in_db = true,
            $time
        );
    }

    /**
     * Removes queued "why did you downgrade to free" email from the queue
     * @author  Will
     */
    public function removeDowngradeEmail()
    {
        $STH = $this->DBH->prepare("
                    delete from user_email_queue
                    where `cust_id` = :cust_id
                    AND `status` = :queued
                    and `email_name` = :downgrade
                ");

        $STH->execute(
            array(
                 ':cust_id'   => $this->cust_id,
                 ':queued'    => UserEmail::STATUS_QUEUED,
                 ':downgrade' => UserEmail::DOWNGRADE_FREE
            )
        );

        return $this;
    }

    /**
     * Removes all queued emails off the queue
     *
     * @author  Will
     *
     * @return $this
     */
    public function removeQueuedAutomatedEmails()
    {
        $STH = $this->DBH->prepare(
                         '
                          delete from user_email_queue
                          where `status` = :queued
                          and `cust_id` = :cust_id
                          and `type` = :type
                          '
        );

        $STH->execute(
            array(
                 ':queued'  => UserEmail::STATUS_QUEUED,
                 ':cust_id' => $this->cust_id,
                 ':type'    => UserEmail::AUTOMATED
            )
        );

        return $this;
    }

    /**
     * @author  Will
     */
    public function saveToDB($statement_type = 'INSERT INTO', $append = false)
    {
        $append = 'ON DUPLICATE KEY UPDATE ';

        foreach ($this->columns as $column) {
            $append .= "$column = VALUES($column),";
        }

        $append = rtrim($append, ',');

        parent::saveToDB('INSERT INTO', $append);

        return $this;
    }

    /**
     * Take the user's email preferences, and add the appropriate emails to the queue
     * Will schedule
     * the daily email tomorrow
     * the weekly email for next monday
     * and the monthly email for the next first monday of the month
     *
     *
     * @author  Will
     */
    public function seedAutomatedEmails()
    {
        $preferences = new UserEmailPreferences();

        foreach ($this->organization()->activeUserAccounts() as $account) {

            $daily_preference   = $this->getEmailPreference(UserEmail::DAILY_SUMMARY, $account->user_id);
            $weekly_preference  = $this->getEmailPreference(UserEmail::WEEKLY_SUMMARY, $account->user_id);
            $monthly_preference = $this->getEmailPreference(UserEmail::MONTHLY_SUMMARY, $account->user_id);
            $monthly_statement  = $this->getEmailPreference(UserEmail::MONTHLY_STATEMENT, $account->user_id);

            $preferences->add($daily_preference);
            $preferences->add($weekly_preference);
            $preferences->add($monthly_preference);
            $preferences->add($monthly_statement);

        }

        $preferences->queueEmails();

        return $this;
    }

    /**
     * @author  Will
     *
     * @param $password
     *
     * @throws UserException
     */
    public function setLogin($password)
    {
        Session::forget('cust_id');
        Session::put('cust_id', $this->cust_id);
    }

    /**
     * @author  Will
     *
     * @param $password
     *
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $this->hashPassword($this->email, $password);

        return $this;
    }

    /**
     * @author  Will
     */
    public function trialEndDate()
    {
        return $this->organization()->trialEndDate();
    }

    /**
     * @author  Will
     */
    public function findUserHistoryEvents($type, $timerange = '30 days ago')
    {
        $timestamp = strtotime($timerange);

        $STH = $this->DBH->prepare("
                    select * from user_history
                    where cust_id = :cust_id
                    and `type` = :type
                    and timestamp < :timestamp
                ");

        $STH->execute(
            array(
                 ':cust_id'   => $this->cust_id,
                 ':type'      => $type,
                 ':timestamp' => $timestamp
            )
        );

        $histories = new UserHistories();
        foreach ($STH->fetchAll() as $historyData) {

            $history = new UserHistory($this->cust_id);
            $history->loadDBData($historyData);
            $histories->add($history);

        }

        return $histories;
    }

    /**
     * Returns link that allows a user to login automatically
     *
     * @author  Will
     * @return string
     */
    public function getAutoLoginLink()
    {
        return url("/login/auto/$this->email/$this->temporary_key/");
    }

    /**
     * @author  Will
     *
     */
    public function getUserProperty($property) {
        $STH = $this->DBH->prepare("
                    SELECT * FROM user_properties
                    WHERE cust_id = :cust_id
                    AND property = :property
                    LIMIT 1
                ");

        $STH->execute(
                    array(
                         ':cust_id' => $this->cust_id,
                         ':property'=> $property
                    )
                );

        if($STH->rowCount() == 0) {
            Log::warning("Tried to get a property($property) that did not exist. Creating it");
            return $this->setUserProperty($property,0);
        }

        return UserProperty::createFromDBData($STH->fetch());
    }

    /**
     * @author  Will
     *
     * @param $property
     * @param $value
     *
     * @return \UserProperty
     */
    public function setUserProperty($property,$value) {
        $user_property = new UserProperty();
        $user_property->cust_id = $this->cust_id;
        $user_property->property = $property;
        $user_property->count = $value;
        $user_property->insertUpdateDB(array('created_at'));

        return $user_property;
    }

    /**
     * @author  Will
     *
     * @param $property
     * @param $amount
     *
     * @return void
     */
    public function incrementUserProperty($property, $amount)
    {

        $STH = $this->DBH->prepare("
          UPDATE user_properties
          SET `count`=`count`+$amount
          WHERE cust_id = :cust_id
          AND property = :property
        ");

        $STH->execute(
            array(
                 ':cust_id'  => $this->cust_id,
                 ':property' => $property
            )
        );

        if ($STH->rowCount() == 0) {
            $this->setUserProperty($property, $amount);
        }
    }

    /**
     * @author  Will
     *
     * @return UserProperties
     */
    public function getAllProperties() {
        $STH = $this->DBH->prepare("
                    select * from user_properties
                                 where cust_id = :cust_id
                ");

        $STH->execute(
                    array(
                         ':cust_id' => $this->cust_id
                    )
                );

        $properties = new UserProperties();
        foreach ($STH->fetchAll() as $propertyData) {
            $properties->add(UserProperty::createFromDBData($propertyData));
        }

        return $properties;

    }

    /**
     * Fetches a social property.
     *
     * @author Janell
     *
     * @param string $type
     * @param string $name
     *
     * @return UserSocialProperty (null if not found)
     */
    public function getSocialProperty($type, $name)
    {
        $STH = $this->DBH->prepare("
            SELECT * FROM user_social_properties
            WHERE cust_id = :cust_id
            AND type = :type
            AND name = :name
            LIMIT 1
        ");

        $STH->execute(array(
            ':cust_id' => $this->cust_id,
            ':type'    => $type,
            ':name'    => $name,
        ));

        if ($STH->rowCount() == 0) {
            return null;
        }

        return UserSocialProperty::createFromDBData($STH->fetch());
    }

    /**
     * Creates or updates a social property.
     *
     * @author Janell
     *
     * @param string $type
     * @param string $name
     * @param mixed $value
     *
     * @return UserSocialProperty
     */
    public function setSocialProperty($type, $name, $value)
    {
        $social_property = new UserSocialProperty();
        $social_property->cust_id = $this->cust_id;
        $social_property->type    = $type;
        $social_property->name    = $name;
        $social_property->value   = $value;

        $social_property->insertUpdateDB(array('created_at'));

        return $social_property;
    }

    /**
     * Returns all social properties, optionally narrowed by type.
     *
     * @author Janell
     *
     * @param string $type
     *
     * @return UserSocialProperties
     */
    public function getAllSocialProperties($type = null)
    {
        $query = "
            SELECT * FROM user_social_properties
            WHERE cust_id = :cust_id
        ";

        $query_vars = array(
            ':cust_id' => $this->cust_id,
        );

        if ($type) {
            $query .= " AND type = :type";
            $query_vars[':type'] = $type;
        }

        $STH = $this->DBH->prepare($query);
        $STH->execute($query_vars);

        $social_properties = new UserSocialProperties();
        foreach ($STH->fetchAll() as $property_data) {
            $social_properties->add(UserSocialProperty::createFromDBData($property_data));
        }

        return $social_properties;
    }

    /**
     * @author  Will
     * @return bool
     */
    public function is_collaborator()
    {
        if ($this->invited_by != 0) {
            return true;
        }

        return false;
    }

    /**
     * @param UserAccount $user_account
     *
     * @return $this
     */
    public function removeEmailPreferences(UserAccount $user_account) {
       $STH =  $this->DBH->prepare("
            DELETE FROM user_email_preferences
            WHERE cust_id = :cust_id
            AND user_id = :user_id
        ");

        $STH->execute(array(
                           ':cust_id' => $this->cust_id,
                           ':user_id' => $user_account->user_id
                      )
        );

        return $this;
    }

    /**
     * @param $type
     *
     * @return bool
     */
    public function subscribedToAutomatedEmail($type)
    {
        foreach ($this->organization()->activeUserAccounts() as $account) {

            $preference = $this->getEmailPreference($type, $account->user_id);

            if ($preference->frequency != UserEmailPreference::NEVER AND
                $preference->frequency != UserEmailPreference::OFF
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return $this|void
     */
    public function saveAsNew() {
        $return = parent::saveAsNew();
        $this->cust_id = $this->DBH->lastInsertId();

        return $return;
    }

    /**
     * Returns the user's timezone.
     *
     * If $default is true and the user does not have a valid timezone, the app default will be
     * returned. Make sure $default is true if you are using the timezone with DateTime or Carbon
     * instances to prevent exceptions.
     *
     * Set $default to false if you want to check for a valid timezone setting.
     *
     * @author Janell
     *
     * @param bool $default
     *
     * @return string|null
     */
    public function getTimezone($default = true)
    {
        // Check for null (new signups) or old offset format.
        if (!empty($this->timezone) && !preg_match('^([\+-]\d{1,2}:\d{2})$', $this->timezone)) {
            return $this->timezone;
        }

        if ($default) {
            return Config::get('app.timezone');
        }

        return null;
    }

    /**
     * @author  Will
     * @return Users
     */
    public function collaborators() {
        return
            $this->organization()
            ->users()
            ->copy()
            ->filter(
                function (User $user) {
                    if ($user->is_admin == User::PERMISSIONS_SUPER) {
                        return false;
                    }

                    return true;
                }
            );
    }

    /**
     * @return Tasks
     */
    public function tasks()
    {
        /*
         * A user's tasks are also those of an organization,
         * so we start with those
         *
         * An organizations tasks also include those of the connected
         * active user accounts, so those are in there too
         *
         * These can be filtered by type later
         */
        $tasks = $this->organization()->tasks();

        foreach (Tasks::$user_task_names as $name) {
            $task = new Task($name);
            $task->setType(Task::TYPE_USER);

            switch ($name) {

                default:
                    Log::warning(
                       'A task was added that can not be evaluated',
                       $task
                    );
                    break;

                case 'invited_collaborator':

                    if ($this->collaborators()->count() > 0) {
                        $task->setComplete();
                    }

                    if ($this->is_admin != User::PERMISSIONS_SUPER) {
                        unset($task);
                    }

                    break;
                case 'confirmed_email':

                    if ($this->email_status == User::EMAIL_CONFIRMED) {
                        $task->setComplete();
                    }

                    break;
            }

            if ($task) {
                $tasks->add($task);
            }
        }

        return $tasks;
    }

    /**
     * @author  Will
     */
    public function sendConfirmationEmail()
    {
        $token = Crypt::encrypt(time());
        $key   = $this->createTemporaryKey();
        $email = urlencode($this->email);

        $url = route('confirm-email', [$email, $key, $token]);

        $email = \Pinleague\Email::instance(UserHistory::SEND_EMAIL_CONFIRMATION);
        $email->subject('Confirm your Tailwind email address');
        $email->body('confirm_email', ['link' => $url, 'first_name' => $this->first_name]);
        $email->to($this);

        return $email->send();
    }

    /**
     * Since we just recently starting recording this, if added_at is null
     * or missing, we try to find it other ways
     */
    public function addedAt() {
        if (empty($this->added_at)) {
                $this->added_at = $this->organization()->signupDate();
        }
        return $this->added_at;
    }

    /**
     * @return int
     */
    public function daysSinceCreated() {
        if(empty($this->addedAt())) {
            return $this->timestamp;
        }
        return Carbon::createFromFormat('U',$this->addedAt())->diffInDays();
    }

    /**
     * There should probably be a method for org and plan but idgaf
     *
     * @author  Will
     *
     * @return Features
     */
    public function getAllFeatures()
    {
        /** @var Features $features */
        $features = Feature::all();

        $user_features = Models\Tailwind\UserFeature::where('cust_id', '=', $this->cust_id)->get();
        $org_features  = \Models\Tailwind\OrganizationFeature::where('org_id', '=', $this->org_id)->get();

        /*
         * We need to use the right plan id for legacy plans
         * this should move to the plan but, still feeling kinda YOLO
         */
        if($this->organization()->is_legacy){
            $plan_id = $this->organization()->plan()->legacyPlanId();
        } else {
            $plan_id = $this->organization()->plan;
        }
        $plan_features = \Models\Tailwind\PlanFeature::where('plan_id', '=', $plan_id)->get();

        foreach ($plan_features as $plan_feature) {
            $feature = $features->get($plan_feature->feature_id);
            $feature->value = $plan_feature->value;
            $feature->specificity = Feature::SPECIFICTY_PLAN;
            $features->put($feature->feature_id,$feature);
        }
        foreach ($org_features as $org_feature) {
            $feature = $features->get($org_feature->feature_id);
            $feature->value = $org_feature->value;
            $feature->specificity = Feature::SPECIFICTY_ORG;
            $features->put($feature->feature_id,$feature);
        }
        foreach ($user_features as $user_feature) {
            $feature = $features->get($user_feature->feature_id);
            $feature->value = $user_feature->value;
            $feature->specificity = Feature::SPECIFICTY_USER;
            $features->put($feature->feature_id,$feature);
        }

        $features->guard(['value']);

        return $this->_features = $features;
    }
}

class UserException extends Exception {}

class UserNotFoundException extends UserException {}

class UserAlreadyExistsException extends UserException {}
