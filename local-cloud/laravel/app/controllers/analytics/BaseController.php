<?php namespace Analytics;

include_once app_path() . '/controllers/analytics/includes/includes.php';

use
    Config,
    DatabaseInstance,
    InvalidArgumentException,
    Log,
    Organization,
    Pinleague\Views,
    Plan,
    Request,
    Session,
    User,
    URL,
    View;

/**
 * Class BaseController
 *
 * @package Analytics
 */
class BaseController extends \BaseController
{

    protected $layout = 'layouts.analytics';


    /**
     * This is the config variable for what components are added when setupLayout runs
     *
     * @var array
     */
    protected $layout_defaults = array(
        'page'           => '',
        'top_nav_title'  => '',
        'head'           => array(
            'segmentio' => 1,
            'head_tag'  => 2
        ),
        'pre_body_close' => array(
            'olark'          => 3,
            'tooltip'        => 4,
            'datepicker'     => 5,
            'feature_kicker' => 6,
            'nav_upgrade'    => 7,
            'cookie'         => 8,
            'side_nav'       => 9
        ),

    );

    /**
     * The logged in user. If the admin account is being used, it is who we are pretending to be
     * @var $logged_in_customer User
     */
    protected $logged_in_customer = false;

    /**
     * The user account in the reports
     * @var $active_user_account \UserAccount
     */
    protected $active_user_account;

    /**
     * The logged in customer when in demo mode (aka not the demo account)
     * @var User
     */
    protected $parent_customer = false;
    /**
     * The parent's active user account (when in demo mode)
     * @var bool
     */
    protected $parent_active_user_account = false;

    /**
     * If logged in customer is actually the admin account pretending to be that account
     * @var bool
     */
    protected $is_admin_account = false;
    /**
     * A cache of $this->baseLegacyVariables()
     * This is working towards being removed.
     *
     * @var $_base_legacy_variables array
     */
    protected $_base_legacy_variables = false;

    /**
     * @author  Will
     */
    public function __construct()
    {
        $id = User::getLoggedInId();

        if ($id) {

           /*
            * We change the account by appending ?account=account_index
            * to the page request and store it in the session
            */
            if (isset($_GET['account'])) {
                Session::set('account_index',$_GET['account']);
            }

            /*
             * If this is the admin account, we want to find out which account we are pretending to
             * be vs just using the id
             */
            if (474 == $id) {

                Session::set('is_admin_account',$this->is_admin_account = true);

                /*
                 * If we're in the admin account, we want to ignore the transaction in newrelic,
                 * so that we don't bloat our acual page load times, etc. 
                 */
                if (extension_loaded('newrelic')) {
                    newrelic_ignore_transaction ();
                }

                if(Session::has('admin_user')) {
                    $id = Session::get('admin_user');
                }

                /*
                 * Getting the active "user" is set by a GET param in this format
                 * user={username}-{org_id}
                 */
                if (isset($_GET['user'])) {
                    $slug = explode('-',$_GET['user']);

                    $organization = Organization::find($slug[1]);

                    /*
                     * We use the main account here because... why not?
                     */
                    $this->logged_in_customer = $organization->billingUser();
                    Session::set('admin_user',$this->logged_in_customer->cust_id);
                }

            }

            if($this->isDemo()) {

                $this->parent_customer = User::find($id);
                $this->parent_active_user_account = $this->parent_customer->getActiveUserAccount();

                if ($this->isDemo() == Plan::LITE) {
                    $this->logged_in_customer = User::find(Config::get('demo.lite_user_id'));
                } else {
                    $this->logged_in_customer = User::find(Config::get('demo.pro_user_id'));
                }

                $this->logged_in_customer->demo_parent = $this->parent_customer;

            }

            if (!$this->logged_in_customer) {
                $this->logged_in_customer = User::find($id);
                $this->logged_in_customer->last_seen_ip = Request::getClientIp();
                $this->logged_in_customer->insertUpdateDB();
            }

            $this->logged_in_customer->getAllFeatures();
            /**
             * Log a debug statement for who the user is, helpful when searching other
             * errors by LOG_ID
             */
            Log::debug('Logged in user set',$this->logged_in_customer);

            $this->active_user_account = $this->logged_in_customer->getActiveUserAccount();
            Log::debug('Active account set',$this->active_user_account);


        }

    }

    /**
     * @author  Will
     * @return \Illuminate\View\View|string
     */
    public function generateAlertBox()
    {
        $legacy = $this->baseLegacyVariables();
        extract($legacy);

        $alert = '';

        if (Session::has('flash_error')) {
            $alert = View::make('shared.components.alert_with_x',
                                array(
                                     'message' => Session::get('flash_error'),
                                     'type'    => 'error'
                                )
            );
        }

        if (Session::has('flash_message')) {
            $alert = View::make('shared.components.alert_with_x',
                                array(
                                     'message' => Session::get('flash_message'),
                                     'type'    => 'info'
                                ));
        }

        if (Session::has('flash_success')) {
            $alert = View::make('shared.components.alert_with_x',
                array(
                     'message' => Session::get('flash_success'),
                     'type'    => 'success'
                ));
        }

        if ($first_day && $alert == '') {

            if ($get_params) {
                $refresh_link = $uri_pass . "&refresh=1";
            } else {
                $refresh_link = $uri_pass . "?refresh=1";
            }

            $alert = View::make('shared.components.alert_with_x',
                                array(
                                     'message' => "<i class='icon-chef'></i>
                                    Our chefs are still in the kitchen cooking up your data.
				                    <a href='#' data-toggle='popover-click'
				                       data-trigger='click'
				                       data-container='body'
				                       data-original-title='<strong>Your data may still
				                            be processing</strong>'
				                       data-content='Since your account is
				                            only $cust_account_age_print, some charts may
				                            appear empty until multiple days of data can be
				                            recorded to show you a trend.  Historical data
				                            is not available for followers and repins, but
				                            we will be estimating that data for you.
				                            <br><br>If you find certain boards or pins missing,
				                            you can refresh your data by clicking here
				                            (may take a few minutes):
				                            <a id=\"refresh-button\" class=\"btn\"
				                                href=\"$refresh_link\">
				                                Refresh Data</a>'
				                       data-placement='bottom'>
				                       <i class='icon-info-2'></i>
				                       Learn more
                                    </a>",
                                     'type'    => 'warning'
                                ));
        }


        return $alert;
    }

