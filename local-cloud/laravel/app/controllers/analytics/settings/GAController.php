<?php namespace Analytics\Settings;

use
    DatabaseInstance,
    GoogleAnalytics,
    Redirect,
    Session,
    User,
    UserHistory,
    View;

/**
 * Class GAController
 *
 * @package Analytics\Settings
 */
class GAController extends BaseController
{

    /**
     * /settings/google-analytics/edit
     *
     * @author  Will
     */
    public function edit()
    {
        /*
       * This makes all the leagacy variables available in this scope
       * Its hacky, but it helps
       */
        extract($this->baseLegacyVariables());
        $conn = \DatabaseInstance::mysql_connect();

        $ga_profile = mysql_real_escape_string($_POST['ga_profile']);

        if (($cust_account_id) && ($ga_profile)) {

            saveAnalyticsProfile($cust_account_id, $ga_profile, $conn);

            return Redirect::back()
                   ->with('flash_message', 'Successfully added google analytics tracking');
        }

        return Redirect::to('/settings/google-analytics')
               ->with('flash_error', 'There was a problem with Google. Womp.');
    }

    /**
     * /settings/google-analytics/resync
     *
     * @author  Will
     * @author  Alex
     */
    public function resync()
    {

        extract($this->baseLegacyVariables());

        $sql  = "update user_analytics set track_type = 'orphan' where account_id = '$cust_account_id'";
        $resu = mysql_query($sql, $conn);

        $sql  = "delete from status_traffic where account_id = '$cust_account_id'";
        $resu = mysql_query($sql, $conn);

        return Redirect::to('/settings/google-analytics/?success=3');

    }

    /**
     * /settings/google-analytics
     *
     * @author  Will
     * @author  Alex
     */
    public function show()
    {
        /*
         * Extracting these varibles bring them into scope.
         * This is hacky . We know.
         */
        $legacy = $this->baseLegacyVariables();
        extract($legacy);

        /*
         * Refresh whether this use has google analytics synced.
         */
        $has_ga = hasAnalytics($cust_account_id, $conn);
        if ($has_ga) {
            Session::put('has_analytics', 1);
            if (!analyticsReady($cust_account_id, $conn)) {
                Session::put('has_analytics', 2);
            }
        } else {
            Session::put('has_analytics', 0);
        }

        /*
         * Redirect to profile settings if user does not have permissions to see this page.
         */
        if ($customer->is_admin == User::PERMISSIONS_VIEWER) {
            return Redirect::to('/settings/profile');
        }

        /*
         * Copying in legacy code...
         */

        $return_url = url('/settings/google-analytics/sync');
        $vars       = array(
            'analytics_navigation' => $this->buildSettingsNavigation('analytics'),
            'return_url'           => $return_url,
            'success'              => 3,
            'comp_name'            => '',
            'comp_domain'          => '',
            'comp_username'        => '',
            'alert' => $this->generateAlertBox()
        );

        $complete_vars = array_merge($vars, $legacy);

        $this->layout->main_content = View::make('analytics.pages.settings.google_analytics', $complete_vars);


    }

    /**
     * /settings/google-analytics/sync
     *
     * @author  Will
     */
    public function sync()
    {

        $legacy = $this->baseLegacyVariables();
        extract($legacy);

        $track_type = $customer->getActiveUserAccount()->track_type;
        if ($_GET['token']) {
            $analytics = new GoogleAnalytics($_GET['token']);
            $o         = $analytics->call("https://www.google.com/accounts/AuthSubSessionToken");
            if ($analytics->isValidToken($o)) {
                $token = $analytics->getToken($o);

                /*
                 * Insert into the user_analytics table
                 */
                $timestamp = time();
                $sql       = "INSERT INTO user_analytics (user_id, org_id, account_id, profile, token, track_type, added_at, timestamp) VALUES ('$cust_id', '$cust_org_id', '$cust_account_id', '', '$token', '$track_type', '$timestamp', '$timestamp')
                              ON DUPLICATE KEY UPDATE profile = VALUES(profile), token = VALUES(token), track_type = VALUES(track_type), timestamp = VALUES(timestamp)";
                $resu      = mysql_query($sql, $conn);

                /*
                 * Check to see whether GA record has been added
                 * to the status_traffic table for this account
                 */
                $acc = "select * from user_analytics where user_id='$cust_id' and org_id='$cust_org_id' and account_id='$cust_account_id'";
                $acc_res = mysql_query($acc,$conn) or die(mysql_error().__LINE__);
                while ($a = mysql_fetch_array($acc_res)) {
                    $user_id = $a['user_id'];
                    $org_id = $a['org_id'];
                    $account_id = $a['account_id'];
                    $profile = $a['profile'];
                    $token = $a['token'];

                    $found = false;
                    $acc2 = "select * from status_traffic where user_id = '$user_id' and org_id = '$org_id' and account_id = '$account_id'";
                    $acc2_res = mysql_query($acc2,$conn) or die(mysql_error().__LINE__);
                    while ($b = mysql_fetch_array($acc2_res)) {
                        $found = true;
                    }


                    /*
                     * If a GA record does not exist for this account in status_traffic table
                     * add it now so that the next view can load correctly
                     */
                    if (!$found) {
                        $time = time();
                        $acc2 = "INSERT IGNORE into status_traffic (user_id, org_id, account_id, profile, token, last_pulled, last_calced, added_at, timestamp) VALUES ('$user_id', '$org_id', '$account_id', '$profile', '$token', '0', '0', '$time', '$time')";
                        $acc2_res = mysql_query($acc2,$conn) or die(mysql_error().__LINE__);
                    }
                }

                /**
                 * @var $customer \User
                 */
                $customer->recordEvent(
                    UserHistory::SYNC_GOOGLE_ANALYTICS,
                        array(),
                    'Synced Google Analyics'
                );

                return Redirect::to('/settings/google-analytics')
                       ->with('flash_message', 'Successfully added google analytics tracking');

            } else {
                return Redirect::to('/settings/google-analytics')
                       ->with('flash_error', 'There was a problem with Google. Womp.');
            }
        }

        return Redirect::to('/settings/google-analytics')
               ->with('flash_error', 'There was a problem with Google. Womp.');

    }

