<?php namespace Analytics;

use View,
    Log,
    Redirect,
    Session,
    UserHistory,
    CategoryFootprint;

/**
 * Class InfluencersController
 *
 * @package Analytics
 */
class InfluencersController extends BaseController
{
    protected $layout = 'layouts.analytics';

    /**
     * Construct
     * @author  Will
     */
    public function __construct() {

        parent::__construct();

        Log::setLog(__FILE__,'Reporting','Influencers_Report');
    }

    public function downloadInfluencers($type, $vars)
    {

        extract($vars);

        //$html = View::make('analytics.pages.influencers', $vars);

        $date = date("F-j-Y");

        $this->logged_in_customer->recordEvent(
                                 UserHistory::EXPORT_REPORT,
                                     $parameters = array(
                                         'report' => 'followers',
                                     )
        );

        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"Tailwind-Analytics-Influencer-Data-$type-$date.csv\"");
        if (isset($_GET['csv'])) {

            if ($type == 'domain-pinners') {
                echo "display_name,username,website,facebook,twitter,location,follows_you,domain_mentions,followers,potential_impressions" .
                    ($has_analytics ? ",revenue" : "") . "\n";
                usort($data_calced, function ($a, $b) {
                    $t = "pins";

                    if ($a["$t"] < $b["$t"]) {
                        return 1;
                    } else if ($a["$t"] == $b["$t"]) {
                        return 0;
                    } else {
                        return -1;
                    }
                });
            } else if ($type == 'followers/influential') {
                echo "display_name,username,website,facebook,twitter,location,followers" .
                    ($has_analytics ? ",revenue" : "") . "\n";
                usort($data_calced, function ($a, $b) {
                    $t = "followers";

                    if ($a["$t"] < $b["$t"]) {
                        return 1;
                    } else if ($a["$t"] == $b["$t"]) {
                        return 0;
                    } else {
                        return -1;
                    }
                });
            } else if ($type == 'followers/newest') {
                echo "display_name,username,website,facebook,twitter,location,followers" .
                    ($has_analytics ? ",revenue" : "") . "\n";
                usort($data_calced, function ($a, $b) {
                    $t = "followers";

                    if ($a["$t"] < $b["$t"]) {
                        return 1;
                    } else if ($a["$t"] == $b["$t"]) {
                        return 0;
                    } else {
                        return -1;
                    }
                });
            } else if ($type == 'Influence') {
                echo "display_name,username,website,facebook,twitter,location,follows_you,followers,pins,influence" .
                    ($has_analytics ? ",revenue" : "") . "\n";
                usort($data_calced, function ($a, $b) {
                    $t = "influence";

                    if ($a["$t"] < $b["$t"]) {
                        return 1;
                    } else if ($a["$t"] == $b["$t"]) {
                        return 0;
                    } else {
                        return -1;
                    }
                });
            } else if ($type == 'most-valuable-pinners') {
                echo "display_name,username,website,facebook,twitter,location,follows_you,visits,conversions,revenue,followers" . "\n";

                usort($data_calced, function ($a, $b) {

//                    if ($revenue_sort == 'revenue') {
//                        $t = 'revenue';
//                    } else if ($revenue_sort == 'transactions') {
//                        $t = 'conversions';
//                    } else if ($revenue_sort == 'visits') {
//                        $t = 'visits';
//                    }

                    $t = 'revenue';

                    if ($a["$t"] < $b["$t"]) {
                        return 1;
                    } else if ($a["$t"] == $b["$t"]) {
                        return 0;
                    } else {
                        return -1;
                    }
                });
            } else if ($type == 'top-repinners') {
                echo "display_name,username,website,facebook,twitter,location,follows_you,repins,followers,potential_impressions" .
                    ($has_analytics ? ",revenue" : "") . "\n";
                usort($data_calced, function ($a, $b) {
                    $t = "repins";

                    if ($a["$t"] < $b["$t"]) {
                        return 1;
                    } else if ($a["$t"] == $b["$t"]) {
                        return 0;
                    } else {
                        return -1;
                    }
                });
            }

            $rank = 1;
            foreach ($data_calced as $data) {

                if ($rank > 100) {
                    break;
                }

                $follower_image        = $data['image'];
                $follower_username     = $data['username'];
                $follower_username_pin = "http://pinterest.com/" . $follower_username;

                $follower_user_id = $data['user_id'];

                $follower_display_name = str_replace("'", "", $data['display_name']);

                if (strlen($follower_display_name) == 0 || $follower_display_name == "Unauthorized") {
                    $follower_display_name = $data['username'];
                }

                $follower_is_following   = 0;
                $follower_followers      = trim(($data['followers']),",");
                $follower_pins           = trim(($data['pins']),",");
                @$follower_repins        = trim(($data['repins']),",");
                $follower_influence      = trim(($data['influence']),",");
                $follower_location_city  = $data['location_city'];
                $follower_location_state = $data['location_state'];
                $follower_website        = $data['website'];

                $revenue     = $data['revenue'];
                @$visits      = $data['visits'];
                @$conversions = $data['conversions'];

                if (!($data['facebook'] == '')) {
                    $follower_facebook = $data['facebook'];
                    $follower_facebook = str_replace("http://facebook.com/", "", $follower_facebook);
                    $follower_facebook = "http://facebook.com/" . $follower_facebook;
                } else {
                    $follower_facebook = $data['facebook'];
                }

                if (!($data['twitter'] == '')) {
                    $follower_twitter = $data['twitter'];
                    $follower_twitter = "http://twitter.com/" . $follower_twitter;
                } else {
                    $follower_twitter = $data['twitter'];
                }

                $follower_website = str_replace("http://www.", "", $follower_website);
                $follower_website = str_replace("http://", "", $follower_website);


                if(isset($follower_user_id)){
                    $acc2 = "select * from data_followers where user_id=$cust_user_id
                                    and follower_user_id=$follower_user_id";
                    $acc2_res = mysql_query($acc2, $conn) or die(mysql_error());
                    while ($b = mysql_fetch_array($acc2_res)) {
                        $follower_is_following = 1;
                    }
                }

                if ($follower_is_following == 1) {
                    $follows_you = "yes";
                } else {
                    $follows_you = "no";
                }


                if ($type == 'domain-pinners') {
                    echo "\"$follower_display_name\",$follower_username_pin,$follower_website,$follower_facebook,$follower_twitter,\"$follower_location_city\",$follows_you,$follower_pins,$follower_followers,$follower_influence" . ($has_analytics ? ",$revenue" : "") . "\n";
                } else if ($type == 'followers/influential') {
                    echo "\"$follower_display_name\",$follower_username_pin,$follower_website,$follower_facebook,$follower_twitter,\"$follower_location_city\",$follower_followers" . ($has_analytics ? ",$revenue" : "") . "\n";
                } else if ($type == 'followers/newest') {
                    echo "\"$follower_display_name\",$follower_username_pin,$follower_website,$follower_facebook,$follower_twitter,\"$follower_location_city\",$follower_followers" . ($has_analytics ? ",$revenue" : "") . "\n";
                } else if ($type == 'Influence') {
                    echo "\"$follower_display_name\",$follower_username_pin,$follower_website,$follower_facebook,$follower_twitter,\"$follower_location_city\",$follows_you,$follower_followers,$follower_pins,$follower_influence" . ($has_analytics ? ",$revenue" : "") . "\n";
                } else if ($type == 'most-valuable-pinners') {
                    echo "\"$follower_display_name\",$follower_username_pin,$follower_website,$follower_facebook,$follower_twitter,\"$follower_location_city\",$follows_you,$visits,$conversions,$revenue,$follower_followers" . "\n";
                } else if ($type == 'top-repinners') {
                    echo "\"$follower_display_name\",$follower_username_pin,$follower_website,$follower_facebook,$follower_twitter,\"$follower_location_city\",$follows_you,$follower_repins,$follower_followers,$follower_influence" . ($has_analytics ? ",$revenue" : "") . "\n";
                }

                $rank++;
            }
        }
        exit;
    }

    public function prepInfluencersPage(){

        $vars         = $this->baseLegacyVariables();
        extract($vars);

        if ($customer->hasFeature('fans_10_users')) {
            $fans_limit = "limit 10";
            $rank_limit = 10;
        } else {
            $fans_limit = "limit 100";
            $rank_limit = 100;
        }

        if($customer->hasFeature('newest_followers_10')){
            $fans_limit = "limit 10";
            $rank_limit = 10;
        } else {
            $fans_limit = "limit 100";
            $rank_limit = 100;
        }

        if (!$customer->hasFeature('nav_fans')) {
            $popover_custom_date = createPopover("#reportrange","click","bottom","<span class=\"text-success\"><strong>Upgrade to Unlock Time Periods</strong></span>","influencer_date_range",
                $customer->plan()->plan_id,"<ul><li>See Trending Domain Pinners and Top Repinners over specific time periods</li><li>See who has been most active over the last 7, 14 or 30 days</li></ul>");
            $popover_report_toggle = createPopover("#influential-followers-toggle-btn","click","bottom","<span class=\"text-success\"><strong>Upgrade to Unlock the Influential Followers Report</strong></span>","influential_followers_toggle",
                $customer->plan()->plan_id,"<ul><li>Find out which of your followers are most influential.</li><li>Find out the most active areas of interest across your Community.</li></ul>");
        } else {
            $popover_custom_date = "";
            $popover_report_toggle = "";
        }




        if ($customer->hasFeature('top_pinners_following')) {
            $show_top_pinners_following = true;
        } else {
            $show_top_pinners_following = false;
        }

        if ($customer->hasFeature('nav_roi_pinners')) {
            $nav_roi_pinners = true;
        } else {
            $nav_roi_pinners = false;
        }

        if ($customer->hasFeature('top_pinners_export')) {
            if (strpos($_SERVER['REQUEST_URI'], '?')){
                $csv_url = "href='" . $_SERVER['REQUEST_URI'] . "&csv=1'";
            } else {
                $csv_url = "href='" . $_SERVER['REQUEST_URI'] . "?csv=1'";
            }
            $export_class = "";
            $export_popover = "";
        } else {
            $csv_url = "";
            $export_class = "disabled";
            $export_popover = createPopover(".influencers-export","hover","bottom","<span class=\"text-success\"><strong>Need to Export your Data?</strong></span>","influencer_export",
                $customer->plan()->plan_id,"Upgrade to enable exporting data across your dashboard.<ul><li><strong>Instantly download CSV files</strong> of any report</li><li><strong>Take your data with you</strong> anywhere it needs to go!</li></ul>");
        }

        if ($customer->hasFeature('category_footprint')) {
            $show_category_footprint = true;
        } else {
            $show_category_footprint = false;
        }

        if(isset($_GET['range'])){
            $range = $_GET['range'];
            if (!$range) {
                $range = "alltime";
            }
        } else {
            $range = "alltime";
        }

        if(isset($_GET['type'])){
            $type = $_GET['type'];
            if (!$type) {
                $type = "newest-followers";
            }
        } else {
            $type = "newest-followers";
        }

        if($range=="alltime"){
            $datepicker_show = "All-Time";
            $period = 0;
        } else if($range=="7days"){
            $datepicker_show = "Last 7 Days";
            $period = 7;
        } else if($range=="14days"){
            $datepicker_show = "Last 14 Days";
            $period = 14;
        } else if($range=="30days"){
            $datepicker_show = "Last 30 Days";
            $period = 30;
        } else if($range=="60days"){
            $datepicker_show = "Last 60 Days";
            $period = 60;
        }

        $uri_pass = "" .$_SERVER['REQUEST_URI']. "";

        if(strpos($uri_pass,"?")){
            $get_params = true;
            $uri_pass = substr($uri_pass, strpos($uri_pass, "?"));
        } else {
            $get_params = false;
        }

        if($get_params){
            $alltime_link = "href='/" . $type . $uri_pass . "&range=alltime'";
        } else {
            $alltime_link = "href='/$type?range=alltime'";
        }


        if ($customer->hasFeature('top_pinners_7_days')) {
            $range7_show = "";
            if($get_params){
                $range7_link = "href='/" . $type . $uri_pass . "&range=7days'";
            } else {
                $range7_link = "href='/$type?range=7days'";
            }
        } else {
            $range7_show = "inactive";
            $range7_link = "";
            $range = "alltime";
        }

        if ($customer->hasFeature('top_pinners_14_days')) {
            $range14_show = "";
            if($get_params){
                $range14_link = "href='/" . $type . $uri_pass . "&range=14days'";
            } else {
                $range14_link = "href='/$type?range=14days'";
            }
        } else {
            $range14_show = "inactive";
            $range14_link = "";
            $range = "alltime";
        }

        if ($customer->hasFeature('top_pinners_30_days')) {
            $range30_show = "";
            $range60_show = "";
            if($get_params){
                $range30_link = "href='/" . $type . $uri_pass . "&range=30days'";
                $range60_link = "href='/" . $type . $uri_pass . "&range=60days'";
            } else {
                $range30_link = "href='/$type?range=30days'";
                $range60_link = "href='/$type?range=60days'";
            }
        } else {
            $range30_show = "inactive";
            $range60_show = "inactive";
            $range30_link = "";
            $range60_link = "";
            $range = "alltime";
        }


        if ($this->isDemo()) {
            $has_analytics = $this->logged_in_customer->organization()->hasGoogleAnalytics();
        } else {
            $has_analytics = Session::get('has_analytics');
        }

        $domainString = '';

        if(count($cust_accounts[$cust_account_num]['domains']) > 0 ){
            foreach ($cust_accounts[$cust_account_num]['domains'] as $domain) {
                $domainString .=  "'".$domain."', ";
            }


            $domainString = rtrim($domainString,', ');

        }

        $total_brand_mention_pins=0;

        $influencer_vars = array(
            'fans_limit' => $fans_limit,
            'rank_limit' => $rank_limit,
            'show_top_pinners_following' => $show_top_pinners_following,
            'nav_roi_pinners' => $nav_roi_pinners,
            'csv_url' => $csv_url,
            'export_class' => $export_class,
            'export_popover' => $export_popover,
            'range' => $range,
            'inf_pill' => $inf_pill,
            'pin_pill' => $pin_pill,
            'fol_pill' => $fol_pill,
            'new_pill' => $new_pill,
            'rep_pill' => $rep_pill,
            'rev_pill' => $rev_pill,
            'type' => $type,
            'datepicker_show' => $datepicker_show,
            'period' => $period,
            'uri_pass' => $uri_pass,
            'get_params' => $get_params,
            'alltime_link' => $alltime_link,
            'range7_show' => $range7_show,
            'range7_link' => $range7_link,
            'popover_custom_date' => $popover_custom_date,
            'popover_report_toggle' => $popover_report_toggle,
            'range14_show' => $range14_show,
            'range14_link' => $range14_link,
            'range30_show' => $range30_show,
            'range30_link' => $range30_link,
            'range60_link' => $range60_link,
            'range60_show' => $range60_show,
            'has_analytics' => $has_analytics,
            'domainString' => $domainString,
            'total_brand_mention_pins' => $total_brand_mention_pins,
            'show_category_footrpint' => $show_category_footprint,
            'current_date'            => flat_date('day')
        );

        return $influencer_vars;
    }

    public function showInfluentialFollowers()
    {
        $vars = $this->baseLegacyVariables();
        $_GET['type'] = "followers/influential";
        $vars = array_merge($vars, $this->prepInfluencersPage());

        /*
         * Redirect to upgrade page if feature not available
         */
        extract($vars);
        if(!$customer->hasFeature('nav_fans')){
            return Redirect::to("/upgrade?ref=followers&plan=" . $customer->plan()->plan_id . "");
        }


        /*
         * Prepare data for top followers table
         */
        $fol_pill = "class=\"active\"";


        /*
         * Prepare query for getting cached results
         */
        $acc = "select influencer_user_id, influencer_username as username, first_name,last_name,
        follower_count, pin_count, image, location, website, facebook, twitter
		from cache_profile_influencers
		where user_id = $cust_user_id
		order by follower_count DESC $fans_limit";

        /*check to see whether to enable the top pinners menu item
        / (check if there is are any domains associated with this account first, and if so,
        / check to see if there are any pins associated with these domains.
        */
        if(count($cust_accounts[$cust_account_num]['domains']) > 0 ){
            $acc2 = "select influencer_user_id, username, first_name, last_name, follower_count, domain_mentions,
            image, location, website, facebook, twitter from cache_domain_influencers
            where domain in ($domainString)
            and period=0
            order by domain_mentions DESC limit 1";
            $acc_res = mysql_query($acc2,$conn) or die(mysql_error() . " Line: " . __LINE__);
            while ($a = mysql_fetch_array($acc_res)) {
                $total_brand_mention_pins = $a['domain_mentions'];
            }
        } else {
            $total_brand_mention_pins = 0;
        }

        /*
         * Pull all data for the top followers table
         */
        $influencer_string = array();

        $acc_res = mysql_query($acc, $conn) or die(mysql_error() . " Line: " . __LINE__);
        while ($a = mysql_fetch_array($acc_res)) {
            $username     = strtolower($a['username']);
            $display_name = $a['first_name'] . " " . $a['last_name'];

            if (isset($a['domain_mentions'])) {
                $pins = $a['domain_mentions'];
            }

            $followers = $a['follower_count'];

            if (isset($a['influence'])) {
                $influence = $a['influence'];
            } else {
                if (!isset($pins) || $pins == 0) {
                    $influence = $followers;
                } else {
                    $influence = $followers * $pins;
                }
            }

            $image          = $a['image'];
            $location_city  = $a['location'];
            $location_state = '';
            $website        = $a['website'];
            $facebook       = $a['facebook'];
            $twitter        = $a['twitter'];
            if(!empty($facebook)){
                $facebook = str_replace("http://facebook.com/", "", $facebook);
                $facebook = "http://facebook.com/" . $facebook;
            }

            if(!empty($twitter)){
                $twitter = "http://twitter.com/" . $twitter;
            }

            if(!empty($website)){
                $website = str_replace("http://www.", "", $website);
                $website = str_replace("http://", "", $website);
                if (strlen($website) > 35) {
                    $website_print = substr($website, 0, 35) . "..";
                } else {
                    $website_print = $website;
                }
            }


            if (isset($a['revenue'])) {
                $revenue = $a['revenue'];
            }
            $influencer_user_id = $a['influencer_user_id'];
            array_push($influencer_string, $influencer_user_id);

            $data_calced["$influencer_user_id"] = array();
            if (isset($pins)) {
                $data_calced["$influencer_user_id"]['pins'] = $pins;
                $total_brand_mention_pins += $pins;
            } else {
                $data_calced["$influencer_user_id"]['pins'] = 0;
            }
            $data_calced["$influencer_user_id"]['username']     = $username;
            $data_calced["$influencer_user_id"]['user_id']      = $influencer_user_id;
            $data_calced["$influencer_user_id"]['display_name'] = $display_name;
            $data_calced["$influencer_user_id"]['followers']    = $followers;

            $footprint = new CategoryFootprint();
            $user_footprint = $footprint->getFootprintByUserID($influencer_user_id);
            if (!$user_footprint && $influencer_count == 0){

                $insert = "insert ignore into status_footprint (user_id) values ($influencer_user_id)";
                $resu = mysql_query($insert, $conn);
                $follower_footprint_bar = "<div class='progress-footprint'
                                            data-toggle='popover'
                                            data-container='body'
                                            data-content='Follower interest data still loading.'
                                            data-placement='bottom'>
                                        </div>";
                $data_calced["$influencer_user_id"]['footprint'] = $follower_footprint_bar;

            } else if (!$show_category_footprint && $influencer_count > 0) {

                $follower_footprint_bar = "<div class='progress-footprint'
                                            data-toggle='popover'
                                            data-container='body'
                                            data-content='More Follower interest data available on Pro plan.'
                                            data-placement='bottom'>
                                        </div>";
                $data_calced["$influencer_user_id"]['footprint'] = $follower_footprint_bar;

            } else {

                $follower_total_score = 0;
                foreach($user_footprint as $category => $score){
                    $follower_total_score += $score;
                }

                foreach($user_footprint as $category => $score){
                    $user_footprint[$category] = array();
                    $user_footprint[$category]['score'] = $score;
                    $user_footprint[$category]['percentage'] = ($score/$follower_total_score)*100;
                    $user_footprint[$category]['percentage_print'] = number_format($score/$follower_total_score,3)*100;
                    $user_footprint[$category]['category'] = renameCategories($category);
                }

                /*
                 * Re-arrange the array to show the "other" category last.
                 */
                if($user_footprint['other']){
                    $other = $user_footprint['other'];
                    unset($user_footprint['other']);
                    $user_footprint['other'] = $other;
                }


                $follower_footprint_bar = "<div class='progress-footprint'>";
                foreach($user_footprint as $category => $score){
                    $follower_footprint_bar .= "<div class='bar bar-$category'
                        style='width: " . $score['percentage'] . "%;'
                        data-ajaxload='/ajax/get-category-boards/$category/$username'
                        data-category='" . $score['category'] . "'
                        data-name='" . $display_name . "'
                        data-toggle='tooltip'
                        data-container='body'
                        data-title='<strong>" . $score['category'] . "</strong>: " . $score['percentage_print'] . "%<br>Click to explore boards!'>
                        " . $score['category'] . "
                        </div>";
                }
                $follower_footprint_bar .= "</div>";

                $data_calced["$influencer_user_id"]['footprint'] = $follower_footprint_bar;
            }

            $data_calced["$influencer_user_id"]['influence'] = $influence;
            $data_calced["$influencer_user_id"]['image']     = $image;

            if (!empty($data_calced["$influencer_user_id"]['image'])) {
                $data_calced["$influencer_user_id"]["image"] = $image;
            } else {
                $data_calced["$influencer_user_id"]["image"] = "http://passets-ec.pinterest.com/images/NoProfileImage.png";
            }

            $data_calced["$influencer_user_id"]['location_city']  = $location_city;
            $data_calced["$influencer_user_id"]['location_state'] = $location_state;
            $data_calced["$influencer_user_id"]['website']        = $website;
            $data_calced["$influencer_user_id"]['facebook']       = $facebook;
            $data_calced["$influencer_user_id"]['twitter']        = $twitter;
            $data_calced["$influencer_user_id"]['website_print']  = $website_print;
            if (isset($revenue)) {
                $data_calced["$influencer_user_id"]['revenue'] = $revenue;
            } else {
                $data_calced["$influencer_user_id"]['revenue'] = 0;
            }

            $data_calced["$influencer_user_id"]['user_meta'] = View::make('analytics.pages.community.profile', $data_calced[$influencer_user_id]);
        }
        $influencer_string = implode(",",$influencer_string);

        /*
         * Check to see if user has analytics synced, and if so, then we want to append any
         * revenue data to these followers
         */
        if ($has_analytics) {

            /*
             * Check to see if user has a traffic_id
             */
            $cust_traffic_id = false;
            $acc             = "select traffic_id from status_traffic where account_id='$cust_account_id'";
            $acc_res = mysql_query($acc, $conn) or die(mysql_error() . " Line: " . __LINE__);
            while ($a = mysql_fetch_array($acc_res)) {
                $cust_traffic_id = $a['traffic_id'];
            }


            /*
             * If user has a traffic_id, then look for their top revenue generators in our
             * database.  If they overlap with any of this user's top followers, we'll append
             * revenue data to these followers
             */
            if ($cust_traffic_id) {

                if (isset($_GET['rev_sort'])) {
                    $revenue_sort = $_GET['rev_sort'];
                } else {
                    $revenue_sort = "visits";
                }

                $acc = "select a.*, b.username from cache_traffic_influencers a
                left join data_profiles_new b
                on a.influencer_user_id=b.user_id
                where a.traffic_id='$cust_traffic_id'
                order by $revenue_sort desc";
                $acc_res = mysql_query($acc, $conn) or die(mysql_error() . " Line: " . __LINE__);
                while ($a = mysql_fetch_array($acc_res)) {

                    $influencer_user_id = $a['influencer_user_id'];
                    $revenue            = $a['revenue'];
                    $visits             = $a['visits'];


                    //If we have revenue data for this follower, we'll append it here
                    if ($data_calced["$influencer_user_id"]) {

                        $data_calced["$influencer_user_id"]['revenue'] = $revenue;
                        $data_calced["$influencer_user_id"]['visits'] = $visits;

                    } else {
                        $data_calced["$influencer_user_id"]['revenue'] = 0;
                        $data_calced["$influencer_user_id"]['visits'] = 0;
                    }
                }
            }
        }

        $data_vars = array(
            'fol_pill'                 => $fol_pill,
            'total_brand_mention_pins' => $total_brand_mention_pins,
            'data_calced'              => $data_calced
        );

        $vars = array_merge($vars, $data_vars);

        if (isset($_GET['csv'])) {
            return $this->downloadInfluencers('followers', $vars);
        }
        $this->layout_defaults['page'] = 'influential_followers';
        $this->layout_defaults['top_nav_title'] = 'Influential Followers';
        $this->layout->top_navigation = $this->buildTopNavigation();

        $this->layout->side_navigation = $this->buildSideNavigation('influential_followers');

        $this->logged_in_customer->recordEvent(
                                 UserHistory::VIEW_REPORT,
                                 $parameters = array(
                                     'report' => 'followers',
                                 )
        );

        $this->layout->body_id = 'influential-followers';
        $vars['nav_influential_followers_class'] .= ' active';
        $vars['nav_followers_class'] = 'active';
        $vars['report_url'] = 'followers/infuential';

        $this->layout->sub_navigation = View::make('analytics.components.sub_nav.community', $vars);

        $this->layout->main_content = View::make('analytics.pages.influencers', $vars);
        $this->layout->pre_body_close .= View::make('analytics.components.pre_body_close.influencers');
    }


    public function showNewestFollowers()
    {
        $vars = $this->baseLegacyVariables();
        $_GET['type'] = "followers/newest";
        $vars = array_merge($vars, $this->prepInfluencersPage());

        /*
         * Redirect to upgrade page if feature not available
         */
        extract($vars);
        if(!$customer->hasFeature('nav_newest_followers')){
            return Redirect::to("/upgrade?ref=followers&plan=" . $customer->plan()->plan_id . "");
        }


        /*
         * Prepare query for getting cached results
         */
        $last_week_time = strtotime("-1 week", time());
        $acc = "select q.follower_user_id, b.username as username, b.first_name, b.last_name,
        b.follower_count, b.pin_count, b.image, b.location, b.website_url as website,
        b.facebook_url as facebook, b.twitter_url as twitter
		from
		  (select follower_user_id from data_followers
          where user_id = $cust_user_id
          and timestamp > $last_week_time
          order by timestamp DESC $fans_limit) as q
        left join data_profiles_new b on q.follower_user_id = b.user_id";

        /*check to see whether to enable the top pinners menu item
        / (check if there is are any domains associated with this account first, and if so,
        / check to see if there are any pins associated with these domains.
        */
        if(count($cust_accounts[$cust_account_num]['domains']) > 0 ){
            $acc2 = "select influencer_user_id, username, first_name, last_name, follower_count, domain_mentions,
            image, location, website, facebook, twitter from cache_domain_influencers
            where domain in ($domainString)
            and period=0
            order by domain_mentions DESC limit 1";
            $acc_res = mysql_query($acc2,$conn) or die(mysql_error() . " Line: " . __LINE__);
            while ($a = mysql_fetch_array($acc_res)) {
                $total_brand_mention_pins = $a['domain_mentions'];
            }
        } else {
            $total_brand_mention_pins = 0;
        }

        /*
         * Pull all data for the newest followers table
         */
        $influencer_string = array();
        $influencer_count = 0;
        $acc_res = mysql_query($acc, $conn) or die(mysql_error() . " Line: " . __LINE__);
        while ($a = mysql_fetch_array($acc_res)) {
            $username     = strtolower($a['username']);
            $display_name = $a['first_name'] . " " . $a['last_name'];

            if (isset($a['domain_mentions'])) {
                $pins = $a['domain_mentions'];
            }

            $followers = $a['follower_count'];

            if (isset($a['influence'])) {
                $influence = $a['influence'];
            } else {
                if (!isset($pins) || $pins == 0) {
                    $influence = $followers;
                } else {
                    $influence = $followers * $pins;
                }
            }

            $image          = $a['image'];
            $location_city  = $a['location'];
            $location_state = '';
            $website        = $a['website'];
            $facebook       = $a['facebook'];
            $twitter        = $a['twitter'];
            if(!empty($facebook)){
                $facebook = str_replace("http://facebook.com/", "", $facebook);
                $facebook = "http://facebook.com/" . $facebook;
            }

            if(!empty($twitter)){
                $twitter = "http://twitter.com/" . $twitter;
            }

            if(!empty($website)){
                $website = str_replace("http://www.", "", $website);
                $website = str_replace("http://", "", $website);
                if (strlen($website) > 35) {
                    $website_print = substr($website, 0, 35) . "..";
                } else {
                    $website_print = $website;
                }
            }




            if (isset($a['revenue'])) {
                $revenue = $a['revenue'];
            }
            $influencer_user_id = $a['follower_user_id'];
            array_push($influencer_string, $influencer_user_id);

            $data_calced["$influencer_user_id"] = array();
            if (isset($pins)) {
                $data_calced["$influencer_user_id"]['pins'] = $pins;
                $total_brand_mention_pins += $pins;
            } else {
                $data_calced["$influencer_user_id"]['pins'] = 0;
            }
            $data_calced["$influencer_user_id"]['username']     = $username;
            $data_calced["$influencer_user_id"]['user_id']      = $influencer_user_id;
            $data_calced["$influencer_user_id"]['display_name'] = $display_name;
            $data_calced["$influencer_user_id"]['followers']    = $followers;


            $footprint = new CategoryFootprint();
            $user_footprint = $footprint->getFootprintByUserID($influencer_user_id);
            if (!$user_footprint && $influencer_count == 0){

                $insert = "insert ignore into status_footprint (user_id) values ($influencer_user_id)";
                $resu = mysql_query($insert, $conn);
                $follower_footprint_bar = "<div class='progress-footprint'
                                            data-toggle='popover'
                                            data-container='body'
                                            data-content='Follower interest data still loading.'
                                            data-placement='bottom'>
                                        </div>";
                $data_calced["$influencer_user_id"]['footprint'] = $follower_footprint_bar;

            } else if (!$show_category_footprint && $influencer_count > 0) {

                $follower_footprint_bar = "<div class='progress-footprint'
                                            data-toggle='popover'
                                            data-container='body'
                                            data-content='More Follower interest data available on Pro plan.'
                                            data-placement='bottom'>
                                        </div>";
                $data_calced["$influencer_user_id"]['footprint'] = $follower_footprint_bar;

            } else {

                $follower_total_score = 0;
                foreach($user_footprint as $category => $score){
                    $follower_total_score += $score;
                }

                foreach($user_footprint as $category => $score){
                    $user_footprint[$category] = array();
                    $user_footprint[$category]['score'] = $score;
                    $user_footprint[$category]['percentage'] = ($score/$follower_total_score)*100;
                    $user_footprint[$category]['percentage_print'] = number_format($score/$follower_total_score,3)*100;
                    $user_footprint[$category]['category'] = renameCategories($category);
                }

                /*
                 * Re-arrange the array to show the "other" category last.
                 */
                if($user_footprint['other']){
                    $other = $user_footprint['other'];
                    unset($user_footprint['other']);
                    $user_footprint['other'] = $other;
                }


                $follower_footprint_bar = "<div class='progress-footprint'>";
                foreach($user_footprint as $category => $score){
                    $follower_footprint_bar .= "<div class='bar bar-$category'
                        style='width: " . $score['percentage'] . "%;'
                        data-ajaxload='/ajax/get-category-boards/$category/$username'
                        data-category='" . $score['category'] . "'
                        data-name='" . $display_name . "'
                        data-toggle='tooltip'
                        data-container='body'
                        data-title='<strong>" . $score['category'] . "</strong>: " . $score['percentage_print'] . "%<br>Click to explore boards!'>
                        " . $score['category'] . "
                        </div>";
                }
                $follower_footprint_bar .= "</div>";

                $data_calced["$influencer_user_id"]['footprint'] = $follower_footprint_bar;
            }


            $data_calced["$influencer_user_id"]['influence']  = $influence;
            $data_calced["$influencer_user_id"]['image']      = $image;

            if (!empty($data_calced["$influencer_user_id"]['image'])) {
                $data_calced["$influencer_user_id"]["image"] = $image;
            } else {
                $data_calced["$influencer_user_id"]["image"] = "http://passets-ec.pinterest.com/images/NoProfileImage.png";
            }

            $data_calced["$influencer_user_id"]['location_city']  = $location_city;
            $data_calced["$influencer_user_id"]['location_state'] = $location_state;
            $data_calced["$influencer_user_id"]['website']        = $website;
            $data_calced["$influencer_user_id"]['website_print']  = $website_print;
            $data_calced["$influencer_user_id"]['facebook']       = $facebook;
            $data_calced["$influencer_user_id"]['twitter']        = $twitter;




            if (isset($revenue)) {
                $data_calced["$influencer_user_id"]['revenue'] = $revenue;
            } else {
                $data_calced["$influencer_user_id"]['revenue'] = 0;
            }

            $data_calced["$influencer_user_id"]['user_meta'] = View::make('analytics.pages.community.profile', $data_calced[$influencer_user_id]);

            $influencer_count++;
        }

        $data_vars = array(
            'new_pill'                 => $new_pill,
            'total_brand_mention_pins' => $total_brand_mention_pins,
            'data_calced'              => $data_calced
        );

        $vars = array_merge($vars, $data_vars);

        if (isset($_GET['csv'])) {
            return $this->downloadInfluencers('newest-followers', $vars);
        }

        $this->layout->body_id = 'newest-followers';
        $vars['nav_newest_followers_class'] .= ' active';
        $vars['nav_followers_class'] = ' active';
        $vars['report_url'] = 'followers/newest';

        $this->layout_defaults['page'] = 'newest_followers';
        $this->layout_defaults['top_nav_title'] = 'Newest Followers';
        $this->layout->top_navigation = $this->buildTopNavigation();

        $this->layout->side_navigation = $this->buildSideNavigation('newest_followers');

        $this->logged_in_customer->recordEvent(
                                 UserHistory::VIEW_REPORT,
                                     $parameters = array(
                                         'report' => 'newest followers',
                                     )
        );

        $this->layout->sub_navigation = View::make('analytics.components.sub_nav.community', $vars);
        $this->layout->main_content = View::make('analytics.pages.influencers', $vars);
        $this->layout->pre_body_close .= View::make('analytics.components.pre_body_close.influencers');
    }



    public function showTopPinnersDefault()
    {
        $vars = $this->baseLegacyVariables();
        $_GET['type'] = "domain-pinners";
        $vars = array_merge($vars, $this->prepInfluencersPage());

        /*
         * Redirect to upgrade page if feature not available
         */
        extract($vars);
        if(!$customer->hasFeature('nav_fans')){
            return Redirect::to("/upgrade?ref=top_pinners&plan=" . $customer->plan()->plan_id . "");
        }


        /*
         * Prepare data for top domain pinners table
         */

        /*
         * Prepare query for getting top pinner results
         */
        $acc = "select influencer_user_id, username, first_name, last_name, follower_count, domain_mentions,
		image, location, website, facebook, twitter
		from cache_domain_influencers
		where domain in ($domainString)
		and period=$period
		order by domain_mentions DESC $fans_limit";

        /*
         * Pull all data for the top followers table
         */
        $acc_res = mysql_query($acc, $conn) or die(mysql_error() . " Line: " . __LINE__);
        while ($a = mysql_fetch_array($acc_res)) {
            $username     = strtolower($a['username']);
            $display_name = $a['first_name'] . " " . $a['last_name'];

            if (isset($a['domain_mentions'])) {
                $pins = $a['domain_mentions'];
            }

            $followers = $a['follower_count'];

            if (isset($a['influence'])) {
                $influence = $a['influence'];
            } else {
                if (!isset($pins) || $pins == 0) {
                    $influence = $followers;
                } else {
                    $influence = $followers * $pins;
                }
            }

            $image          = $a['image'];
            $location_city  = $a['location'];
            $location_state = '';
            $website        = $a['website'];
            $facebook       = $a['facebook'];
            $twitter        = $a['twitter'];
            if(!empty($facebook)){
                $facebook = str_replace("http://facebook.com/", "", $facebook);
                $facebook = "http://facebook.com/" . $facebook;
            }

            if(!empty($twitter)){
                $twitter = "http://twitter.com/" . $twitter;
            }

            if(!empty($website)){
                $website = str_replace("http://www.", "", $website);
                $website = str_replace("http://", "", $website);
                if (strlen($website) > 35) {
                    $website_print = substr($website, 0, 35) . "..";
                } else {
                    $website_print = $website;
                }
            }

            if (isset($a['revenue'])) {
                $revenue = $a['revenue'];
            }
            $influencer_user_id = $a['influencer_user_id'];


            $data_calced["$influencer_user_id"] = array();
            if (isset($pins)) {
                $data_calced["$influencer_user_id"]['pins'] = $pins;
                $total_brand_mention_pins += $pins;
            } else {
                $data_calced["$influencer_user_id"]['pins'] = 0;
            }
            $data_calced["$influencer_user_id"]['username']     = $username;
            $data_calced["$influencer_user_id"]['user_id']      = $influencer_user_id;
            $data_calced["$influencer_user_id"]['display_name'] = $display_name;
            $data_calced["$influencer_user_id"]['followers']    = $followers;

            $footprint = new CategoryFootprint();
            $user_footprint = $footprint->getFootprintByUserID($influencer_user_id);
            if (!$user_footprint && $influencer_count == 0){

                $insert = "insert ignore into status_footprint (user_id) values ($influencer_user_id)";
                $resu = mysql_query($insert, $conn);
                $follower_footprint_bar = "<div class='progress-footprint'
                                            data-toggle='popover'
                                            data-container='body'
                                            data-content='Follower interest data still loading.'
                                            data-placement='bottom'>
                                        </div>";
                $data_calced["$influencer_user_id"]['footprint'] = $follower_footprint_bar;

            } else if (!$show_category_footprint && $influencer_count > 0) {

                $follower_footprint_bar = "<div class='progress-footprint'
                                            data-toggle='popover'
                                            data-container='body'
                                            data-content='More Follower interest data available on Pro plan.'
                                            data-placement='bottom'>
                                        </div>";
                $data_calced["$influencer_user_id"]['footprint'] = $follower_footprint_bar;

            } else {

                $follower_total_score = 0;
                foreach($user_footprint as $category => $score){
                    $follower_total_score += $score;
                }

                foreach($user_footprint as $category => $score){
                    $user_footprint[$category] = array();
                    $user_footprint[$category]['score'] = $score;
                    $user_footprint[$category]['percentage'] = ($score/$follower_total_score)*100;
                    $user_footprint[$category]['percentage_print'] = number_format($score/$follower_total_score,3)*100;
                    $user_footprint[$category]['category'] = renameCategories($category);
                }

                /*
                 * Re-arrange the array to show the "other" category last.
                 */
                if($user_footprint['other']){
                    $other = $user_footprint['other'];
                    unset($user_footprint['other']);
                    $user_footprint['other'] = $other;
                }


                $follower_footprint_bar = "<div class='progress-footprint'>";
                foreach($user_footprint as $category => $score){
                    $follower_footprint_bar .= "<div class='bar bar-$category'
                        style='width: " . $score['percentage'] . "%;'
                        data-ajaxload='/ajax/get-category-boards/$category/$username'
                        data-category='" . $score['category'] . "'
                        data-name='" . $display_name . "'
                        data-toggle='tooltip'
                        data-container='body'
                        data-title='<strong>" . $score['category'] . "</strong>: " . $score['percentage_print'] . "%<br>Click to explore boards!'>
                        " . $score['category'] . "
                        </div>";
                }
                $follower_footprint_bar .= "</div>";

                $data_calced["$influencer_user_id"]['footprint'] = $follower_footprint_bar;
            }

            $data_calced["$influencer_user_id"]['influence'] = $influence;
            $data_calced["$influencer_user_id"]['image']     = $image;

            if (!empty($data_calced["$influencer_user_id"]['image'])) {
                $data_calced["$influencer_user_id"]["image"] = $image;
            } else {
                $data_calced["$influencer_user_id"]["image"] = "http://passets-ec.pinterest.com/images/NoProfileImage.png";
            }

            $data_calced["$influencer_user_id"]['location_city']  = $location_city;
            $data_calced["$influencer_user_id"]['location_state'] = $location_state;
            $data_calced["$influencer_user_id"]['website']        = $website;
            $data_calced["$influencer_user_id"]['facebook']       = $facebook;
            $data_calced["$influencer_user_id"]['twitter']        = $twitter;
            $data_calced["$influencer_user_id"]['website_print']  = $website_print;
            if (isset($revenue)) {
                $data_calced["$influencer_user_id"]['revenue'] = $revenue;
            } else {
                $data_calced["$influencer_user_id"]['revenue'] = 0;
            }


            $data_calced["$influencer_user_id"]['type']           = "domain-pinners";
            $data_calced["$influencer_user_id"]['cust_username']  = $cust_username;
            $data_calced["$influencer_user_id"]['cust_domain']    = $cust_domain;
            $data_calced["$influencer_user_id"]['is_following']  = 0;

            $acc2 = "select * from data_followers where user_id=$cust_user_id and follower_user_id=$influencer_user_id";
            $acc2_res = mysql_query($acc2, $conn) or die(mysql_error() . " Line: " . __LINE__);
            while ($b = mysql_fetch_array($acc2_res)) {
                $data_calced["$influencer_user_id"]['is_following']  = 1;
            }

            $data_calced["$influencer_user_id"]['show_top_pinners_following'] = $show_top_pinners_following;
            $data_calced["$influencer_user_id"]['user_meta']      = View::make('analytics.pages.community.profile', $data_calced[$influencer_user_id]);



        }

        /*
         * Check to see if user has analytics synced, and if so, then we want to append any
         * revenue data to these followers
         */
        if ($has_analytics) {

            /*
             * Check to see if user has a traffic_id
             */
            $cust_traffic_id = false;
            $acc             = "select traffic_id from status_traffic where account_id='$cust_account_id'";
            $acc_res = mysql_query($acc, $conn) or die(mysql_error() . " Line: " . __LINE__);
            while ($a = mysql_fetch_array($acc_res)) {
                $cust_traffic_id = $a['traffic_id'];
            }


            /*
             * If user has a traffic_id, then look for their top revenue generators in our
             * database.  If they overlap with any of this user's top followers, we'll append
             * revenue data to these followers
             */
            if ($cust_traffic_id) {

                if (isset($_GET['rev_sort'])) {
                    $revenue_sort = $_GET['rev_sort'];
                } else {
                    $revenue_sort = "visits";
                }

                $acc = "select a.*, b.username from cache_traffic_influencers a
                left join data_profiles_new b
                on a.influencer_user_id=b.user_id
                where a.traffic_id='$cust_traffic_id'
                order by $revenue_sort desc";
                $acc_res = mysql_query($acc, $conn) or die(mysql_error() . " Line: " . __LINE__);
                while ($a = mysql_fetch_array($acc_res)) {

                    $influencer_user_id = $a['influencer_user_id'];
                    $revenue            = $a['revenue'];
                    $visits             = $a['visits'];


                    //If we have revenue data for this follower, we'll append it here
                    if ($data_calced["$influencer_user_id"]) {

                        $data_calced["$influencer_user_id"]['revenue'] = $revenue;
                        $data_calced["$influencer_user_id"]['visits']  = $visits;

                    } else {
                        $data_calced["$influencer_user_id"]['revenue'] = 0;
                        $data_calced["$influencer_user_id"]['visits']  = 0;
                    }
                }
            }
        }

        $data_vars = array(
            'pin_pill'                 => $pin_pill,
            'total_brand_mention_pins' => $total_brand_mention_pins,
            'data_calced'              => $data_calced
        );

        $vars = array_merge($vars, $data_vars);

        if (isset($_GET['csv'])) {
            return $this->downloadInfluencers('domain-pinners', $vars);
        }

        $this->layout_defaults['page'] = 'fans & influencers';
        $this->layout_defaults['top_nav_title'] = 'Top Brand Advocates';
        $this->layout->top_navigation = $this->buildTopNavigation();

        $this->layout->side_navigation = $this->buildSideNavigation('domain_pinners');

        $this->logged_in_customer->recordEvent(
                                 UserHistory::VIEW_REPORT,
                                 $parameters = array(
                                     'report' => 'top pinners',
                                 )
        );

        $this->layout->body_id = 'brand-pinners';
        $vars['nav_domain_pinners_class'] .= ' active';
        $vars['report_url'] = 'brand-pinners';

        $this->layout->sub_navigation = View::make('analytics.components.sub_nav.community', $vars);

        $this->layout->head = View::make('analytics.components.head.influencers');
        $this->layout->main_content = View::make('analytics.pages.influencers', $vars);
        $this->layout->pre_body_close .= View::make('analytics.components.pre_body_close.influencers');
    }

    public function showTopPinners($range)
    {
        echo $range;
    }

    public function showTopRepinnersDefault()
    {
        $vars = $this->baseLegacyVariables();
        $_GET['type'] = "top-repinners";
        $vars = array_merge($vars, $this->prepInfluencersPage());

        /*
         * Redirect to upgrade page if feature not available
         */
        extract($vars);
        if(!$customer->hasFeature('nav_fans')){
            return Redirect::to("/upgrade?ref=top_repinners&plan=" . $customer->plan()->plan_id . "");
        }


        /*
         * Prepare data for top repinners table
         */
        $rep_pill = "class=\"active\"";


        /*
         * Prepare query for getting top repinner data
         */
        $acc = "select repinner_user_id as influencer_user_id, overall_engagement, username, first_name, last_name, follower_count,
        following_count, image, location, website_url, facebook_url, twitter_url, pin_count, board_count
        from cache_engagement_influencers
        where user_id = $cust_user_id
        and period=$period
        order by overall_engagement DESC $fans_limit";


        /*check to see whether to enable the top pinners menu item
        / (check if there is are any domains associated with this account first, and if so,
        / check to see if there are any pins associated with these domains.
        */
        if (count($cust_accounts[$cust_account_num]['domains']) > 0) {
            $acc2 = "select influencer_user_id, username, first_name, last_name, follower_count, domain_mentions,
            image, location, website, facebook, twitter from cache_domain_influencers
            where domain in ($domainString)
            and period=0
            order by domain_mentions DESC limit 1";
            $acc_res = mysql_query($acc2, $conn) or die(mysql_error() . " Line: " . __LINE__);
            while ($a = mysql_fetch_array($acc_res)) {
                $total_brand_mention_pins = $a['domain_mentions'];
            }
        } else {
            $total_brand_mention_pins = 0;
        }


        $total_repins = 0;
        $acc_res = mysql_query($acc, $conn) or die(mysql_error() . " Line: " . __LINE__);
        while ($a = mysql_fetch_array($acc_res)) {
            $username     = strtolower($a['username']);
            $display_name = $a['first_name'] . " " . $a['last_name'];
            $repins       = $a['overall_engagement'];
            $followers    = $a['follower_count'];
            $following    = $a['following_count'];

            if (isset($a['influence'])) {
                $influence = $a['influence'];
            } else {
                if ($repins == 0) {
                    $influence = $followers;
                } else {
                    $influence = $followers * $repins;
                }
            }

            $board_count    = $a['board_count'];
            $pin_count      = $a['pin_count'];
            $image          = $a['image'];
            $location_city  = $a['location'];
            $location_state = '';
            $website        = $a['website_url'];
            $facebook       = $a['facebook_url'];
            $twitter        = $a['twitter_url'];
            if(!empty($facebook)){
                $facebook = str_replace("http://facebook.com/", "", $facebook);
                $facebook = "http://facebook.com/" . $facebook;
            }

            if(!empty($twitter)){
                $twitter = "http://twitter.com/" . $twitter;
            }

            if(!empty($website)){
                $website = str_replace("http://www.", "", $website);
                $website = str_replace("http://", "", $website);
                if (strlen($website) > 35) {
                    $website_print = substr($website, 0, 35) . "..";
                } else {
                    $website_print = $website;
                }
            }


            if (isset($a['revenue'])) {
                $revenue = $a['revenue'];
            }
            $influencer_user_id = $a['influencer_user_id'];


            $data_calced["$influencer_user_id"]                   = array();
            $data_calced["$influencer_user_id"]['repins']         = $repins;
            $data_calced["$influencer_user_id"]['pins']           = $pin_count;
            $data_calced["$influencer_user_id"]['board_count']    = $board_count;
            $data_calced["$influencer_user_id"]['username']       = $username;
            $data_calced["$influencer_user_id"]['user_id']        = $influencer_user_id;
            $data_calced["$influencer_user_id"]['display_name']   = $display_name;
            $data_calced["$influencer_user_id"]['followers']      = $followers;
            $data_calced["$influencer_user_id"]['following']      = $following;
            $data_calced["$influencer_user_id"]['influence']      = $influence;
            $data_calced["$influencer_user_id"]['image']          = $image;
            $data_calced["$influencer_user_id"]['location_city']  = $location_city;
            $data_calced["$influencer_user_id"]['location_state'] = $location_state;
            $data_calced["$influencer_user_id"]['website']        = $website;
            $data_calced["$influencer_user_id"]['facebook']       = $facebook;
            $data_calced["$influencer_user_id"]['twitter']        = $twitter;
            $data_calced["$influencer_user_id"]['website_print']  = $website_print;

            $footprint = new CategoryFootprint();
            $user_footprint = $footprint->getFootprintByUserID($influencer_user_id);
            if (!$user_footprint && $influencer_count == 0){

                $insert = "insert ignore into status_footprint (user_id) values ($influencer_user_id)";
                $resu = mysql_query($insert, $conn);
                $follower_footprint_bar = "<div class='progress-footprint'
                                            data-toggle='popover'
                                            data-container='body'
                                            data-content='Follower interest data still loading.'
                                            data-placement='bottom'>
                                        </div>";
                $data_calced["$influencer_user_id"]['footprint'] = $follower_footprint_bar;

            } else if (!$show_category_footprint && $influencer_count > 0) {

                $follower_footprint_bar = "<div class='progress-footprint'
                                            data-toggle='popover'
                                            data-container='body'
                                            data-content='More Follower interest data available on Pro plan.'
                                            data-placement='bottom'>
                                        </div>";
                $data_calced["$influencer_user_id"]['footprint'] = $follower_footprint_bar;

            } else {

                $follower_total_score = 0;
                foreach($user_footprint as $category => $score){
                    $follower_total_score += $score;
                }

                foreach($user_footprint as $category => $score){
                    $user_footprint[$category] = array();
                    $user_footprint[$category]['score'] = $score;
                    $user_footprint[$category]['percentage'] = ($score/$follower_total_score)*100;
                    $user_footprint[$category]['percentage_print'] = number_format($score/$follower_total_score,3)*100;
                    $user_footprint[$category]['category'] = renameCategories($category);
                }

                /*
                 * Re-arrange the array to show the "other" category last.
                 */
                if($user_footprint['other']){
                    $other = $user_footprint['other'];
                    unset($user_footprint['other']);
                    $user_footprint['other'] = $other;
                }


                $follower_footprint_bar = "<div class='progress-footprint'>";
                foreach($user_footprint as $category => $score){
                    $follower_footprint_bar .= "<div class='bar bar-$category'
                        style='width: " . $score['percentage'] . "%;'
                        data-ajaxload='/ajax/get-category-boards/$category/$username'
                        data-category='" . $score['category'] . "'
                        data-name='" . $display_name . "'
                        data-toggle='tooltip'
                        data-container='body'
                        data-title='<strong>" . $score['category'] . "</strong>: " . $score['percentage_print'] . "%<br>Click to explore boards!'>
                        " . $score['category'] . "
                        </div>";
                }
                $follower_footprint_bar .= "</div>";

                $data_calced["$influencer_user_id"]['footprint'] = $follower_footprint_bar;
            }


            if (isset($revenue)) {
                $data_calced["$influencer_user_id"]['revenue'] = $revenue;
            } else {
                $data_calced["$influencer_user_id"]['revenue'] = 0;
            }

            $total_repins += $repins;

            if (!empty($data_calced["$influencer_user_id"]['image'])) {
                $data_calced["$influencer_user_id"]["image"] = $image;
            } else {
                $data_calced["$influencer_user_id"]["image"] = "http://passets-ec.pinterest.com/images/NoProfileImage.png";
            }

            $data_calced["$influencer_user_id"]['type']           = "top-repinners";
            $data_calced["$influencer_user_id"]['cust_username']  = $cust_username;
            $data_calced["$influencer_user_id"]['show_top_pinners_following'] = $show_top_pinners_following;

            $data_calced["$influencer_user_id"]['is_following']  = 0;
            $acc2 = "select * from data_followers where user_id='$cust_user_id' and follower_user_id='$influencer_user_id'";
            $acc2_res = mysql_query($acc2, $conn) or die(mysql_error() . " Line: " . __LINE__);
            while ($b = mysql_fetch_array($acc2_res)) {
                $data_calced["$influencer_user_id"]['is_following']  = 1;
            }

            $data_calced["$influencer_user_id"]['user_meta']      = View::make('analytics.pages.community.profile', $data_calced[$influencer_user_id]);

        }

        /*
         * Check to see if user has analytics synced, and if so, then we want to append any
         * revenue data to these followers
         */
        if ($has_analytics) {

            /*
             * Check to see if user has a traffic_id
             */
            $cust_traffic_id = false;
            $acc             = "select traffic_id from status_traffic where account_id='$cust_account_id'";
            $acc_res = mysql_query($acc, $conn) or die(mysql_error() . " Line: " . __LINE__);
            while ($a = mysql_fetch_array($acc_res)) {
                $cust_traffic_id = $a['traffic_id'];
            }


            /*
             * If user has a traffic_id, then look for their top revenue generators in our
             * database.  If they overlap with any of this user's top followers, we'll append
             * revenue data to these followers
             */
            if ($cust_traffic_id) {

                if (isset($_GET['rev_sort'])) {
                    $revenue_sort = $_GET['rev_sort'];
                } else {
                    $revenue_sort = "visits";
                }

                $acc = "select a.*, b.username from cache_traffic_influencers a
                left join data_profiles_new b
                on a.influencer_user_id=b.user_id
                where a.traffic_id='$cust_traffic_id'
                order by $revenue_sort desc";
                $acc_res = mysql_query($acc, $conn) or die(mysql_error() . " Line: " . __LINE__);
                while ($a = mysql_fetch_array($acc_res)) {

                    $influencer_user_id = $a['influencer_user_id'];
                    $revenue            = $a['revenue'];
                    $visits             = $a['visits'];


                    //If we have revenue data for this follower, we'll append it here
                    if ($data_calced["$influencer_user_id"]) {

                        $data_calced["$influencer_user_id"]['revenue'] = $revenue;
                        $data_calced["$influencer_user_id"]['visits']  = $visits;

                    } else {
                        $data_calced["$influencer_user_id"]['revenue'] = 0;
                        $data_calced["$influencer_user_id"]['visits']  = 0;
                    }
                }
            }
        }


        $data_vars = array(
            'rep_pill'                 => $rep_pill,
            'total_brand_mention_pins' => $total_brand_mention_pins,
            'data_calced'              => $data_calced
        );

        $vars = array_merge($vars, $data_vars);

        if (isset($_GET['csv'])) {
            return $this->downloadInfluencers('top-repinners', $vars);
        }
        $this->layout_defaults['page'] = 'fans & influencers';
        $this->layout_defaults['top_nav_title'] = 'Top Repinners';
        $this->layout->top_navigation = $this->buildTopNavigation();

        $this->layout->side_navigation = $this->buildSideNavigation('top_repinners');

        $this->logged_in_customer->recordEvent(
                                 UserHistory::VIEW_REPORT,
                                 $parameters = array(
                                     'report' => 'top repinners',
                                 )
        );

        $this->layout->body_id = 'top-repinners';
        $vars['nav_top_repinners_class'] .= ' active';
        $vars['report_url'] = 'top-repinners';

        $this->layout->sub_navigation = View::make('analytics.components.sub_nav.community', $vars);

        $this->layout->main_content = View::make('analytics.pages.influencers', $vars);
        $this->layout->pre_body_close .= View::make('analytics.components.pre_body_close.influencers');
    }

    public function showTopRepinners($range)
    {
        echo $range;
    }


    public function showMostValuablePinners()
    {
        $vars         = $this->baseLegacyVariables();
        $_GET['type'] = "most-valuable-pinners";
        $vars         = array_merge($vars, $this->prepInfluencersPage());

        /*
         * Redirect to upgrade page if feature not available
         */
        extract($vars);
        $customer = $this->logged_in_customer;
        if(!$customer->hasFeature('nav_fans')){
            return Redirect::to("/upgrade?ref=roi_pinners&plan=" . $customer->plan()->plan_id . "");
        }

        if(isset($_GET['rev_sort'])){
            $revenue_sort = $_GET['rev_sort'];
        } else {
            $revenue_sort = "revenue";
        }

        $revenue_header = "";
        $visit_header = "";
        $conversion_header = "";

        if($revenue_sort=="revenue"){
            $revenue_header = "sorting_desc";
        } else if($revenue_sort=="visits"){
            $visit_header = "sorting_desc";
        } else if ($revenue_sort=="transactions"){
            $conversion_header = "sorting_desc";
        }

        /*
         * Prepare data for Most Valuable Pinners table
         */
        $rev_pill = "class=\"active\"";


        /*
         * Check to ensure user actually has google analytics synced and the data is available.
         * Then, prepare the query.
         */
        if($has_analytics){

            $cust_traffic_id = false;
            $acc3            = "select traffic_id from status_traffic where account_id='$cust_account_id'";
            $acc_res = mysql_query($acc3, $conn) or die(mysql_error() . " Line: " . __LINE__);
            while ($a = mysql_fetch_array($acc_res)) {
                $cust_traffic_id = $a['traffic_id'];
            }

            if ($cust_traffic_id) {

                $acc = "select a.*, b.username from cache_traffic_influencers a
                        left join data_profiles_new b
                        on a.influencer_user_id=b.user_id
                        where a.traffic_id='$cust_traffic_id'
                        and period=$period
                        order by $revenue_sort desc";
            }
        }

        /*check to see whether to enable the top pinners menu item
        / (check if there is are any domains associated with this account first, and if so,
        / check to see if there are any pins associated with these domains.
        */
        if (count($cust_accounts[$cust_account_num]['domains']) > 0) {
            $acc2 = "select influencer_user_id, username, first_name, last_name, follower_count, domain_mentions,
            image, location, website, facebook, twitter from cache_domain_influencers
            where domain in ($domainString)
            and period=0
            order by domain_mentions DESC limit 1";
            $acc_res = mysql_query($acc2, $conn) or die(mysql_error() . " Line: " . __LINE__);
            while ($a = mysql_fetch_array($acc_res)) {
                $total_brand_mention_pins = $a['domain_mentions'];
            }
        } else {
            $total_brand_mention_pins = 0;
        }


        $acc_res = mysql_query($acc, $conn) or die(mysql_error() . " Line: " . __LINE__);
        while ($a = mysql_fetch_array($acc_res)) {

            $username           = strtolower($a['username']);
            $display_name       = $a['first_name'] . " " . $a['last_name'];
            $visits             = $a['visits'];
            $conversions        = $a['transactions'];
            $revenue            = $a['revenue'];
            $followers          = $a['follower_count'];
            $following          = $a['following_count'];
            $board_count        = $a['board_count'];
            $pin_count          = $a['pin_count'];
            $image              = $a['image'];
            $location_city      = $a['location'];
            $location_state     = '';
            $website            = $a['website'];
            $facebook           = $a['facebook'];
            $twitter            = $a['twitter'];
            $influencer_user_id = $a['influencer_user_id'];
            if(!empty($facebook)){
                $facebook = str_replace("http://facebook.com/", "", $facebook);
                $facebook = "http://facebook.com/" . $facebook;
            }

            if(!empty($twitter)){
                $twitter = "http://twitter.com/" . $twitter;
            }

            if(!empty($website)){
                $website = str_replace("http://www.", "", $website);
                $website = str_replace("http://", "", $website);
                if (strlen($website) > 35) {
                    $website_print = substr($website, 0, 35) . "..";
                } else {
                    $website_print = $website;
                }
            }


            $data_calced["$influencer_user_id"]                   = array();
            $data_calced["$influencer_user_id"]['visits']         = $visits;
            $data_calced["$influencer_user_id"]['conversions']    = $conversions;
            $data_calced["$influencer_user_id"]['revenue']        = $revenue;
            $data_calced["$influencer_user_id"]['pins']           = $pin_count;
            $data_calced["$influencer_user_id"]['board_count']    = $board_count;
            $data_calced["$influencer_user_id"]['username']       = $username;
            $data_calced["$influencer_user_id"]['user_id']        = $influencer_user_id;
            $data_calced["$influencer_user_id"]['display_name']   = $display_name;
            $data_calced["$influencer_user_id"]['followers']      = $followers;
            $data_calced["$influencer_user_id"]['following']      = $following;
            $data_calced["$influencer_user_id"]['image']          = $image;
            $data_calced["$influencer_user_id"]['location_city']  = $location_city;
            $data_calced["$influencer_user_id"]['location_state'] = $location_state;
            $data_calced["$influencer_user_id"]['website']        = $website;
            $data_calced["$influencer_user_id"]['facebook']       = $facebook;
            $data_calced["$influencer_user_id"]['twitter']        = $twitter;
            $data_calced["$influencer_user_id"]['website_print']  = $website_print;

            $footprint = new CategoryFootprint();
            $user_footprint = $footprint->getFootprintByUserID($influencer_user_id);
            if (!$user_footprint && $influencer_count == 0){

                $insert = "insert ignore into status_footprint (user_id) values ($influencer_user_id)";
                $resu = mysql_query($insert, $conn);
                $follower_footprint_bar = "<div class='progress-footprint'
                                            data-toggle='popover'
                                            data-container='body'
                                            data-content='Follower interest data still loading.'
                                            data-placement='bottom'>
                                        </div>";
                $data_calced["$influencer_user_id"]['footprint'] = $follower_footprint_bar;

            } else if (!$show_category_footprint && $influencer_count > 0) {

                $follower_footprint_bar = "<div class='progress-footprint'
                                            data-toggle='popover'
                                            data-container='body'
                                            data-content='More Follower interest data available on Pro plan.'
                                            data-placement='bottom'>
                                        </div>";
                $data_calced["$influencer_user_id"]['footprint'] = $follower_footprint_bar;

            } else {

                $follower_total_score = 0;
                foreach($user_footprint as $category => $score){
                    $follower_total_score += $score;
                }

                foreach($user_footprint as $category => $score){
                    $user_footprint[$category] = array();
                    $user_footprint[$category]['score'] = $score;
                    $user_footprint[$category]['percentage'] = ($score/$follower_total_score)*100;
                    $user_footprint[$category]['percentage_print'] = number_format($score/$follower_total_score,3)*100;
                    $user_footprint[$category]['category'] = renameCategories($category);
                }

                /*
                 * Re-arrange the array to show the "other" category last.
                 */
                if($user_footprint['other']){
                    $other = $user_footprint['other'];
                    unset($user_footprint['other']);
                    $user_footprint['other'] = $other;
                }


                $follower_footprint_bar = "<div class='progress-footprint'>";
                foreach($user_footprint as $category => $score){
                    $follower_footprint_bar .= "<div class='bar bar-$category'
                        style='width: " . $score['percentage'] . "%;'
                        data-ajaxload='/ajax/get-category-boards/$category/$username'
                        data-category='" . $score['category'] . "'
                        data-name='" . $display_name . "'
                        data-toggle='tooltip'
                        data-container='body'
                        data-title='<strong>" . $score['category'] . "</strong>: " . $score['percentage_print'] . "%<br>Click to explore boards!'>
                        " . $score['category'] . "
                        </div>";
                }
                $follower_footprint_bar .= "</div>";

                $data_calced["$influencer_user_id"]['footprint'] = $follower_footprint_bar;
            }

            if (!empty($data_calced["$influencer_user_id"]['image'])) {
                $data_calced["$influencer_user_id"]["image"] = $image;
            } else {
                $data_calced["$influencer_user_id"]["image"] = "http://passets-ec.pinterest.com/images/NoProfileImage.png";
            }

            $data_calced["$influencer_user_id"]['type']           = "most-valuable-pinners";
            $data_calced["$influencer_user_id"]['cust_username']  = $cust_username;
            $data_calced["$influencer_user_id"]['show_top_pinners_following'] = $show_top_pinners_following;

            $data_calced["$influencer_user_id"]['is_following']  = 0;
            $acc2 = "select * from data_followers where user_id='$cust_user_id' and follower_user_id='$influencer_user_id'";
            $acc2_res = mysql_query($acc2, $conn) or die(mysql_error() . " Line: " . __LINE__);
            while ($b = mysql_fetch_array($acc2_res)) {
                $data_calced["$influencer_user_id"]['is_following']  = 1;
            }

            $data_calced["$influencer_user_id"]['user_meta'] = View::make('analytics.pages.community.profile', $data_calced[$influencer_user_id]);

        }

        if($this->isDemo()) {
            $this->layout->alert = '<div class="alert alert-info">
                <button class="close" data-dismiss="alert" style="border: 0; background-color: transparent;">×</button>
                <strong>Note:</strong> This report is displaying demo data.</div>';
        }

        $data_vars = array(
            'rev_pill'                 => $rev_pill,
            'revenue_sort'             => $revenue_sort,
            'revenue_header'           => $revenue_header,
            'visit_header'             => $visit_header,
            'conversion_header'        => $conversion_header,
            'total_brand_mention_pins' => $total_brand_mention_pins,
            'data_calced'              => $data_calced
        );

        $vars = array_merge($vars, $data_vars);

        if (isset($_GET['csv'])) {
            return $this->downloadInfluencers('most-valuble-pinners', $vars);
        }
        $this->layout_defaults['page'] = 'fans & influencers';
        $this->layout_defaults['top_nav_title'] = 'Most Valuable Pinners';
        $this->layout->top_navigation = $this->buildTopNavigation();

        $this->layout->side_navigation = $this->buildSideNavigation('roi_pinners');

        $this->logged_in_customer->recordEvent(
                                 UserHistory::VIEW_REPORT,
                                 $parameters = array(
                                     'report' => 'most valuable pinners',
                                 )
        );

        $this->layout->body_id = 'roi-pinners';
        $vars['nav_roi_pinners_class'] .= ' active';
        $vars['report_url'] = 'most-valuable-pinners';

        $this->layout->sub_navigation = View::make('analytics.components.sub_nav.community', $vars);

        $this->layout->main_content = View::make('analytics.pages.influencers', $vars);
        $this->layout->pre_body_close .= View::make('analytics.components.pre_body_close.influencers');
    }


    public function showInfluencers()
    {
        $vars = $this->baseLegacyVariables();
        $vars = array_merge($vars, $this->prepInfluencersPage());

        $this->layout_defaults['page'] = 'fans & influencers';
        $this->layout_defaults['top_nav_title'] = 'Fans and Influencers';
        $this->layout->top_navigation = $this->buildTopNavigation();

        $this->layout->side_navigation = $this->buildSideNavigation('fans & influencers');

        $this->logged_in_customer->recordEvent(
                                 UserHistory::VIEW_REPORT,
                                 $parameters = array(
                                     'report' => 'fans & influencers',
                                 )
        );

        $this->layout->main_content = View::make('analytics.pages.influencers', $vars);
        $this->layout->pre_body_close = View::make('analytics.components.pre_body_close.influencers');
    }
}

