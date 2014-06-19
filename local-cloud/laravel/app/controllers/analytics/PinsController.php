<?php

namespace Analytics;

use View,
    Log,
    Redirect;

use UserHistory;

/**
 * Class PinsController
 *
 * @package Analytics
 */
class PinsController extends BaseController
{

    protected $layout = 'layouts.analytics';

    /**
     * Construct
     *
     * @author  Will
     */
    public function __construct()
    {

        parent::__construct();

        Log::setLog(__FILE__, 'Reporting', 'Pins_Report');
    }

    public function showTrendingOwnedPins($start_date, $end_date){
        $vars = $this->baseLegacyVariables();
        extract($vars);

        $this->layout_defaults['page'] = 'viral_pins';
        $this->layout_defaults['top_nav_title'] = 'Trending Pins';
        $this->layout->top_navigation = $this->buildTopNavigation();

        $this->layout->side_navigation = $this->buildSideNavigation('viral_pins');

        /*
        |--------------------------------------------------------------------------
        | Check the user's report permissions
        |--------------------------------------------------------------------------
        */

        if(!$customer->hasFeature('nav_viral_pins')){
            return Redirect::to("/upgrade?ref=viral_pins&plan=" . $customer->plan()->plan_id . "");
        }


        //get features
        if ($customer->hasFeature('viral_pin_history_180')) {
            $day_limit           = 365;
            $popover_custom_date = "";
            $is_free_account     = false;
        } else {
            if ($customer->hasFeature('viral_pin_history_90')) {
                $day_limit           = 90;
                $popover_custom_date = "";
                $is_free_account     = false;
            } else {
                $day_limit         = 7;
                $is_free_account   = true;
                $popover_custom_date = createPopover("#reportrange", "hover", "bottom", "<span class=\"text-success\"><strong>Upgrade to Unlock</strong></span>", "profile_date_range",
                    $customer->plan()->plan_id, "<strong><ul><li>Get more historical data</li><li>Filter using custom date ranges</li></ul>");
            }
        }


        if ($customer->hasFeature('viral_pin_context')) {

        } else {

        }

        if($customer->hasFeature('pins_history')){
            $history_enabled = true;
        } else {
            $history_enabled = false;
            $history_popover = createNavPopover(".history", "hover", "left", "<span class=\"text-success\"><strong>Get This Pins Story</strong></span>", "history",
                $customer->plan()->plan_id, "Tracking a pins engagement over time is available on the Professional plan.<ul><li><strong>Find out when and how your pins went viral.</strong></li><li><strong>Track Campaigns</strong> and measure the effects of your promotions over time.</li></ul>");
        }


        /*
         * Set Available features for the Pin Inspector page
         */
        if($customer->hasFeature('viral_pin_export')){
            $export_module = "<div class='pull-right dataTable-export' style='text-align:right;'></div>";
            $export_insert = "T";
            $export_popover = "";
        } else {
            $export_module = "
                    <div class='pull-right pins-export' style='text-align:right;'>
                        <div class='DTTT btn-group'>
                            <a class='btn btn-mini disabled DTTT_button_csv' id='ToolTables_example_1'>
                                <span>
                                    Export <i class='icon-new-tab'></i>
                                </span>
                            </a>
                        </div>
                    </div>";
            $export_popover = createPopover(".pins-export", "hover", "bottom", "<span class=\"text-success\"><strong>Need to Export your Data?</strong></span>", "pins_export",
                $customer->plan()->plan_id, "Upgrade to enable exporting data across your dashboard.<ul><li><strong>Instantly download CSV files</strong> of any report</li><li><strong>Take your data with you</strong> anywhere it needs to go!</li></ul>");
            $export_insert =  "";
        }

        $max_date_range_limit = $customer->maxAllowed('viral_pin_date_range_max');

        $permission_check_vars = array(
            'day_limit'              => $day_limit,
            'popover_custom_date'    => $popover_custom_date,
            'is_free_account'        => $is_free_account,
            'csv_url'                => $csv_url,
            'export_class'           => $export_class,
            'export_view_class'      => $export_view_class,
            'export_popover'         => $export_popover,
            'history_enabled'        => $history_enabled,
            'history_popover'        => $history_enabled,
            'max_date_range_limit'   => $max_date_range_limit
        );

        $vars = array_merge($vars, $permission_check_vars);


        /*
        |--------------------------------------------------------------------------
        | Parse the user's date selection
        |--------------------------------------------------------------------------
        */

        //get the last date we've pulled pins for this profile
        $acc = "select * from status_profile_pins where user_id='$cust_user_id'";
        $acc_res = mysql_query($acc, $conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $cache_timestamp = $a['last_pulled'];
        }

        $cache_timestamp = flat_date('day', $cache_timestamp);

        if(!$end_date){
            $end_date = $cache_timestamp;
        }

        //see how old this account is
        $cust_timestamp = getFlatDate($cust_timestamp);
        $days_old = round(($cache_timestamp - $cust_timestamp) / 60 / 60 / 24);
        if ($days_old < 14) {
            $fresh_account = true;
        }

        //set date range values
        if ($start_date && $end_date) {

            if(!is_numeric($start_date)){
                $last_date    = getTimestampFromDate($start_date);
            } else {
                $last_date    = $start_date;
            }

            if(!is_numeric($end_date)){
                $current_date = getTimestampFromDate($end_date);
            } else {
                $current_date = $end_date;
            }

            $custom_num_days = round(abs($current_date - $last_date) / 60 / 60 / 24);
            $day_range       = $custom_num_days;

            $compare_date  = strtotime("-$custom_num_days days", $last_date);
            $compare2_date = strtotime("-$custom_num_days days", $compare_date);

            $current_name        = "Last " . round(abs($current_date - $last_date) / 60 / 60 / 24) . " days";
            $current_chart_label = "Past " . round(abs($current_date - $last_date) / 60 / 60 / 24) . " Days";
            $old_chart_label     = "Prior " . round(abs($current_date - $last_date) / 60 / 60 / 24) . " Days";
            $old_name            = $old_chart_label;
            $older_chart_label   = "Two " . round(abs($current_date - $last_date) / 60 / 60 / 24) . " Day Periods Prior";

            if ($current_date < $last_date) {
                $temp         = $current_date;
                $current_date = $last_date;
                $last_date    = $temp;
            }

        } else {
            return $this->showTrendingOwnedPinsDefault();
        }

        $cache_timestamp_print = date("m-d-Y", $cache_timestamp);
        $last_date_print       = date("m-d-Y", $last_date);
        $current_date_print    = date("m-d-Y", $current_date);
        $compare_date_print    = date("m-d-Y", $compare_date);
        $compare2_date_print   = date("m-d-Y", $compare2_date);



        $profile_date_vars = array(
            'cache_timestamp'           => $cache_timestamp,
            'cust_timestamp'            => $cust_timestamp,
            'fresh_account'             => $fresh_account,
            'current_date'              => $current_date,
            'last_date'                 => $last_date,
            'day_range'                 => $day_range,
            'custom_num_days'           => $custom_num_days,
            'compare_date'              => $compare_date,
            'compare2_date'             => $compare2_date,
            'current_name'              => $current_name,
            'current_chart_label'       => $current_chart_label,
            'old_chart_label'           => $old_chart_label,
            'old_name'                  => $old_name,
            'older_chart_label'         => $older_chart_label,
            'older_name'                => $older_name,
            'cache_timestamp_print'     => $cache_timestamp_print,
            'last_date_print'           => $last_date_print,
            'current_date_print'        => $current_date_print,
            'compare_date_print'        => $compare_date_print,
            'compare2_date_print'       => $compare2_date_print
        );

        $vars = array_merge($vars, $profile_date_vars);




        /*
        |--------------------------------------------------------------------------
        | Set Up All of the Data You Need
        |--------------------------------------------------------------------------
        */


        $profile = $this->active_user_account->profile();

        $pins = $profile->mostRepinnedPins(100,$last_date,$current_date);

        /*
         * Get the user's latest pin count
         */
        $acc = "select pin_count from data_profiles_new where user_id=$cust_user_id";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $cust_pin_count = $a['pin_count'];
        }