    /**
     * The goal is to get rid of this, so please use only when we have to
     *
     *
     * @author  Will
     */
    protected function baseLegacyVariables()
    {
        if ($this->_base_legacy_variables) {
            return $this->_base_legacy_variables;
        }

        if (!$this->logged_in_customer) {
            return array();
        }

        $conn = DatabaseInstance::mysql_connect();

        $nav_traffic_jquery = '';
        $nav_domain_jquery  = '';

        $cust_first_name   = $this->logged_in_customer->first_name;
        $cust_last_name    = $this->logged_in_customer->last_name;
        $cust_email        = $this->logged_in_customer->email;
        $cust_timestamp    = $this->logged_in_customer->timestamp;
        $cust_id           = $this->logged_in_customer->cust_id;
        $cust_org_id       = $this->logged_in_customer->org_id;
        $cust_is_admin     = $this->logged_in_customer->is_admin;
        $cust_type         = $this->logged_in_customer->type;
        $cust_invited_by   = $this->logged_in_customer->invited_by;
        $cust_source       = $this->logged_in_customer->source;
        $cust_timezone     = $this->logged_in_customer->timezone;
        $cust_display_name = $this->logged_in_customer->getName();


        /*--------------------------------------------------------------------
        /	GET ORG AND ACCOUNT DETAILS
        /--------------------------------------------------------------------*/
        $cust_org_name    = $this->logged_in_customer->organization()->org_name;
        $cust_org_type    = $this->logged_in_customer->organization()->org_type;
        $cust_plan_id     = $this->logged_in_customer->organization()->plan;
        $cust_chargify_id = $this->logged_in_customer->organization()->chargify_id;
        $cust_plan_name   = $this->logged_in_customer->organization()->plan()->name;

        $cust_accounts = array();
        //get account details
        $acc = "select q.*, c.image from
                  (select a.*, b.industry
                  from user_accounts a
                  left join user_industries b
                  on a.industry_id = b.industry_id
                  where a.org_id = '$cust_org_id' and (a.track_type='user' or a.track_type='free')
                  and (competitor_of=0 or competitor_of is NULL)) as q
	            left join data_profiles_new c on q.user_id = c.user_id;";
        $acc_res = mysql_query($acc, $conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $cust_account_id = $a['account_id'];

            $cust_accounts[] = array(
                'account_id'    => $a['account_id'],
                'account_name'  => $a['account_name'],
                'org_id'        => $a['org_id'],
                'username'      => strtolower($a['username']),
                'user_id'       => $a['user_id'],
                'industry_id'   => $a['industry_id'],
                'industry_name' => $a['industry'],
                'account_type'  => $a['account_type'],
                'created_at'    => $a['created_at'],
                'competitor_of' => $a['competitor_of'],
                'track_type'    => $a['track_type'],
                'image'         => $a['image']
            );
        }

        if (array_key_exists('account', $_GET)) {
            if ($cust_plan_id == 1) {
                $_GET['account'] = 0;
            }
        }


        $cust_accounts_count = count($cust_accounts);
        $is_multi_account    = false;
        if ($cust_accounts_count > 1) {
            $is_multi_account = true;

            if (array_key_exists('account', $_GET)) {
                $cust_account_num             = $_GET['account'];
                Session::put('cust_account_num', $cust_account_num);
                Session::forget('has_competitors');
                Session::forget('num_competitors');
                Session::forget('days_of_calcs_last_check');
                Session::forget('days_of_calcs');
            } else {
                if (Session::has('cust_account_num')) {
                    $cust_account_num = Session::get('cust_account_num');
                } else {
                    $cust_account_num = 0;
                }
            }
        } else {
            $cust_account_num = 0;
            Session::put('cust_account_num', $cust_account_num);
        }

        //get all domain info for each account
        foreach ($cust_accounts as $k => $ac) {
            $account_id = $ac['account_id'];

            $acc = "select * from user_accounts_domains where account_id = '$account_id'";
            $acc_res = mysql_query($acc, $conn) or die(mysql_error());
            while ($a = mysql_fetch_array($acc_res)) {
                $domain = $a['domain'];

                $cust_accounts[$k]['domains'][] = $domain;
            }
        }

        //get profile picture and follower count if we don't already have it
        if (!Session::has('image') || (Session::get('cust_user_id') != $cust_accounts[$cust_account_num]['user_id'])) {
            $acc = "select follower_count, image from data_profiles_new where user_id = " . $cust_accounts[$cust_account_num]['user_id'] . " limit 1";
            $acc_res = mysql_query($acc, $conn) or die(mysql_error());
            while ($a = mysql_fetch_array($acc_res)) {
                $cust_image                 = $a['image'];
                $cust_follower_count        = $a['follower_count'];
                Session::put('image', $cust_image);
                Session::put('follower_count', $cust_follower_count);
            }

        }


        //get account info
        $cust_account_id         = $cust_accounts[$cust_account_num]['account_id'];
        $cust_account_name       = $cust_accounts[$cust_account_num]['account_name'];
        $cust_account_type       = $cust_accounts[$cust_account_num]['account_type'];
        $cust_account_track_type = $cust_accounts[$cust_account_num]['track_type'];
        $cust_user_id            = $cust_accounts[$cust_account_num]['user_id'];
        $cust_username           = $cust_accounts[$cust_account_num]['username'];
        $cust_industry           = $cust_accounts[$cust_account_num]['industry_name'];
        $cust_industry_id        = $cust_accounts[$cust_account_num]['industry_id'];
        $cust_account_created_at = $cust_accounts[$cust_account_num]['created_at'];

        Session::put('cust_user_id', $cust_user_id);


        //get domain info
        if (isset($cust_accounts[$cust_account_num]['domains'])) {
            $domains = $cust_accounts[$cust_account_num]['domains'];
        } else {
            $domains = array();
        }
        $cust_domains_count = count($domains);

//		$cust_domains = array();
//		foreach($domains as $d){
//			array_push($cust_domains, $d['domain']);
//		}

        if ($cust_domains_count > 0) {

            $cust_domain = $domains[0];
        } else {
            $cust_domain = '';
        }


        /*------------------------------------------------------------------------------------------------
        /	DO ACCOUNT FEATURE CHECKS
        /-------------------------------------------------------------------------------------------------*/

        if ($this->isDemo()) {

            $has_ga = hasAnalytics($cust_account_id, $conn);
            $has_competitors = false;

            $acc = "SELECT count(*) FROM user_accounts WHERE competitor_of='$cust_account_id' AND track_type != 'orphan'";
            $acc_res = mysql_query($acc, $conn) or die(mysql_error());
            while ($a = mysql_fetch_array($acc_res)) {
                if ($a['count(*)'] > 0) {
                    $has_competitors = true;
                    Session::put('has_competitors', true);
                }
                $cust_num_competitors = $a['count(*)'];
                Session::put('num_competitors', $cust_num_competitors);
            }

            $has_ga = hasAnalytics($cust_account_id, $conn);
            if ($has_ga) {
                Session::put('has_analytics', 1);
                if (!analyticsReady($cust_account_id, $conn)) {
                    Session::put('has_analytics', 2);
                }
            } else {
                Session::put('has_analytics', 0);
            }

        } else {

            if (!Session::has('analytics_last_check')) {

                Session::forget('has_analytics');
                Session::put('analytics_last_check', time());
            } else if (Session::get('analytics_last_check') < strtotime('-30 minutes', time())) {

                Session::forget('has_analytics');
                Session::put('analytics_last_check', time());
            }

            //check if user has google analytics integrated
            if (!Session::has('has_analytics') || $this->isAdmin() || $this->isDemo()) {
                $has_ga = hasAnalytics($cust_account_id, $conn);
                if ($has_ga) {
                    Session::put('has_analytics', 1);
                    if (!analyticsReady($cust_account_id, $conn)) {
                        Session::put('has_analytics', 2);
                    }
                } else {
                    Session::put('has_analytics', 0);
                }
            }

            $listening_org_ids = array();

            //check if the user has competitors or not
            if (!Session::has('has_competitors') || $this->isAdmin() || $this->isDemo()) {
                $has_competitors = false;
                Session::put('has_competitors', false);
                $acc = "SELECT count(*) as count FROM user_accounts WHERE competitor_of='$cust_account_id' AND track_type != 'orphan'";
                $acc_res = mysql_query($acc, $conn) or die(mysql_error());
                while ($a = mysql_fetch_array($acc_res)) {
                    if ($a['count'] > 0) {
                        $has_competitors = true;
                        Session::put('has_competitors', true);
                    }
                    $cust_num_competitors = $a['count'];
                    Session::put('num_competitors', $cust_num_competitors);
                }
            } else {
                $has_competitors = Session::get('has_competitors');
            }
        }


        $first_day        = false;
        $cust_account_age = round((time() - $cust_account_created_at) / 60 / 60);
        if ($cust_account_age < 1) {
            $cust_account_age_print = "less than an hour old";
        } elseif ($cust_account_age < 12) {
            $cust_account_age_print = "only a few hours old";
        } elseif ($cust_account_age < 24) {
            $cust_account_age_print = "less than a day old";
        } else {
            $cust_account_age_print = ($cust_account_age / 24) . " days old";
        }
        if ($cust_account_age < 12) {

            $first_day = true;
            if (isset($_GET['refresh'])) {

                if ($_GET['refresh'] == 1) {
                    $sql  = "update status_profiles set last_calced=0, last_calced_engagement=0 where user_id='$cust_user_id'";
                    $resu = mysql_query($sql, $conn);
                    $sql  = "update status_boards a
                    left join data_boards b
                    on a.board_id = b.board_id
                    set a.last_calced=0 where b.user_id='$cust_user_id'";
                    $resu = mysql_query($sql, $conn);
//                    $sql  = "update status_domains set last_calced=0 where domain='$cust_domain'";
//                    $resu = mysql_query($sql, $conn);
                }
            }

        }

        //see how old this account is
        if (round((time() - $cust_account_created_at) / 60 / 60 / 24) < 14) {
            $fresh_account = true;
        } else {
            $fresh_account = false;
        }


        if(!$this->isDemo()) {
            if (Session::has('days_of_calcs_last_check') == false) {
                Session::put('days_of_calcs_last_check', time());
            }

            if (
                Session::has('days_of_calcs') == false OR
                time() - Session::get('days_of_calcs_last_check', 0) > 3600
            ) {
                Session::put('days_of_calcs_last_check', time());
                Session::put(
                       'days_of_calcs',
                       $this->active_user_account->profile()->daysOfCalcsAvailable()
                );
            }
        } else {

            Session::put('days_of_calcs',$this->active_user_account->profile()->daysOfCalcsAvailable());
        }

        /*------------------------------------------------------------------------------------------------
        /	GET ACCOUNT STATS FOR INTERCOM
        /-------------------------------------------------------------------------------------------------*/
        if (!Session::has('follower_count')) {

            $acc = "select max(date) from calcs_profile_history where user_id='$cust_user_id' order by date desc limit 1";
            $acc_res = mysql_query($acc, $conn) or die(mysql_error());
            while ($a = mysql_fetch_array($acc_res)) {
                $last_profile_calc_date = $a['max(date)'];
            }

            $acc = "select date from calcs_domain_history where domain='$cust_domain' order by date desc limit 1";
            $acc_res = mysql_query($acc, $conn) or die(mysql_error());
            while ($a = mysql_fetch_array($acc_res)) {
                $last_domain_calc_date = $a['date'];
            }

            $competitor_count = 0;
            @$acc = "select a.*, b.*, c.*, d.* from user_accounts a, user_accounts_domains b, calcs_profile_history c, calcs_domain_history d where a.account_id=b.account_id and a.user_id=c.user_id and b.domain=d.domain and a.org_id='$cust_org_id' and c.date='$last_profile_calc_date' and d.date='$last_domain_calc_date'";
            $acc_res = mysql_query($acc, $conn) or die(mysql_error());
            while ($a = mysql_fetch_array($acc_res)) {


                if ($competitor_count == 0) {
                    Session::put('cust_profile_pins', $a['pin_count']);
                    Session::put('total_profile_pins',$a['pin_count']);
                    Session::put('total_profile_pins', Session::get('total_profile_pins') + $a['domain_mentions']);
                    Session::put('total_competitor_pins', 0);
                } else if ($competitor_count == 1) {
                    Session::put('total_competitor_pins', $a['pin_count']);
                    Session::put('total_competitor_pins', Session::get('total_competitor_pins') + $a['domain_mentions']);
                } else {
                    Session::put('total_competitor_pins', Session::get('total_competitor_pins') + $a['pin_count']);
                    Session::put('total_competitor_pins', Session::get('total_competitor_pins') + $a['domain_mentions']);
                }

                $competitor_count++;
            }

            if (Session::has('total_profile_pins')) {
                Session::set('total_pins', Session::get('total_profile_pins') + Session::get('total_competitor_pins'));
            }
            $competitor_count--;

        }


        if (!Session::has('industries')) {

            $industries = array();
            $acc = "select * from user_industries";
            $acc_res = mysql_query($acc, $conn) or die(mysql_error());
            while ($a = mysql_fetch_array($acc_res)) {
                $industry_id   = $a['industry_id'];
                $industry_name = $a['industry'];

                $industries["$industry_id"]      = array();
                $industries["$industry_id"]['id']   = $industry_id;
                $industries["$industry_id"]['name'] = $industry_name;

            }

            Session::put('industries', $industries);
        }

        $image    = '';
        if (!isset($customer)) {
            $customer = $this->logged_in_customer;
        }

        if (Session::has('image')) {
            $image = Session::get('image');
        }



/*
|--------------------------------------------------------------------------
| NAVIGATION PERMISSIONS
|--------------------------------------------------------------------------
*/


        $links['domain_insights_link']             = URL::route('domain-insights-default');
        $links['domain_insights_attributes']       = '';
        $links['domain_benchmarks_link']           = URL::route('domain-competitor-benchmarks');
        $links['domain_benchmarks_attributes']     = '';
        $links['profile_benchmarks_link']          = URL::route('profile-competitor-benchmarks');
        $links['profile_benchmarks_attributes']    = '';
        $links['followers_link']                   = URL::route('newest-followers');
        $links['followers_link_attributes']        = '';
        $links['brand_advocates_enabled']             = true;
        $links['brand_advocates_link']             = URL::route('domain-pinners');
        $links['brand_advocates_attributes']       = '';
        $links['repinners_link']                   = URL::route('top-repinners');
        $links['repinners_attributes']             = '';
        $links['most_valuable_pinners_link']       = URL::route('most-valuable-pinners');
        $links['most_valuable_pinners_attributes'] = '';
        $links['viral_pins_link']                  = URL::route('owned-trending-pins');
        $links['viral_pins_attributes']            = '';
        $links['category_heatmaps_link']           = URL::route('categories');
        $links['category_heatmaps_attributes']     = '';
        $links['days_times_link']                  = URL::route('peak-days');
        $links['days_times_attributes']            = '';

        if (!$this->logged_in_customer->hasFeature('nav_comp_bench')) {

            $links['domain_benchmarks_link']       = '/upgrade/modal/domain_benchmarks';
            $links['domain_benchmarks_attributes'] = 'data-target="#modal" data-toggle="modal" ';

            $links['profile_benchmarks_link']       = '/upgrade/modal/profile_benchmarks';
            $links['profile_benchmarks_attributes'] = 'data-target="#modal" data-toggle="modal" ';
        }


        if (!$this->logged_in_customer->hasFeature('nav_newest_followers')) {
            $links['followers_link']            = '/upgrade/modal/followers';
            $links['followers_link_attributes'] = 'data-target="#modal" data-toggle="modal" ';
        }

        if (!$this->logged_in_customer->hasFeature('nav_fans')) {
            $links['brand_advocates_enabled']             = false;
            $links['brand_advocates_link']       = '/upgrade/modal/brand_advocates';
            $links['brand_advocates_attributes'] = 'data-target="#modal" data-toggle="modal" ';

            $links['repinners_link']       = '/upgrade/modal/repinners';
            $links['repinners_attributes'] = 'data-target="#modal" data-toggle="modal" ';
        } else {
            $links['followers_link']            = URL::route('influential-followers');
            $links['followers_link_attributes'] = '';
        }

        if (!$this->logged_in_customer->hasFeature('nav_fans')) {
            $links['most_valuable_pinners_link']       = '/upgrade/modal/most_valuable_pinners';
            $links['most_valuable_pinners_attributes'] = 'data-target="#modal" data-toggle="modal" ';
        }

        if (!$this->logged_in_customer->hasFeature('nav_viral_pins')) {
            $links['viral_pins_link']       = '/upgrade/modal/viral_pins';
            $links['viral_pins_attributes'] = 'data-target="#modal" data-toggle="modal" ';
        }

        if (!$this->logged_in_customer->hasFeature('nav_category')) {
            $links['category_heatmaps_link']       = '/upgrade/modal/category_heatmaps';
            $links['category_heatmaps_attributes'] = 'data-target="#modal" data-toggle="modal" ';
        }

        if (!$this->logged_in_customer->hasFeature('nav_day_time')) {
            $links['days_times_link']       = '/upgrade/modal/days_times';
            $links['days_times_attributes'] = 'data-target="#modal" data-toggle="modal" ';
        }

        if ($customer->hasFeature('nav_profile')) {
            $nav_profile_class = "";
        } else {
            $nav_profile_class = "inactive";
        }


        if ($customer->hasFeature('nav_boards')) {
            $nav_boards_class = "";
        } else {
            $nav_boards_class = "inactive";

        }


        if ($customer->hasFeature('nav_website')) {
            //logic on whether to activate domain-related links in the menu
            if (!$cust_domain || $cust_domain == '') {
                $nav_website_class       = "inactive";

                $nav_link_website       = "href='/website'";

                $nav_domain_jquery = "
                    $('#trending-nav-label, #website-nav-label, #domain-pinners-nav-label').attr({
                        'data-toggle':'popover-click',
                        'data-placement':'right',
                        'data-title':'',
                        'data-content':'Start tracking a domain to enable this report<br><br> <a href=\"/pins/domain/trending\"><button class=\"btn btn-primary\">Add your Domain →</button></a>',
                        'data-container':'body'
                    });

                    $('#domain-pinners-subnav-label').attr({
                        'data-toggle':'popover-click',
                        'data-placement':'bottom',
                        'data-title':'',
                        'data-content':'Start tracking a domain to enable this report<br><br> <a href=\"/pins/domain/trending\"><button class=\"btn btn-primary\">Add your Domain →</button></a>',
                        'data-container':'body'
                    });
                    $('#domain-pinners-subnav-label a, #domain-pinners-nav-label a span.menu-icon-right, #website-nav-label a span.menu-icon-right').append(
                        '<i class=\"icon-warning\" style=\"color: #CC2127;\"></i>'
                    );";

            } else {
                $nav_website_class       = "";

                $nav_link_website       = "href='/website'";

                $nav_domain_jquery = "";
            }
        } else {
            $nav_website_class = "inactive go-pro";
            $nav_link_website  = "href='/website'";
        }

        if ($customer->hasFeature('nav_category')) {
            $nav_categories_class = "";
        } else {
            $nav_categories_class = "inactive go-pro";
        }

        if ($customer->hasFeature('nav_day_time')) {
            $nav_day_time_class = "";
            $nav_link_day_time  = "href='/days-and-times'";
        } else {
            $nav_day_time_class = "inactive go-pro";
            $nav_link_day_time  = "href='/days-and-times'"; //TODO: send to feature overview page
        }

        if ($customer->hasFeature('nav_newest_followers')) {
            $nav_newest_followers_class = "";
            $nav_link_newest_followers  = "href='/followers/newest'";
        } else {
            $nav_newest_followers_class = "inactive go-pro";
            $nav_link_newest_followers  = "href='/followers/newest'";
        }
        $nav_followers_class = $nav_newest_followers_class;


        if ($customer->hasFeature('nav_fans')) {
            $nav_influential_followers_class = "";
            $nav_link_influential_followers  = "href='/followers/influential'";
            $nav_link_followers              = $nav_link_influential_followers;
        } else {
            $nav_influential_followers_class = "inactive go-pro";
            $nav_link_influential_followers  = "href='/followers/influential'"; //TODO: send to feature overview page
            $nav_link_followers              = $nav_link_newest_followers;
        }

        if ($customer->hasFeature('nav_repinners')) {
            $nav_top_repinners_class = "";
            $nav_link_top_repinners  = "href='/top-repinners'";
        } else {
            $nav_top_repinners_class = "inactive disabled go-pro";
            $nav_link_top_repinners  = "href='/top-repinners'"; //TODO: send to feature overview page
        }

        if ($customer->hasFeature('nav_domain_pinners')) {
            if (!$cust_domain || $cust_domain == '') {
                $nav_domain_pinners_class = "disabled inactive";
                $nav_link_domain_pinners  = "";
            } else {
                $nav_domain_pinners_class = "";
                $nav_link_domain_pinners  = "href='/domain-pinners'";
            }
        } else {
            $nav_domain_pinners_class = "inactive disabled go-pro";
            $nav_link_domain_pinners  = "href='/domain-pinners'"; //TODO: send to feature overview page
        }

        if ($customer->hasFeature('nav_roi_pins')) {
            $nav_roi_pins_class = "";
        } else {
            $nav_roi_pins_class = "inactive go-pro";
        }

        if ($customer->hasFeature('nav_viral_pins')) {
            $nav_viral_pins_class = "";
            $nav_link_viral_pins = "href='/pins/owned/trending'";
        } else {
            $nav_viral_pins_class = "inactive go-pro";
            $nav_link_viral_pins = "";
        }


        if ($customer->hasFeature('nav_roi_pinners')) {
            $nav_roi_pinners_class = "";
        } else {
            $nav_roi_pinners_class = "inactive go-pro";
        }


        if ($customer->hasFeature('nav_traffic')) {
            $nav_traffic_class = "";

            //logic on whether to activate Google Analytics-related links in the menu


            if (Session::get('has_analytics') == 1 || Session::get('has_analytics') == 2 || $this->isDemo()) {
                $nav_traffic_class     = "";
                $nav_link_traffic     = "href='/domain/traffic'";
                $nav_traffic_jquery = "";
            } else {
                $nav_traffic_class     = "inactive";
                $nav_link_traffic     = "";
                $nav_traffic_jquery = "
                    $('#traffic-nav-label').attr({
                        'data-toggle':'popover-click',
                        'data-placement':'right',
                        'data-title':'',
                        'data-content':'Sync your Google Analytics account to enable this report<br><br> <a href=\"/settings/google-analytics\"><button class=\"btn btn-primary\">Sync Google Analytics →</button></a>',
                        'data-container':'body'
                    });
                    $('#traffic-subnav-label').attr({
                        'data-toggle':'popover-click',
                        'data-placement':'bottom',
                        'data-title':'',
                        'data-content':'Sync your Google Analytics account to enable this report<br><br> <a href=\"/settings/google-analytics\"><button class=\"btn btn-primary\">Sync Google Analytics →</button></a>',
                        'data-container':'body'
                    });
                    $('#traffic-subnav-label a, #traffic-nav-label a span.menu-icon-right').append(
                        '<i class=\"icon-warning\" style=\"color: #CC2127;\"></i>'
                    );";

                //$nav_traffic_jquery = createNavPopover(".traffic-nav-label-wrapper","hover","right",".traffic-nav-label-wrapper","Sync your Google Analytics account to enable this report<br><br> <a href=\"settings.php?tab=Analytics\"><button class=\"btn btn-primary\">Sync Google Analytics →</button></a>");
            }


        } else {
            $nav_traffic_class     = "inactive go-pro";
            $nav_link_traffic      = "href='/domain/traffic'"; //TODO: send to feature overview page
        }

        if ($customer->hasFeature('nav_roi_pinners')) {
            if (Session::get('has_analytics') == 1 OR $this->isDemo()) {

                $nav_roi_pinners_class = "";
                $nav_roi_pins_class    = "";

                $nav_link_roi_pinners = "href='/most-valuable-pinners'";
                $nav_link_roi_pins    = "href=/pins/most-valuable";

                $nav_traffic_jquery = "";
            } else {

                $nav_roi_pinners_class = "inactive";
                $nav_roi_pins_class    = "inactive";

                $nav_link_roi_pinners = "";
                $nav_link_roi_pins    = "";
                $links['most_valuable_pinners_link'] = "#";

                $nav_traffic_jquery .= "
                $('#roi-pinners-nav-label, #roi-pins-nav-label').attr({
                    'data-toggle':'popover-click',
                    'data-placement':'right',
                    'data-title':'',
                    'data-content':'Sync your Google Analytics account to enable this report<br><br> <a href=\"/settings/google-analytics\"><button class=\"btn btn-primary\">Sync Google Analytics →</button></a>',
                    'data-container':'body'
                });
                $('#roi-pinners-subnav-label, #roi-pins-subnav-label').attr({
                    'data-toggle':'popover-click',
                    'data-placement':'bottom',
                    'data-title':'',
                    'data-content':'Sync your Google Analytics account to enable this report<br><br> <a href=\"/settings/google-analytics\"><button class=\"btn btn-primary\">Sync Google Analytics →</button></a>',
                    'data-container':'body'
                });
                $('#roi-pinners-subnav-label a, #roi-pinners-nav-label a span.menu-icon-right, #roi-pins-subnav-label a, #roi-pins-nav-label a span.menu-icon-right').append(
                    '<i class=\"icon-warning\" style=\"color: #CC2127;\"></i>'
                );";
            }
        } else {
            $nav_roi_pinners_class = "inactive go-pro";
            $nav_roi_pins_class    = "inactive go-pro";
            $nav_link_roi_pinners  = "href='/most-valuable-pinners'"; //TODO: send to feature overview page
            $nav_link_roi_pins     = "href='/pins/most-valuable'"; //TODO: send to feature overview page
        }


        if ($customer->hasFeature('nav_comp_bench')) {

            //logic on whether to activate Competitor-related links in the menu
            $nav_competitors_enabled  = true;
            $nav_competitors_locked   = "";
            $nav_show_add_competitors = "";
            $nav_add_competitor_link  = "href='/settings/competitors'";

            if ($has_competitors) {
                $nav_competitor_benchmarks_class = "";

                $nav_link_competitor_benchmarks = "href='/competitors/benchmarks'";
                $nav_add_competitor_link  = "href='/settings/competitors'";

                $nav_competitor_benchmarks_jquery = "";
            } else {
                $nav_competitor_benchmarks_class = "inactive";

                $nav_link_competitor_benchmarks = "";

                $nav_competitor_benchmarks_jquery = "
                    $('#comp-bench-nav-label').attr({
                        'data-toggle':'popover-click',
                        'data-placement':'right',
                        'data-title':'',
                        'data-content':'Add competitors to enable this report<br><br> <a href=\"/settings/competitors\"><button class=\"btn btn-primary\">Add a Competitor →</button></a>',
                        'data-container':'body'
                    });
                    $('#comp-bench-subnav-label').attr({
                        'data-toggle':'popover-click',
                        'data-placement':'bottom',
                        'data-title':'',
                        'data-content':'Add competitors to enable this report<br><br> <a href=\"/settings/competitors\"><button class=\"btn btn-primary\">Add a Competitor →</button></a>',
                        'data-container':'body'
                    });
                    $('#comp-bench-subnav-label a, #comp-bench-nav-label a span.menu-icon-right').append(
                        '<i class=\"icon-warning\" style=\"color: #CC2127;\"></i>'
                    );";
            }


        } else {
            $nav_competitors_enabled          = false;
            $nav_competitors_locked           = "<i class='icon-lock'></i> &nbsp;";
            $nav_show_add_competitors         = "inactive disabled";
            $nav_add_competitor_link          = "";
            $nav_competitor_benchmarks_class  = "inactive go-pro";
            $nav_link_competitor_benchmarks   =  ' href="/upgrade/modal/profile_benchmarks" data-target="#modal" data-toggle="modal"  ';
            $nav_competitor_benchmarks_jquery = "
                $('#comp-bench-subnav-label').attr({
                       'data-toggle':'popover-click',
                        'data-placement':'bottom',
                        'data-title':'',
                        'data-content':'Try the Pro plan to view Competitor Benchmarks <br> <a href=\"upgrade?ref=nav_competitors\"><button class=\"btn btn-success\">Learn More →</button></a>',
                        'data-container':'body'
                });";

        }

        if ($customer->hasFeature('comp_bench_domains')) {

            if ($has_competitors) {
                $nav_domain_benchmarks_class     = "";
                $nav_link_domain_benchmarks = "href='/domain/benchmarks'";
            } else {
                $nav_domain_benchmarks_class     = "inactive";
                $nav_link_domain_benchmarks = "";
                $nav_competitor_benchmarks_jquery .= "
                    $('#domain-benchmarks-nav-label').attr({
                        'data-toggle':'popover-click',
                        'data-placement':'right',
                        'data-title':'',
                        'data-content':'Add competitors to enable this report<br><br> <a href=\"/settings/competitors\"><button class=\"btn btn-primary\">Add a Competitor →</button></a>',
                        'data-container':'body'
                    });
                    $('#comp-domain-bench-subnav-label').attr({
                        'data-toggle':'popover-click',
                        'data-placement':'bottom',
                        'data-title':'',
                        'data-content':'Add competitors to enable this report<br><br> <a href=\"/settings/competitors\"><button class=\"btn btn-primary\">Add a Competitor →</button></a>',
                        'data-container':'body'
                    });
                    $('#comp-domain-bench-subnav-label a, #domain-benchmarks-nav-label a span.menu-icon-right').append(
                        '<i class=\"icon-warning\" style=\"color: #CC2127;\"></i>'
                    );";
            }

        } else {
            $nav_domain_benchmarks_class      = "inactive go-pro";
            $nav_link_domain_benchmarks       = 'href="/upgrade/modal/domain_benchmarks" data-target="#modal" data-toggle="modal"';
            $nav_competitor_benchmarks_jquery = '';
        }

        if ($customer->hasFeature('nav_comp_intel')) {
            $nav_show_comp_intel = "inactive";
        } else {
            $nav_show_comp_intel = "inactive";
        }


        if ($customer->hasFeature('nav_listen')) {
            $nav_show_listen = "";
        } else {
            $nav_show_listen = "inactive";

        }


        if ($customer->hasFeature('nav_add_account')) {
            $nav_show_add_account   = "";
            $nav_add_account_locked = "";
            $nav_add_account_link   = "href='/settings/accounts/?add=1'";
            $nav_add_account_jquery = "";

            if($this->isDemo()) {

                $nav_show_add_account   = "inactive disabled";
                $nav_add_account_locked = "<i class='icon-lock'></i> &nbsp;";
                $nav_add_account_link   = "";
                $nav_add_account_jquery = "
            $('#nav-add-account-wrapper').attr({
                'data-toggle':'popover-click',
                'data-placement':'right',
                'data-title':'',
                'data-content':'This feature is not allowed in the demo, but is allowed in both Pro and Lite.',
                'data-container':'body'
            });";

            }
        } else {
            $nav_show_add_account   = "inactive disabled";
            $nav_add_account_locked = "<i class='icon-lock'></i> &nbsp;";
            $nav_add_account_link   = "";
            $nav_add_account_jquery = "
            $('#nav-add-account-wrapper').attr({
                'data-toggle':'popover-click',
                'data-placement':'right',
                'data-title':'',
                'data-content':'Try the Lite or Professional plan to add more accounts<br> <a href=\"upgrade?ref=nav_add_accounts\"><button class=\"btn btn-success\">Learn More →</button></a>',
                'data-container':'body'
            });";
        }


        if ($customer->hasFeature('nav_add_user')) {
            $nav_add_user_show        = "";
            $nav_add_user_locked      = "";
            $nav_add_user_link        = "href='/settings/collaborators'";
            $settings_add_user_show   = "";
            $settings_add_user_link   = "href='/settings/collaborators'";
            $collaborator_popover     = "";
            $collaborator_tab_popover = "";
            $add_user_enabled         = true;

        } else {
            $nav_add_user_show        = "inactive disabled";
            $nav_add_user_locked      = "<i class='icon-lock'></i> &nbsp;";
            $nav_add_user_link        = "";
            $settings_add_user_show   = "inactive disabled";
            $settings_add_user_link   = "";
            $add_user_enabled         = false;
            $collaborator_popover     = createPopover("#add-collaborator-button", "hover", "bottom", "<span class=\"text-success\"><strong>Try a Lite or Pro Plan</strong></span>", "add-collaborator-button",
                                                      $customer->plan()->plan_id, "<span style=\"color:#000;\"><strong>Invite your colleagues, co-workers or clients</strong> to access your dashboard with their own personal login.</span><br>");
            $collaborator_tab_popover = createPopover("#add-collaborator-tab", "hover", "bottom", "<span class=\"text-success\"><strong>Try the Professional Plan</strong></span>", "add-collaborator-tab",
                                                      $customer->plan()->plan_id, "<span style=\"color:#000;\"><strong>Invite your colleagues, co-workers or clients</strong> to access your dashboard with their own personal login.</span><br>");
        }


        if (($customer->doesNotHaveCreditCardOnFile() && $customer->plan()->plan_id == 1) || $customer->plan()->plan_id == 1) {
            $go_pro_class    = "";
            $go_pro_text     = "Go <span class='go-pro label'>PRO</span>";
            $go_pro_tooltip  = "Upgrade to Tailwind PRO";
            $go_pro_link     = "href='/upgrade?ref=nav_trial_button'";
        } else {
            $go_pro_class    = "no-show";
            $go_pro_text     = "";
            $go_pro_tooltip  = "";
            $go_pro_link     = "";
        }



        $uri_pass = "" . $_SERVER['REQUEST_URI'] . "";
        if (strpos($uri_pass, "?")) {
            $get_params = true;
            $uri_pass   = substr($uri_pass, strpos($uri_pass, "?"));
        } else {
            $get_params = false;
        }


        if($cust_is_admin == "V"){
            $nav_add_user_link = "";
            $nav_add_competitor_link = "";
            $nav_add_account_link = "";
            $nav_show_add_account = "disabled";
            $nav_show_add_competitors = "disabled";
            $nav_add_user_show = "disabled";
        }

        return $this->_base_legacy_variables = array_merge(array(
            'conn'                               => $conn,
            'Pinterest'                          => new \Pinleague\Pinterest(),
            'cust_id'                            => $cust_id,
            'cust_accounts'                      => $cust_accounts,
            'cust_account_id'                    => $cust_account_id,
            'cust_account_name'                  => $cust_account_name,
            'cust_account_type'                  => $cust_account_type,
            'cust_account_track_type'            => $cust_account_track_type,
            'cust_org_id'                        => $cust_org_id,
            'cust_image'                         => $image,
            'cust_domain'                        => $cust_domain,
            'cust_user_id'                       => $cust_user_id,
            'cust_timestamp'                     => $cust_timestamp,
            'cust_source'                        => $cust_source,
            'cust_username'                      => $cust_username,
            'has_competitors'                    => $has_competitors,
            'cust_num_competitors'               => $cust_num_competitors,
            'days_of_calcs'                      => Session::get('days_of_calcs'),
            'fresh_account'                      => $fresh_account,
            'cust_account_age'                   => $cust_account_age,
            'cust_account_age_print'             => $cust_account_age_print,
            'has_ga'                             => @$has_ga,
            'cust_account_num'                   => $cust_account_num,
            'cust_is_admin'                      => $cust_is_admin,
            'cust_first_name'                    => $cust_first_name,
            'cust_last_name'                     => $cust_last_name,
            'cust_org_name'                      => $cust_org_name,
            'cust_email'                         => $cust_email,
            'cust_source'                        => $this->logged_in_customer->source,
            'is_multi_account'                   => $is_multi_account,
            'cust_accounts_count'                => $cust_accounts_count,
            'cust_type'                          => $cust_type,
            'cust_chargify_id'                   => $cust_chargify_id,
            'cust_org_type'                      => $cust_org_type,
            'cust_industry_id'                   => $cust_industry_id,
            'cust_industry'                      => $cust_industry,
            'customer'                           => $this->logged_in_customer,
            'cust_created_at'                    => $cust_account_created_at,
            'cust_plan'                          => $cust_plan_id,
            'cust_plan_id'                       => $cust_plan_id,
            'nav_show_add_account'               => $nav_show_add_account,
            'nav_add_account_locked'             => $nav_add_account_locked,
            'nav_add_account_link'               => $nav_add_account_link,
            'nav_add_account_jquery'             => $nav_add_account_jquery,
            'nav_competitors_enabled'            => $nav_competitors_enabled,
            'nav_competitors_locked'             => $nav_competitors_locked,
            'nav_show_add_competitors'           => $nav_show_add_competitors,
            'nav_add_competitor_link'            => $nav_add_competitor_link,
            'nav_comp_bench_jquery'              => $nav_competitor_benchmarks_jquery,
            'nav_traffic_jquery'                 => $nav_traffic_jquery,
            'nav_domain_jquery'                  => $nav_domain_jquery,
            'collaborator_popover'               => $collaborator_popover,
            'collaborator_tab_popover'           => $collaborator_tab_popover,
            'is_admin_account'                   => $this->isAdmin(),
            'nav_add_user_link'                  => $nav_add_user_link,
            'nav_add_user_show'                  => $nav_add_user_show,
            'nav_add_user_locked'                => $nav_add_user_locked,
            'nav_link_day_time'                  => $nav_link_day_time,
            'nav_link_influential_followers'     => $nav_link_influential_followers,
            'nav_link_traffic'                   => $nav_link_traffic,
            'nav_link_roi_pinners'               => $nav_link_roi_pinners,
            'nav_link_roi_pins'                  => $nav_link_roi_pins,
            'nav_link_website'                   => $nav_link_website,
            'nav_link_trending_pins'             => $nav_link_trending_pins,
            'nav_listening_pulse_class'          => $nav_listening_pulse_class,
            'nav_listening_insights_class'       => $nav_listening_insights_class,
            'nav_listening_trending_class'       => $nav_listening_trending_class,
            'nav_listening_most_repinned_class'  => $nav_listening_most_repinned_class,
            'nav_listening_most_liked_class'     => $nav_listening_most_liked_class,
            'nav_listening_most_commented_class' => $nav_listening_most_commented_class,
            'nav_domain_insights_class'          => $nav_domain_insights,
            'nav_domain_benchmarks_class'        => $nav_domain_benchmarks_class,
            'nav_domain_latest_class'            => $nav_domain_latest_class,
            'nav_domain_trending_images_class'   => $nav_domain_trending_images_class,
            'nav_domain_most_repinned_class'     => $nav_domain_most_repinned_class,
            'nav_domain_most_liked_class'        => $nav_domain_most_liked_class,
            'nav_domain_most_commented_class'    => $nav_domain_most_commented_class,
            'nav_domain_most_clicked_class'      => $nav_domain_most_clicked_class,
            'nav_domain_most_vists_class'        => $nav_domain_most_vists_class,
            'nav_domain_most_transactions_class' => $nav_domain_most_transactions_class,
            'nav_domain_most_revenue_class'      => $nav_domain_most_revenue_class,
            'nav_link_domain_benchmarks'         => $nav_link_domain_benchmarks,
            'nav_link_competitor_benchmarks'     => $nav_link_competitor_benchmarks,
            'nav_link_top_repinners'             => $nav_link_top_repinners,
            'nav_link_domain_pinners'            => $nav_link_domain_pinners,
            'nav_link_newest_followers'          => $nav_link_newest_followers,
            'nav_link_followers'                 => $nav_link_followers,
            'nav_link_viral_pins'                => $nav_link_viral_pins,
            'nav_profile_class'                  => $nav_profile_class,
            'nav_boards_class'                   => $nav_boards_class,
            'nav_website_class'                  => $nav_website_class,
            'nav_categories_class'               => $nav_categories_class,
            'nav_day_time_class'                 => $nav_day_time_class,
            'nav_influential_followers_class'    => $nav_influential_followers_class,
            'nav_traffic_class'                  => $nav_traffic_class,
            'nav_roi_pinners_class'              => $nav_roi_pinners_class,
            'nav_roi_pins_class'                 => $nav_roi_pins_class,
            'nav_competitor_benchmarks_class'    => $nav_competitor_benchmarks_class,
            'nav_top_repinners_class'            => $nav_top_repinners_class,
            'nav_domain_pinners_class'           => $nav_domain_pinners_class,
            'nav_newest_followers_class'         => $nav_newest_followers_class,
            'nav_followers_class'                => $nav_followers_class,
            'nav_viral_pins_class'               => $nav_viral_pins_class,
            'first_day'                          => $first_day,
            'uri_pass'                           => $uri_pass,
            'get_params'                         => $get_params,
            'listening_org_ids'                  => $listening_org_ids,
            'settings_add_user_show'             => $settings_add_user_show,
            'settings_add_user_link'             => $settings_add_user_link,
            'add_user_enabled'                   => $add_user_enabled,
            'go_pro_class'                       => $go_pro_class,
            'go_pro_text'                        => $go_pro_text,
            'go_pro_tooltip'                     => $go_pro_tooltip,
            'go_pro_link'                        => $go_pro_link
        ), $links);
    }

