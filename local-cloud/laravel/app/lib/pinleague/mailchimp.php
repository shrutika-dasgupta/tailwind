<?php

namespace Pinleague;

use Mailchimp,
    Mailchimp_Error,
    Config,
    User,
    UserEmail,
    UserEmailPreferences,
    UserEmailPreference,
    UserHistory,
    Log,
    Exception,
    UserSocialProperty;

/**
 * Mailchimp wrapper class.
 *
 * @author Janell
 */
class MailchimpWrapper
{
    /**
     * Mailchimp instance.
     *
     * @var Mailchimp
     */
    protected $mailchimp;

    /**
     * Initializes the class.
     *
     * @author Janell
     *
     * @return void
     */
    public function __construct()
    {
        try {
            $this->mailchimp = new Mailchimp(Config::get('mailchimp.API_KEY'));
        } catch (Mailchimp_Error $e) {
            Log::error($e);
        }
    }

    /**
     * Gets a new MailchimpWrapper instance.
     *
     * @author Janell
     *
     * @return MailchimpWrapper
     */
    public static function instance()
    {
        return new self();
    }

    /**
     * Checks the user's current subscription status to a list.
     *
     * @author Janell
     *
     * @param string $list_id
     * @param User   $user
     *
     * @return bool
     */
    public function isUserSubscribed($list_id, User $user)
    {
        $subscribed = false;

        try {
            $response = $this->mailchimp->lists->memberInfo($list_id, array(
                'emails' => array(
                    'email' => $user->email,
                ),
            ));

            Log::debug('Retrieved member info from MailChimp', array(
                'email'    => $user->email,
                'response' => $response,
            ));

            if (!empty($response['data'])) {
                $member = $response['data'][0];

                if ($member['status'] == 'subscribed') {
                    $subscribed = true;
                }
            }
        } catch (Mailchimp_Error $e) {
            Log::error($e);
        }

        // Save this preference for later.
        try {
            self::saveSubscriptionPreference($list_id, $user, $subscribed);
        } catch (Exception $e) {
            Log::error($e);
        }

        return $subscribed;
    }

    /**
     * Subscribes a user to a mailing list.
     *
     * @author Janell
     *
     * @param string $list_id
     * @param User   $user
     * @param array  $parameters
     *
     * @throws Exception
     */
    public function subscribeToList($list_id, User $user, $parameters = array())
    {
        try {
            $response = $this->mailchimp->lists->subscribe(
                $list_id,
                array(
                    'email' => $user->email,
                ),
                self::getMergeTagsForList($list_id, $user),
                'html',
                false
            );

            Log::debug('Subscribed user to MailChimp list', array(
                'email'    => $user->email,
                'list_id'  => $list_id,
                'response' => $response,
            ));
        } catch (\Mailchimp_List_AlreadySubscribed $e) {
            Log::debug('Email was already subscribed to mailing list', array(
                'email'   => $user->email,
                'list_id' => $list_id,
            ));
        } catch (Mailchimp_Error $e) {
            Log::error($e);

            throw $e;
        }

        self::saveSubscriptionPreference($list_id, $user, true, $parameters);
    }

    /**
     * Unsubscribes a user from a mailing list.
     *
     * @author Janell
     *
     * @param string $list_id
     * @param User   $user
     * @param array  $parameters
     *
     * @throws Exception
     */
    public function unsubscribeFromList($list_id, User $user, $parameters = array())
    {
        try {
            $response = $this->mailchimp->lists->unsubscribe($list_id, array(
                'email' => $user->email,
            ));

            Log::debug('Unsubscribed user from MailChimp list', array(
                'email'    => $user->email,
                'list_id'  => $list_id,
                'response' => $response,
            ));
        } catch (\Mailchimp_List_NotSubscribed $e) {
            Log::debug('Email was not subscribed to mailing list', array(
                'email'   => $user->email,
                'list_id' => $list_id,
            ));
        } catch (Mailchimp_Error $e) {
            Log::error($e);

            throw $e;
        }

        self::saveSubscriptionPreference($list_id, $user, false, $parameters);
    }

