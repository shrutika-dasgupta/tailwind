<?php namespace Analytics\Settings;

use Config,
    Exception,
    Redirect,
    Pinleague\MailchimpWrapper,
    Pinleague\IntercomWrapper,
    User,
    UserHistory,
    UserEmailPreference,
    UserEmailPreferences,
    UserEmailAttachmentPreference,
    UserEmailAttachmentPreferences,
    UserEmail,
    View;

/**
 * Class NotificationsController
 *
 * @package Analytics\Settings
 */
class NotificationsController extends BaseController
{

    protected $layout = 'layouts.analytics';

    /**
     * POST /settings/notifications/update
     *
     * @author  Will
     */
    public function editNotifications()
    {
        extract($this->baseLegacyVariables());
        /** @var  $customer User */
        /** @var  $user User */
        $user = $customer;

        try {

            $preferences            = new UserEmailPreferences();
            $attachment_preferences = new UserEmailAttachmentPreferences();
            $main_account = null;

            /*
             * We want to look for the inputs from the active accounts that should
             * have been on the notifications page
             *
             * the inputs we expect are
             * {username}-time
             * {username}-daily_stats_report
             * {username}-weekly_stats_report
             * etc etc
             *
             * there is an argument to made that this should use the username.. I agree
             * and I'm sorry :(
             *
             * if its not there, we'll use the default
             */
            foreach ($user->organization()->connectedUserAccounts('active') as $account) {

                if (empty($main_account)) {
                    $main_account = $account;
                }

                if (isset($_POST[$account->username . '-time'])) {
                    $time =
                        filter_var($_POST[$account->username . '-time'], FILTER_SANITIZE_STRING);
                } else {
                    $time = UserEmailPreference::DEFAULT_SEND_TIME;
                }

                /*
                 * Daily Report
                 */
                $stats_preference               = new UserEmailPreference();
                $stats_preference->name         = UserEmail::DAILY_SUMMARY;
                $stats_preference->user_id      = $account->user_id;
                $stats_preference->username     = $account->username;
                $stats_preference->hour_to_send = $time;


                if (isset($_POST[$account->username . '-daily_stats_report'])) {
                    $stats_preference->frequency = UserEmailPreference::DAILY;

                    $daily_summary = true;

                } else {
                    $stats_preference->frequency = UserEmailPreference::OFF;
                    $daily_summary = false;


                }

                $preferences->add($stats_preference);

                /*
                 * Weekly Report
                 */
                $weekly_preference               = new UserEmailPreference();
                $weekly_preference->name         = UserEmail::WEEKLY_SUMMARY;
                $weekly_preference->username     = $account->username;
                $weekly_preference->user_id      = $account->user_id;
                $weekly_preference->hour_to_send = $time;

                if (isset($_POST[$account->username . '-weekly_stats_report'])) {
                    $weekly_preference->frequency = UserEmailPreference::WEEKLY;

                    $weekly_summary = true;

                } else {
                    $weekly_preference->frequency = UserEmailPreference::OFF;

                    $weekly_summary = false;

                }

                $preferences->add($weekly_preference);

                /*
                 * Monthly Report
                 */
                $monthly_preference               = new UserEmailPreference();
                $monthly_preference->name         = UserEmail::MONTHLY_SUMMARY;
                $monthly_preference->username     = $account->username;
                $monthly_preference->user_id      = $account->user_id;
                $monthly_preference->hour_to_send = $time;

                if (isset($_POST[$account->username . '-monthly_stats_report'])) {
                    $monthly_preference->frequency = UserEmailPreference::MONTHLY;

                    $monthly_summary = true;

                } else {
                    $monthly_preference->frequency = UserEmailPreference::OFF;

                    $monthly_summary = false;
                }

                $preferences->add($monthly_preference);

                /*
                 * Monthly statement
                 */
                $statement_preference               = new UserEmailPreference();
                $statement_preference->name         = UserEmail::MONTHLY_STATEMENT;
                $statement_preference->username     = $account->username;
                $statement_preference->user_id      = $account->user_id;
                $statement_preference->hour_to_send = $time;

                if (isset($_POST[$account->username . '-monthly_statement'])) {
                    $statement_preference->frequency = UserEmailPreference::MONTHLY;

                    $monthly_statement = true;

                } else {
                    $statement_preference->frequency = UserEmailPreference::OFF;

                    $monthly_statement = false;
                }

                $preferences->add($statement_preference);

                /*
                 * Profile Alerts
                 */
                $alerts_preference           = new UserEmailPreference();
                $alerts_preference->name     = UserEmail::ALERTS;
                $alerts_preference->username = $account->username;
                $alerts_preference->user_id  = $account->user_id;

                if (isset($_POST[$account->username . '-alerts'])) {
                    $alerts_preference->frequency = UserEmailPreference::ON;
                    $profile_alerts = true;

                } else {
                    $alerts_preference->frequency = UserEmailPreference::OFF;
                    $profile_alerts = false;
                }

                $preferences->add($alerts_preference);

                /*
                 * Domain Alerts
                 */
                $domain_alerts_preference           = new UserEmailPreference();
                $domain_alerts_preference->name     = UserEmail::DOMAIN_ALERTS;
                $domain_alerts_preference->username = $account->username;
                $domain_alerts_preference->user_id  = $account->user_id;

                if (isset($_POST[$account->username . '-domain_alerts'])) {
                    $domain_alerts_preference->frequency = UserEmailPreference::ON;

                    $domain_alerts = true;

                } else {
                    $domain_alerts_preference->frequency = UserEmailPreference::OFF;

                    $domain_alerts = false;
                }

                $preferences->add($domain_alerts_preference);

                /*
                 * Attachment settings
                 */

                foreach (UserEmailAttachmentPreference::$defaults as $name => $preference) {

                    $include_csv = false;
                    $include_pdf = false;

                    /*
                     * Check if the checkbox was sent for both csv and pdf
                     * if it was, that means it was checked
                     */
                    if (isset($_POST[$account->username . '-' . $name . '-csv'])) {
                        $include_csv = true;
                    }

                    if (isset($_POST[$account->username . '-' . $name . '-pdf'])) {
                        $include_pdf = true;
                    }

                    $attachment_preference           = new UserEmailAttachmentPreference();
                    $attachment_preference->cust_id  = $user->cust_id;
                    $attachment_preference->username = $account->username;
                    $attachment_preference->user_id  = $account->user_id;
                    $attachment_preference->name     = $name;

                    /*
                     * Set the type
                     */
                    if ($include_csv AND $include_pdf) {

                        $attachment_preference->type = UserEmailAttachmentPreference::BOTH;

                    } elseif ($include_csv) {

                        $attachment_preference->type = UserEmailAttachmentPreference::CSV;

                    } elseif ($include_pdf) {

                        $attachment_preference->type = UserEmailAttachmentPreference::PDF;

                    } else {

                        $attachment_preference->type = UserEmailAttachmentPreference::NONE;
                    }

                    $attachment_preferences->add($attachment_preference);
                }
            }

            // MailChimp settings
            $mailchimp_blog_rss = isset($_POST[$main_account->username . '-blog_rss']);

            $blog_rss_preference = $user->getEmailPreference(
                UserEmail::MAILCHIMP_BLOG_RSS,
                $main_account->user_id
            );

            $blog_rss_enabled = ($blog_rss_preference->frequency == UserEmailPreference::ON) ? true : false;

            if ($mailchimp_blog_rss != $blog_rss_enabled) {
                $list_id = Config::get('mailchimp.BLOG_RSS_LIST_ID');

                try {
                    if ($mailchimp_blog_rss) {
                        MailchimpWrapper::instance()->subscribeToList($list_id, $user);
                    } else {
                        MailchimpWrapper::instance()->unsubscribeFromList($list_id, $user);
                    }
                } catch (Exception $e) {
                    // Attempt to capture the user's current subscription status.
                    $mailchimp_blog_rss = MailchimpWrapper::instance()->isUserSubscribed($list_id, $user);
                }
            }

            // Intercom settings
            $intercom_email = isset($_POST[$main_account->username . '-intercom']);

            $intercom_preference = $customer->getEmailPreference(
                UserEmail::INTERCOM_EMAIL,
                $main_account->user_id
            );

            $intercom_enabled = ($intercom_preference->frequency == UserEmailPreference::ON) ? true : false;

            if ($intercom_email != $intercom_enabled) {
                try {
                    if ($intercom_email) {
                        IntercomWrapper::instance()->subscribe($user);
                    } else {
                        IntercomWrapper::instance()->unsubscribe($user);
                    }
                } catch (Exception $e) {
                    // Attempt to capture the user's current subscription status.
                    $intercom_email = IntercomWrapper::instance()->isUserSubscribed($user);
                }
            }

            $preferences->setPropertyOfAllModels('cust_id', $user->cust_id);
            $preferences->insertUpdateDB();
            $attachment_preferences->insertUpdateDB();


            $user->removeQueuedAutomatedEmails()
                 ->seedAutomatedEmails();

            $user->recordEvent(
                 UserHistory::UPDATE_NOTIFICATIONS_SETTINGS,
                 $parameters = array(
                     'daily_summary_enabled'      => $daily_summary,
                     'weekly_summary_enabled'     => $weekly_summary,
                     'monthly_summary_enabled'    => $monthly_summary,
                     'monthly_statement_enabled'  => $monthly_statement,
                     'profile_alerts_enabled'     => $profile_alerts,
                     'domain_alerts_enabled'      => $domain_alerts,
                     'mailchimp_blog_rss_enabled' => $mailchimp_blog_rss,
                     'intercom_email_enabled'     => $intercom_email,
                 )
            );

            return Redirect::back()
                           ->with('flash_message', 'Your notification settings have been updated!');

        }
        catch (Exception $e) {
            return Redirect::back()
                           ->with('flash_error', $e->getMessage());
        }

    }