        $boards = $profile->getDBBoards();
        if(!empty($pins)){
            $pins->loadCache('board', 'board_id', $boards);
        }


        /*
        |--------------------------------------------------------------------------
        | Create the DataTable javascript to insert into the <head>
        |--------------------------------------------------------------------------
        */


        $max_repins = 0;
        $max_likes = 0;
        $max_comments = 0;
        $min_date = 99999999999999;
        $max_date = 1;

        $datatable_js = "";



        foreach($pins as $pin){

            $days_ago = round(($current_date - $b['created_at'])/60/60/24);

            $pin->link = trim(str_replace('"', '', $pin->link));

            if(empty($pin->domain)){

                /*
                 * Using a MAGIC SETTER for these two properties
                 * They are not actually properties of the Pin Object
                 */
                $pin->domain_label = $b['method'];
                $pin->domain_text = $b['method'];
            } else {
                if(strlen($pin->domain)>16){
                    $pin->domain_text = substr($pin_domain,0,14)."..";
                } else {
                    $pin->domain_text = "$pin_domain";
                }

                $pin->domain_label = $pin->link;
            }

            $pin->description = str_replace('\\', '', $pin->description);
            $pin->description = str_replace('"', '\\"', $pin->description);
            $pin->description = trim(preg_replace('/[^(\x20-\x7F)]*/','', $pin->description));


            if($pin->repin_count_change > $max_repins){
                $max_repins = $pin->repin_count_change;
            }

            if($pin->like_count_change > $max_likes){
                $max_likes = $pin->like_count_change;
            }

            if($pin->comment_count_change > $max_comments){
                $max_comments = $pin->comment_count_change;
            }

            if($pin->created_at > $max_date){
                $max_date = $pin->created_at;
            }
            if($pin->created_at < $min_date && $pin->created_at > 0){
                $min_date = $pin->created_at;
            }

            /*
             * "created_at_print" is being set using the MAGIC SETTER
             * and is not an actual property of the pin object
             */
            if($pin->created_at >= 0){
                $pin->created_at_print = date("m-d-Y", $pin->created_at);
            } else {
                $pin->created_at_print = date("m-d-Y", 1325394000);
            }


            $pin->board()->name = str_replace('"','\"', $pin->board()->name);

            //checks for board_id (last 8 digits) as the GET parameter and assigns the 'board name' to a variable which will be checked against the board filter's list of dropdown options and if there is a match, that board will be automatically selected to pre-filter the page on load (see footer.php for jQuery which matches the board name with the dropdown option and auto-selects the option).
            if(isset($_GET['b'])){
                if((substr($pin->board_id,-8) == $_GET['b']) && (!isset($board_filter))){
                    $board_filter = $pin->board()->board_name;
                }
            } else {
                $board_filter = false;
            }

            $pin_board_category = renameCategories($pin->board()->category);


//            if(!empty($pin->repin_count_change) || !empty($pin->like_count_change) || !empty($pin->comment_count_change)){
                $datatable_js .= "
                [\"$pin_board_category\",
                \"$pin->board_id\",
                \"" . $pin->board()->name . "\",
                \"" . $pin->board()->url . "\",
                \"$pin->pin_id\",
                \"$pin->repin_count_change\",
                \"$pin->like_count_change\",
                \"$pin->comment_count_change\",
                \"$pin->created_at_print\",
                \"$pin->domain_label\",
                \"$pin->image_url\",
                \"$pin->description\",
                \"\"],";
//            }

        }

        $dataTable_pins = true;

        $max_date_print = date("m/d/Y", $max_date);
        $today_date_print = date("m/d/Y", $current_date);
        $min_date_print = date("m/d/Y", $min_date);
        $last_week_date = date("m/d/Y", strtotime("-7 days",$current_date));
        $last_month_date = date("m/d/Y", strtotime("-1 month",$current_date));



        /*
        |--------------------------------------------------------------------------
        | Header Vars
        |--------------------------------------------------------------------------
        */
        /*
         * Insert necessary variables into the <head> tag
         */
        $head_vars = array(
            'last_week_date'   => $last_week_date,
            'last_month_date'  => $last_month_date,
            'today_date_print' => $today_date_print,
            'min_date_print'   => $min_date_print,
            'max_date_print'   => $max_date_print,
            'max_repins'       => $max_repins,
            'max_likes'        => $max_likes,
            'max_comments'     => $max_comments,
            'board_filter'     => $board_filter,
            'current_date'     => $current_date,
            'export_insert'    => $export_insert,
            'history_enabled'  => $history_enabled,
            'dataTable_pins'   => $dataTable_pins,
            'datatable_js'     => $datatable_js
        );

        $vars = array_merge($vars, $head_vars);



        /*
        |--------------------------------------------------------------------------
        | Main Content Vars
        |--------------------------------------------------------------------------
        */
        /*
         * Insert variables into the profile_pins view for the main_content area
         */
        $pins_vars = array(
            'customer'                  => $this->logged_in_customer,
            'pins_upgrade_alert'        => $pins_upgrade_alert,
            'export_module'             => $export_module,
            'export_popover'            => $export_popover,
            'history_enabled'           => $history_enabled
        );

        $this->logged_in_customer->recordEvent(
                                 UserHistory::VIEW_REPORT,
                                     $parameters = array(
                                         'report' => 'Pin Inspector'
                                     )
        );

        $vars = array_merge($vars, $pins_vars);

        $this->layout->body_id = 'viral-pins';
        $vars['nav_viral_pins_class'] .= ' active';
        $vars['nav_top_pins_class'] .= ' active';
        $vars['report_url'] = 'pins/owned/trending';

        $this->layout->head .= View::make('analytics.components.head.datatable_pins', $vars);
        $this->layout->head .= View::make('analytics.components.head.site_tour', $vars);

        $this->layout->sub_navigation = View::make('analytics.components.sub_nav.optimize', $vars);


        $this->layout->main_content = View::make('analytics.pages.profile_pins', $vars);

        /*
        |--------------------------------------------------------------------------
        | Footer Vars
        |--------------------------------------------------------------------------
        */
        /*
         * Insert the datatable_pins component into the pre_body_close section
         */
        $this->layout->pre_body_close .= View::make('analytics.components.pre_body_close.datatable_pins');


    }

