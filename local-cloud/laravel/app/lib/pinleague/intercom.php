<?php

namespace Pinleague;

use Intercom,
    Config,
    User,
    UserEmail,
    UserEmailPreferences,
    UserEmailPreference,
    UserHistory,
    Log,
    Exception;

/**
 * Intercom wrapper class.
 *
 * @author Janell
 */
class IntercomWrapper
{
    /**
     * Intercom instance.
     *
     * @var Intercom
     */
    protected $intercom;

    /**
     * Initializes the class.
     *
     * @author Janell
     *
     * @return void
     */
    public function __construct()
    {
        $this->intercom = new Intercom(
            Config::get('intercom.APP_ID'),
            Config::get('intercom.API_KEY')
        );
    }

    /**
     * Gets a new IntercomWrapper instance.
     *
     * @author Janell
     *
     * @return IntercomWrapper
     */
    public static function instance()
    {
        return new self();
    }

    /**
     * Fetches users in batches. The current limit per page is 500.
     *
     * @author Janell
     *
     * @param int $page
     * @param int $per_page
     *
     * @return stdClass object
     */
    public function getUsers($page = 1, $per_page = 500)
    {
        $response = $this->intercom->getAllUsers($page, $per_page);

        Log::debug('Retrieved users from Intercom', array(
            'page'     => $page,
            'per_page' => $per_page,
            'response' => $response,
        ));

        return $response;
    }

    /**
     * Checks the user's current subscription status.
     *
     * @author Janell
     *
     * @param User $user
     *
     * @return bool
     */
    public function isUserSubscribed(User $user)
    {
        $subscribed = true;

        $response = $this->intercom->getUser($user->email);

        Log::debug('Retrieved user from Intercom', array(
            'email'    => $user->email,
            'response' => $response,
        ));

        if (isset($response->unsubscribed_from_emails)) {
            $subscribed = ! (bool) $response->unsubscribed_from_emails;
        }

        // Save this preference for later.
        try {
            self::saveSubscriptionPreference($user, $subscribed);
        } catch (Exception $e) {
            Log::error($e);
        }

        return $subscribed;
    }

    /**
     * Subscribes a user to Intercom email.
     *
     * @author Janell
     *
     * @param User $user
     *
     * @throws Exception
     */
    public function subscribe(User $user)
    {
        $response = $this->intercom->updateUser(
            null,
            $user->email,
            null,
            array(),
            null,
            null,
            null,
            null,
            false
        );

        Log::debug('Updated Intercom user subscription status', array(
            'email'      => $user->email,
            'subscribed' => true,
            'response'   => $response,
        ));

        if (!empty($response->error)) {
            Log::debug('Intercom returned an error on subscribe', array(
                'type'    => $response->error->type,
                'message' => $response->error->message,
            ));

            throw new Exception($response->error->message);
        }

        self::saveSubscriptionPreference($user, true);
    }

    /**
     * Unsubscribes a user from Intercom email.
     *
     * @author Janell
     *
     * @param User $user
     *
     * @throws Exception
     */
    public function unsubscribe(User $user)
    {
        $response = $this->intercom->updateUser(
            null,
            $user->email,
            null,
            array(),
            null,
            null,
            null,
            null,
            true
        );

        Log::debug('Updated Intercom user subscription status', array(
            'email'      => $user->email,
            'subscribed' => false,
            'response'   => $response,
        ));

        if (!empty($response->error)) {
            Log::debug('Intercom returned an error on unsubscribe', array(
                'type'    => $response->error->type,
                'message' => $response->error->message,
            ));

            throw new Exception($response->error->message);
        }

        self::saveSubscriptionPreference($user, false);
    }

    /**
     * Records a user's Intercom subscription preference.
     *
     * @author Janell
     *
     * @param User $user
     * @param bool $subscribed
     *
     * @throws Exception
     */
    public static function saveSubscriptionPreference(User $user, $subscribed)
    {
        $account = $user->organization()->primaryAccount();
        if (empty($account)) {
            throw new Exception('Could not find active account for user');
        }

        $preference = new UserEmailPreference();
        $preference->name      = UserEmail::INTERCOM_EMAIL;
        $preference->cust_id   = $user->cust_id;
        $preference->username  = $account->username;
        $preference->user_id   = $account->user_id;
        $preference->frequency = $subscribed ? UserEmailPreference::ON : UserEmailPreference::OFF;

        $preferences = new UserEmailPreferences();
        $preferences->add($preference);
        $preferences->insertUpdateDB();

        $parameters = array(
            'email'    => $user->email,
            'username' => $preference->username,
            'user_id'  => $preference->user_id,
        );

        $event = $subscribed ? UserHistory::INTERCOM_SUBSCRIBE : UserHistory::INTERCOM_UNSUBSCRIBE;
        $user->recordEvent($event, $parameters);

        Log::debug('Recorded user history', $parameters);
    }

    /**
     * Update the user's last seen time
     *
     * @param $user
     * @param $last_seen
     *
     * @return object
     */
    public function updateLastSeen(User $user, $last_seen)
    {

        return $response = $this->intercom->updateUser(
                                          $id = null,
                                          $email = $user->email,
                                          $name = null,
                                          $customData = array(),
                                          $createdAt = null,
                                          $lastSeenIp = null,
                                          $lastSeenUserAgent = null,
                                          $lastRequestAt = $last_seen,
                                          $unsubscribedFromEmails = null
        );

    }
}