    /**
     * GET /settings/notifications
     *
     * @author  Will
     */
    public function showNotifications()
    {
        extract($this->baseLegacyVariables());

        /**
         * @var string of HTML of each accounts for summary emails and alert emails
         */

        $accounts = array();
        $main_account = null;

        /** @var $customer User */
        foreach ($customer->organization()->connectedUserAccounts('active') as $account) {

            if (empty($main_account)) {
                $main_account = $account;
            }

            $html['name']     = $account->account_name;
            $html['username'] = $account->username;

            /*
             * Build the summary email settings
             */
            $daily_preference   =
                $customer->getEmailPreference(UserEmail::DAILY_SUMMARY, $account->user_id);
            $weekly_preference  =
                $customer->getEmailPreference(UserEmail::WEEKLY_SUMMARY, $account->user_id);
            $monthly_preference =
                $customer->getEmailPreference(UserEmail::MONTHLY_SUMMARY, $account->user_id);

            $vars = array(
                'daily_report_checked'   => $daily_preference->checked(),
                'weekly_report_checked'  => $weekly_preference->checked(),
                'monthly_report_checked' => $monthly_preference->checked(),
                'time_value'             => $weekly_preference->hour_to_send,
                'time'                   => date('g:ia', strtotime($weekly_preference->hour_to_send)),
                'attachments'            => $customer->attachmentPreferences($account->username),
                'username'               => $account->username
            );


            $html['summary email settings'] = View::make('analytics.pages.settings.summary_email_settings', $vars);


            /*
             * Build the profile alerts
             */
            $alerts_preference =
                $customer->getEmailPreference(UserEmail::ALERTS, $account->user_id);

            $domain_alerts_preference =
                $customer->getEmailPreference(UserEmail::DOMAIN_ALERTS, $account->user_id);

            $vars = array(
                'alerts_report_checked'        => $alerts_preference->checked(),
                'domain_alerts_report_checked' => $domain_alerts_preference->checked(),
                'username'                     => $account->username
            );

            $html['profile alert settings'] = View::make('analytics.pages.settings.alert_email_settings', $vars);


            /*
             * Monthly Statement
             */
            $monthly_statement_preference =
                $customer->getEmailPreference(UserEmail::MONTHLY_STATEMENT, $account->user_id);

            if(!$customer->hasCreditCardOnFile()) {
                $disabled = 'disabled = "disabled"';
            } else {
                $disabled = '';
            }

            $vars = array(
                'monthly_statement_disabled' => $disabled,
                'monthly_statement_checked' => $monthly_statement_preference->checked(),
                'username'                  => $account->username
            );

            $html['statement settings'] = View::make('analytics.pages.settings.statements', $vars);

            $accounts[] = $html;

        }

        // Email subscriptions
        $blog_rss_preference = $customer->getEmailPreference(
            UserEmail::MAILCHIMP_BLOG_RSS,
            $main_account->user_id
        );

        $intercom_preference = $customer->getEmailPreference(
            UserEmail::INTERCOM_EMAIL,
            $main_account->user_id
        );


        $subscription_settings = View::make('analytics.pages.settings.email_subscription_settings', array(
            'username'          => $main_account->username,
            'blog_rss_checked'  => $blog_rss_preference->checked(),
            'intercom_checked'  => $intercom_preference->checked(),
        ));

        $this->layout->main_content = View::make('analytics.pages.settings.notifications_settings', array(
            'navigation'            => $this->buildSettingsNavigation('notifications'),
            'accounts'              => $accounts,
            'email'                 => $customer->email,
            'customer_name'         => $this->logged_in_customer->getName(),
            'subscription_settings' => $subscription_settings,
        ));

    }

}