    public function showTrendingOwnedPinsDefault(){

        $week_ago = strtotime("7 days ago",flat_date('day'));
        $today = flat_date('day');

        return $this->showTrendingOwnedPins($week_ago, $today);
    }



    public function showOwnedPinsDefault(){
        $vars = $this->baseLegacyVariables();
        extract($vars);

        $this->layout_defaults['page'] = 'pins';
        $this->layout_defaults['top_nav_title'] = 'Pin Inspector';
        $this->layout->top_navigation = $this->buildTopNavigation();

        $this->layout->side_navigation = $this->buildSideNavigation('pins');


        /*
        |--------------------------------------------------------------------------
        | Set Up All of the Data You Need
        |--------------------------------------------------------------------------
        */


        /*
         * Get the user's latest pin count
         */
        $acc = "select pin_count from data_profiles_new where user_id=$cust_user_id";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $cust_pin_count = $a['pin_count'];
        }


        /*
         * Set Available features for the Pin Inspector page
         */
        if($customer->hasFeature('pins_export')){
            $export_module = "<div class='pull-right dataTable-export' style='text-align:right;'></div>";
            $export_insert = "T";
            $export_popover = "";
        } else {
            $export_module = "
                    <div class='pull-right pins-export' style='text-align:right;'>
                        <div class='DTTT btn-group'>
                            <a class='btn btn-mini disabled DTTT_button_csv' id='ToolTables_example_1'>
                                <span>
                                    Export <i class='icon-new-tab'></i>
                                </span>
                            </a>
                        </div>
                    </div>";
            $export_popover = createPopover(".pins-export", "hover", "bottom", "<span class=\"text-success\"><strong>Need to Export your Data?</strong></span>", "pins_export",
                $customer->plan()->plan_id, "Upgrade to enable exporting data across your dashboard.<ul><li><strong>Instantly download CSV files</strong> of any report</li><li><strong>Take your data with you</strong> anywhere it needs to go!</li></ul>");
            $export_insert =  "";
        }