    /**
     * /settings/google-analytics/select-profile
     * @author  Will
     *          Alex
     */
    public function selectProfile() {
        $legacy = $this->baseLegacyVariables();
        extract($legacy);

        $ga_profile = mysql_real_escape_string($_POST['ga_profile']);

        if (($cust_account_id) && ($ga_profile)) {

            /**
             * Save the profile to the user_analytics table
             */
            saveAnalyticsProfile($cust_account_id, $ga_profile, $conn);

            /**
             * Now we will pull all of the profile's metadata and save it to the user_analytics
             * table as well
             */
            $DBH = DatabaseInstance::DBO();

            $acc2 = "SELECT * from user_analytics
                    WHERE account_id = $cust_account_id
                    AND profile = '$ga_profile'
                    LIMIT 1";
            $acc2_res = mysql_query($acc2,$conn) or die(mysql_error().__LINE__);
            while ($b = mysql_fetch_array($acc2_res)) {
                $token = $b['token'];
                $track_type = $b['track_type'];
            }

            $analytics = new GoogleAnalytics($token);
            $profile_num = str_replace("ga:","",$ga_profile);

            $account_id = $cust_account_id;
            $token = $token;
            $track_type = $track_type;

            $json = $analytics->call("https://www.googleapis.com/analytics/v3/management/accounts/~all/webproperties/~all/profiles/");

            $data = json_decode($json);

            if(count($data->items) != 0){

                foreach($data->items as $api_profile) {
                    if ($api_profile->id == $profile_num){

                        $name = $api_profile->name;
                        $timezone = $api_profile->timezone;
                        $currency = $api_profile->currency;
                        $accountId = $api_profile->accountId;
                        $webPropertyId = $api_profile->webPropertyId;
                        $eCommerceTracking = $api_profile->eCommerceTracking;
                        $websiteUrl = $api_profile->websiteUrl;

                        $STH = $DBH->prepare(
                                   "UPDATE user_analytics
                                    SET
                                       name = :name,
                                       timezone = :timezone,
                                       currency = :currency,
                                       accountId = :accountId,
                                       webPropertyId = :webPropertyId,
                                       eCommerceTracking = :eCommerceTracking,
                                       websiteUrl = :websiteUrl,
                                       timestamp = :timestamp
                                    WHERE
                                       account_id = :account_id
                                       AND profile = :profile
                                       AND token = :token"
                        );

                        $STH->execute(
                            array(
                                 ':name'     => $name,
                                 ':timezone' => $timezone,
                                 ':currency' => $currency,
                                 ':accountId' => $accountId,
                                 ':webPropertyId' => $webPropertyId,
                                 ':eCommerceTracking' => $eCommerceTracking,
                                 ':websiteUrl' => $websiteUrl,
                                 ':timestamp' => time(),
                                 ':account_id' => $account_id,
                                 ':profile' => $ga_profile,
                                 ':token' => $token
                            )
                        );
                    }
                }
            }

            return Redirect::to("/settings/google-analytics/?success=5");
        }

        return Redirect::to('/settings/google-analytics')
                       ->with('flash_error', 'There was a problem retrieving your Google Analytics profile.');
    }

}