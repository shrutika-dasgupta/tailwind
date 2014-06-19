<?php

/**
 * Class UserHistory
 */
class UserHistory extends PDODatabaseModel
{
    /*
    |--------------------------------------------------------------------------
    | Don’t use nondescript names like Event 12 or TMDropd. Instead, use unique
    | but recognizable names like Recorded Video and Completed Order. The best
    | analytics setups we’ve seen have event names built from a past-tense verb
    | and a noun.
    |--------------------------------------------------------------------------
    */
    const SIGNUP          = 'Sign up for an account';
    const SIGNED_UP_AGAIN = 'Tried to sign up for an account, but email address was already used';
    const CONFIRM_EMAIL   = 'Confirm email address';
    const SEND_EMAIL_CONFIRMATION = 'Send email confirmation email';
    /**
     * When a user signs up on chargify and fails for one reason or another
     */
    const FAILED_SUBSCRIPTION_SIGNUP = 'Failed subscription signup';

    const TOGGLE_DEMO = 'Toggle demo mode';

    /*
    |--------------------------------------------------------------------------
    | Trials
    |--------------------------------------------------------------------------
    */
    /**
     * When they first get a chargify ID
     */
    const TRIAL_START = 'Start trial';
    /**
     * The end date of the trial
     * trial -> anything else
     */
    const TRIAL_END = 'End of trial';
    /**
     * Date user downgrade's to free during their trial
     */
    const TRIAL_STOP = 'Stop trial early';
    /**
     * 1st billing event after a trial
     */
    const TRIAL_CONVERTED = 'Convert a trial to paid';
    /**
     * After stopping a trial, upgrading back to a paid plan before trial expires
     */
    const TRIAL_RESTARTED = 'Restarted trial';


    /*
    |--------------------------------------------------------------------------
    | Upgrades / Downgrades / Billing
    |--------------------------------------------------------------------------
    */
    /**
     * Plan states
     *      ->free-no-credit-card       (no chargify subscription)
     *      ->free-with-credit-card     (chargify subscription)
     *      ->lite
     *      ->pro
     *
     * Properties
     *      from plan (string)
     *      to plan (string)
     *      in trial (bool)
     */
    const UPGRADE          = 'Upgraded plan';
    const DOWNGRADE        = 'Downgraded plan';
    const CANCEL_DOWNGRADE = 'Cancelled plan downgrade';
    /**
     * When a we downgrade a user to free and cancel their chargify subscription
     */
    const CANCELLED_SUBSCRIPTION = 'Cancelled chargify subscription';
    /**
     * When we deactivate an account (manually)
     */
    const DEACTIVATE = 'Deactivate account';
    /**
     * When a customer's credit card is charged
     */
    const BILLED = 'Subscription billed';
    /**
     * properties
     *      ->cc_declined
     *      ->cc_expired
     *      ->cc_invalid
     *      ->cc_exp_invalid
     *      ->processing_error
     */
    const BILLING_FAILED = 'Payment failed';
    const REFUNDED = 'Refunded';
    /**
     * Happens on the 1st, 15th or 7 days before card expires
     * thanks a lot chargify
     */
    const CARD_SOON_TO_EXPIRE       = 'Credit card expiring soon';
    const CARD_UPDATE               = 'Credit card updated';
    const SUBSCRIPTION_STATE_CHANGE = 'Subscription state changed';

    /*
    |--------------------------------------------------------------------------
    | User Events
    |--------------------------------------------------------------------------
    */
    const LOGIN                              = 'Login';
    const LOGIN_DEACTIVATED                  = 'Attempted to login to deactivated account';
    const LOGOUT                             = 'Logout';
    const FORCE_LOGOUT                       = 'Force logout';
    const PASSWORD_RESET                     = 'Reset password';
    const PASSWORD_RESET_EMAIL_SENT          = 'Reset password email sent';
    const VIEW_CATALOG_FEATURES              = 'View catalog features';
    const VIEW_CATALOG_ABOUT                 = 'View catalog about';
    const CLICK_CATALOG_SIGNUP_MODAL         = 'Click the catalog signup modal';
    const VIEW_CATALOG_PRE_SIGNUP            = 'View catalog pre-signup';
    const VIEW_CATALOG_PRICING               = 'View catalog pricing';
    const CLICK_CATALOG_PRICING_FULL_FEATURE = 'Click the full feature button on catalog pricing page';
    const VIEW_APP_UPGRADE                   = 'View app upgrade';
    const CLICK_APP_UPGRADE_FULL_FEATURE     = 'Click the full feature button on app upgrade page';
    const HELP_LOGGING_IN_EMAIL_SENT         = 'Email to help user login sent';
    const PROFILE_HISTORY_EMAIL_SENT         = 'Profile History Email Sent';