        if($customer->hasFeature('pins_date_custom')){
            //NOT CURRENTLY BEING USED
        } else {

        }

        if($customer->hasFeature('pins_history')){
            $history_enabled = true;
        } else {
            $history_enabled = false;
            $history_popover = createNavPopover(".history", "hover", "left", "<span class=\"text-success\"><strong>Get This Pins Story</strong></span>", "history",
                $customer->plan()->plan_id, "Tracking a pins engagement over time is available on the Professional plan.<ul><li><strong>Find out when and how your pins went viral.</strong></li><li><strong>Track Campaigns</strong> and measure the effects of your promotions over time.</li></ul>");
        }

        if($customer->hasFeature('pins_all_pins')){
            $pins_limit = "limit 15000";
            $pins_upgrade_alert =
                "<div class='alert alert-block alert-info'>
                    <a type='button' class='btn close' data-dismiss='alert'
                    style='opacity:0.8;'>Got it!</a>
                    <div class='row-fluid'>
                        <div class='span11'>
                            <strong>Tip:</strong> The Pin Inspector shows how many repins, likes and comments
                            your pins have as of <em><u>today</u></em>.
                            <br>→ Check out your <a class='btn' href='/pins/owned/trending'>Trending Pins</a> report
                            to see how much engagement your pins received over a specific period of time.
                        </div>
                    </div>

                </div>";
        } else {
            if($customer->hasFeature('pins_last_250')){
                $pins_limit = "limit 250";
                $pins_upgrade_alert =
                    "<div class='well'>
                    <a type='button' class='btn close' data-dismiss='alert'
                    style='opacity:0.5;'>Got it!</a>
                    <strong>Hey $cust_first_name!</strong> You can analyze up to 250 of your latest pins here with
                    your Free Account.
                    <br><br>";

                if ($cust_pin_count > 250) {
                    $pins_upgrade_alert .= "<a class='btn btn-success' href='/upgrade?ref=pins_note&plan=".$customer->plan()->plan_id."'>
                        <strong><i class='icon-arrow-right'></i> Upgrade Now</strong> to analyze
                        <strong>ALL $cust_pin_count of your pins</strong>
                    </a>
                    <a href=\"/demo/pro?follow=back\" class=\"btn\"> or Try the Pro Demo</a>";
                }

                $pins_upgrade_alert .=
                "</div>";
            } else {
                $pins_limit = "limit 15000";
                $pins_upgrade_alert = "";
            }
        }