    /**
     * @author  Will
     */
    protected function buildFeatureKicker()
    {

        if ($this->logged_in_customer) {

            $legacy = $this->baseLegacyVariables();

            if ($legacy['is_admin_account']) {
                return '';
            }

            $vars = array(
                'username'        => $legacy['cust_username'],
                'email'           => $this->logged_in_customer->email,
                'name'            => $this->logged_in_customer->getName(),
                'cust_id'         => $this->logged_in_customer->cust_id,
                'account_id'      => $legacy['cust_account_id'],
                'org_id'          => $this->logged_in_customer->org_id,
                'plan'            => $this->logged_in_customer->organization()->plan,
                'account_type'    => $legacy['cust_account_type'],
                'website'         => $legacy['cust_domain'],
                'created_at'      => $legacy['cust_created_at'],
                'source'          => $this->logged_in_customer->source,
                'organization'    => $this->logged_in_customer->organization()->org_name,
                'industry'        => $legacy['cust_industry'],
                'competitors'     => $legacy['cust_num_competitors'],
                'analytics'       => $this->logged_in_customer->organization()->hasGoogleAnalytics('string'),
                'followers'       => Session::get('follower_count'),
                'profile_pins'    => Session::get('total_profile_pins'),
                'competitor_pins' => Session::get('total_competitor_pins'),
                'total_pins'      => Session::get('total_pins'),
                'trial_end_date'  => $this->logged_in_customer->trialEndDate()
            );

            return View::make('analytics.components.pre_body_close.feature_kicker', $vars);

        }

        return '';
    }

