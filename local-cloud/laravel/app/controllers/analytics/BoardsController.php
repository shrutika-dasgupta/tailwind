<?php namespace Analytics;

ini_set('memory_limit', '250M');

use
    DateTime,
    DatePeriod,
    DateInterval,
    Log,
    Session,
    UserHistory,
    View;

/**
 * Class BoardsController
 *
 * @package Analytics
 */
class BoardsController extends BaseController
{

    /**
     * Construct
     * @author  Will
     */
    public function __construct() {

        parent::__construct();

        Log::setLog(__FILE__,'Reporting','Boards_Report');
    }


    protected $layout = 'layouts.analytics';

    public function downloadBoards($start_date, $end_date, $type)
    {

        $vars = $this->baseLegacyVariables();
        extract($vars);

        /*
         * Check customer features
         */
        if ($customer->hasFeature('boards_history_all')) {
            $day_limit         = 730;
            $date_limit_clause = "and date >= " . strtotime("-$day_limit days", getFlatDate(time())) . "";
            $popover_custom_date = "";
            $popover_basic_date  = "";
        } else {
            if ($customer->hasFeature('boards_history_180')) {
                $day_limit         = 365;
                $date_limit_clause = "and date >= " . strtotime("-$day_limit days", getFlatDate(time())) . "";
                $popover_custom_date = "";
                $popover_basic_date  = "";
            } else {
                if ($customer->hasFeature('boards_history_90')) {
                    $day_limit           = 90;
                    $chart_hide          = "";
                    $date_limit_clause   = "and date >= " . strtotime("-$day_limit days", getFlatDate(time())) . "";
                    $popover_custom_date = "";
                    $popover_basic_date  = "";
                } else {
                    $day_limit           = 2;
                    $is_free_account     = true;
                    $date_limit_clause   = "and date >= " . strtotime("-$day_limit days", getFlatDate(time())) . "";
                    $range               = "Day";

                    $popover_custom_date = createPopover("#reportrange", "click", "bottom", "<span class=\"text-success\"><strong>Upgrade to Unlock</strong></span>", "boards-custom-date-wrapper",
                        $customer->plan()->plan_id, "<strong><ul><li>Get historical data on every board</li><li>Filter using custom date ranges</li></ul>");
                }
            }
        }

        if ($customer->hasFeature('boards_export')) {
            $export_button  = "<div class='export-show active pull-right'>Export</div>";
            $export_module  = "<div class='pull-right dataTable-export' style='text-align:right;'></div>";
            $export_insert  = "T";
            $export_popover = "";
        } else {
            return $this->showBoardsDefault();
        }

        if($type != "csv"){
            return $this->showBoardsDefault();
        }



        $date_limit = strtotime("-$day_limit days", getFlatDate(time()));


        /*
         * Get All of a Customer's Boards
         */
        $boards = array();

        $acc = "select * from data_boards where user_id='$cust_user_id' and track_type!='deleted'";
        $acc_res = mysql_query($acc, $conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $board_id = $a['board_id'];

            $boards["$board_id"]                       = array();
            $boards["$board_id"]['board_id']           = $board_id;
            $boards["$board_id"]['url']                = $a['url'];
            $boards["$board_id"]['is_collaborator']    = $a['is_collaborator'];
            $boards["$board_id"]['is_owner']           = $a['is_owner'];
            $boards["$board_id"]['collaborator_count'] = $a['collaborator_count'];
            $boards["$board_id"]['image_cover_url']    = $a['image_cover_url'];
            $boards["$board_id"]['name']               = $a['name'];
            $boards["$board_id"]['description']        = $a['description'];

            if ($a['category'] == "") {
                $boards["$board_id"]['category'] = "none";
            } else {
                $boards["$board_id"]['category'] = $a['category'];
            }

            $boards["$board_id"]['pin_count']      = $a['pin_count'];
            $boards["$board_id"]['follower_count'] = $a['follower_count'];
            $boards["$board_id"]['created_at']     = $a['created_at'];
        }


        /*
         * Get the last date calcs were completed for these boards
         */
        $cache_timestamp = 0;
        foreach ($boards as $b) {
            $acc = "select * from status_boards where board_id= " . $b['board_id'] . " and track_type!='orphan'";
            $acc_res = mysql_query($acc, $conn) or die(mysql_error());
            while ($a = mysql_fetch_array($acc_res)) {
                if ($a['last_calced'] < $cache_timestamp && $a['last_calced']!=0) {
                    $cache_timestamp = $a['last_calced'];
                }
            }
        }


        /*
         * Sets time variables if custom date range is used
         */
        if ($start_date && $end_date) {
            $last_date    = getTimestampFromDate($start_date);
            $current_date = getTimestampFromDate($end_date);

            if ($current_date < $last_date) {
                $temp         = $current_date;
                $current_date = $last_date;
                $last_date    = $temp;
            }

        } else {

            return $this->showBoardsDefault();
        }



        /*
         * Create header row in csv output
         */
        $csv_output = 'board,category,date,follower_count,pin_count,repin_count,like_count,comment_count,virality_score,engagement_score' . "\n";

        /*
         * Get Historical data for each board
         */
        foreach ($boards as $b) {

            $board_id = $b['board_id'];

            $acc = "select * from calcs_board_history where board_id='$board_id' $date_limit_clause
                    order by date desc";
            $acc_res = mysql_query($acc, $conn) or die(mysql_error());
            while ($a = mysql_fetch_array($acc_res)) {
                $date = $a['date'];

                $boards[$board_id]["$date"]                 = array();
                $boards[$board_id]["$date"]['date']         = $date;
                $boards[$board_id]["$date"]['followers']    = $a['followers'];
                $boards[$board_id]["$date"]['pins']         = $a['pins'];
                $boards[$board_id]["$date"]['repins']       = $a['repins'];
                $boards[$board_id]["$date"]['likes']        = $a['likes'];
                $boards[$board_id]["$date"]['comments']     = $a['comments'];
                $boards[$board_id]["$date"]['has_repins']   = $a['pins_atleast_one_repin'];
                $boards[$board_id]["$date"]['has_likes']    = $a['pins_atleast_one_like'];
                $boards[$board_id]["$date"]['has_comments'] = $a['pins_atleast_one_comment'];
                $boards[$board_id]["$date"]['has_actions']  = $a['pins_atleast_one_engage'];
            }

        }


        /*
        |--------------------------------------------------------------------------
        | Prepare Data
        |--------------------------------------------------------------------------
        */




        foreach ($boards as $b) {

            $name     = str_replace(',', '', $b['name']);
            $name     = str_replace('\'', '', $name);
            $name     = preg_replace('/[^A-Za-z0-9 ]/', ' ', $name);
            $category = $b['category'];

            foreach($b as $bdate => $bvalue){
                if ($bdate >= $last_date) {

                    $csv_date       = date("n-j-Y", $bdate);
                    $csv_followers  = $bvalue['followers'];
                    $csv_pins       = $bvalue['pins'];
                    $csv_repins     = $bvalue['repins'];
                    $csv_likes      = $bvalue['likes'];
                    $csv_comments   = $bvalue['comments'];
                    $csv_virality   = number_format($bvalue['repins'] / $bvalue['pins'], 2);
                    $csv_engagement = number_format($bvalue['repins'] / $bvalue['pins'] / $bvalue['followers'] * 1000, 2);

                    $csv_output .= "$name,$category,$csv_date,$csv_followers,$csv_pins,$csv_repins,$csv_likes,$csv_comments,$csv_virality,$csv_engagement" . "\n";
                }
            }
        }

        $date = date("F-j-Y");

        $this->logged_in_customer->recordEvent(
            UserHistory::EXPORT_REPORT,
            $parameters = array(
                'report' => 'boards'
            )
        );

        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"Tailwind-Analytics-$cust_username-Board-Data-$date.csv\"");
        echo $csv_output;
        exit;
    }


    /**
     * @author Alex
     */
    public function showBoards($range, $start_date = false, $end_date = false)
    {

        $vars = $this->baseLegacyVariables();

        /*
        |--------------------------------------------------------------------------
        | Merged stuff
        |--------------------------------------------------------------------------
        */
        extract($vars);

        $this->layout_defaults['page']          = 'boards';
        $this->layout_defaults['top_nav_title'] = 'Your Boards';
        $this->layout->top_navigation           = $this->buildTopNavigation();

        $this->layout->side_navigation = $this->buildSideNavigation('boards');
        $is_free_account               = false;


        /*
         * Check customer features
         */
        if ($customer->hasFeature('boards_history_all')) {
            $day_limit           = 1000;
            $date_limit_clause = "";
            $popover_custom_date = "";
            $popover_basic_date  = "";
        } else {
            if ($customer->hasFeature('boards_history_180')) {
                $day_limit         = 365;
                $date_limit_clause = "and date >= " . strtotime("-$day_limit days", getFlatDate(time())) . "";
                $popover_custom_date = "";
                $popover_basic_date  = "";
            } else {
                if ($customer->hasFeature('boards_history_90')) {
                    $day_limit           = 90;
                    $chart_hide          = "";
                    $date_limit_clause   = "and date >= " . strtotime("-$day_limit days", getFlatDate(time())) . "";
                    $popover_custom_date = "";
                    $popover_basic_date  = "";
                } else {
                    $day_limit           = 2;
                    $is_free_account     = true;
                    $date_limit_clause   = "and date >= " . strtotime("-$day_limit days", getFlatDate(time())) . "";
                    $range               = "Day";
                    $popover_custom_date = createPopover("#reportrange", "click", "bottom", "<span class=\"text-success\"><strong>Upgrade to Unlock</strong></span>", "boards-custom-date-wrapper",
                        $customer->plan()->plan_id, "<strong><ul><li>Get historical data on every board</li><li>Filter using custom date ranges</li></ul>");
                }
            }
        }


        if ($customer->hasFeature('boards_date_7_days')) {
            $week_link = "href='/boards/Week'";
            $week_pill = "";
        } else {
            $week_link = "";
            $week_pill = "class='inactive'";
        }

        if ($customer->hasFeature('boards_date_14_days')) {
            $week2_link = "href='/boards/2Weeks'";
            $week2_pill = "";
        } else {
            $week2_link = "";
            $week2_pill = "class='inactive'";
        }

        if ($customer->hasFeature('boards_date_30_days')) {
            $month_link = "href='/boards/Month'";
            $month_pill = "";
        } else {
            $month_link = "";
            $month_pill = "class='inactive'";
        }

        if ($customer->hasFeature('boards_date_custom')) {
            $custom_date_state      = "";
            $custom_datepicker_to   = "id=\"datepickerTo\"";
            $custom_datepicker_from = "id=\"datepickerFrom\"";
            $custom_button          = "<button style='margin-right: 17px; margin-left: 10px;' onclick=\"var start_date=$('#datepickerFrom').val();var end_date=$('#datepickerTo').val();window.location = '/boards/custom/'+start_date+'/'+end_date;\" class=\"btn\">Change Date</button>";
            $custom_button_disabled = "";
        } else {
            $custom_date_state      = "inactive";
            $custom_datepicker_to   = "";
            $custom_datepicker_from = "";
            $custom_button          = "";
            $custom_button_disabled = "<button style='margin: 7px 17px 0 10px;' class=\"btn disabled pull-right\">Change Date</button>";
        }

        if ($range == "Week") {
            $week_pill = "class=\"active\"";
        } else if ($range == "2Weeks") {
            $week2_pill = "class=\"active\"";
        } else if ($range == "Month") {
            $month_pill = "class=\"active\"";
        }


        $date_limit = strtotime("-$day_limit days", getFlatDate(time()));


        /*
         * Get All of a Customer's Boards
         */
        $boards = array();

        $acc = "select * from data_boards where user_id='$cust_user_id' and track_type!='deleted'";
        $acc_res = mysql_query($acc, $conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $board_id = $a['board_id'];

            $boards["$board_id"]                       = array();
            $boards["$board_id"]['board_id']           = $board_id;
            $boards["$board_id"]['url']                = $a['url'];
            $boards["$board_id"]['is_collaborator']    = $a['is_collaborator'];
            $boards["$board_id"]['is_owner']           = $a['is_owner'];
            $boards["$board_id"]['collaborator_count'] = $a['collaborator_count'];
            $boards["$board_id"]['image_cover_url']    = $a['image_cover_url'];
            $boards["$board_id"]['name']               = $a['name'];
            $boards["$board_id"]['description']        = $a['description'];

            if ($a['category'] == "") {
                $boards["$board_id"]['category'] = "none";
            } else {
                $boards["$board_id"]['category'] = $a['category'];
            }

            $boards["$board_id"]['pin_count']      = $a['pin_count'];
            $boards["$board_id"]['follower_count'] = $a['follower_count'];
            $boards["$board_id"]['created_at']     = $a['created_at'];
        }

        /*
         * Get the last date calcs were completed for these boards
         */
        $cache_timestamp = 0;
        foreach ($boards as $b) {
            $acc = "select * from status_boards where board_id= " . $b['board_id'] . "
            and track_type != 'not_found' and track_type != 'deleted'";
            $acc_res = mysql_query($acc, $conn) or die(mysql_error());
            while ($a = mysql_fetch_array($acc_res)) {
                if($a['track_type']!='orphan'){
                    if($a['last_calced']!=0){
                        if($cache_timestamp == 0){
                            $cache_timestamp = $a['last_calced'];
                        } else {
                            if ($a['last_calced'] < $cache_timestamp) {
                                $cache_timestamp = $a['last_calced'];
                            }
                        }
                    }
                }
            }
        }



//	if($cache_timestamp == 0){
//		print "boards not ready yet";
//		exit;
//	}


        /*
         * Sets time variables if custom date range is used
         */
        if ($range == "custom" && $start_date && $end_date) {

            $cache_timestamp = getFlatDate($cache_timestamp);

            $last_date    = getTimestampFromDate($start_date);
            $current_date = getTimestampFromDate($end_date);

            $custom_num_days = round(abs($current_date - $last_date) / 60 / 60 / 24);

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

            /*
             * Sets time variables for standard periodic requests (week, months, etc).
             */
            if ($range == "Month" && !$is_free_account) {
                $current_date        = getFlatDate($cache_timestamp);
                $last_date           = getFlatDate(strtotime("-1 month", $cache_timestamp));
                $last_date_fallback  = getFlatDate(strtotime("-1 month 1 day", $cache_timestamp));
                $compare_date        = getFlatDate(strtotime("-2 months", $cache_timestamp));
                $compare2_date       = getFlatDate(strtotime("-3 months", $cache_timestamp));
                $current_name        = "Last 30 Days";
                $old_name            = "Prior 30 Days";
                $older_name          = "2 Months Ago";
                $current_chart_label = "Past Month";
                $old_chart_label     = "Prior Month";
                $older_chart_label   = "2 Months Prior";
                $chart_time_label    = "Monthly";
                $day_range           = 30;

            } else if ($range == "Week") {
                $current_date        = getFlatDate($cache_timestamp);
                $last_date           = getFlatDate(strtotime("-7 days", $cache_timestamp));
                $last_date_fallback  = getFlatDate(strtotime("-8 days", $cache_timestamp));
                $compare_date        = getFlatDate(strtotime("-14 days", $cache_timestamp));
                $compare2_date       = getFlatDate(strtotime("-20 days", $cache_timestamp));
                $current_name        = "Last 7 Days";
                $old_name            = "Prior 7 Days";
                $older_name          = "2 Weeks Ago";
                $current_chart_label = "Past Week";
                $old_chart_label     = "Prior Week";
                $older_chart_label   = "2 Weeks Prior";
                $chart_time_label    = "Weekly";
                $day_range           = 7;
            } else if ($range == "2Weeks" && !$is_free_account) {
                $current_date        = getFlatDate($cache_timestamp);
                $last_date           = getFlatDate(strtotime("-14 days", $cache_timestamp));
                $compare_date        = getFlatDate(strtotime("-28 days", $cache_timestamp));
                $compare2_date       = getFlatDate(strtotime("-42 days", $cache_timestamp));
                $limit_date          = getFlatDate(strtotime("-56 days", $cache_timestamp));
                $current_name        = "Last 14 Days";
                $old_name            = "Prior 14 Days";
                $older_name          = "2 Weeks Ago";
                $current_chart_label = "Past 14 Days";
                $old_chart_label     = "Prior 14 Days";
                $older_chart_label   = "2 Weeks Prior";
                $chart_time_label    = "Weekly";
                $day_range           = 14;
            } else if ($is_free_account) {
                $current_date        = getFlatDate($cache_timestamp);
                $last_date           = getFlatDate(strtotime("-7 days", $cache_timestamp));
                $last_date_fallback  = getFlatDate(strtotime("-8 days", $cache_timestamp));
                $compare_date        = getFlatDate(strtotime("-14 days", $cache_timestamp));
                $compare2_date       = getFlatDate(strtotime("-20 days", $cache_timestamp));
                $current_name        = "Last 7 Days";
                $old_name            = "Prior 7 Days";
                $older_name          = "2 Weeks Ago";
                $current_chart_label = "Past Week";
                $old_chart_label     = "Prior Week";
                $older_chart_label   = "2 Weeks Prior";
                $chart_time_label    = "Weekly";
                $day_range           = 7;
                $range               = "None";
            }
        }

        $cache_timestamp_print = date("m-d-Y", $cache_timestamp);
        $last_date_print       = date("m-d-Y", $last_date);
        $current_date_print    = date("m-d-Y", $current_date);
        $compare_date_print    = date("m-d-Y", $compare_date);
        $compare2_date_print   = date("m-d-Y", $compare2_date);

        $last_date_export    = date("m-d-Y", $last_date);
        $current_date_export = date("m-d-Y", $current_date);


        if ($customer->hasFeature('boards_export')) {
            $export_button  = "<div class='export-show active pull-right'>Export</div>";
            $export_module  = "<div class='pull-right dataTable-export' style='text-align:right;'></div>
                               <div class='pull-right boards-export'>
                                    <a class='btn btn-mini DTTT_button_csv' href='/boards/$last_date_export/$current_date_export/export/csv'>
                                        <span>
                                            Export <i class='icon-new-tab'></i>
                                        </span>
                                    </a>
                               </div>";
            $export_insert  = "T";
            $export_popover = "";
        } else {
            $export_button  = "<div class='export-show pull-right'>Export</div>";
            $export_module  = "
                        <div class='boards-export inactive'>
                            <a class='btn btn-mini DTTT_button_csv'>
                                <span>
                                    Export <i class='icon-new-tab'></i>
                                </span>
                            </a>
                        </div>";
            $export_insert  = "";
            $export_popover = createPopover(".boards-export", "hover", "bottom", "<span class=\"text-success\"><strong>Need to Export your Data?</strong></span>", "boards_export",
                $customer->plan()->plan_id, "Upgrade to enable exporting data across your dashboard.<ul><li><strong>Instantly download CSV files</strong> of any report</li><li><strong>Take your data with you</strong> anywhere it needs to go!</li></ul>");
        }



        //setup notes for not enough history
        if ($range == "Week") {
            if ($days_of_calcs < 7) {
                if ($days_of_calcs == 2) {
                    $current_name = "Last 24 Hours";
                } else {
                    $current_name = "Last $days_of_calcs Days";
                }

                $not_enough_history_week   = "
									<span class='gauge-icon' data-toggle='tooltip' data-container='body' data-original-title=\"<strong>Showing $current_name.</strong> Your account is only $days_of_calcs days old!  Keep stopping by and building more history!\" data-placement='bottom'><i id='gauge-icon' class='icon-new'></i></span>
									";
                $not_enough_history_2weeks = "";
                $not_enough_history_month  = "";
            } else {
                $not_enough_history_week   = "";
                $not_enough_history_2weeks = "";
                $not_enough_history_month  = "";
            }
        } else if ($range == "2Weeks") {
            if ($days_of_calcs < 14) {
                if ($days_of_calcs == 2) {
                    $current_name = "Last 24 Hours";
                } else {
                    $current_name = "Last $days_of_calcs Days";
                }
                $not_enough_history_week   = "";
                $not_enough_history_2weeks = "
									<span class='gauge-icon' data-toggle='tooltip' data-container='body' data-original-title=\"<strong>Showing $current_name.</strong> Your account is only $days_of_calcs days old!  Keep stopping by and building more history!\" data-placement='bottom'><i id='gauge-icon' class='icon-new'></i></span>
									";
                $not_enough_history_month  = "";
            } else {
                $not_enough_history_week   = "";
                $not_enough_history_2weeks = "";
                $not_enough_history_month  = "";
            }
        } else if ($range == "Month") {
            if ($days_of_calcs < 30) {
                if ($days_of_calcs == 2) {
                    $current_name = "Last 24 Hours";
                } else {
                    $current_name = "Last $days_of_calcs Days";
                }
                $not_enough_history_week   = "";
                $not_enough_history_2weeks = "";
                $not_enough_history_month  = "
									<span class='gauge-icon' data-toggle='tooltip' data-container='body' data-original-title=\"<strong>Showing $current_name.</strong> Your account is only $days_of_calcs days old!  Keep stopping by and building more history!\" data-placement='bottom'><i id='gauge-icon' class='icon-new'></i></span>
									";
            } else {
                $not_enough_history_week   = "";
                $not_enough_history_2weeks = "";
                $not_enough_history_month  = "";
            }
        } else {
            $not_enough_history_week   = "";
            $not_enough_history_2weeks = "";
            $not_enough_history_month  = "";
        }


        /*
         * Get Historical data for each board to fill Datatables
         */
        foreach ($boards as $b) {

            $board_id = $b['board_id'];

            $acc = "select * from calcs_board_history where board_id='$board_id' $date_limit_clause";
            $acc_res = mysql_query($acc, $conn) or die(mysql_error());
            while ($a = mysql_fetch_array($acc_res)) {
                $date = $a['date'];

                $boards[$board_id]["$date"]                 = array();
                $boards[$board_id]["$date"]['date']         = $date;
                $boards[$board_id]["$date"]['followers']    = $a['followers'];
                $boards[$board_id]["$date"]['pins']         = $a['pins'];
                $boards[$board_id]["$date"]['repins']       = $a['repins'];
                $boards[$board_id]["$date"]['likes']        = $a['likes'];
                $boards[$board_id]["$date"]['comments']     = $a['comments'];
                $boards[$board_id]["$date"]['has_repins']   = $a['pins_atleast_one_repin'];
                $boards[$board_id]["$date"]['has_likes']    = $a['pins_atleast_one_like'];
                $boards[$board_id]["$date"]['has_comments'] = $a['pins_atleast_one_comment'];
                $boards[$board_id]["$date"]['has_actions']  = $a['pins_atleast_one_engage'];
            }

        }


        //check for GET parameters in the url so we can pass them further on
        $uri_keep = "" . $_SERVER['REQUEST_URI'] . "";
        if (strpos($uri_keep, "?")) {
            $get_params = true;
        } else {
            $get_params = false;
        }

        $is_boards        = true;
        $dataTable_boards = true;
        $datePicker       = true;


        /*
        |--------------------------------------------------------------------------
        | Datatable
        |--------------------------------------------------------------------------
        */

        $min_date = 99999999999999;
        $max_date = 1;

        $max_repins_change = 0;

        $max_rppf  = 0;
        $max_rpppf = 0;


        //Choose which type of chart to show by default, or show based on last user user choice
        if (isset($_GET['type'])) {
            if ($_GET['type'] == "cumulative") {
                Session::put('board_table_type', "cumulative");
            } else if ($_GET['type'] == "daily") {
                Session::put('board_table_type', "daily");
            } else if ($_GET['type'] == "none") {
                Session::put('board_table_type', "none");
            }
        } else {
            $boards_daily                 = false;
            $boards_cumulative            = true;
            $chart_btn_none               = "btn-pressed disabled active";
            $chart_btn_daily              = "";
            $chart_btn_cum                = "";
            Session::put('board_table_type', "none");
        }

        if (Session::get('board_table_type') == "cumulative") {
            $boards_cumulative = true;
            $boards_daily      = false;
            $chart_btn_cum     = "btn-pressed disabled active";
            $chart_btn_daily   = "";
            $chart_btn_none    = "";
        } else if (Session::get('board_table_type') == "daily") {
            $boards_daily      = true;
            $boards_cumulative = false;
            $chart_btn_daily   = "btn-pressed disabled active";
            $chart_btn_cum     = "";
            $chart_btn_none    = "";
        } else if (Session::get('board_table_type') == "none") {
            $boards_daily      = false;
            $boards_cumulative = true;
            $chart_btn_daily   = "";
            $chart_btn_cum     = "";
            $chart_btn_none    = "btn-pressed disabled active";
        } else {
            $boards_daily      = false;
            $boards_cumulative = true;
            $chart_btn_daily   = "";
            $chart_btn_cum     = "";
            $chart_btn_none    = "btn-pressed disabled active";
        }

        $datatable_js = "";

        if(isset($_GET['metric'])){
            if($_GET['metric']=="Like"){
                Session::put('metric', "Like");
            } else {
                Session::put('metric', "Repin");
            }
        }

        if(Session::has('metric')){
            if(Session::get('metric') == "Like"){
                $show_metric = "Like";
                $like_button_class = "active";
                $repin_button_class = "";
            } else {
                $show_metric = "Repin";
                $like_button_class = "";
                $repin_button_class = "active";
            }
        } else {
            $show_metric = "Repin";
            $like_button_class = "";
            $repin_button_class = "active";
        }


        if ($is_free_account) {

            foreach ($boards as $key => $b) {

                $name               = str_replace(',', '', $b['name']);
                $name               = str_replace('\'', '', $name);
                $name               = preg_replace('/[^A-Za-z0-9 ]/', ' ', $name);
                $url                = $b['url'];
                $board_id           = $b['board_id'];
                $cover_url          = $b['image_cover_url'];
                $category           = $b['category'];
                $is_owner           = $b['is_owner'];
                $is_collaborator    = $b['is_collaborator'];
                $collaborator_count = $b['collaborator_count'];
                if($collaborator_count > 0){
                    $is_collaborative = "(<i class='icon-users' data-toggle='tooltip' data-container='body' data-placement='top' data-title='This is a Collaborative Board'></i>)";
                } else {
                    $is_collaborative = "";
                }
                $created_at         = $b['created_at'];
                @$current_followers = $b[$current_date]['followers'];
                @$current_repins = $b[$current_date]['repins'];
                @$current_likes = $b[$current_date]['likes'];
                @$current_comments = $b[$current_date]['comments'];
                @$current_pins = $b[$current_date]['pins'];
                @$current_has_repins = $b[$current_date]['has_repins'];
                @$current_has_actions = $b[$current_date]['has_actions'];
                @$current_rppf = $current_repins / $current_pins;
                @$current_rpppf = $current_repins / $current_pins / $current_followers * 1000;

                $current_followers = number_format($current_followers, 0);
                $current_likes     = number_format($current_likes, 0);
                $current_repins    = number_format($current_repins, 0);
                $current_comments  = number_format($current_comments, 0);
                $current_pins      = number_format($current_pins, 0);
                $current_rppf      = number_format($current_rppf, 2, ".","");
                $current_rpppf     = number_format($current_rpppf, 2, ".","");

                //Set max Repins/Pin and Repins/Pin/Follower(1000)
                if ($current_rppf > $max_rppf) {
                    $max_rppf = $current_rppf;
                }
                if ($current_rpppf > $max_rpppf) {
                    $max_rpppf = $current_rpppf;
                }


                //check max followers/repins/pins numbers across all boards to levelset.
                $max_followers = max_with_2keys($boards, $current_date, 'followers');
                $min_followers = min_with_2keys($boards, $current_date, 'followers');
                $max_pins      = max_with_2keys($boards, $current_date, 'pins');
                $min_pins      = min_with_2keys($boards, $current_date, 'pins');
                $max_repins    = max_with_2keys($boards, $current_date, 'repins');
                $min_repins    = min_with_2keys($boards, $current_date, 'repins');
                $max_likes     = max_with_2keys($boards, $current_date, 'likes');
                $min_likes     = min_with_2keys($boards, $current_date, 'likes');
                $max_comments  = max_with_2keys($boards, $current_date, 'comments');
                $min_comments  = min_with_2keys($boards, $current_date, 'comments');


                $board_follower_range = $max_followers - $min_followers;
                $board_pin_range      = $max_pins - $min_pins;
                $board_repin_range   = $max_repins - $min_repins;
                $board_like_range   = $max_likes - $min_likes;
                $board_comment_range   = $max_comments - $min_comments;


                $datatable_js .= "
                [\"<div class='datatable_board_bg' style='background-image: url($cover_url);'><div class='datatable_board_label'>$is_collaborative <a data-toggle='tooltip' data-placement='top' data-original-title='Click to Analyze Your Pins' href='/pins/owned?b=" . (substr($board_id, -8)) . "'>$name</a><a class='board_link_out' data-toggle='tooltip' data-original-title='See Board on Pinterest' data-placement='right' href='http://pinterest.com$url' target='_blank'> <i class='icon-new-tab'></i></a></div></div>\",

                    \"$current_pins\",

                    \"$current_followers\",

                    \"$current_repins\",

                    \"$current_likes\",

                    \"$current_comments\",

                    \"$current_rppf\",
                    \"$current_rpppf\"],
                ";
            }


        } else {

            foreach ($boards as $b) {

                $name                = str_replace(',', '', $b['name']);
                $name                = str_replace('\'', '', $name);
                $name                = preg_replace('/[^A-Za-z0-9 ]/', ' ', $name);
                $url                 = $b['url'];
                $board_id            = $b['board_id'];
                $cover_url           = $b['image_cover_url'];
                $category            = $b['category'];
                $is_owner            = $b['is_owner'];
                $is_collaborator     = $b['is_collaborator'];
                $collaborator_count  = $b['collaborator_count'];
                if($collaborator_count > 0){
                    $is_collaborative = "(<i class='icon-users' data-toggle='tooltip' data-container='body' data-placement='top' data-title='This is a Collaborative Board'></i>)";
                } else {
                    $is_collaborative = "";
                }
                $created_at          = $b['created_at'];
                $current_followers   = $b[$current_date]['followers'];
                $current_repins      = $b[$current_date]['repins'];
                $current_likes       = $b[$current_date]['likes'];
                $current_comments    = $b[$current_date]['comments'];
                $current_pins        = $b[$current_date]['pins'];
                $current_has_repins  = $b[$current_date]['has_repins'];
                $current_has_actions = $b[$current_date]['has_actions'];
                @$current_rppf = $current_repins / $current_pins;
                @$current_rpppf = $current_repins / $current_pins / $current_followers * 1000;


                @$past_followers = $b[$last_date]['followers'];
                @$past_repins = $b[$last_date]['repins'];
                @$past_likes = $b[$last_date]['likes'];
                @$past_comments = $b[$last_date]['comments'];
                @$past_pins = $b[$last_date]['pins'];
                @$past_has_repins = $b[$last_date]['has_repins'];
                @$past_has_actions = $b[$last_date]['has_actions'];
                @$past_rppf = $past_repins / $past_pins;
                @$past_rpppf = $past_repins / $past_pins / $past_followers * 1000;

                @$compare_followers = $b[$compare_date]['followers'];
                @$compare_repins = $b[$compare_date]['repins'];
                @$compare_likes = $b[$compare_date]['likes'];
                @$compare_comments = $b[$compare_date]['comments'];
                @$compare_pins = $b[$compare_date]['pins'];
                @$compare_has_repins = $b[$compare_date]['has_repins'];
                @$compare_has_actions = $b[$compare_date]['has_actions'];
                @$compare_rppf = $compare_repins / $compare_pins;
                @$compare_rpppf = $compare_repins / $compare_pins / $compare_followers * 1000;

                @$compare2_followers = $b[$compare2_date]['followers'];
                @$compare2_repins = $b[$compare2_date]['repins'];
                @$compare2_likes = $b[$compare2_date]['likes'];
                @$compare2_comments = $b[$compare2_date]['comments'];
                @$compare2_pins = $b[$compare2_date]['pins'];
                @$compare2_has_repins = $b[$compare2_date]['has_repins'];
                @$compare2_has_actions = $b[$compare2_date]['has_actions'];
                @$compare2_rppf = $compare2_repins / $compare2_pins;
                @$compare2_rpppf = $compare2_repins / $compare2_pins / $compare2_followers * 1000;

                if ($name == "") {
                    continue;
                }


                if($created_at > $last_date){
                    //growth period 1
                    if (!$past_followers) {
                        $followers_growth = 0;
                    } else {
                        if ($past_followers == 0) {
                            $followers_growth = $current_followers;
                        } else {
                            $followers_growth = $current_followers - $past_followers;
                        }
                    }

                    if (!$past_repins) {
                        $repins_growth = 0;
                    } else {
                        if ($past_repins == 0) {
                            $repins_growth = $current_repins;
                        } else {
                            $repins_growth = $current_repins - $past_repins;
                        }
                    }

                    if (!$past_likes) {
                        $likes_growth = 0;
                    } else {
                        if ($past_likes == 0) {
                            $likes_growth = $current_likes;
                        } else {
                            $likes_growth = $current_likes - $past_likes;
                        }
                    }

                    if (!$past_comments) {
                        $comments_growth = 0;
                    } else {
                        if ($past_comments == 0) {
                            $comments_growth = $current_comments;
                        } else {
                            $comments_growth = $current_comments - $past_comments;
                        }
                    }

                    if (!$past_pins) {
                        $pin_growth = 0;
                    } else {
                        if ($past_pins == 0) {
                            $pins_growth = $current_pins;
                        } else {
                            $pins_growth = $current_pins - $past_pins;
                        }
                    }

                    if (!$past_rpppf) {
                        $rpppf_growth = 0;
                    } else {
                        if ($past_rpppf == 0) {
                            $rpppf_growth = number_format($current_rpppf, 2);
                        } else {
                            $rpppf_growth = number_format($current_rpppf - $past_rpppf, 2);
                        }
                    }
                } else {
                    //growth period 1
                    if (!$past_followers) {
                        $followers_growth = 0;
                    } else {
                        if ($past_followers == 0) {
                            $followers_growth = $current_followers;
                        } else {
                            $followers_growth = $current_followers - $past_followers;
                        }
                    }

                    if (!$past_repins) {
                        $repins_growth = 0;
                    } else {
                        if ($past_repins == 0) {
                            $repins_growth = $current_repins;
                        } else {
                            $repins_growth = $current_repins - $past_repins;
                        }
                    }

                    if (!$past_likes) {
                        $likes_growth = 0;
                    } else {
                        if ($past_likes == 0) {
                            $likes_growth = $current_likes;
                        } else {
                            $likes_growth = $current_likes - $past_likes;
                        }
                    }

                    if (!$past_comments) {
                        $comments_growth = 0;
                    } else {
                        if ($past_comments == 0) {
                            $comments_growth = $current_comments;
                        } else {
                            $comments_growth = $current_comments - $past_comments;
                        }
                    }

                    if (!$past_pins) {
                        $pin_growth = 0;
                    } else {
                        if ($past_pins == 0) {
                            $pins_growth = $current_pins;
                        } else {
                            $pins_growth = $current_pins - $past_pins;
                        }
                    }

                    if (!$past_rpppf) {
                        $rpppf_growth = 0;
                    } else {
                        if ($past_rpppf == 0) {
                            $rpppf_growth = number_format($current_rpppf, 2);
                        } else {
                            $rpppf_growth = number_format($current_rpppf - $past_rpppf, 2);
                        }
                    }
                }







                //growth period 2
                if (!$compare_followers) {
                    $followers_growth2 = 0;
                } else {
                    if ($compare_followers == 0) {
                        $followers_growth2 = $past_followers;
                    } else {
                        $followers_growth2 = $past_followers - $compare_followers;
                    }
                }

                if (!$compare_repins) {
                    $repins_growth2 = 0;
                } else {
                    if ($compare_repins == 0) {
                        $repins_growth2 = $past_repins;
                    } else {
                        $repins_growth2 = $past_repins - $compare_repins;
                    }
                }

                if (!$compare_likes) {
                    $likes_growth2 = 0;
                } else {
                    if ($compare_likes == 0) {
                        $likes_growth2 = $past_likes;
                    } else {
                        $likes_growth2 = $past_likes - $compare_likes;
                    }
                }

                if (!$compare_comments) {
                    $comments_growth2 = 0;
                } else {
                    if ($compare_comments == 0) {
                        $comments_growth2 = $past_comments;
                    } else {
                        $comments_growth2 = $past_comments - $compare_comments;
                    }
                }

                if (!$compare_pins) {
                    $pin_growth2 = 0;
                } else {
                    if ($compare_pins == 0) {
                        $pins_growth2 = $past_pins;
                    } else {
                        $pins_growth2 = $past_pins - $compare_pins;
                    }
                }

                if (!$compare_rpppf) {
                    $rpppf_growth2 = 0;
                } else {
                    if ($compare_rpppf == 0) {
                        $rpppf_growth2 = number_format($past_rpppf, 2);
                    } else {
                        $rpppf_growth2 = number_format($past_rpppf - $compare_rpppf, 2);
                    }
                }

                //growth period 3
                if (!$compare2_followers) {
                    $followers_growth3 = 0;
                } else {
                    if ($compare2_followers == 0) {
                        $followers_growth3 = $compare_followers;
                    } else {
                        $followers_growth3 = $compare_followers - $compare2_followers;
                    }
                }

                if (!$compare2_repins) {
                    $repins_growth3 = 0;
                } else {
                    if ($compare2_repins == 0) {
                        $repins_growth3 = $compare_repins;
                    } else {
                        $repins_growth3 = $compare_repins - $compare2_repins;
                    }
                }

                if (!$compare2_likes) {
                    $likes_growth3 = 0;
                } else {
                    if ($compare2_likes == 0) {
                        $likes_growth3 = $compare_likes;
                    } else {
                        $likes_growth3 = $compare_likes - $compare2_likes;
                    }
                }

                if (!$compare2_comments) {
                    $comments_growth3 = 0;
                } else {
                    if ($compare2_comments == 0) {
                        $comments_growth3 = $compare_comments;
                    } else {
                        $comments_growth3 = $compare_comments - $compare2_comments;
                    }
                }

                if (!$compare2_pins) {
                    $pin_growth3 = 0;
                } else {
                    if ($compare2_pins == 0) {
                        $pins_growth3 = $compare_pins;
                    } else {
                        $pins_growth3 = $compare_pins - $compare2_pins;
                    }
                }

                if (!$compare2_rpppf) {
                    $rpppf_growth3 = 0;
                } else {
                    if ($compare2_rpppf == 0) {
                        $rpppf_growth3 = number_format($compare_rpppf, 2);
                    } else {
                        $rpppf_growth3 = number_format($compare_rpppf - $compare2_rpppf, 2);
                    }
                }


                $current_followers = number_format($current_followers, 0);
                //						$past_followers = formatNumber($past_followers);
                //						$compare_followers = formatNumber($compare_followers);
                //						$compare2_followers = formatNumber($compare2_followers);

                $current_likes = number_format($current_likes, 0);
                //						$past_likes = formatNumber($past_likes);
                //						$compare_likes = formatNumber($compare_likes);
                //						$compare2_likes = formatNumber($compare2_likes);

                $current_repins = number_format($current_repins, 0);
                //						$past_repins = formatNumber($past_repins);
                //						$compare_repins = formatNumber($compare_repins);
                //						$compare2_repins = formatNumber($compare2_repins);
                $current_comments = number_format($current_comments, 0);

                $current_pins = number_format($current_pins, 0);
                //						$past_pins = formatNumber($past_pins);
                //						$compare_pins = formatNumber($compare_pins);
                //						$compare2_pins = formatNumber($compare2_pins);

                $current_rppf = number_format($current_rppf, 2, ".","");
                //						$past_rppf = formatNumber($past_rppf);
                //						$compare_rppf = formatNumber($compare_rppf);
                //						$compare2_rppf = formatNumber($compare2_rppf);

                $current_rpppf = number_format($current_rpppf, 2, ".","");
                //						$past_rpppf = formatNumber($past_rpppf);
                //						$compare_rpppf = formatNumber($compare_rpppf);
                //						$compare2_rpppf = formatNumber($compare2_rpppf);


                //Set max Repins/Pin and Repins/Pin/Follower(1000)
                if ($current_rppf > $max_rppf) {
                    $max_rppf = $current_rppf;
                }
                if ($current_rpppf > $max_rpppf) {
                    $max_rpppf = $current_rpppf;
                }


                if ($boards_daily) {
                    $board_pins_graph = "//chart.googleapis.com/chart?chf=bg,s,67676700&chbh=a,1&chs=100x35&cht=bvs&chco=82649E&chxs=0,676767,0,0,_,676767&chxt=x&chd=t:";

                    $board_followers_graph = "//chart.googleapis.com/chart?chs=100x35&chbh=a,1&cht=bvs&chf=bg,s,67676700&chco=3399CC&chm=B,3366CC8D,0,0,0&chxs=0,676767,0,0,_,676767&chxt=x&chd=t:";

                    $board_repins_graph = "//chart.googleapis.com/chart?chs=100x35&chbh=a,1&cht=bvs&chf=bg,s,67676700&chco=D77E81&chm=B,F6E8E88D,0,0,0&chxs=0,676767,0,0,_,676767&chxt=x&chd=t:";

                    $board_likes_graph = "//chart.googleapis.com/chart?chs=100x35&chbh=a,1&cht=bvs&chf=bg,s,67676700&chco=D77E81&chm=B,F6E8E88D,0,0,0&chxs=0,676767,0,0,_,676767&chxt=x&chd=t:";

                    $board_comments_graph = "//chart.googleapis.com/chart?chs=100x35&chbh=a,1&cht=bvs&chf=bg,s,67676700&chco=D77E81&chm=B,F6E8E88D,0,0,0&chxs=0,676767,0,0,_,676767&chxt=x&chd=t:";
                }

                if ($boards_cumulative) {
                    $board_pins_graph = "//chart.googleapis.com/chart?chf=bg,s,67676700&chs=100x35&cht=ls&chco=82649E&chm=B,EDF6ED8D,0,0,0&chd=t:";

                    $board_followers_graph = "//chart.googleapis.com/chart?chs=100x35&cht=ls&chf=bg,s,67676700&chco=3399CC&chm=B,3366CC8D,0,0,0&chd=t:";

                    $board_repins_graph = "//chart.googleapis.com/chart?chs=100x35&cht=ls&chf=bg,s,67676700&chco=D77E81&chm=B,F6E8E88D,0,0,0&chd=t:";

                    $board_likes_graph = "//chart.googleapis.com/chart?chs=100x35&cht=ls&chf=bg,s,67676700&chco=D77E81&chm=B,F6E8E88D,0,0,0&chd=t:";

                    $board_comments_graph = "//chart.googleapis.com/chart?chs=100x35&cht=ls&chf=bg,s,67676700&chco=D77E81&chm=B,F6E8E88D,0,0,0&chd=t:";
                }


                $max_pin_growth      = 0;
                $real_max_pin_growth = 0;
                $min_pin_growth      = INF;
                $real_min_pin_growth = INF;
                $total_pin_growth    = 0;

                $max_follower_growth      = 0;
                $real_max_follower_growth = 0;
                $min_follower_growth      = INF;
                $real_min_follower_growth = INF;
                $total_follower_growth    = 0;

                $max_repin_growth      = 0;
                $real_max_repin_growth = 0;
                $min_repin_growth      = INF;
                $real_min_repin_growth = INF;
                $total_repin_growth    = 0;

                $max_like_growth      = 0;
                $real_max_like_growth = 0;
                $min_like_growth      = INF;
                $real_min_like_growth = INF;
                $total_like_growth    = 0;

                $max_comment_growth      = 0;
                $real_max_comment_growth = 0;
                $min_comment_growth      = INF;
                $real_min_comment_growth = INF;
                $total_comment_growth    = 0;


                if ($range != "Day") {
                    $start = new DateTime();
                    $end   = new DateTime();

                    $start->setTimestamp($last_date);
                    $end->setTimestamp(strtotime("-1 day", $current_date));

                    $period = new DatePeriod($start, new DateInterval('P1D'), $end);


                    //iterate over user selected time-frame to get all historical data in order to populate each board's graphs
                    $period_counter        = 0;
                    $first_pin_amount      = array();
                    $first_follower_amount = array();
                    $first_repin_amount    = array();
                    $first_like_amount    = array();
                    $first_comment_amount    = array();
                    foreach ($period as $dt) {

                        //get timestamp for each day
                        $dtt = $dt->getTimestamp();


                        //get next and last day's timestamp for operations such as number of pins/day (total pins today - total pins yesterday) instead of cumulative pins to date.
                        $dtt_next = strtotime("+1 day", $dtt);
                        $dtt_last = strtotime("-1 day", $dtt);

                        if ($boards_daily) {

                            //PINS -- check that records exist for this day and prior day
                            if (isset($b["$dtt"]['pins'])) {
                                if (isset($b["$dtt_last"]['pins'])) {
                                    $board_pins_graph = $board_pins_graph . ($b["$dtt"]['pins'] - $b["$dtt_last"]['pins']) . ",";

                                    //get Max and Min for pin growth on every given day for this board in order to set the boundaries of the graph
                                    if (($b["$dtt"]['pins'] - $b["$dtt_last"]['pins']) > $max_pin_growth) {
                                        $max_pin_growth = ($b["$dtt"]['pins'] - $b["$dtt_last"]['pins']);
                                    }
                                    if (($b["$dtt"]['pins'] - $b["$dtt_last"]['pins']) < $min_pin_growth) {
                                        $min_pin_growth = ($b["$dtt"]['pins'] - $b["$dtt_last"]['pins']);
                                    }

                                    //calculate total cumulative pin growth based on incremental daily changes to display this number as a fallback incase 'current' date's or 'last' date's data is missing and the usual calculation is not available.
                                    $total_pin_growth += ($b["$dtt"]['pins'] - $b["$dtt_last"]['pins']);


                                } elseif ($period_counter != 0) {
                                    $board_pins_graph = $board_pins_graph . ($b["$dtt"]['pins'] - $last_pin_amount) . ",";
                                }

                                $last_pin_amount = $b["$dtt"]['pins'];
                            } else {
                                $board_pins_graph = $board_pins_graph . 0 . ",";
                            }

                            //FOLLOWERS -- check that record exists for this day and prior day
                            if (isset($b["$dtt"]['followers'])) {
                                if (isset($b["$dtt_last"]['followers'])) {
                                    $board_followers_graph = $board_followers_graph . ($b["$dtt"]['followers'] - $b["$dtt_last"]['followers']) . ",";

                                    //get Max and Min for follower growth on every given day for this board in order to set the boundaries of the graph
                                    if (($b["$dtt"]['followers'] - $b["$dtt_last"]['followers']) > $max_follower_growth) {
                                        $max_follower_growth = ($b["$dtt"]['followers'] - $b["$dtt_last"]['followers']);
                                    }
                                    if (($b["$dtt"]['followers'] - $b["$dtt_last"]['followers']) < $min_follower_growth) {
                                        $min_follower_growth = ($b["$dtt"]['followers'] - $b["$dtt_last"]['followers']);
                                    }

                                    //calculate total cumulative follower growth based on incremental daily changes to display this number as a fallback incase 'current' date's or 'last' date's data is missing and the usual calculation is not available.
                                    $total_follower_growth += ($b["$dtt"]['followers'] - $b["$dtt_last"]['followers']);

                                } elseif ($period_counter != 0) {
                                    $board_followers_graph = $board_followers_graph . ($b["$dtt"]['pins'] - $last_follower_amount) . ",";
                                }

                                $last_follower_amount = $b["$dtt"]['followers'];
                            } else {
                                $board_followers_graph = $board_followers_graph . 0 . ",";
                            }

                            //REPINS -- check that record exists for this day and prior day
                            if (isset($b["$dtt"]['repins'])) {
                                if (isset($b["$dtt_last"]['repins'])) {
                                    $board_repins_graph = $board_repins_graph . ($b["$dtt"]['repins'] - $b["$dtt_last"]['repins']) . ",";

                                    //get Max and Min for repin growth on every given day for this board in order to set the boundaries of the graph
                                    if (($b["$dtt"]['repins'] - $b["$dtt_last"]['repins']) > $max_repin_growth) {
                                        $max_repin_growth = ($b["$dtt"]['repins'] - $b["$dtt_last"]['repins']);
                                    }
                                    if (($b["$dtt"]['repins'] - $b["$dtt_last"]['repins']) < $min_repin_growth) {
                                        $min_repin_growth = ($b["$dtt"]['repins'] - $b["$dtt_last"]['repins']);
                                    }

                                    //calculate total cumulative repin growth based on incremental daily changes to display this number as a fallback incase 'current' date's or 'last' date's data is missing and the usual calculation is not available.
                                    $total_repin_growth += ($b["$dtt"]['repins'] - $b["$dtt_last"]['repins']);

                                } elseif ($period_counter != 0) {
                                    $board_repins_graph = $board_repins_graph . ($b["$dtt"]['pins'] - $last_repin_amount) . ",";
                                }

                                $last_repin_amount = $b["$dtt"]['repins'];
                            } else {
                                $board_repins_graph = $board_repins_graph . 0 . ",";
                            }

                            //likeS -- check that record exists for this day and prior day
                            if (isset($b["$dtt"]['likes'])) {
                                if (isset($b["$dtt_last"]['likes'])) {
                                    $board_likes_graph = $board_likes_graph . ($b["$dtt"]['likes'] - $b["$dtt_last"]['likes']) . ",";

                                    //get Max and Min for like growth on every given day for this board in order to set the boundaries of the graph
                                    if (($b["$dtt"]['likes'] - $b["$dtt_last"]['likes']) > $max_like_growth) {
                                        $max_like_growth = ($b["$dtt"]['likes'] - $b["$dtt_last"]['likes']);
                                    }
                                    if (($b["$dtt"]['likes'] - $b["$dtt_last"]['likes']) < $min_like_growth) {
                                        $min_like_growth = ($b["$dtt"]['likes'] - $b["$dtt_last"]['likes']);
                                    }

                                    //calculate total cumulative like growth based on incremental daily changes to display this number as a fallback incase 'current' date's or 'last' date's data is missing and the usual calculation is not available.
                                    $total_like_growth += ($b["$dtt"]['likes'] - $b["$dtt_last"]['likes']);

                                } elseif ($period_counter != 0) {
                                    $board_likes_graph = $board_likes_graph . ($b["$dtt"]['pins'] - $last_like_amount) . ",";
                                }

                                $last_like_amount = $b["$dtt"]['likes'];
                            } else {
                                $board_likes_graph = $board_likes_graph . 0 . ",";
                            }

                            //commentS -- check that record exists for this day and prior day
                            if (isset($b["$dtt"]['comments'])) {
                                if (isset($b["$dtt_last"]['comments'])) {
                                    $board_comments_graph = $board_comments_graph . ($b["$dtt"]['comments'] - $b["$dtt_last"]['comments']) . ",";

                                    //get Max and Min for comment growth on every given day for this board in order to set the boundaries of the graph
                                    if (($b["$dtt"]['comments'] - $b["$dtt_last"]['comments']) > $max_comment_growth) {
                                        $max_comment_growth = ($b["$dtt"]['comments'] - $b["$dtt_last"]['comments']);
                                    }
                                    if (($b["$dtt"]['comments'] - $b["$dtt_last"]['comments']) < $min_comment_growth) {
                                        $min_comment_growth = ($b["$dtt"]['comments'] - $b["$dtt_last"]['comments']);
                                    }

                                    //calculate total cumulative comment growth based on incremental daily changes to display this number as a fallback incase 'current' date's or 'last' date's data is missing and the usual calculation is not available.
                                    $total_comment_growth += ($b["$dtt"]['comments'] - $b["$dtt_last"]['comments']);

                                } elseif ($period_counter != 0) {
                                    $board_comments_graph = $board_comments_graph . ($b["$dtt"]['pins'] - $last_comment_amount) . ",";
                                }

                                $last_comment_amount = $b["$dtt"]['comments'];
                            } else {
                                $board_comments_graph = $board_comments_graph . 0 . ",";
                            }

                        }

                        if ($boards_cumulative) {

                            //PINS -- check that records exist for this day and prior day
                            if (isset($b["$dtt"]['pins'])) {
                                $board_pins_graph = $board_pins_graph . $b["$dtt"]['pins'] . ",";

                                $last_pin_amount = $b["$dtt"]['pins'];
                                if ($last_pin_amount > $max_pin_growth) {
                                    $max_pin_growth = $last_pin_amount;
                                }
                                if ($last_pin_amount < $min_pin_growth) {
                                    $min_pin_growth = $last_pin_amount;
                                }
                            } else {
                                if ($last_pin_amount == 0 && $period_counter != 0) {
                                    @$last_pin_amount = $b["$dtt_last"]['pins'];
                                    $board_pins_graph = $board_pins_graph . $last_pin_amount . ",";
                                }
                                if (isset($b["$dtt"]['pins'])
                                    && $period_counter == 0
                                ) {
                                    $first_pin_amount["$board_id"] = $b["$dtt"]['pins'];
                                }
                                if (isset($b["$dtt"]['pins'])
                                    && !$first_pin_amount["$board_id"]
                                ) {
                                    $first_pin_amount["$board_id"] = $b["$dtt"]['pins'];
                                }
                            }

                            //FOLLOWERS -- check that record exists for this day and prior day
                            if (isset($b["$dtt"]['followers'])) {
                                $board_followers_graph = $board_followers_graph . $b["$dtt"]['followers'] . ",";

                                $last_follower_amount = $b["$dtt"]['followers'];
                                if ($last_follower_amount > $max_follower_growth) {
                                    $max_follower_growth = $last_follower_amount;
                                }
                                if ($last_follower_amount < $min_follower_growth) {
                                    $min_follower_growth = $last_follower_amount;
                                }
                            } else {
                                if ($last_follower_amount == 0 && $period_counter != 0) {
                                    @$last_follower_amount = $b["$dtt_last"]['followers'];
                                    $board_followers_graph = $board_followers_graph . $last_follower_amount . ",";
                                }
                                if (isset($b["$dtt"]['followers'])
                                    && $period_counter == 0
                                ) {
                                    $first_follower_amount["$board_id"] = $b["$dtt"]['followers'];
                                }
                                if (isset($b["$dtt"]['followers'])
                                    && !$first_follower_amount["$board_id"]
                                ) {
                                    $first_follower_amount["$board_id"] = $b["$dtt"]['followers'];
                                }
                            }

                            //REPINS -- check that record exists for this day and prior day
                            if (isset($b["$dtt"]['repins'])) {
                                $board_repins_graph = $board_repins_graph . $b["$dtt"]['repins'] . ",";

                                $last_repin_amount = $b["$dtt"]['repins'];
                                if ($last_repin_amount > $max_repin_growth) {
                                    $max_repin_growth = $last_repin_amount;
                                }
                                if ($last_repin_amount < $min_repin_growth) {
                                    $min_repin_growth = $last_repin_amount;
                                }
                            } else {
                                if ($last_repin_amount == 0 && $period_counter != 0) {
                                    @$last_repin_amount = $b["$dtt_last"]['repins'];
                                    $board_repins_graph = $board_repins_graph . $last_repin_amount . ",";
                                }
                                if (isset($b["$dtt"]['repins'])
                                    && $period_counter == 0
                                ) {
                                    $first_repin_amount = $b["$dtt"]['repins'];
                                }
                                if (isset($b["$dtt"]['repins'])
                                    && !$first_repin_amount["$board_id"]
                                ) {
                                    $first_repin_amount["$board_id"] = $b["$dtt"]['repins'];
                                }
                            }

                            //likeS -- check that record exists for this day and prior day
                            if (isset($b["$dtt"]['likes'])) {
                                $board_likes_graph = $board_likes_graph . $b["$dtt"]['likes'] . ",";

                                $last_like_amount = $b["$dtt"]['likes'];
                                if ($last_like_amount > $max_like_growth) {
                                    $max_like_growth = $last_like_amount;
                                }
                                if ($last_like_amount < $min_like_growth) {
                                    $min_like_growth = $last_like_amount;
                                }
                            } else {
                                if ($last_like_amount == 0 && $period_counter != 0) {
                                    @$last_like_amount = $b["$dtt_last"]['likes'];
                                    $board_likes_graph = $board_likes_graph . $last_like_amount . ",";
                                }
                                if (isset($b["$dtt"]['likes'])
                                    && $period_counter == 0
                                ) {
                                    $first_like_amount = $b["$dtt"]['likes'];
                                }
                                if (isset($b["$dtt"]['likes'])
                                    && !$first_like_amount["$board_id"]
                                ) {
                                    $first_like_amount["$board_id"] = $b["$dtt"]['likes'];
                                }
                            }

                            //commentS -- check that record exists for this day and prior day
                            if (isset($b["$dtt"]['comments'])) {
                                $board_comments_graph = $board_comments_graph . $b["$dtt"]['comments'] . ",";

                                $last_comment_amount = $b["$dtt"]['comments'];
                                if ($last_comment_amount > $max_comment_growth) {
                                    $max_comment_growth = $last_comment_amount;
                                }
                                if ($last_comment_amount < $min_comment_growth) {
                                    $min_comment_growth = $last_comment_amount;
                                }
                            } else {
                                if ($last_comment_amount == 0 && $period_counter != 0) {
                                    @$last_comment_amount = $b["$dtt_last"]['comments'];
                                    $board_comments_graph = $board_comments_graph . $last_comment_amount . ",";
                                }
                                if (isset($b["$dtt"]['comments'])
                                    && $period_counter == 0
                                ) {
                                    $first_comment_amount = $b["$dtt"]['comments'];
                                }
                                if (isset($b["$dtt"]['comments'])
                                    && !$first_comment_amount["$board_id"]
                                ) {
                                    $first_comment_amount["$board_id"] = $b["$dtt"]['comments'];
                                }
                            }

                        }

                        $period_counter++;


                    } //END FOREACH LOOP

                } else {
                    if ($boards_daily) {

                        $board_pins_graph = $board_pins_graph . 0 . ",";

                        $board_followers_graph = $board_followers_graph . 0 . ",";

                        $board_repins_graph = $board_repins_graph . 0 . ",";

                        $board_likes_graph = $board_likes_graph . 0 . ",";

                        $board_comments_graph = $board_comments_graph . 0 . ",";

                    }

                    if ($boards_cumulative) {
                        $board_pins_graph = $board_pins_graph . $b[$last_date]['pins'] . ",";

                        $board_followers_graph = $board_followers_graph . $b[$last_date]['followers'] . ",";

                        $board_repins_graph = $board_repins_graph . $b[$last_date]['repins'] . ",";

                        $board_likes_graph = $board_likes_graph . $b[$last_date]['likes'] . ",";

                        $board_comments_graph = $board_comments_graph . $b[$last_date]['comments'] . ",";

                    }

                    $min_pin_growth   = 0;
                    $max_pin_growth   = $b[$current_date]['pins'] - $b[$last_date]['pins'];
                    $total_pin_growth = $max_pin_growth;

                    $min_follower_growth   = 0;
                    $max_follower_growth   = $b[$current_date]['followers'] - $b[$last_date]['followers'];
                    $total_follower_growth = $max_follower_growth;

                    $min_repin_growth   = 0;
                    $max_repin_growth   = $b[$current_date]['repins'] - $b[$last_date]['repins'];
                    $total_repin_growth = $max_repin_growth;

                    $min_like_growth   = 0;
                    $max_like_growth   = $b[$current_date]['likes'] - $b[$last_date]['likes'];
                    $total_like_growth = $max_like_growth;

                    $min_comment_growth   = 0;
                    $max_comment_growth   = $b[$current_date]['comments'] - $b[$last_date]['comments'];
                    $total_comment_growth = $max_comment_growth;
                }


                if ($boards_daily) {

                    $current_date_yest = strtotime("-1 day", $current_date);

                    //PINS -- add on final row of datatable array (can't have trailing commas, so need to handle this outside of the loop)
                    if ($b["$current_date"]['pins'] && $b["$current_date_yest"]['pins']) {
                        $board_pins_graph = $board_pins_graph . ($b["$current_date"]['pins'] - $b["$current_date_yest"]['pins']);
                    } else {
                        $board_pins_graph = substr($board_pins_graph, 0, strlen($board_pins_graph) - 1);
                    }

                    //FOLLOWERS -- add on final row of datatable array (due to same trailing comma issue)
                    if ($b["$current_date"]['followers'] && $b["$current_date_yest"]['followers']) {
                        $board_followers_graph = $board_followers_graph . ($b["$current_date"]['followers'] - $b["$current_date_yest"]['followers']);
                    } else {
                        $board_followers_graph = substr($board_followers_graph, 0, strlen($board_followers_graph) - 1);
                    }

                    //REPINS -- add on final row of datatable array (same trailing comma issue)
                    if ($b["$current_date"]['repins'] && $b["$current_date_yest"]['repins']) {
                        $board_repins_graph = $board_repins_graph . ($b["$current_date"]['repins'] - $b["$current_date_yest"]['repins']);
                    } else {
                        $board_repins_graph = substr($board_repins_graph, 0, strlen($board_repins_graph) - 1);
                    }

                    //likeS -- add on final row of datatable array (same trailing comma issue)
                    if ($b["$current_date"]['likes'] && $b["$current_date_yest"]['likes']) {
                        $board_likes_graph = $board_likes_graph . ($b["$current_date"]['likes'] - $b["$current_date_yest"]['likes']);
                    } else {
                        $board_likes_graph = substr($board_likes_graph, 0, strlen($board_likes_graph) - 1);
                    }

                    //commentS -- add on final row of datatable array (same trailing comma issue)
                    if ($b["$current_date"]['comments'] && $b["$current_date_yest"]['comments']) {
                        $board_comments_graph = $board_comments_graph . ($b["$current_date"]['comments'] - $b["$current_date_yest"]['comments']);
                    } else {
                        $board_comments_graph = substr($board_comments_graph, 0, strlen($board_comments_graph) - 1);
                    }
                }

                if ($boards_cumulative) {

                    //PINS -- add on final row of datatable array (can't have trailing commas, so need to handle this outside of the loop)
                    if ($b["$current_date"]['pins']) {
                        $board_pins_graph = $board_pins_graph . $b["$current_date"]['pins'];
                        $last_pin_amount  = $b["$dtt"]['pins'];
                    } else {
                        $board_pins_graph = substr($board_pins_graph, 0, strlen($board_pins_graph) - 1);
                    }

                    //FOLLOWERS -- add on final row of datatable array (due to same trailing comma issue)
                    if ($b["$current_date"]['followers']) {
                        $board_followers_graph = $board_followers_graph . $b["$current_date"]['followers'];
                        $last_follower_amount  = $b["$dtt"]['followers'];
                    } else {
                        $board_followers_graph = substr($board_followers_graph, 0, strlen($board_followers_graph) - 1);
                    }

                    //REPINS -- add on final row of datatable array (same trailing comma issue)
                    if ($b["$current_date"]['repins']) {
                        $board_repins_graph = $board_repins_graph . $b["$current_date"]['repins'];
                        $last_repin_amount  = $b["$dtt"]['repins'];
                    } else {
                        $board_repins_graph = substr($board_repins_graph, 0, strlen($board_repins_graph) - 1);
                    }

                    //likeS -- add on final row of datatable array (same trailing comma issue)
                    if ($b["$current_date"]['likes']) {
                        $board_likes_graph = $board_likes_graph . $b["$current_date"]['likes'];
                        $last_like_amount  = $b["$dtt"]['likes'];
                    } else {
                        $board_likes_graph = substr($board_likes_graph, 0, strlen($board_likes_graph) - 1);
                    }

                    //commentS -- add on final row of datatable array (same trailing comma issue)
                    if ($b["$current_date"]['comments']) {
                        $board_comments_graph = $board_comments_graph . $b["$current_date"]['comments'];
                        $last_comment_amount  = $b["$dtt"]['comments'];
                    } else {
                        $board_comments_graph = substr($board_comments_graph, 0, strlen($board_comments_graph) - 1);
                    }
                }

                if ($boards_daily) {

                    //Check overall Max and Min for pins, followers and repins across ALL boards
                    if ($max_pin_growth > $real_max_pin_growth) {
                        $real_max_pin_growth = $max_pin_growth;
                    }
                    if ($min_pin_growth < $real_min_pin_growth) {
                        $real_min_pin_growth = $min_pin_growth;
                    }

                    if ($max_follower_growth > $real_max_follower_growth) {
                        $real_max_follower_growth = $max_follower_growth;
                    }
                    if ($min_follower_growth < $real_min_follower_growth) {
                        $real_min_follower_growth = $min_follower_growth;
                    }

                    if ($max_repin_growth > $real_max_repin_growth) {
                        $real_max_repin_growth = $max_repin_growth;
                    }
                    if ($min_repin_growth < $real_min_repin_growth) {
                        $real_min_repin_growth = $min_repin_growth;
                    }

                    if ($max_like_growth > $real_max_like_growth) {
                        $real_max_like_growth = $max_like_growth;
                    }
                    if ($min_like_growth < $real_min_like_growth) {
                        $real_min_like_growth = $min_like_growth;
                    }

                    if ($max_comment_growth > $real_max_comment_growth) {
                        $real_max_comment_growth = $max_comment_growth;
                    }
                    if ($min_comment_growth < $real_min_comment_growth) {
                        $real_min_comment_growth = $min_comment_growth;
                    }
                }

                if ($boards_cumulative) {

                    //Check overall Max and Min for pins, followers and repins across ALL boards
                    if ($last_pin_amount > $real_max_pin_growth) {
                        $real_max_pin_growth = $last_pin_amount;
                    }

                    if ($last_follower_amount > $real_max_follower_growth) {
                        $real_max_follower_growth = $last_follower_amount;
                    }

                    if ($last_repin_amount > $real_max_repin_growth) {
                        $real_max_repin_growth = $last_repin_amount;
                    }

                    if ($last_like_amount > $real_max_like_growth) {
                        $real_max_like_growth = $last_like_amount;
                    }

                    if ($last_comment_amount > $real_max_comment_growth) {
                        $real_max_comment_growth = $last_comment_amount;
                    }

                }


                if ($boards_daily) {
                    if (is_infinite($min_pin_growth) || $min_pin_growth >= 0) {
                        $min_pin_growth = 0;
                    }
                    if (is_infinite($min_follower_growth) || $min_follower_growth >= 0) {
                        $min_follower_growth = 0;
                    }
                    if (is_infinite($min_repin_growth) || $min_repin_growth >= 0) {
                        $min_repin_growth = 0;
                    }
                    if (is_infinite($min_like_growth) || $min_like_growth >= 0) {
                        $min_like_growth = 0;
                    }
                    if (is_infinite($min_comment_growth) || $min_comment_growth >= 0) {
                        $min_comment_growth = 0;
                    }
                }

                if ($boards_cumulative) {
                    if (is_infinite($min_pin_growth)) {
                        $min_pin_growth = 0;
                    }
                    if (is_infinite($min_follower_growth)) {
                        $min_follower_growth = 0;
                    }
                    if (is_infinite($min_repin_growth)) {
                        $min_repin_growth = 0;
                    }
                    if (is_infinite($min_like_growth)) {
                        $min_like_growth = 0;
                    }
                    if (is_infinite($min_comment_growth)) {
                        $min_comment_growth = 0;
                    }
                }

                //PINS - set max and min range for each graph
                $pin_growth_range = $real_max_pin_growth - $min_pin_growth;

                //PINS - set the x-axis for each graph (needs to move dynamically in order to stay at the Zero value - depends on if there are negative values in the given board's chart or not)
                @$pin_axis = ((abs($min_pin_growth) / $pin_growth_range >= 1) || (abs($min_pin_growth) / $pin_growth_range == 0)) ? 0 : abs($min_pin_growth) / $pin_growth_range;

                //FOLLOWERS - set max and min range for each graph
                $follower_growth_range = $real_max_follower_growth - $min_follower_growth;

                //FOLLOWERS - set the x-axis for each graph (needs to move dynamically in order to stay at the Zero value - depends on if there are negative values in the given board's chart or not)
                @$follower_axis = ((abs($min_follower_growth) / $follower_growth_range >= 1) || (abs($min_follower_growth) / $follower_growth_range == 0)) ? 0 : abs($min_follower_growth) / $follower_growth_range;

                //REPINS - set max and min range for each graph
                $repin_growth_range = $real_max_repin_growth - $min_repin_growth;

                //REPINS - set the x-axis for each graph (needs to move dynamically in order to stay at the Zero value - depends on if there are negative values in the given board's chart or not)
                @$repin_axis = ((abs($min_repin_growth) / $repin_growth_range >= 1) || (abs($min_repin_growth) / $repin_growth_range == 0)) ? 0 : abs($min_repin_growth) / $repin_growth_range;

                //LIKES - set max and min range for each graph
                $like_growth_range = $real_max_like_growth - $min_like_growth;

                //LIKES - set the x-axis for each graph (needs to move dynamically in order to stay at the Zero value - depends on if there are negative values in the given board's chart or not)
                @$like_axis = ((abs($min_like_growth) / $like_growth_range >= 1) || (abs($min_like_growth) / $like_growth_range == 0)) ? 0 : abs($min_repin_growth) / $repin_growth_range;

                //commentS - set max and min range for each graph
                $comment_growth_range = $real_max_comment_growth - $min_comment_growth;

                //commentS - set the x-axis for each graph (needs to move dynamically in order to stay at the Zero value - depends on if there are negative values in the given board's chart or not)
                @$comment_axis = ((abs($min_comment_growth) / $comment_growth_range >= 1) || (abs($min_comment_growth) / $comment_growth_range == 0)) ? 0 : abs($min_repin_growth) / $repin_growth_range;



                if ($boards_cumulative) {

                    //check if data exists for current date and last date in user's chosen time range to show the growth.  If either does not exist, provide a fallback to calculated range of the stat over the time period.  As stats usually only grow with time, this will very likely be a very accurate fallback in the rare case either piece of data does not exist.
                    if (isset($b[$current_date]['pins'])
                        && isset($b[$last_date]['pins'])
                    ) {
                        $total_pin_growth = $b[$current_date]['pins'] - $b[$last_date]['pins'];
                    } elseif (!isset($b[$last_date]['pins']) && $created_at > $last_date) {
                        $total_pin_growth = $b[$current_date]['pins'];
                    } elseif (@$first_pin_amount["$board_id"] >= $last_pin_amount) {
                        //$total_pin_growth = $min_pin_growth - $max_pin_growth;
                    } else {
                        $total_pin_growth = $max_pin_growth - $min_pin_growth;
                    }

                    if (isset($b[$current_date]['followers'])
                        && isset($b[$last_date]['followers'])
                    ) {
                        $total_follower_growth = $b[$current_date]['followers'] - $b[$last_date]['followers'];
                    } elseif (@$first_follower_amount["$board_id"] >= $last_follower_amount) {
                        //$total_follower_growth = $min_follower_growth - $max_follower_growth;
                    } elseif (!isset($b[$last_date]['followers']) && $created_at > $last_date) {
                        $total_follower_growth = $b[$current_date]['followers'] - $last_follower_amount;
                    } else {
                        $total_follower_growth = $max_follower_growth - $min_follower_growth;
                    }

                    if (isset($b[$current_date]['repins'])
                        && isset($b[$last_date]['repins'])
                    ) {
                        $total_repin_growth = $b[$current_date]['repins'] - $b[$last_date]['repins'];
                    } elseif (empty($b[$last_date]['repins']) && $created_at > $last_date) {
                        $total_repin_growth = $b[$current_date]['repins'];
                    } elseif ($first_repin_amount["$board_id"] >= $last_repin_amount) {
                        //$total_repin_growth = $min_repin_growth - $max_repin_growth;
                    } else {
                        $total_repin_growth = $max_repin_growth - $min_repin_growth;
                    }

                    if (isset($b[$current_date]['likes'])
                        && isset($b[$last_date]['likes'])
                    ) {
                        $total_like_growth = $b[$current_date]['likes'] - $b[$last_date]['likes'];
                    } elseif (!isset($b[$last_date]['likes']) && $created_at > $last_date) {
                        $total_like_growth = $b[$current_date]['likes'];
                    } elseif (@$first_like_amount["$board_id"] >= $last_like_amount) {
                        //$total_like_growth = $min_like_growth - $max_like_growth;
                    } else {
                        $total_like_growth = $max_like_growth - $min_like_growth;
                    }

                    if (isset($b[$current_date]['comments'])
                        && isset($b[$last_date]['comments'])
                    ) {
                        $total_comment_growth = $b[$current_date]['comments'] - $b[$last_date]['comments'];
                    } elseif (!isset($b[$last_date]['comments']) && $created_at > $last_date) {
                        $total_comment_growth = $b[$current_date]['comments'];
                    } elseif (@$first_comment_amount["$board_id"] >= $last_comment_amount) {
                        //$total_comment_growth = $min_comment_growth - $max_comment_growth;
                    } else {
                        $total_comment_growth = $max_comment_growth - $min_comment_growth;
                    }

                }


                //PINS - set the styles for how to display the periodic growth amounts if positive (green) vs. non-positive (grey).
                if ($total_pin_growth > 0) {
                    $pin_label_style = "<span class='graph_label_pos'><i class='icon-arrow-up'></i>";
                } else {
                    $pin_label_style = "<span class='graph_label_neg'>";
                }

                //FOLLOWERS - set the styles for how to display the periodic growth amounts if positive (green) vs. non-positive (grey).
                if ($total_follower_growth > 0) {
                    $follower_label_style = "<span class='graph_label_pos'><i class='icon-arrow-up'></i>";
                } else {
                    $follower_label_style = "<span class='graph_label_neg'>";
                }

                //REPINS - set the styles for how to display the periodic growth amounts if positive (green) vs. non-positive (grey).
                if ($total_repin_growth > 0) {
                    $repin_label_style = "<span class='graph_label_pos'><i class='icon-arrow-up'></i>";
                } else {
                    $repin_label_style = "<span class='graph_label_neg'>";
                }

                //likeS - set the styles for how to display the periodic growth amounts if positive (green) vs. non-positive (grey).
                if ($total_like_growth > 0) {
                    $like_label_style = "<span class='graph_label_pos'><i class='icon-arrow-up'></i>";
                } else {
                    $like_label_style = "<span class='graph_label_neg'>";
                }

                //commentS - set the styles for how to display the periodic growth amounts if positive (green) vs. non-positive (grey).
                if ($total_comment_growth > 0) {
                    $comment_label_style = "<span class='graph_label_pos'><i class='icon-arrow-up'></i>";
                } else {
                    $comment_label_style = "<span class='graph_label_neg'>";
                }

                if ($boards_daily) {

                    //Complete the image API call string for each board's graph, prepending and appending IMG tag attributes
                    $board_pins_graph = "<img src='" . $board_pins_graph . "&chds=" . ($min_pin_growth) . "," . $real_max_pin_growth . "&chm=h,616161,0," . $pin_axis . ":" . $pin_axis . ",1,-1' width='100' height='35' alt='' />";

                    $board_followers_graph = "<img src='" . $board_followers_graph . "&chds=" . ($min_follower_growth) . "," . $real_max_follower_growth . "&chm=o,FF0000,0,360:360,5&chm=h,616161,0," . $follower_axis . ":" . $follower_axis . ",1,-1' width='100' height='35' alt='' />";

                    $board_repins_graph = "<img src='" . $board_repins_graph . "&chds=" . ($min_repin_growth) . "," . $real_max_repin_growth . "&chm=o,FF0000,0,360:360,5&chm=h,616161,0," . $repin_axis . ":" . $repin_axis . ",1,-1' width='100' height='35' alt='' />";

                    $board_likes_graph = "<img src='" . $board_likes_graph . "&chds=" . ($min_like_growth) . "," . $real_max_like_growth . "&chm=o,FF0000,0,360:360,5&chm=h,616161,0," . $like_axis . ":" . $like_axis . ",1,-1' width='100' height='35' alt='' />";

                    $board_comments_graph = "<img src='" . $board_comments_graph . "&chds=" . ($min_comment_growth) . "," . $real_max_comment_growth . "&chm=o,FF0000,0,360:360,5&chm=h,616161,0," . $comment_axis . ":" . $comment_axis . ",1,-1' width='100' height='35' alt='' />";
                }


                if ($boards_cumulative) {

                    //Complete the image API call string for each board's graph, prepending and appending IMG tag attributes
                    $board_pins_graph = "<img src='" . $board_pins_graph . "&chds=" . ($min_pin_growth - 1) . "," . ($max_pin_growth + 1) . "&chm=o,FF0000,0,360:360,5' width='100' height='35' alt='' />";

                    $board_followers_graph = "<img src='" . $board_followers_graph . "&chds=" . ($min_follower_growth - 1) . "," . ($max_follower_growth + 1) . "&chm=o,FF0000,0,360:360,5' width='100' height='35' alt='' />";

                    $board_repins_graph = "<img src='" . $board_repins_graph . "&chds=" . ($min_repin_growth - 1) . "," . ($max_repin_growth + 1) . "&chm=o,FF0000,0,360:360,5' width='100' height='35' alt='' />";

                    $board_likes_graph = "<img src='" . $board_likes_graph . "&chds=" . ($min_like_growth - 1) . "," . ($max_like_growth + 1) . "&chm=o,FF0000,0,360:360,5' width='100' height='35' alt='' />";

                    $board_comments_graph = "<img src='" . $board_comments_graph . "&chds=" . ($min_comment_growth - 1) . "," . ($max_comment_growth + 1) . "&chm=o,FF0000,0,360:360,5' width='100' height='35' alt='' />";
                }


                //check max follower number across all boards to levelset.
                $max_followers = max_with_2keys($boards, $current_date, 'followers');
                $min_followers = min_with_2keys($boards, $current_date, 'followers');
                $max_repins    = max_with_2keys($boards, $current_date, 'repins');
                $min_repins    = min_with_2keys($boards, $current_date, 'repins');
                $max_likes     = max_with_2keys($boards, $current_date, 'likes');
                $min_likes     = min_with_2keys($boards, $current_date, 'likes');
                $max_pins      = max_with_2keys($boards, $current_date, 'pins');
                $min_pins      = min_with_2keys($boards, $current_date, 'pins');
                $max_comments  = max_with_2keys($boards, $current_date, 'comments');
                $min_comments  = min_with_2keys($boards, $current_date, 'comments');

                $board_follower_range = $max_followers - $min_followers;
                $board_pin_range      = $max_pins - $min_pins;
                $board_repin_range    = $max_repins - $min_repins;
                $board_like_range     = $max_likes - $min_likes;
                $board_comment_range  = $max_comments - $min_comments;


                $max_follower_change  = max_change_2keys($boards, $current_date, $last_date, 'followers');
                $max_follower_change2 = max_change_2keys($boards, $last_date, $compare_date, 'followers');
                if ($max_follower_change > $max_follower_change2) {
                    $max_follower_change_all = $max_follower_change;
                } else {
                    $max_follower_change_all = $max_follower_change2;
                }


                $max_date_print   = date("m/d/Y", $max_date);
                $today_date_print = date("m/d/Y", $current_date);
                $min_date_print   = date("m/d/Y", $min_date);
                $last_week_date   = date("m/d/Y", strtotime("-7 days", $current_date));
                $last_month_date  = date("m/d/Y", strtotime("-1 month", $current_date));


                @$pin_perc = ((($current_pins - $min_pins) / $board_pin_range) * 80) + 20;
                @$pin_prev_perc = ((($current_pins - $past_pins) / $current_pins) * 80) + 20;
                @$pin_growth_perc = (($total_pin_growth / $current_pins) * 80);

                @$follower_perc = ((($current_followers - $min_followers) / $board_follower_range) * 80) + 20;
                @$follower_prev_perc = ((($current_followers - $past_followers) / $current_followers) * 80) + 20;
                @$follower_growth_perc = (($total_follower_growth / $current_followers) * 80);

                @$repin_perc = ((($current_repins - $min_repins) / $board_repin_range) * 80) + 20;
                @$repin_prev_perc = ((($current_repins - $past_repins) / $current_repins) * 80) + 20;
                @$repin_growth_perc = (($total_repin_growth / $current_repins) * 80);

                @$like_perc = ((($current_likes - $min_likes) / $board_like_range) * 80) + 20;
                @$like_prev_perc = ((($current_likes - $past_likes) / $current_likes) * 80) + 20;
                @$like_growth_perc = (($total_like_growth / $current_likes) * 80);

                @$comment_perc = ((($current_comments - $min_comments) / $board_comment_range) * 80) + 20;
                @$comment_prev_perc = ((($current_comments - $past_comments) / $current_comments) * 80) + 20;
                @$comment_growth_perc = (($total_comment_growth / $current_comments) * 80);

                //Supply final computed values into DataTable row
                $datatable_js .= "
                [\"<div class='datatable_board_bg' style='background-image: url($cover_url);'><div class='datatable_board_label'>$is_collaborative <a data-toggle='tooltip' data-placement='top' data-original-title='Click to Analyze Your Pins' href='/pins/owned?b=" . (substr($board_id, -8)) . "'>$name</a><a class='board_link_out' data-toggle='tooltip' data-original-title='See Board on Pinterest' data-placement='right' href='http://pinterest.com$url' target='_blank'> <i class='icon-new-tab'></i></a></div></div>\",



                    \"$current_pins\",
                    \"<div class='pull-left'><div>$board_pins_graph</div></div><div class='pull-right graph_label_wrap'> " . $pin_label_style . "$total_pin_growth</span></div>\",
                    \"<div class='graph_label_wrap_alt'><center> " . $pin_label_style . "$total_pin_growth</span></center></div>\",

                    \"$current_followers\",
                    \"<div class='pull-left'><div>$board_followers_graph</div></div><div class='pull-right graph_label_wrap'> " . $follower_label_style . "$total_follower_growth</span></div>\",
                    \"<div class='graph_label_wrap_alt'><center> " . $follower_label_style . "$total_follower_growth</span></center></div>\",

                    \"$current_repins\",
                    \"<div class='pull-left'><div>$board_repins_graph</div></div><div class='pull-right graph_label_wrap'> " . $repin_label_style . "$total_repin_growth</span></div>\",
                    \"<div class='graph_label_wrap_alt'><center> " . $repin_label_style . "$total_repin_growth</span></center></div>\",

                    \"$current_likes\",
                    \"<div class='pull-left'><div>$board_likes_graph</div></div><div class='pull-right graph_label_wrap'> " . $like_label_style . "$total_like_growth</span></div>\",
                    \"<div class='graph_label_wrap_alt'><center> " . $like_label_style . "$total_like_growth</span></center></div>\",

                    \"$current_comments\",
                    \"<div class='pull-left'><div>$board_comments_graph</div></div><div class='pull-right graph_label_wrap'> " . $comment_label_style . "$total_comment_growth</span></div>\",
                    \"<div class='graph_label_wrap_alt'><center> " . $comment_label_style . "$total_comment_growth</span></center></div>\",

                    \"$current_rppf\",
                    \"$current_rpppf\",
                    \"$pin_perc\",
                    \"$pin_growth_perc\",
                    \"$follower_perc\",
                    \"$follower_growth_perc\",
                    \"$repin_perc\",
                    \"$repin_growth_perc\",
                    \"$like_perc\",
                    \"$like_growth_perc\",
                    \"$comment_perc\",
                    \"$comment_growth_perc\"],
                ";

            }
        }

        /*
       |--------------------------------------------------------------------------
       | Header Vars
       |--------------------------------------------------------------------------
       */
        if (is_infinite($min_followers)) {
            $min_followers = 0;
        }

        if (is_infinite($min_pins)) {
            $min_pins = 0;
        }

        if (is_infinite($board_follower_range)) {
            $board_follower_range = 0;
        }
        if (is_infinite($board_pin_range)) {
            $board_pin_range = 0;
        }

        if (is_infinite($min_repins)) {
            $min_repins = 0;
        }

        if (is_infinite($board_repin_range)) {
            $board_repin_range = 0;
        }

        if (is_infinite($min_likes)) {
            $min_likes = 0;
        }

        if (is_infinite($board_like_range)) {
            $board_like_range = 0;
        }

        if (is_infinite($min_comments)) {
            $min_comments = 0;
        }

        if (is_infinite($board_comment_range)) {
            $board_comment_range = 0;
        }

        /*
         * Insert variables into the <head> tag for the datatables_boards view
         */
        $head_vars = array_merge($vars,
            array(
                 'boards'               => $boards,
                 'current_name'         => $current_name,
                 'current_chart_label'  => $current_chart_label,
                 'is_free_account'      => $is_free_account,
                 'board_table_type'     => Session::get('board_table_type'),
                 'max_pins'             => $max_pins,
                 'min_pins'             => $min_pins,
                 'board_pin_range'      => $board_pin_range,
                 'max_followers'        => $max_followers,
                 'min_followers'        => $min_followers,
                 'board_follower_range' => $board_follower_range,
                 'max_repins'           => $max_repins,
                 'min_repins'           => $min_repins,
                 'board_repin_range'    => $board_repin_range,
                 'max_likes'            => $max_likes,
                 'min_likes'            => $min_likes,
                 'board_like_range'     => $board_like_range,
                 'max_comments'         => $max_comments,
                 'min_comments'         => $min_comments,
                 'board_comment_range'  => $board_comment_range,
                 'show_metric'          => $show_metric,
                 'max_rppf'             => $max_rppf,
                 'max_rpppf'            => $max_rpppf,
                 'current_date'         => $current_date,
                 'export_insert'        => $export_insert,
                 'dataTable_boards'     => $dataTable_boards,
                 'datatable_js'         => $datatable_js
            ));

        $this->layout->head .= View::make('analytics.components.head.datatable_boards', $head_vars);


        /*
        |--------------------------------------------------------------------------
        | Main Content Vars
        |--------------------------------------------------------------------------
        */
        /*
         * Insert variables into the board_summary main_content page
         */
        $boards_vars = array(
            'customer'                  => $this->logged_in_customer,
            'range'                     => $range,
            'current_date'              => $current_date,
            'is_free_account'           => $is_free_account,
            'last_date'                 => $last_date,
            'current_date_print'        => $current_date_print,
            'last_date_print'           => $last_date_print,
            'cache_timestamp_print'     => $cache_timestamp_print,
            'day_limit'                 => $day_limit,
            'week_link'                 => $week_link,
            'week_pill'                 => $week_pill,
            'week2_link'                => $week2_link,
            'week2_pill'                => $week2_pill,
            'month_link'                => $month_link,
            'month_pill'                => $month_pill,
            'custom_date_state'         => $custom_date_state,
            'custom_datepicker_to'      => $custom_datepicker_to,
            'custom_datepicker_from'    => $custom_datepicker_from,
            'custom_button'             => $custom_button,
            'custom_button_disabled'    => $custom_button_disabled,
            'not_enough_history_week'   => $not_enough_history_week,
            'not_enough_history_2weeks' => $not_enough_history_2weeks,
            'not_enough_history_month'  => $not_enough_history_month,
            'export_button'             => $export_button,
            'export_module'             => $export_module,
            'export_popover'            => $export_popover,
            'popover_custom_date'       => $popover_custom_date,
            'popover_basic_date'        => $popover_basic_date,
            'show_metric'               => Session::get('metric')
        );

        $this->logged_in_customer->recordEvent(
                                 UserHistory::VIEW_REPORT,
                                     $parameters = array(
                                         'report' => 'boards',
                                         'range'  => $range,
                                     )
        );

        $vars = array_merge($vars, $boards_vars);

        $this->layout->body_id = 'boards';
        $vars['nav_boards_class'] .= ' active';
        $vars['report_url'] = 'boards';

        $this->layout->sub_navigation = View::make('analytics.components.sub_nav.measure', $vars);

        $this->layout->main_content = View::make('analytics.pages.board_summary', $vars);

        /*
        |--------------------------------------------------------------------------
        | Footer Vars
        |--------------------------------------------------------------------------
        */
        /*
         * Insert variables into the pre_body_close section for the datatable_boards view
         */
        $footer_vars = array_merge($vars,
            array(
                 'is_free_account' => $is_free_account,
                 'show_metric'     => $show_metric
            ));

        $this->layout->pre_body_close .= View::make('analytics.components.pre_body_close.datatable_boards', $footer_vars);
    }

    /**
     * @author  Will
     */
    public function showBoardsDefault()
    {
        return $this->showBoards('Week', '', '');

    }
}

function cmp($a, $b)
{
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

function formatNumber($x)
{
    if (!$x) {
        return "-";
    } else {
        return number_format($x);
    }
}

function formatPercentage($x)
{
    $x = $x * 100;
    if ($x >= 0) {
        return "<span style='color: #549E54;font-weight:normal;font-size:12px'>(" . number_format($x, 1) . "%)</span>";
    } else if ($x < 0) {
        return "<span style='color: #aaa;font-weight:normal;font-size:12px'>(" . number_format($x, 1) . "%)</span>";
    } else if ($x == "na") {
        return "<span style='color: #aaa;font-weight:normal;font-size:12px'>(--%)</span>";
    }
}

function formatAbsolute($x)
{
    if ($x > 0) {
        return "<span class='pos'><i class='icon-arrow-up'></i>" . number_format($x, 0) . "</span>";
    } elseif ($x == 0) {
        return "<span class='neg'> &nbsp;--</span>";
    } else {
        return "<span class='neg'><i class='icon-arrow-down'></i>" . number_format($x, 0) . "</span>";
    }
}

function formatAbsoluteKPI($x)
{
    if ($x > 0) {
        return "<span style='color: #549E54;'><i class='icon-arrow-up'></i>" . number_format($x, 2) . "</span>";
    } elseif ($x == 0) {
        return "<span style='color: #aaa;'> &nbsp;--</span>";
    } else {
        return "<span style='color: #aaa;'><i class='icon-arrow-down'></i>" . number_format($x, 2) . "</span>";
    }
}

function renameCategories($a)
{

    if ($a == "womens_fashion") {
        $b = "womens fashion";
    } elseif ($a == "diy_crafts") {
        $b = "diy & crafts";
    } elseif ($a == "health_fitness") {
        $b = "health & fitness";
    } elseif ($a == "holidays_events") {
        $b = "holidays & events";
    } elseif ($a == "none") {
        $b = "not specified";
    } elseif ($a == "holiday_events") {
        $b = "holidays & events";
    } elseif ($a == "home_decor") {
        $b = "home decor";
    } elseif ($a == "food_drink") {
        $b = "food & drink";
    } elseif ($a == "film_music_books") {
        $b = "film, music & books";
    } elseif ($a == "hair_beauty") {
        $b = "hair & beauty";
    } elseif ($a == "cars_motorcycles") {
        $b = "cars & motorcycles";
    } elseif ($a == "science_nature") {
        $b = "science & nature";
    } elseif ($a == "mens_fashion") {
        $b = "mens fashion";
    } elseif ($a == "illustrations_posters") {
        $b = "illustrations & posters";
    } elseif ($a == "art_arch") {
        $b = "art & architecture";
    } elseif ($a == "wedding_events") {
        $b = "weddings & events";
    } else {
        $b = $a;
    }

    return $b;

}

function max_with_2keys($array, $key1, $key2)
{
    if (!is_array($array) || count($array) == 0) return false;
    $max = 0;
    foreach ($array as $a) {
        if (@$a[$key1][$key2] > $max) {
            $max = $a[$key1][$key2];
        }
    }

    return $max;
}

function min_with_2keys($array, $key1, $key2)
{
    if (!is_array($array) || count($array) == 0) return false;
    $min = INF;
    foreach ($array as $a) {
        if (($a[$key1][$key2] < $min) && ($a[$key1][$key2] != 0)) {
            $min = $a[$key1][$key2];
        }
    }

    return $min;
}

function max_change_2keys($array, $key1, $key2, $data)
{
    if (!is_array($array) || count($array) == 0) return false;
    $max = @$array[0][$key1][$data] - @$array[0][$key2][$data];
    foreach (@$array as $a) {
        if ((@$a[$key1][$data] != 0) && (@$a[$key2][$data] != 0) && (@$a[$key1][$data] - @$a[$key2][$data] > $max)) {
            $max = @$a[$key1][$data] - @$a[$key2][$data];
        }
    }

    return $max;
}