        /*
         * get the last date calcs were completed for this profile
         */
        $acc = "select * from status_profiles where user_id=$cust_user_id";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $cache_timestamp = $a['last_calced'];
        }

        /*
         * Set current date to base the rest of the report from
         */
        $current_date = getFlatDate($cache_timestamp);



        /*
         * Get all boards for the logged in user, we will use these to be able to return relevant
         * board data for each pin by matching via 'board_id'
         */
        $boards = array();

        $acc = "select * from data_boards where user_id=$cust_user_id";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $board_id = $a['board_id'];

            $boards["$board_id"] = array();
            $boards["$board_id"]['board_id'] = $board_id;
            $boards["$board_id"]['url'] = $a['url'];
            $boards["$board_id"]['is_collaborator'] = $a['is_collaborator'];
            $boards["$board_id"]['is_owner'] = $a['is_owner'];
            $boards["$board_id"]['collaborator_count'] = $a['collaborator_count'];
            $boards["$board_id"]['image_cover_url'] = $a['image_cover_url'];
            $boards["$board_id"]['name'] = $a['name'];
            $boards["$board_id"]['description'] = $a['description'];

            if($a['category']==""){
                $boards["$board_id"]['category'] = "none";
            }
            else{
                $boards["$board_id"]['category'] = $a['category'];
            }

            $boards["$board_id"]['pin_count'] = $a['pin_count'];
            $boards["$board_id"]['follower_count'] = $a['follower_count'];
            $boards["$board_id"]['created_at'] = $a['created_at'];
        }


        /*
         * Get all pins for the logged-in user
         */
        $pins = array();

        $acc = "select * from data_pins_new where user_id=$cust_user_id
	        order by created_at desc $pins_limit";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $pin_id = $a['pin_id'];
            $board_id = $a['board_id'];

            $pins["$pin_id"] = array(
                'pin_id' => $pin_id,
                'board_id' => $a['board_id'],
                'domain' => $a['domain'],
                'method' => $a['method'],
                'is_repin' => $a['is_repin'],
                'image_url' => $a['image_url'],
                'link' => $a['link'],
                'description' => $a['description'],
                'dominant_color' => $a['dominant_color'],
                'repin_count' => $a['repin_count'],
                'like_count' => $a['like_count'],
                'comment_count' => $a['comment_count'],
                'created_at' => $a['created_at'],
                'board_url' => @$boards[$board_id]['url'],
                'board_name' => @$boards[$board_id]['name'],
                'category' => @$boards[$board_id]['category']
            );
        }


        $dataTable_pins = true;
        $datePicker = true;



        /*
        |--------------------------------------------------------------------------
        | Create the DataTable javascript to insert into the <head>
        |--------------------------------------------------------------------------
        */


        $max_repins = 0;
        $max_likes = 0;
        $max_comments = 0;
        $min_date = 99999999999999;
        $max_date = 1;

        $datatable_js = "";

        foreach($pins as $b){

            $days_ago = round(($current_date - $b['created_at'])/60/60/24);

            //							if($days_ago > 21){
            //								$days_ago_print = number_format(($days_ago/7),0) . " weeks ago";
            //							} else {
            //								$days_ago_print = $days_ago . " days ago";
            //							}

            $pin_id = $b['pin_id'];
            $pin_board_id = $b['board_id'];
            $pin_image_url = $b['image_url'];
            $pin_domain = $b['domain'];
            $pin_is_repin = $b['is_repin'];
            $pin_link = trim(str_replace('"', '', $b['link']));

            if($b['dominant_color']!=""){
                $pin_dominant_color = hex2rgb($b['dominant_color']);
            } else {
                $pin_dominant_color = "";
            }
            $pin_method = $b['method'];


            if($pin_domain==""){
                $pin_domain_label = $b['method'];
                $pin_domain_text = $b['method'];
            } else {
                if(strlen($pin_domain)>16){
                    $pin_domain_text = substr($pin_domain,0,14)."..";
                } else {
                    $pin_domain_text = "$pin_domain";
                }

                $pin_domain_label = $pin_link;
            }


            $pin_description = $b['description'];

            $pin_description = str_replace('\\', '', $pin_description);
            $pin_description = str_replace('"', '\\"', $pin_description);
            $pin_description = trim(preg_replace('/[^(\x20-\x7F)]*/','', $pin_description));

            $pin_repins = $b['repin_count'];

            if($pin_repins > $max_repins){
                $max_repins = $pin_repins;
            }

            $pin_likes = $b['like_count'];

            if($pin_likes > $max_likes){
                $max_likes = $pin_likes;
            }

            $pin_comments = $b['comment_count'];

            if($pin_comments > $max_comments){
                $max_comments = $pin_comments;
            }

            $pin_created_at = $b['created_at'];

            if($pin_created_at > $max_date){
                $max_date = $pin_created_at;
            }
            if($pin_created_at < $min_date && $pin_created_at > 0){
                $min_date = $pin_created_at;
            }

            if($pin_created_at >= 0){
                $date_created_at = date("m/d/Y", $pin_created_at);
            } else {
                $date_created_at = date("m/d/Y", 1325394000);
            }


            $pin_board_name = str_replace('"','\"', $b['board_name']);

            //checks for board_id (last 8 digits) as the GET parameter and assigns the 'board name' to a variable which will be checked against the board filter's list of dropdown options and if there is a match, that board will be automatically selected to pre-filter the page on load (see footer.php for jQuery which matches the board name with the dropdown option and auto-selects the option).
            if(isset($_GET['b'])){
                if((substr($pin_board_id,-8) == $_GET['b']) && (!isset($board_filter))){
                    $board_filter = $pin_board_name;
                }
            } else {
                $board_filter = false;
            }

            $pin_board_url = $b['board_url'];
            $pin_board_category = renameCategories($b['category']);



            $datatable_js .= "
            [\"$pin_board_category\",
            \"$pin_board_id\",
            \"$pin_board_name\",
            \"$pin_board_url\",
            \"$pin_id\",
            \"$pin_repins\",
            \"$pin_likes\",
            \"$pin_comments\",
            \"$date_created_at\",
            \"$pin_domain_label\",
            \"$pin_image_url\",
            \"$pin_description\",
            \"\"],";

        }

        $max_date_print = date("m/d/Y", $max_date);
        $today_date_print = date("m/d/Y", $current_date);
        $min_date_print = date("m/d/Y", $min_date);
        $last_week_date = date("m/d/Y", strtotime("-7 days",$current_date));
        $last_month_date = date("m/d/Y", strtotime("-1 month",$current_date));

        $date_filters = View::make('analytics.pages.optimize.pins_date_filter');

        /*
        |--------------------------------------------------------------------------
        | Header Vars
        |--------------------------------------------------------------------------
        */
        /*
         * Insert necessary variables into the <head> tag
         */
        $head_vars = array(
                 'last_week_date'   => $last_week_date,
                 'last_month_date'  => $last_month_date,
                 'today_date_print' => $today_date_print,
                 'min_date_print'   => $min_date_print,
                 'max_date_print'   => $max_date_print,
                 'max_repins'       => $max_repins,
                 'max_likes'        => $max_likes,
                 'max_comments'     => $max_comments,
                 'board_filter'     => $board_filter,
                 'current_date'     => $current_date,
                 'export_insert'    => $export_insert,
                 'history_enabled'  => $history_enabled,
                 'date_filters'     => $date_filters,
                 'dataTable_pins'   => $dataTable_pins,
                 'datatable_js'     => $datatable_js
        );

        $vars = array_merge($vars, $head_vars);

        /*
        |--------------------------------------------------------------------------
        | Main Content Vars
        |--------------------------------------------------------------------------
        */
        /*
         * Insert variables into the profile_pins view for the main_content area
         */
        $pins_vars = array(
            'customer'                  => $this->logged_in_customer,
            'pins_upgrade_alert'        => $pins_upgrade_alert,
            'export_module'             => $export_module,
            'export_popover'            => $export_popover,
            'history_enabled'           => $history_enabled
        );

        $this->logged_in_customer->recordEvent(
                                 UserHistory::VIEW_REPORT,
                                 $parameters = array(
                                     'report' => 'Pin Inspector'
                                 )
        );

        $vars                   = array_merge($vars, $pins_vars);

        $this->layout->body_id = 'pin-inspector';
        $vars['nav_pins_class'] .= ' active';
        $vars['report_url'] = 'pins/owned';

        $this->layout->head .= View::make('analytics.components.head.datatable_pins', $vars);
        $this->layout->head .= View::make('analytics.components.head.site_tour', $vars);

        $this->layout->sub_navigation = View::make('analytics.components.sub_nav.optimize', $vars);


        $this->layout->main_content = View::make('analytics.pages.profile_pins', $vars);

        /*
        |--------------------------------------------------------------------------
        | Footer Vars
        |--------------------------------------------------------------------------
        */
        /*
         * Insert the datatable_pins component into the pre_body_close section
         */
        $this->layout->pre_body_close .= View::make('analytics.components.pre_body_close.datatable_pins');


    }

    public function showOwnedBoardPins($board_id_piece){

        echo $board_id_piece;
    }

    public function showTrendingPinsDefault(){

        echo "Trending default";
    }

    public function showTrendingPins($number_to_show){

        echo $number_to_show;
    }
}