    const VIEW_REPORT   = 'View a report';
    const EXPORT_REPORT = 'Export a report';

    /*
     * Profile Feature
     */
    const CLICK_REPIN_BOX   = 'Click repin box on profile page';
    const CLICK_PINS_BOX    = 'Click pins box on profile page';
    const CLICK_LIKES_BOX   = 'Click likes box on profile page';

    /*
     * Boards Feature
     */

    /*
     * Website Feature
     */

    /*
     * Trending Pins
     */

    /*
     * Top Repinners
     */

    /*
     * Influential followers
     */

    /*
     * Brand Promoters
     */

    /*
     * Pin Inspector
     */

    /*
     * Category heatmaps
     */

    /*
     * Peak days + times
     */

    /*
     * Traffic and Revenue
     */

    /*
     * Listening
     */
    const VIEW_LISTENING_DASHBOARD = 'View listening summary dashboard';
    const VIEW_LISTENING_TRENDING  = 'View listening trending pins page';
    const ADD_KEYWORD              = 'Add Keyword';
    const REMOVE_KEYWORD           = 'Remove Keyword';
    const ADD_TAG                  = 'Add Tag';
    const REMOVE_TAG               = 'Remove Tag';

    /*
     * Publisher
     */
    const VIEW_PUBLISHER_SCHEDULE = 'View publisher schedule page';
    const VIEW_PUBLISHER_POSTS    = 'View publisher posts page';
    const VIEW_EDIT_POST_MODAL    = 'View edit post modal';
    const ADD_TIMESLOT            = 'Add TimeSlot';
    const REMOVE_TIMESLOT         = 'Remove TimeSlot';
    const ADD_DRAFTS              = 'Add drafts';
    const DELETE_DRAFT            = 'Delete draft';
    const ADD_POST                = 'Add Post';
    const UPDATE_POST             = 'Update Post';
    const APPROVE_POST            = 'Approve Post';
    const REMOVE_POST             = 'Remove Post';
    const PUBLISH_POST            = 'Publish Post';

    /**
     * Content Discovery
     */
    const CONTENT_ENTRY_FLAGGED = 'Content Entry Flagged';

    /*
     * Settings
     */
    const UPDATE_CUSTOMER_NAME     = 'Updated customer name';
    const UPDATE_ORGANIZATION_NAME = 'Updated organization name';
    const UPDATE_ORGANIZATION_TYPE = 'Updated organization type';
    const UPDATE_CUSTOMER_TIMEZONE = 'Updated customer timezone';
    const UPDATE_CUSTOMER_LOCATION = 'Updated customer location';
    //---
    const UPDATE_ACCOUNT_NAME     = 'Update user account name';
    const UPDATE_ACCOUNT_USERNAME = 'Update user account username';
    const ADD_ACCOUNT_DOMAIN      = 'Add user account domain';
    const REMOVE_ACCOUNT_DOMAIN   = 'Remove user account domain';
    const UPDATE_ACCOUNT_DOMAIN   = 'Update user account domain';
    const UPDATE_ACCOUNT_TYPE     = 'Update user account type';
    const UPDATE_ACCOUNT_INDUSTRY = 'Update user account industry';
    const ADD_ACCOUNT             = 'Add user account';
    const REMOVE_ACCOUNT          = 'Remove user account';
    //---
    const ADD_COMPETITOR           = 'Add a competitor';
    const REMOVE_COMPETITOR        = 'Remove a competitor';
    //--
    const INVITE_COLLABORATOR        = 'Invite collaborator';
    const REMOVE_COLLABORATOR        = 'Remove collaborator';
    const ACCEPT_COLLABORATOR        = 'Accept collaborator invite';
    const REMOVE_CUSTOMER            = 'remove_user';
    const UPDATE_COLLABORATOR_ROLE   = 'Update collaborator role';
    //--
    const UPDATE_NOTIFICATIONS_SETTINGS = 'Update notification settings';
    const EMAIL_SEND                    = 'Was sent email';
    const EMAIL_OPEN                    = 'Open email';
    const EMAIL_COMPLAIN                = 'Complain email is spam';
    const EMAIL_BOUNCE                  = 'Email bounced';
    const EMAIL_CLICK                   = 'Email clicked';
    const EMAIL_DROPPED                 = 'Email dropped';
    const EMAIL_UNSUBSCRIBE             = 'Email unsubscribed';
    const MAILCHIMP_SUBSCRIBE           = 'MailChimp email subscribed';
    const MAILCHIMP_UNSUBSCRIBE         = 'MailChimp email unsubscribed';
    const MAILCHIMP_PROFILE_UPDATE      = 'MailChimp profile updated';
    const INTERCOM_SUBSCRIBE            = 'Intercom email subscribed';
    const INTERCOM_UNSUBSCRIBE          = 'Intercom email unsubscribed';

