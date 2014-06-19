<?php namespace Analytics\Settings;

use
    GoogleAnalytics,
    Pinleague\Pinterest,
    Redirect,
    Session,
    User,
    UserHistory,
    View;

/**
 * Class CompetitorsController
 *
 * @package Analytics\Settings
 */
class CompetitorsController extends BaseController
{

    /**
     * /settings/competitor/add
     *
     * @author  Will
     */
    public function add()
    {
        extract($this->baseLegacyVariables());

        $pinterest = new Pinterest();
        $conn      = \DatabaseInstance::mysql_connect();


        $account_name = formatTextUserInput($_POST['account_name']);
        $domain       = strtolower($_POST['domain']);
        $domain       = str_replace("http://", "", $domain);
        $domain       = str_replace("https://", "", $domain);
        $domain       = str_replace("www.", "", $domain);
        $domain       = formatTextUserInput($domain);

        $username = strtolower($_POST['username']);
        $username = formatTextUserInput($username);

        $user_id = $pinterest->getUserIDFromUsername($username);

        $no_user_id = false;
        if ($user_id == 0) {
            $no_user_id = true;

            return Redirect::back()
                   ->with('flash_error_code', 1)
                   ->with('account_name', $account_name)
                   ->with('domain', $domain)
                   ->with('username', $username);

        } else {

            if (($username) && ($domain)) {
                $already_has = false;
                $acc         = "select username from user_accounts where (account_id = '$cust_account_id' OR competitor_of = '$cust_account_id') and track_type!='orphan'";
                $acc_res = mysql_query($acc, $conn) or die(mysql_error());
                while ($a = mysql_fetch_array($acc_res)) {
                    $check_username = $a['username'];
                    if ($check_username == $username) {
                        $already_has = true;
                    }
                }

                //insert new account info
                $created_at = time();
                if (!$already_has) {
                    $sql  = "insert into user_accounts (account_name, org_id, username, user_id, account_type, track_type, competitor_of, created_at) values ('$account_name', '$cust_org_id', '$username', \"$user_id\", \"$cust_org_type\", \"competitor\", \"$cust_account_id\",\"$created_at\")";
                    $resu = mysql_query($sql, $conn);
                } else {
                    return Redirect::back()
                           ->with('flash_error_code', 2)
                           ->with('account_name', $account_name)
                           ->with('domain', $domain)
                           ->with('username', $username);
                }

                //get new competitors account_id
                $acc = "select account_id from user_accounts where user_id='$user_id'";
                $acc_res = mysql_query($acc, $conn) or die(mysql_error());
                while ($a = mysql_fetch_array($acc_res)) {
                    $competitor_account_id = $a['account_id'];
                }

                //insert domain with new competitor account_id
                if ($domain) {
                    $found = false;
                    $sql   = "insert into user_accounts_domains (account_id, domain) values ('$competitor_account_id', '$domain')";
                    $resu  = mysql_query($sql, $conn);
                }

                /**
                 * @var $customer User
                 */
                $customer->recordEvent(
                    UserHistory::ADD_COMPETITOR,
                    'Added '.$account_name
                );

                return Redirect::back()
                       ->with('flash_message', 'Competitor Added!');
            }
        }

        return Redirect::back();
    }


    /**
     * /settings/competitor/{id}/remove
     *
     * @author  Will
     */
    public function remove($id)
    {
        $legacy = $this->baseLegacyVariables();
        extract($legacy);

            $found = false;
            $acc = "select * from user_accounts where account_id = '$id' AND competitor_of = '$cust_account_id' limit 1";
            $acc_res = mysql_query($acc,$conn) or die(mysql_error());
            while ($a = mysql_fetch_array($acc_res)) {
                $found = true;
            }

            if ($found) {
                $sql = "update user_accounts set track_type = 'orphan' where account_id = \"$id\" AND competitor_of = '$cust_account_id'";
                $resu = mysql_query($sql, $conn);

                /**
                 * @var $customer User
                 */
                $customer->recordEvent(
                    UserHistory::REMOVE_COMPETITOR,
                    'Removed '.$id
                );

                return Redirect::back()
                       ->with('flash_message', 'Competitor removed!');
            }

        return Redirect::back()
               ->with('flash_error', "Competitor with id $id not found");
    }

    /**
     * /settings/competitors
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
         * Redirect to profile settings if user does not have permissions to see this page.
         */
        if ($customer->is_admin == User::PERMISSIONS_VIEWER) {
            return Redirect::to('/settings/profile');
        }

        /*
         * Copying in legacy code...
         */

        //check for google analytics profile already synced
        if (hasAnalytics($cust_account_id, $conn)) {
            $analytics = new GoogleAnalytics(getAnalyticsToken($cust_account_id, $conn));
            $analytics->setProfile(getAnalyticsProfile($cust_account_id, $conn));
            Session::put('has_analytics',1);
        } else {
            $analytics = null;
        }

        $max_allowed_competitors = $customer->maxAllowed('num_competitors');

        $avail_competitors = $max_allowed_competitors - $cust_num_competitors;

        if($avail_competitors > 0){
            //do nothing
            $comp_access_js = "";
            $inactivate_competitors = "";
            $inactivate_competitor_admin = "";
            $comp_message = "
            <div class='alert alert-success'>
                You currently have $avail_competitors Competitor slots remaining
            </div>";
        } else {

            $comp_access_js = "
            <script>
                $(document).ready(function () {
                    $('.add-competitor-admin button').remove();
                    $('.competitor-admin .alert').remove();
                })
            </script>";

            $inactivate_competitors = "inactive";
            $inactivate_competitor_admin = "";

            if($max_allowed_competitors==0){
                $comp_message = "
                <div class='alert alert-info alert-block'>
                    Please upgrade your account in order to track competitors!
                    <span class='pull-right' style='margin-top: -5px;'>
                        <a href='/upgrade?ref=comp_free'>
                            <button class='btn'><i class='icon-arrow-right'></i> <strong>Upgrade Now</strong></button>
                        </a>
                    </span>
                </div>";

                $inactivate_competitor_admin = "inactive";

            } else {
                $comp_message = "
                <div class='alert alert-info alert-block'>
                    <strong>Whoops!</strong> Looks like you've filled all $max_allowed_competitors of your Competitor Slots.
                    <span class='pull-right' style='margin-top: -5px;'>
                        <a href='/upgrade?ref=comp_add'>
                            <button class='btn'><i class='icon-arrow-right'></i> <strong>Upgrade Now</strong> to track more
                                Competitors!</button>
                        </a>
                    </span>
                </div>";

                $inactivate_competitor_admin = "inactive";
                $inactivate_competitors = "";
            }
        }
        $vars = array(
            'navigation' => $this->buildSettingsNavigation('competitors'),
            'alert'      => $this->generateAlertBox(),
            'comp_message'=> $comp_message,
            'comp_access_js' => $comp_access_js,
            'inactivate_competitors' => $inactivate_competitors,
            'inactivate_competitor_admin' => $inactivate_competitor_admin,
            'comp_name' => '',
            'comp_domain' => '',
            'comp_username' => '',



        );

        $merged                     = array_merge($vars, $legacy);
        $this->layout->main_content = View::make('analytics.pages.settings.competitors', $merged);

    }

}