function cmp($a, $b) {
    if (!$_GET['sort']) {
        $sort = "followers";
    } else {
        $sort = $_GET['sort'];
    }

    if ($a['current']["$sort"] > $b['current']["$sort"]) {
        return -1;
    } else if ($a['current']["$sort"] < $b['current']["$sort"]) {
        return 1;
    } else {
        return 0;
    }
}

function formatNumber($x) {
    if (!$x) {
        return "-";
    } else {
        return number_format($x);
    }
}

function formatPercentage($x) {
    $x = $x * 100;
    if ($x >= 0) {
        return "<span style='color: green;font-weight:normal;font-size:12px'>(" . number_format($x,1) . "%)</span>";
    } else if($x < 0) {
        return "<span style='color: #aaa;font-weight:normal;font-size:12px'>(" . number_format($x,1) . "%)</span>";
    } else if($x == "na"){
        return "<span style='color: #aaa;font-weight:normal;font-size:12px'>(--%)</span>";
    }
}
function formatAbsolute($x) {
    if ($x > 0) {
        return "<span class='pos'><i class='icon-arrow-up'></i>" . number_format($x,0) . "</span>";
    } elseif ($x == 0) {
        return "<span class='neg'> &nbsp;--</span>";
    } else {
        return "<span class='neg'><i class='icon-arrow-down'></i>" . number_format($x,0) . "</span>";
    }
}

