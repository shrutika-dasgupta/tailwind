<?php namespace Composers\Analytics\Components;

use
    Config,
    Request,
    UserEmail,
    UserProperty,
    View;

/**
 * Class AnalyticsComposer
 *
 * @package Layouts
 */
class SegmentIOComposer
{

    /**
     * When layout is created, this method is run. Useful for setting defaults,
     * creating assets etc
     *
     * @param $view
     */
    public function create(View $view)
    {
        $view->segmentio_write_key = Config::get('segmentio.WRITE_KEY');

        /** @var \User $user */
        $user = $view->user;
        /** @var \UserAccount $active_user_account */
        $active_user_account = $view->active_user_account;

        /**
         * Some of the traits you can record have semantic meaning, and we
         * handle them in special ways. For example, we always expect email to
         * be a string of the user’s email address. We’ll send this on to
         * integrations like Mailchimp that require an email address for their
         * tracking.
         *
         * For that reason, you should only use special traits for their
         * intended meaning.
         *
         * address
         * The street address of a user or group. This should be a dictionary
         * containing optional city, country, postalCode, state or street.
         *
         * age
         * The age of a user.
         *
         * avatar
         * A URL to an avatar image for the user or group.
         *
         * birthday
         * A user’s birthday.
         *
         * createdAt
         * The date the user’s or group’s account was first created. We accept
         * date objects and a wide range of date formats, including ISO strings
         * and Unix timestamps. Feel free to use whatever format is easiest
         * for you.
         *
         * description
         * A description of the user or group, like their personal bio.
         *
         * email
         * The email address of a user or group.
         *
         * employees
         * The number of employees of a group, typically used for companies.
         *
         * firstName
         * The first name of a user.
         *
         * gender
         * The gender of a user.
         *
         * id
         * The unique ID in your database for a user or group.
         *
         * industry
         * The industry a user works in, or a group is part of.
         *
         * lastName
         * The last name of a user.
         *
         * name
         * The full name of a user or group. For groups this might be their
         * company name, or similar. For users, if you only pass a first and
         * last name we’ll automatically fill in the full name for you, in case
         * that’s easier for you.
         *
         * phone
         * The phone number of a user or group.
         *
         * title
         * The title of a user, usually related to their position at a specific
         * company, for example “VP of Engineering”.
         *
         * username
         * A user’s username. This should be unique to each user, like the
         * usernames of Twitter or GitHub.
         *
         * website
         * The website of a user or group.
         */
        $view->cust_id = $user->cust_id;

        if (empty($active_user_account) || empty($user) || $view->is_demo || $view->is_admin) {

            $view->identity_variables = json_encode([]);
            $view->app_data           = json_encode([]);
            $view->org_id             = json_encode([]);
            $view->group_data         = json_encode([]);

            if ($view->is_admin) {
                $view->cust_id = 474;
            } else {
                $view->cust_id = 0;
            }
            
        } else {


        $view->identity_variables = json_encode(
            array_merge($user->getAllProperties()->toKeyValues(),array(
                 'name'                                   => $user->getName(),
                 'email'                                  => $user->email,
                 'account_id'                             => $active_user_account->account_id,
                 'username'                               => $active_user_account->username,
                 'pin_count'                              => $active_user_account->profile()->pin_count,
                 'repin_count'                            => $active_user_account->profile()->getRepinCount(),
                 'following_count'                        => $active_user_account->profile()->following_count,
                 'follower_count'                         => $active_user_account->profile()->follower_count,
                 'pinterest_account_age'                  => $active_user_account->profile()->daysSinceCreated(),
                 'tailwind_account_age'                   => $user->daysSinceCreated(),
                 'domain_verified'                        => $active_user_account->profile()->domain_verified,
                 'competitor_pins'                        => $user->organization()->competitorAccounts()->profiles()->sum('pin_count'),
                 'total_pins'                             => $user->organization()->activeUserAccounts()->profiles()->sum('pin_count'),
                 'website'                                => $active_user_account->mainDomain()->domain,
                 'plan'                                   => $user->organization()->plan()->plan_id,
                 'plan_name'                              => $user->organization()->plan()->getName(),
                 'industry'                               => $active_user_account->industry()->industry,
                 'analytics'                              => $user->organization()->hasGoogleAnalytics(),
                 'source'                                 => $user->source,
                 'subscribed_to_daily_summary'            => $user->subscribedToAutomatedEmail(UserEmail::DAILY_SUMMARY),
                 'subscribed_to_weekly_summary'           => $user->subscribedToAutomatedEmail(UserEmail::WEEKLY_SUMMARY),
                 'subscribed_to_monthly_summary'          => $user->subscribedToAutomatedEmail(UserEmail::MONTHLY_SUMMARY),
                 'subscribed_to_monthly_statement'        => $user->subscribedToAutomatedEmail(UserEmail::MONTHLY_STATEMENT),
                 'last_seen_environment'                  => parse_url(Request::url(), PHP_URL_HOST),
                 'is_legacy'                              => (bool) $user->organization()->is_legacy,
                 'chargify_id'                            => $user->organization()->chargify_id,
                 'chargify_id_at'                         => $user->organization()->chargify_id_alt,
                 'coupon_code'                            => $user->organization()->coupon_code,
                 'subscription_state'                     => $user->organization()->subscription_state,
                 'billing_event_count'                    => $user->organization()->billing_event_count,
                 'first_billing_event_at'                 => $user->organization()->first_billing_event_at,
                 'total_amount_billed'                    => $user->organization()->total_amount_billed,
                 'last_billing_amount'                    => $user->organization()->last_billing_amount,
                 'last_billing_event_at'                  => $user->organization()->last_billing_event_at,
                 'average_bill_amount'                    => $user->organization()->averageBillAmount(),
                 'has_google_analytics'                   => $user->organization()->hasGoogleAnalytics(),
                 'active_accounts_count'                  => $user->organization()->activeUserAccounts()->count(),
                 'active_accounts_total_pins_count'       => $user->organization()->activeUserAccounts()->profiles()->sum('pin_count'),
                 'active_accounts_average_pin_count'      => $user->organization()->activeUserAccounts()->profiles()->average('pin_count'),
                 'active_accounts_total_follower_count'   => $user->organization()->activeUserAccounts()->profiles()->sum('follower_count'),
                 'active_accounts_average_follower_count' => $user->organization()->activeUserAccounts()->profiles()->average('follower_count'),
                 'has_domain'                             => $user->organization()->activeUserAccounts()->hasDomain(),
                 'competitors'                            => $user->organization()->competitorAccounts()->count(),
                 'competitors_total_pins_count'           => $user->organization()->competitorAccounts()->profiles()->sum('pin_count'),
                 'competitors_average_pin_count'          => $user->organization()->competitorAccounts()->profiles()->average('pin_count'),
                 'competitors_total_follower_count'       => $user->organization()->competitorAccounts()->profiles()->sum('follower_count'),
                 'competitors_average_follower_count'     => $user->organization()->competitorAccounts()->profiles()->average('follower_count'),
                 'content_enabled'                        => $user->hasFeature('content_enabled'),
                 'pin_scheduling_enabled'                 => $user->hasFeature('pin_scheduling_enabled'),
                 'listening_enabled'                      => $user->hasFeature('listening_enabled'),
                 'is_collaborator'                        => $user->is_collaborator(),
                 'keywords_added'                         => $user->organization()->totalKeywordsAdded(),
                 'domains_added'                          => $user->organization()->totalDomainsAdded(),
                 'company'                                => array(
                     'id'                                     => $user->organization()->org_id,
                     'name'                                   => $user->organization()->org_name,
                     'created_at'                             => $user->organization()->created_at,
                     'plan'                                   => $user->organization()->plan()->plan_id,
                     'type'                                   => $user->organization()->org_type,
                     'is_legacy'                              => (bool) $user->organization()->is_legacy,
                     'chargify_id'                            => $user->organization()->chargify_id,
                     'chargify_id_at'                         => $user->organization()->chargify_id_alt,
                     'coupon_code'                            => $user->organization()->coupon_code,
                     'subscription_state'                     => $user->organization()->subscription_state,
                     'billing_event_count'                    => $user->organization()->billing_event_count,
                     'first_billing_event_at'                 => $user->organization()->first_billing_event_at,
                     'total_amount_billed'                    => $user->organization()->total_amount_billed,
                     'last_billing_amount'                    => $user->organization()->last_billing_amount,
                     'last_billing_event_at'                  => $user->organization()->last_billing_event_at,
                     'average_bill_amount'                    => $user->organization()->averageBillAmount(),
                     'has_google_analytics'                   => $user->organization()->hasGoogleAnalytics(),
                     'active_accounts_count'                  => $user->organization()->activeUserAccounts()->count(),
                     'active_accounts_total_pins_count'       => $user->organization()->activeUserAccounts()->profiles()->sum('pin_count'),
                     'active_accounts_average_pin_count'      => $user->organization()->activeUserAccounts()->profiles()->average('pin_count'),
                     'active_accounts_total_follower_count'   => $user->organization()->activeUserAccounts()->profiles()->sum('follower_count'),
                     'active_accounts_average_follower_count' => $user->organization()->activeUserAccounts()->profiles()->average('follower_count'),
                     'competitors_count'                      => $user->organization()->competitorAccounts()->count(),
                     'competitors_total_pins_count'           => $user->organization()->competitorAccounts()->profiles()->sum('pin_count'),
                     'competitors_average_pin_count'          => $user->organization()->competitorAccounts()->profiles()->average('pin_count'),
                     'competitors_total_follower_count'       => $user->organization()->competitorAccounts()->profiles()->sum('follower_count'),
                     'competitors_average_follower_count'     => $user->organization()->competitorAccounts()->profiles()->average('follower_count'),
                     'is_collaborator'                        => $user->is_collaborator(),
                     'keywords_added'                         => $user->organization()->totalKeywordsAdded(),
                     'domains_added'                          => $user->organization()->totalDomainsAdded(),
                 )
            )
            ));


        $view->app_data = json_encode(
            array(
                 'Intercom' => [
                     'user_hash' => hash_hmac('sha256', $user->cust_id, Config::get('intercom.APP_SECRET')),
                 ]
            )
        );

        $view->org_id = $user->org_id;

        $view->group_data = json_encode(
            array(
                 'id'                                     => $user->organization()->org_id,
                 'name'                                   => $user->organization()->org_name,
                 'created_at'                             => $user->organization()->created_at,
                 'plan'                                   => $user->organization()->plan()->plan_id,
                 'type'                                   => $user->organization()->org_type,
                 'is_legacy'                              => $user->organization()->is_legacy,
                 'chargify_id'                            => $user->organization()->chargify_id,
                 'chargify_id_at'                         => $user->organization()->chargify_id_alt,
                 'coupon_code'                            => $user->organization()->coupon_code,
                 'subscription_state'                     => $user->organization()->subscription_state,
                 'billing_event_count'                    => $user->organization()->billing_event_count,
                 'first_billing_event_at'                 => $user->organization()->first_billing_event_at,
                 'total_amount_billed'                    => $user->organization()->total_amount_billed,
                 'last_billing_amount'                    => $user->organization()->last_billing_amount,
                 'last_billing_event_at'                  => $user->organization()->last_billing_event_at,
                 'has_google_analytics'                   => $user->organization()->hasGoogleAnalytics(),
                 'active_accounts_count'                  => $user->organization()->activeUserAccounts()->count(),
                 'active_accounts_total_pins_count'       => $user->organization()->activeUserAccounts()->profiles()->sum('pin_count'),
                 'active_accounts_average_pin_count'      => $user->organization()->activeUserAccounts()->profiles()->average('pin_count'),
                 'active_accounts_total_follower_count'   => $user->organization()->activeUserAccounts()->profiles()->sum('follower_count'),
                 'active_accounts_average_follower_count' => $user->organization()->activeUserAccounts()->profiles()->average('follower_count'),
                 'competitors_count'                      => $user->organization()->competitorAccounts()->count(),
                 'competitors_total_pins_count'           => $user->organization()->competitorAccounts()->profiles()->sum('pin_count'),
                 'competitors_average_pin_count'          => $user->organization()->competitorAccounts()->profiles()->average('pin_count'),
                 'competitors_total_follower_count'       => $user->organization()->competitorAccounts()->profiles()->sum('follower_count'),
                 'competitors_average_follower_count'     => $user->organization()->competitorAccounts()->profiles()->average('follower_count'),
            )
        );
        }
    }

    /**
     * This fires when the view is rendered
     *
     * @param View $view
     *
     * @author  Will
     */
    public function compose(View $view)
    {
        //do nothing
    }
}