    /**
     * Unsubscribes a user from all mailing lists.
     *
     * @author Janell
     *
     * @param User $user
     *
     * @return bool
     */
    public function unsubscribeFromAll(User $user)
    {
        $success = true;

        try {
            $response = $lists = $this->mailchimp->helper->listsForEmail(array(
                'email' => $user->email,
            ));

            Log::debug('Retrieved mailing lists for email', array(
                'email'    => $user->email,
                'response' => $response,
            ));

            foreach ($lists as $list) {
                try {
                    $this->unsubscribeFromList($list['id'], $user);
                } catch (Exception $e) {
                    $success = false;
                }
            }
        } catch (\Mailchimp_List_NotSubscribed $e) {
            Log::debug('Email was not subscribed to any mailing lists', array(
                'email' => $user->email,
            ));
        } catch (Exception $e) {
            Log::error($e);

            $success = false;
        }

        return $success;
    }

    /**
     * Updates a user's member info for a list.
     *
     * @author Janell
     *
     * @param $list_id
     * @param User $user
     *
     * @throws \Mailchimp_Error
     */
    public function updateListMember($list_id, User $user)
    {
        try {
            $response = $this->mailchimp->lists->updateMember(
                $list_id,
                array(
                    'email' => $user->email,
                ),
                self::getMergeTagsForList($list_id, $user),
                'html',
                false
            );

            Log::debug('Updated MailChimp list member info', array(
                'email'    => $user->email,
                'list_id'  => $list_id,
                'response' => $response,
            ));
        } catch (\Mailchimp_List_NotSubscribed $e) {
            Log::debug($e);
        } catch (\Mailchimp_Email_NotExists $e) {
            Log::debug($e);
        } catch (Mailchimp_Error $e) {
            Log::error($e);

            throw $e;
        }
    }

    /**
     * Records a user's MailChimp subscription preference.
     *
     * @author Janell
     *
     * @param string $list_id
     * @param User   $user
     * @param bool   $subscribed
     * @param array  $parameters
     *
     * @throws Exception
     */
    public static function saveSubscriptionPreference($list_id, User $user, $subscribed, $parameters = array())
    {
        $account = $user->organization()->primaryAccount();
        if (empty($account)) {
            throw new Exception('Could not find active account for user');
        }

        $preference = new UserEmailPreference();
        $preference->name      = self::getPreferenceForList($list_id);
        $preference->cust_id   = $user->cust_id;
        $preference->username  = $account->username;
        $preference->user_id   = $account->user_id;
        $preference->frequency = $subscribed ? UserEmailPreference::ON : UserEmailPreference::OFF;

        $preferences = new UserEmailPreferences();
        $preferences->add($preference);
        $preferences->insertUpdateDB();

        if (empty($parameters)) {
            $parameters = array(
                'list_id'  => $list_id,
                'email'    => $user->email,
                'username' => $preference->username,
                'user_id'  => $preference->user_id,
            );
        }

        $event = $subscribed ? UserHistory::MAILCHIMP_SUBSCRIBE : UserHistory::MAILCHIMP_UNSUBSCRIBE;
        $user->recordEvent($event, $parameters);

        Log::debug('Recorded user history', $parameters);
    }

    /**
     * Returns the associated UserEmail preference name for a list.
     *
     * @author Janell
     *
     * @param string $list_id
     * 
     * @return null|string
     */
    public static function getPreferenceForList($list_id)
    {
        switch ($list_id) {
            case Config::get('mailchimp.BLOG_RSS_LIST_ID'):
                return UserEmail::MAILCHIMP_BLOG_RSS;
                break;
            default:
                return null;
                break;
        }
    }

    /**
     * Returns a list's merge tags filled with the supplied user's information.
     *
     * @author Janell
     *
     * @param string $list_id
     * @param User   $user
     *
     * @return array
     */
    public static function getMergeTagsForList($list_id, User $user)
    {
        switch ($list_id) {
            case Config::get('mailchimp.BLOG_RSS_LIST_ID'):
                return self::getBlogRssMergeTags($user);
                break;

            default:
                return array();
                break;
        }
    }