function renameCategories($a) {

    if($a=="womens_fashion"){
        $b="womens fashion";
    }
    elseif($a=="diy_crafts"){
        $b="diy & crafts";
    }
    elseif($a=="health_fitness"){
        $b="health & fitness";
    }
    elseif($a=="holidays_events"){
        $b="holidays & events";
    }
    elseif($a=="none"){
        $b="not specified";
    }
    elseif($a=="holiday_events"){
        $b="holidays & events";
    }
    elseif($a=="home_decor"){
        $b="home decor";
    }
    elseif($a=="food_drink"){
        $b="food & drink";
    }
    elseif($a=="film_music_books"){
        $b="film, music & books";
    }
    elseif($a=="hair_beauty"){
        $b="hair & beauty";
    }
    elseif($a=="cars_motorcycles"){
        $b="cars & motorcycles";
    }
    elseif($a=="science_nature"){
        $b="science & nature";
    }
    elseif($a=="mens_fashion"){
        $b="mens fashion";
    }
    elseif($a=="illustrations_posters"){
        $b="illustrations & posters";
    }
    elseif($a=="art_arch"){
        $b="art & architecture";
    }
    elseif($a=="wedding_events"){
        $b="weddings & events";
    }
    else{
        $b=$a;
    }

    return $b;
}