function formatAbsoluteKPI($x) {
    if ($x > 0) {
        return "<span style='color: green;'><i class='icon-arrow-up'></i>" . number_format($x,2) . "</span>";
    } elseif ($x == 0) {
        return "<span style='color: #aaa;'> &nbsp;--</span>";
    } else {
        return "<span style='color: #aaa;'><i class='icon-arrow-down'></i>" . number_format($x,2) . "</span>";
    }
}

function getTimestampFromDate($date) {
    $m = substr($date, 0, 2);
    $d = substr($date, 3, 2);
    $y = substr($date, 6, 4);
    $t = mktime(0,0,0,$m, $d, $y);
    return $t;
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

function max_with_2keys($array, $key1, $key2) {
    if (!is_array($array) || count($array) == 0) return false;
    $max = $array[0][$key1][$key2];
    foreach($array as $a) {
        if($a[$key1][$key2] > $max) {
            $max = $a[$key1][$key2];
        }
    }
    return $max;
}

function min_with_2keys($array, $key1, $key2) {
    if (!is_array($array) || count($array) == 0) return false;
    $min = INF;
    foreach($array as $a) {
        if(($a[$key1][$key2] < $min) && ($a[$key1][$key2] != 0)) {
            $min = $a[$key1][$key2];
        }
    }
    return $min;
}

function max_change_2keys($array, $key1, $key2, $data) {
    if (!is_array($array) || count($array) == 0) return false;
    $max = $array[0][$key1][$data] - $array[0][$key2][$data];
    foreach($array as $a) {
        if(($a[$key1][$data]!=0) && ($a[$key2][$data]!=0) && ($a[$key1][$data] - $a[$key2][$data] > $max)) {
            $max = $a[$key1][$data] - $a[$key2][$data];
        }
    }
    return $max;
}

function hex2rgb($hex) {
    $hex = str_replace("#", "", $hex);

    if(strlen($hex) == 3) {
        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
    } else {
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
    }
    $rgb = "rgba(".$r.", ".$g.", ".$b.", 0.4)";
    // returns the rgb values separated by commas
    return $rgb;
}