    /**
     * @author  Will
     *
     */
    protected function buildInclude($type)
    {

        asort($this->layout_defaults[$type]);

        $html = '';

        foreach ($this->layout_defaults[$type] as $file => $status) {
            if ($status) {
                switch ($file) {
                    default:
                        $html .= View::make('analytics.components.' . $type . '.' . $file
                            , $this->baseLegacyVariables());
                        break;

                    case 'segmentio':
                    case 'olark':
                        $html .= View::make(
                                     'components::' . $type . '.' . $file,
                                     [
                                     'user'                => $this->logged_in_customer,
                                     'active_user_account' => $this->active_user_account,
                                     'is_demo'             => $this->isDemo(),
                                     'is_admin'             => $this->isAdmin(),
                                     ]
                        );
                        break;
                    case 'feature_kicker':
                        $html .= $this->buildFeatureKicker();
                        break;
                }
            }

        }

        return $html;
    }

    /**
     * @author  Alex
     */
    protected function buildPreNav()
    {

        if ($this->logged_in_customer) {

            if ($this->isDemo()) {
                return View::make(
                           'components::pre_nav.demo_bar',
                           [
                           'username' => $this->parent_active_user_account->username,
                           'profile_image' => $this->parent_active_user_account->profile()->image,
                           'email' => $this->parent_customer->email,
                           'pins' => $this->parent_active_user_account->profile()->getDBPins(12),
                           'plan'                 => $this->isDemo(),
                           'is_incomplete_signup' => $this->isIncompleteSignup()
                           ]
                );
            }

            $legacy = $this->baseLegacyVariables();
            extract($legacy);

            /** @var $customer User */
            if ($customer->hasCreditCardOnFile()) {

                $sub = $customer->organization()->subscription();

                $token                = sha1('update_payment--' . $sub->id . '--QRx_0FzJtvqT30dAQFd9');

                $cust_update_billing_link = "<a target='_blank' class='label label-info'
                                   href='https://tailwind.chargify.com/update_payment/"
                    . $sub->id . "-" . $sub->customer->first_name . "-"
                    . $sub->customer->last_name . "/" . substr($token, 0, 12) . "'>
                                       Update Billing Profile →
                                    </a>";

                if ($sub->state == "past_due") {
                    $state_style = "label-important";

                    $top_bar_alert_message = "<i class='icon-new'></i>
                        <strong>Whoops!</strong>
                        <span>There was a problem processing your credit card..</span>
                        <span class=''>
                            To continue your service, please verify the credit card on your account:
                        </span>
                        $cust_update_billing_link";
                    $show_top_bar_alert    = "";
                    $top_bar_alert_js      = "
                        <script>
                            $(document).ready(function(){
                                $('.wrapper').css('top','40px');
                            })
                        </script>";
                    $top_bar_alert_class   = "error";

                } else {
                        $top_bar_alert_message = "";
                        $show_top_bar_alert    = "display:none;";
                        $top_bar_alert_js      = "";
                        $top_bar_alert_class   = "";
                }

            } else {
               
            }

            $vars = array_merge(
                $this->baseLegacyVariables(),
                array(
                     'top_bar_alert_message' => $top_bar_alert_message,
                     'top_bar_alert_class'   => $top_bar_alert_class,
                     'top_bar_alert_js'      => $top_bar_alert_js,
                     'show_top_bar_alert'    => $show_top_bar_alert,
                     'state_style'           => $state_style
                )
            );

            return View::make('analytics.components.pre_nav.top_bar_alert', $vars);
        }

        return '';
    }