    //--
    const SYNC_GOOGLE_ANALYTICS          = 'Sync Google Analytics';
    const RESYNC_GOOGLE_ANALYTICS        = 'Resync Google Analytics';
    //--
    const VIEW_CHARGIFY_UPDATE_BILLING_DETAILS = 'View update billing chargify page';
    const DOWNLOAD_BILLING_STATEMENT           = 'Download Billing Statement';

    /*
     * Properties
     */
    const PROPERTY_INDUSTRY                = 'industry';
    const PROPERTY_ACCOUNT_TYPE            = 'type of account';
    const PROPERTY_NUMBER_OF_ACCOUNTS      = 'number of accounts';
    const PROPERTY_AVG_PINTEREST_FOLLOWERS = 'average number of Pinterest account followers';
    const PROPERTY_AVG_PINTEREST_REPINS    = 'average number of Pinterest account repins';
    const PROPERTY_AVG_PINTEREST_LIKES     = 'average number of Pinterest account likes';
    const PROPERTY_AVG_PINTEREST_COMMENTS  = 'average number of Pinterest account comments';

    const CREATE_ORGANIZATION = 'Create an organization';

    public
        $id,
        $cust_id,
        $type,
        $description,
        $timestamp;

    public $table = 'user_history',
        $columns = array(
        'id',
        'cust_id',
        'type',
        'description',
        'timestamp'
    ),
        $primary_keys = array('id');

    protected $_user = false;


    /**
     * @author  Will
     */
    public function __construct($cust_id = false)
    {
        $this->cust_id = $cust_id ? $cust_id : null;

        $this->timestamp = time();
        parent::__construct();
    }

    /**
     * @author   Will
     *
     * @param             $event
     * @param array       $parameters  and array of data associated with
     * @param bool|string $description For internal use only, a textual description of the parameters
     *
     *
     * @param bool        $send_external
     * @param bool        $store_in_db
     *
     * @param bool        $time
     *
     * @return bool
     */
    public function record(
        $event,
        $parameters = array(),
        $description = false,
        $send_external = true,
        $store_in_db = true,
        $time = null
    )
    {
        $parameters['ip']         = array_get($parameters, 'ip', ip());

        if ($send_external) {
            /**
             * https://segment.io/docs/integrations/intercom/
             *
             * By default Intercom updates the Last Seen user trait whenever a
             * user’s profile is updated by track or identify calls. If you want
             * to update a user without updating their Last Seen time, pass
             * active with a value of false into the context (see below).
             * This setting can be passed to the context for both track and
             * identify calls.
             */
            Analytics::track($this->cust_id, $event, $parameters, $time, ['active' => false]);
        }

        /*
        * We want to have some record of whats going on in a human readable format in our DB
        * If we don't include one explicitly, we create one from the parameters
        */
        if (!$description AND !empty($parameters)) {
            $description = json_encode($parameters);
        } else if (!$description) {
            $description = '';
        }

        if ($store_in_db) {
            return $this->recordInDB($event, $description);
        }

        return true;
    }

    /**
     * @author  Will
     *
     * @param $amount
     *
     * @param $parameters
     *
     * @return bool
     */
    public function recordBilling($amount, $parameters)
    {
        $parameters['Revenue'] = $amount;

        Analytics::track($this->cust_id, UserHistory::BILLED, $parameters);

        return $this->recordInDB(
                    $type = UserHistory::BILLED,
                    $description = $amount
        );
    }

    /**
     * Stores the history event in our database
     *
     * @author  Will
     *
     * @param $type
     * @param $description
     *
     * @return bool
     */
    public function recordInDB($type, $description)
    {
        $STH = $this->DBH->prepare('
            insert into user_history
            set cust_id = :cust_id,
            description = :description,
            type = :type,
            timestamp =:timestamp
        ');

        $params = array(
            ':cust_id'     => $this->cust_id,
            ':description' => $description,
            ':type'        => $type,
            ':timestamp'   => $this->timestamp
        );

        $STH->execute($params);

        if ($STH->rowCount() > 0) {
            return true;
        }

        return false;
    }

    /**
     * @author  Will
     *
     * @param bool $force_update
     *
     * @return array|bool
     */
    protected function getUser($force_update = false) {
        if (!$this->_user && !$force_update) {
            return $this->_user = User::find($this->cust_id);
        }

        return $this->_user;
    }
}

class UserHistoryException extends DBModelException {}