    /**
     * Returns an array of filled merge tags for the Blog RSS Newsletter list.
     *
     * @author Janell
     *
     * @param User $user
     *
     * @return array
     */
    public static function getBlogRssMergeTags(User $user)
    {
        $tags         = array();
        $organization = $user->organization();
        $account      = $user->organization()->primaryAccount();
        $subscription = null;

        if (!empty($organization) && !empty($organization->chargify_id)) {
            $subscription = $organization->subscription(true);
        }

        foreach (Config::get('mailchimp.BLOG_RSS_MERGE_TAGS') as $field_name => $tag_name) {
            switch ($field_name) {
                case 'first_name':
                    if (!empty($subscription)) {
                        $tags[$tag_name] = $subscription->customer->first_name;
                    } else {
                        $tags[$tag_name] = $user->first_name;
                    }
                    break;

                case 'last_name':
                    if (!empty($subscription)) {
                        $tags[$tag_name] = $subscription->customer->last_name;
                    } else {
                        $tags[$tag_name] = $user->last_name;
                    }
                    break;

                case 'pinterest_username':
                    if (!empty($account)) {
                        $tags[$tag_name] = $account->username;
                    }
                    break;

                case 'website':
                    if (!empty($account)) {
                        $tags[$tag_name] = $account->mainDomain()->domain;
                    }
                    break;

                case 'chargify_id':
                    if (!empty($organization)) {
                        $tags[$tag_name] = $organization->chargify_id;
                    }
                    break;

                case 'company':
                    if (!empty($subscription)) {
                        $tags[$tag_name] = $subscription->customer->organization;
                    } else if (!empty($organization)) {
                        $tags[$tag_name] = $organization->org_name;
                    }
                    break;

                case 'phone':
                    if (!empty($subscription)) {
                        $tags[$tag_name] = $subscription->customer->phone;
                    }
                    break;

                case 'acquisition_source':
                    $tags[$tag_name] = $user->source;
                    break;

                case 'state':
                    $tags[$tag_name] = $user->region;
                    break;

                case 'name':
                    if (!empty($subscription)) {
                        $tags[$tag_name] = $subscription->customer->getFullName();
                    } else {
                        $tags[$tag_name] = trim($user->first_name . ' ' . $user->last_name);
                    }
                    break;

                case 'full_address':
                    if (!empty($subscription)) {
                        if (!empty($subscription->customer->address)) {
                            $tags[$tag_name] = array(
                                'addr1'   => $subscription->customer->address,
                                'addr2'   => $subscription->customer->address_2,
                                'city'    => $subscription->customer->city,
                                'state'   => $subscription->customer->state,
                                'zip'     => $subscription->customer->zip,
                                'country' => $subscription->customer->country,
                            );
                        } else if (!empty($subscription->credit_card->billing_address)) {
                            $tags[$tag_name] = array(
                                'addr1'   => $subscription->credit_card->billing_address,
                                'addr2'   => $subscription->credit_card->billing_address_2,
                                'city'    => $subscription->credit_card->billing_city,
                                'state'   => $subscription->credit_card->billing_state,
                                'zip'     => $subscription->credit_card->billing_zip,
                                'country' => $subscription->credit_card->billing_country,
                            );
                        }
                    }
                    break;

                case 'zip':
                    if (!empty($subscription->customer->zip)) {
                        $tags[$tag_name] = $subscription->customer->zip;
                    } else if (!empty($subscription->credit_card->billing_zip)) {
                        $tags[$tag_name] = $subscription->credit_card->billing_zip;
                    }
                    break;

                case 'country':
                case 'country_2':
                    $tags[$tag_name] = $user->country;
                    break;

                case 'linkedin_link':
                    $linkedin_url = $user->getSocialProperty(
                        UserSocialProperty::LINKEDIN,
                        UserSocialProperty::URL
                    );
                    if (!empty($linkedin_url)) {
                        $tags[$tag_name] = $linkedin_url->value;
                    }
                    break;

                case 'twitter_profile':
                    $twitter_url = $user->getSocialProperty(
                        UserSocialProperty::TWITTER,
                        UserSocialProperty::URL
                    );
                    if (!empty($twitter_url)) {
                        $tags[$tag_name] = $twitter_url->value;
                    }
                    break;

                case 'signup_date':
                    $signup_date = !empty($organization) ? $organization->signupDate() : 0;

                    // Just in case, let's make sure MailChimp doesn't default to 1970.
                    if (empty($signup_date)) {
                        $signup_date = time();
                    }

                    $tags[$tag_name] = date('Y-m-d', $signup_date);
                    break;

                case 'plan_id':
                    $tags[$tag_name] = $user->plan()->plan_id;
                    break;

                case 'plan_name':
                    $tags[$tag_name] = $user->plan()->name;
                    break;

                default:
                    if (isset($user->$field_name)) {
                        $tags[$tag_name] = $user->$field_name;
                    }
                    break;
            }
        }

        return $tags;
    }

}