    /**
     * @author  Alex
     */
    protected function buildPreNavOverlay()
    {

        if ($this->logged_in_customer) {
            if (!$this->logged_in_customer->isDashboardReady()) {

                $html = View::make('analytics.components.pre_nav.loading_overlay', $this->baseLegacyVariables());
                $html .= View::make('analytics.components.pre_nav.loading_overlay_script', $this->baseLegacyVariables());

                return $html;
            }
        }

        return '';
    }

    /**
     * @author  Will
     */
    protected function buildSideNavigation($page = '')
    {
        if (!$this->logged_in_customer) {
            return '';
        }
        $legacy = $this->baseLegacyVariables();
        extract($legacy);

        $image = '';

        if (Session::has('image')) {
            $image = Session::get('image');
        }

        $upgrade_button_class    = "no-show";
        $upgrade_button_text     = "";
        $upgrade_button_sub_text = "";
        $upgrade_button_link     = "";

        $nav_menu_listening = "";
        $nav_menu_domains   = "";
        $nav_menu_profile   = "";
        $nav_menu_community = "";
        $nav_menu_optimize  = "";
        $nav_menu_publisher = "";

        /*
         * Set which section of the navigation should be expanded
         */
        switch ($page) {
            case "profile":
            case "boards":
            case "competitor_benchmarks":
                $nav_menu_profile = "in";
                break;

            case "listening-pulse":
            case "listening-insights":
            case "listening-trending":
            case "listening-most-repinned":
            case "listening-most-liked":
            case "listening-most-commented":
                $nav_menu_listening = "in";
                break;

            case "domain-insights":
            case "domain-latest":
            case "domain-trending-images":
            case "domain-most-repinned":
            case "domain-most-liked":
            case "domain-most-commented":
            case "domain-most-clicked":
            case "domain-most-visits":
            case "domain-most-transactions":
            case "domain-most-revenue":
            case "domain-benchmarks":
            case "traffic":
                $nav_menu_domains = "in";
                break;

            case "influential_followers":
            case "newest_followers":
                $legacy['nav_followers_class'] = ' active';
                $nav_menu_community = "in";
                break;

            case "domain_pinners":
            case "top_repinners":
            case "roi_pinners":
                $nav_menu_community = "in";
                break;

            case "pins":
            case "viral_pins":
            case "roi_pins":
            case "categories":
            case "day_time":
                $nav_menu_optimize = "in";
                break;

            case 'publisher-schedule':
            case 'publisher-posts-drafts':
            case 'publisher-posts-scheduled':
            case 'publisher-posts-published':
            case 'publisher-tools':
            case 'publisher-permissions':
                $nav_menu_publisher = 'in';
                $legacy['nav_schedule_class'] = ' active';
                break;

            case 'content-feed':
                $nav_menu_publisher = 'in';
                $legacy['nav_content_class'] = ' active';
                break;

            default:
                break;
        }

        if ($this->logged_in_customer) {

            /*
             * Handle the multi-account section
             * Check to see if the logged in user has multiple accounts and has access to them
             * by being in a higher tier plan than free.
             */
            if ($is_multi_account && $customer->plan()->plan_id > 1) {

                $uri_pass_account = '';
                $uri_pass = "" . $_SERVER['REQUEST_URI'] . "";
                if (strpos($uri_pass, "?")) {
                    $get_params = true;
                    if (strpos($uri_pass, "&account=") != 0) {
                        $uri_pass_account = substr($uri_pass, 0, strpos($uri_pass, "&account="));
                    } elseif (strpos($uri_pass, "?account=") != 0) {
                        $uri_pass_account = substr($uri_pass, 0, strpos($uri_pass, "?account="));
                        $get_params       = false;
                    } else {
                        $uri_pass_account = $uri_pass;
                    }
                } else {
                    $get_params = false;
                }

                $multi_account_active_class = 'active';
                $account_action = "<div class='caret-down'>
                                        <i class='icon-caret-down'></i>
                                   </div>";

                /*
                 * If the user is only a viewer, they will not have ability to add accounts and
                 * should not be able to click on the add account link
                 */
                if ($cust_is_admin == "V") {
                    $nav_show_add_account = "inactive disabled";
                    $nav_add_account_link = "data-toggle='tooltip'
                                         data-placement='right'
                                         data-container='body'
                                         data-title='Only Admin users have access to add
                                         additional accounts'";
                }



                $multi_account_section = '<ul class="dropdown-menu pull-right"
                                              role="menu"
                                              aria-labelledby="account-dropdown">
                                              <li class="dropdown-menu-title">Change Accounts:</li>';
                $multi_account_counter = 0;
                foreach ($cust_accounts as $aa) {

                    if ($aa['username'] != '' && $aa['username'] != $cust_username) {
                        $multi_account_section .= "<li role='presentation' class='account-dropdown'>
                                                <a role='menuitem' href='";

                        if ($get_params) {
                            $multi_account_section .= $uri_pass_account . "&account=" . $multi_account_counter . "'><img class='account-dropdown-user-image' src='" . $aa['image'] . "'> " . $aa['username'];
                        } else {
                            $multi_account_section .= $uri_pass_account . "?account=" . $multi_account_counter . "'><img class='account-dropdown-user-image' src='" . $aa['image'] . "'> " . $aa['username'];
                        }

                        $multi_account_section .= "  </a>
                                               </li>";
                    }

                    $multi_account_counter++;
                }

                $multi_account_section .=
                    "<li role='presentation' class='$nav_show_add_account account-dropdown'>
                        <a role='menuitem' $nav_add_account_link class='dropdown-add-account'>
                            <i class='icon-plus'></i> Add / Manage Accounts
                        </a>
                    </li>
                </ul>";
                $multi_account_js = "";


            } else {

                /*
                 * If user does not have multiple accounts, we'll add a button to let them add new
                 * accounts, and disable it if they are not on a paid plan (that logic is handled
                 * above with user->has_feature('nav_add_account')
                 */
                $account_action = "<div class='pull-left'>
                                <span id='nav-add-account-wrapper'
                                      data-toggle='tooltip'
                                      data-container='body'
                                      data-title='Add an Additional Account''
                                      data-placement='right'
                                      class='pull-left btn $nav_show_add_account'
                                      style='margin:0px;'>
                                        <a id='nav-add-account'
                                           class='$nav_show_add_account'
                                           $nav_add_account_link>
                                            <i class='icon-plus'></i>
                                        </a>
                                </span>
                               </div>";
                $multi_account_active_class = '';
                $multi_account_section = '';
                $multi_account_js = "<div class='clearfix'></div>
                                    <script type='text/javascript'>
                                        $(document).ready(function() { $nav_add_account_jquery });
                                    </script>";
            }

            $vars = array_merge($legacy,
                array(
                     'multi_account_active_class' => $multi_account_active_class,
                     'account_action'             => $account_action,
                     'multi_account_section'      => $multi_account_section,
                     'multi_account_js'           => $multi_account_js,
                     'plan_badge'                 => ($this->logged_in_customer->plan()->name == "Agency" ? "Enterprise" : $this->logged_in_customer->plan()->name)
                )
            );

            $account_select = View::make('analytics.components.account_select', $vars);
        }

        $content_enabled   = false;
        $publisher_enabled = false;
        $listening_enabled = false;

        if ($this->logged_in_customer) {
            $content_enabled   = $this->logged_in_customer->hasFeature('content_enabled');
            $publisher_enabled = $this->logged_in_customer->hasFeature('pin_scheduling_enabled');
            $listening_enabled = $this->logged_in_customer->hasFeature('listening_enabled');
        }

        $vars = array_merge($legacy, array(
            'upgrade_button_link'     => $upgrade_button_link,
            'upgrade_button_text'     => $upgrade_button_text,
            'upgrade_button_class'    => $upgrade_button_class,
            'upgrade_button_sub_text' => $upgrade_button_sub_text,
            'menu_bottom_js'          => $menu_bottom_js,
            'cust_image'              => $image,
            'account_select'          => $account_select,
            'nav_menu_profile'        => $nav_menu_profile,
            'nav_menu_listening'      => $nav_menu_listening,
            'nav_menu_domains'        => $nav_menu_domains,
            'nav_menu_community'      => $nav_menu_community,
            'nav_menu_optimize'       => $nav_menu_optimize,
            'nav_menu_publisher'      => $nav_menu_publisher,
            'content_enabled'         => $content_enabled,
            'listening_enabled'       => $listening_enabled,
            'publisher_enabled'       => $publisher_enabled,
        ));


        $nav_class = "nav_" . str_replace('-', '_', $page) . "_class";

        if (array_key_exists($nav_class, $vars)) {
            $vars[$nav_class] .= ' active';
        }

        return View::make('analytics.components.side_nav', $vars);

    }

    /**
     * @author  Will
     */
    protected function buildTopNavigation()
    {
        if (!$this->logged_in_customer) {
            return '';
        }

        if ($this->logged_in_customer->is_admin == User::PERMISSIONS_VIEWER) {
            $view = 'viewer_settings_drop_down';
        } else {
            $view = 'settings_drop_down';
        }

        $notice = '';

        if ($this->logged_in_customer->getFeature('side_nav_version')->value == 2) {
            $notice = View::make('analytics.components.alerts.request_new_tailwind');
        }

        $admin_dropdown = '';

        if ($this->isAdmin()) {

            $DBH = DatabaseInstance::DBO();
            $STH = $DBH->query(
                       "SELECT
                           users.cust_id,
                           users.org_id,
                           user_accounts.username,
                           user_accounts.account_id,
                           user_organizations.plan
                       FROM
                           users
                       JOIN
                           user_accounts
                       ON
                           user_accounts.org_id = users.org_id
                       RIGHT JOIN
                           user_organizations
                       ON
                           users.org_id = user_organizations.org_id
                       WHERE user_accounts.username != ''
                       ORDER BY user_accounts.username
                       LIMIT 20
                   ");

            $admin_dropdown = View::make('components::admin_dropdown',
                                         [
                                         'accounts'       => $STH->fetchAll(),
                                         'user'           => $this->logged_in_customer,
                                         'active_account' => $this->active_user_account
                                         ]
            )->render();
        }

        $vars = array(
            'page'              => $this->layout_defaults['page'],
            'top_nav_title'     => $this->layout_defaults['top_nav_title'],
            'notice'            => $notice,
            'admin_dropdown'    => $admin_dropdown,
            'add_dropdown'      => View::make(
                                       'analytics.components.add_drop_down',
                                       $this->baseLegacyVariables()
                ),
            'settings_dropdown' => View::make('analytics.components.' . $view,
                                              array(
                                                   'page'                    => $this->layout_defaults['page'],
                                                   'has_credit_card_on_file' => $this->logged_in_customer->hasCreditCardOnFile(),
                                                   'cust_plan'               => $this->logged_in_customer->plan()->plan_id,
                                                   'able_to_add_users'       => true,
                                                   'has_competitors'         => $this->logged_in_customer->organization()->hasCompetitors(),
                                                   'nav_competitors_enabled' => $this->logged_in_customer->hasFeature('nav_comp_bench')
                                              )),
            'help_dropdown'     => View::make('analytics.components.help_drop_down'),
        );

        $combined = array_merge($vars, $this->baseLegacyVariables());

        if ($this->isDemo()) {
            return View::make('analytics.components.top_demo_nav', $combined);
        }

        return View::make('analytics.components.top_nav', $combined);
    }

    /**
     * Add a css file to header
     *
     * @param       $file
     * @param array $data
     *
     * @return $this
     */
    protected function css($file, $data = array())
    {
        $this->__addAsset(
             'head',
             'css',
             $file,
             $data,
             'end'
        );

        return $this;
    }

    /**
     * Add a js file to pre body close
     *
     * @param       $file
     * @param array $data
     *
     * @return $this
     */
    protected function js($file, $data = array())
    {
        $this->__addAsset(
             'pre_body_close',
             'js-link',
             $file,
             $data,
             'end'
        );

        return $this;
    }

    /**
     * @param       $file
     * @param array $data
     *
     * @return $this
     */
    protected function jsSnippet($file, $data = array())
    {
        $this->__addAsset(
             'pre_body_close',
             'js',
             $file,
             $data,
             'next'
        );

        return $this;
    }

    /**
     * @author  Will
     *
     * @param $where
     * @param $type
     * @param $file
     * @param $data
     * @param $position
     *
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    private function __addAsset($where, $type, $file, $data, $position)
    {
        if (!$this->layout->$where instanceof Views) {
            throw new InvalidArgumentException(
                "$where is not a Views object, and therefore can't have assets" .
                " have assets added to it"
            );
        }

        $data['src']        = $file;
        $data['attributes'] = array_get($data, 'attributes', '');

        switch ($type) {
            default:
                throw new InvalidArgumentException(
                    "$type is not a valid asset type"
                );
                break;

            case 'js':

                $this->layout->$where->insert(
                     View::make('shared.components.js_link', $data),
                     $position
                );
                break;

            case 'css':

                $this->layout->$where->insert(
                     View::make('shared.components.stylesheet_link', $data),
                     $position
                );
                break;

            case 'js-tag':
            case 'css-tag':
            case 'js-onload':
                throw new \Exception('This hasnt been build yet');
                break;

            case 'string':

                $this->layout->$where->insert(
                     $file,
                     $position
                );
                break;
        }
    }
    /**
     * Alias for setting the main content variable
     *
     * @param       $view
     * @param array $data
     *
     * @return $this
     */
    protected function mainContent($view, $data = array())
    {
        $this->layout->main_content = View::make($view, $data);

        return $this;
    }

    /**
     * Setup the layout used by the controller.
     *
     * @return void
     */
    protected function setupLayout()
    {
        if (!is_null($this->layout)) {
            $this->layout                 = View::make($this->layout);
            $this->layout->head           = new Views;
            $this->layout->pre_body_close = new Views;
        }

        /*
         * We need to include a TRADA tracking pixel for new signups
         * This helps see if it's a new signup or not
         */
        if (Session::has('new_signup')) {
            $this->layout->trada =
                View::make('analytics.components.trada_pixel',['cust_id'=>$this->logged_in_customer->cust_id])->render();
        }

        $this->layout->loading_overlay = $this->buildPreNavOverlay();
        $this->layout->top_bar_alert   = $this->buildPreNav();
        $this->layout->head            = $this->buildInclude('head');
        $this->layout->alert           = $this->generateAlertBox();
        $this->layout->top_navigation  = $this->buildTopNavigation();
        $this->layout->side_navigation = $this->buildSideNavigation();
        $this->layout->pre_body_close  = $this->buildInclude('pre_body_close');

    }

    /**
     *
     * @param null $plan
     *
     * @return bool|mixed
     */
    protected function isDemo($plan = null)
    {
        if (!Session::has('demo_enabled')) {
            /*
             * If there is an incomplete signup, we need to set it into
             * demo as a default
             */
            if(Session::has('incomplete_signup')) {
                Session::put('demo_enabled',Plan::PRO);
            } else {
                return false;
            }
        }

        if (is_null($plan)) {
            return Session::get('demo_enabled');
        }

        if (Session::get('demo_enabled') == $plan) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function isAdmin() {
        return $this->is_admin_account;
    }

    /**
     * @return bool|mixed
     */
    protected function isIncompleteSignup() {
        if (Session::has('incomplete_signup')) {
            return Session::get('incomplete_signup');
        }
        return false;
    }
}