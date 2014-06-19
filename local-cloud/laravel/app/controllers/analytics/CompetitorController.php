<?php namespace Analytics;

use View,
    Log,
    Redirect,
    Session;

use UserHistory,
    UserProperty;

use DateTime,
    DatePeriod,
    DateInterval;


ini_set('max_execution_time', '120');
ini_set('memory_limit', '200M');



/**
 * Class CompetitorController
 *
 * @package Analytics
 */
class CompetitorController extends BaseController
{

    protected $layout = 'layouts.analytics';

    /**
     * Construct
     * @author  Will
     */
    public function __construct() {

        parent::__construct();

        Log::setLog(__FILE__,'Reporting','Competitor_Report');
    }

    /**
     *
     */
    public function downloadBenchmarks($start_date, $end_date, $type)
    {
        return $start_date . $end_date . $type;
    }

    /**
     * Check feature permissions user has for the competitors report
     *
     * @author Alex
     *
     */
    public function checkCompetitorPermissions($range){

        $vars = $this->baseLegacyVariables();
        extract($vars);

        /*
         * Redirect to upgrade page if feature not available
         */
        if(!$customer->hasFeature('nav_comp_bench')){
            $query_string = http_build_query(
                array(
                     'ref'  => 'comp_bench',
                     'plan' => $customer->plan()->plan_id
                )
            );
            header('location:/upgrade?'.$query_string);
            exit;
        }

        $day_link = "href='/competitors/benchmarks/Day'";
        $day_pill = "";
        $week_link = "href='/competitors/benchmarks/Week'";
        $week_pill = "";
        $week2_link = "href='/competitors/benchmarks/2Weeks'";
        $week2_pill = "";
        $month_link = "href='/competitors/benchmarks/Month'";
        $month_pill = "";
        $custom_range = "";
        $custom_date_state      = "";
        $custom_datepicker_to   = "id=\"datepickerTo\"";
        $custom_datepicker_from = "id=\"datepickerFrom\"";
        $custom_button          = "<button style='margin-right: 17px; margin-left: 10px;' onclick=\"var start_date=$('#datepickerFrom').val();var end_date=$('#datepickerTo').val();window.location = '/competitors/benchmarks/custom/'+start_date+'/'+end_date;\" class=\"btn\">Change Date</button>";
        $custom_button_disabled = "";


        if($customer->hasFeature('comp_bench_history_all')){
            $day_limit         = 730;
            $date_limit_clause = "and date >= ".strtotime("-$day_limit days", getFlatDate(time()))."";
        } else {
            if($customer->hasFeature('comp_bench_history_180')){
                $day_limit = 365;
                $date_limit_clause = "and date >= ".strtotime("-$day_limit days", getFlatDate(time()))."";
            } else {
                if($customer->hasFeature('comp_bench_history_90')){
                    $day_limit = 90;
                    $date_limit_clause = "and date >= ".strtotime("-$day_limit days", getFlatDate(time()))."";
                } else {
                    $day_limit = 2;
                    $date_limit_clause = "and date >= ".strtotime("-$day_limit days", getFlatDate(time()))."";
                    $range     = "Day";

                    $day_link = "";
                    $day_pill = "class='inactive'";
                    $week_link = "";
                    $week_pill = "class='inactive'";
                    $week2_link = "";
                    $week2_pill = "class='inactive'";
                    $month_link = "";
                    $month_pill = "class='inactive'";
                    $custom_date_state      = "inactive";
                    $custom_datepicker_to   = "";
                    $custom_datepicker_from = "";
                    $custom_button          = "";
                    $custom_button_disabled = "<button style='margin: 7px 17px 0 10px;' class=\"btn disabled pull-right\">Change Date</button>";
                }
            }
        }

        if($customer->hasFeature('comp_bench_domains')){
            $is_domains_enabled  = true;
            $domains_tab_link    = "href=\"#domains\" data-toggle=\"tab\"";
            $domains_tab         = "";
            $domains_tab_enabled = "";
            $domains_popover     = "";
        } else {
            $is_domains_enabled  = false;
            $domains_tab_link    = "";
            $domains_tab         = "id='domains-tab'";
            $domains_tab_enabled = "class='inactive pointer'";
            $domains_popover = createPopover("#domains-tab","hover","bottom","<span class=\"text-success\"><strong>Upgrade to Unlock</strong></span>","comp_domains",
                $customer->plan()->plan_id,
                '<strong>Get Domain-level Benchmarking</strong> <br>and compare: <br><br><ul><li>number of Pins being created</li><li>number of Unique Pinners</li><li> and more</li></ul>');
        }


        $feature_date_limit = strtotime("-$day_limit days", getFlatDate(time()));

        $permission_check_vars = array(
            'date_limit_clause'      => $date_limit_clause,
            'day_limit'              => $day_limit,
            'day_link'               => $day_link,
            'day_pill'               => $day_pill,
            'week_link'              => $week_link,
            'week_pill'              => $week_pill,
            'week2_link'             => $week2_link,
            'week2_pill'             => $week2_pill,
            'month_link'             => $month_link,
            'month_pill'             => $month_pill,
            'custom_range'           => $custom_range,
            'custom_date_state'      => $custom_date_state,
            'custom_datepicker_from' => $custom_datepicker_from,
            'custom_datepicker_to'   => $custom_datepicker_to,
            'custom_button'          => $custom_button,
            'custom_button_disabled' => $custom_button_disabled,
            'range'                  => $range,
            'is_domains_enabled'     => $is_domains_enabled,
            'domains_tab_link'       => $domains_tab_link,
            'domains_tab'            => $domains_tab,
            'domains_tab_enabled'    => $domains_tab_enabled,
            'domains_popover'        => $domains_popover,
            'feature_date_limit'     => $feature_date_limit

        );

        return array_merge($vars, $permission_check_vars);
    }



    /**
     * Parse date selections made by the user
     *
     * @author Alex
     */
    public function parseDateSelection($vars, $range, $start_date = false, $end_date = false){

        extract($vars);

        //get the last date calcs were completed for this profile
        $acc = "select * from status_profiles where user_id='$cust_user_id'";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $cache_timestamp = $a['last_calced'];
        }

        //see how old this account is
        $cust_timestamp = getFlatDate($cust_timestamp);
        if(round(($cache_timestamp - $cust_timestamp)/60/60/24) < 14){
            $fresh_account = true;
        }

        //set custom date range values
        if ($range == "custom" && $start_date && $end_date) {
            $last_date = getTimestampFromDate($start_date);
            $current_date = getTimestampFromDate($end_date);

            $custom_num_days = round(abs($current_date - $last_date)/60/60/24)*2;

            $compare_date = strtotime("-$custom_num_days days",$last_date);
            $compare2_date = strtotime("-$custom_num_days days",$compare_date);
            $limit_date = strtotime("-$custom_num_days days",$current_date);


            $current_name = "Last " . round(abs($current_date - $last_date)/60/60/24) . " days";
            $current_chart_label = "Past " . round(abs($current_date - $last_date)/60/60/24) . " Days";
            $old_chart_label = "Prior " . round(abs($current_date - $last_date)/60/60/24) . " Days";
            $old_name = $old_chart_label;
            $older_chart_label = "Two " . round(abs($current_date - $last_date)/60/60/24) . "-Day Periods Prior";
            $older_name = $older_chart_label;
            $day_range = $custom_num_days;


            if ($current_date < $last_date) {
                $temp = $current_date;
                $current_date = $last_date;
                $last_date = $temp;
            }

        } else {

            //set standard periodic date range values
            if ($range == "Month") {
                $current_date = getFlatDate($cache_timestamp);
                $last_date = getFlatDate(strtotime("-1 month",$cache_timestamp));
                $last_date_fallback = getFlatDate(strtotime("-1 month 1 day",$cache_timestamp));
                $compare_date = getFlatDate(strtotime("-2 months",$cache_timestamp));
                $compare2_date = getFlatDate(strtotime("-3 months",$cache_timestamp));
                $limit_date = getFlatDate(strtotime("-$day_limit days",$cache_timestamp));
                $current_name = "This Month";
                $old_name = "Last Month";
                $older_name = "2 Months Ago";
                $current_chart_label = "Past Month";
                $old_chart_label = "Prior Month";
                $older_chart_label = "2 Months Prior";
                $chart_time_label = "Monthly";
                $day_range = 30;
            } else if ($range == "Week") {
                $current_date = getFlatDate($cache_timestamp);
                $last_date = getFlatDate(strtotime("-7 days",$cache_timestamp));
                $last_date_fallback = getFlatDate(strtotime("-8 days",$cache_timestamp));
                $compare_date = getFlatDate(strtotime("-14 days",$cache_timestamp));
                $compare2_date = getFlatDate(strtotime("-20 days",$cache_timestamp));
                $limit_date = getFlatDate(strtotime("-$day_limit days",$cache_timestamp));
                $current_name = "This Week";
                $old_name = "Last Week";
                $older_name = "2 14-Day Periods Prior";
                $current_chart_label = "Past Week";
                $old_chart_label = "Prior 14 Days";
                $older_chart_label = "2 14-Day Periods Prior";
                $chart_time_label = "Weekly";
                $day_range = 7;
            } else if ($range == "2Weeks") {
                $current_date = getFlatDate($cache_timestamp);
                $last_date = getFlatDate(strtotime("-14 days",$cache_timestamp));
                $compare_date = getFlatDate(strtotime("-28 days",$cache_timestamp));
                $compare2_date = getFlatDate(strtotime("-42 days",$cache_timestamp));
                $limit_date = getFlatDate(strtotime("-$day_limit days",$cache_timestamp));
                $current_name = "Last 14 Days";
                $old_name = "Prior 14 Days";
                $older_name = "2 Weeks Ago";
                $current_chart_label = "Past 14 Days";
                $old_chart_label = "Prior 14 Days";
                $older_chart_label = "2 Weeks Prior";
                $chart_time_label = "Weekly";
                $day_range = 14;
            } else if ($range == "Day") {
                $current_date = getFlatDate($cache_timestamp);
                $last_date = getFlatDate(strtotime("-1 day",$cache_timestamp));
                $last_date_fallback = getFlatDate(strtotime("-2 days",$cache_timestamp));
                $compare_date = getFlatDate(strtotime("-2 days",$cache_timestamp));
                $compare2_date = getFlatDate(strtotime("-3 days",$cache_timestamp));
                $limit_date = getFlatDate(strtotime("-$day_limit days",$cache_timestamp));
                $current_name = "Today";
                $old_name = "Yesterday";
                $older_name = "2 Days Ago";
                $current_chart_label = "Past Day";
                $old_chart_label = "Prior Day";
                $older_chart_label = "2 Days Prior";
                $chart_time_label = "Daily";
                $day_range = 1;
            }
        }


        if($limit_date < $feature_date_limit){
            $limit_date = $feature_date_limit;
        }

        $cache_timestamp_print = date("m-d-Y", $cache_timestamp);
        $last_date_print = date("m-d-Y", $last_date);
        $current_date_print = date("m-d-Y", $current_date);
        $compare_date_print = date("m-d-Y", $compare_date);
        $compare2_date_print = date("m-d-Y", $compare2_date);


        $tomorrow_date = strtotime("+1 day",$current_date);
        $hours_until_charts = ceil(number_format(($tomorrow_date - time())/60/60,1));
        if($hours_until_charts==1){
            $hours_until_charts = "an hour";
        } else {
            $hours_until_charts = "".$hours_until_charts." hours";
        }

        $profile_date_vars = array(
            'cache_timestamp'           => $cache_timestamp,
            'cache_timestamp_print'     => $cache_timestamp_print,
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
            'range'                     => $range,
            'last_date_fallback'        => $last_date_fallback,
            'limit_date'                => $limit_date,
            'chart_time_label'          => $chart_time_label,
            'last_date_print'           => $last_date_print,
            'current_date_print'        => $current_date_print,
            'compare_date_print'        => $compare_date_print,
            'compare2_date_print'       => $compare2_date_print,
            'tomorrow_date'             => $tomorrow_date,
            'hours_until_charts'        => $hours_until_charts,
            'start_date'                => $start_date,
            'end_date'                  => $end_date
        );

        return array_merge($vars, $profile_date_vars);

    }




    /**
     * Pull and prepare all competitor data
     *
     * @author
     */
    protected function prepCompetitorData($vars) {

        extract($vars);

        $competitors = array();
        $competitors["$current_date"] = array();
        $acc = "select a.account_id as account_id
                , a.account_name as account_name
                , a.org_id as org_id
                , a.username as username
                , a.user_id as user_id
                , a.competitor_of as competitor_of
                , b.domain as domain
                 from user_accounts a
                left join user_accounts_domains b on a.account_id = b.account_id
                where (a.account_id = '$cust_account_id' or a.competitor_of='$cust_account_id')
                and a.track_type!='orphan'
                order by account_id asc";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {

            $comp_account_id = $a['account_id'];
            $comp_user_id = $a['user_id'];
            $comp_domain = $a['domain'];
            if($comp_user_id!=0){
                if(!isset($competitors[$current_date]["$comp_user_id"])){
                    $competitors[$current_date]["$comp_user_id"] = array();
                    $competitors[$current_date]["$comp_user_id"]['account_id'] = $a['account_id'];
                    $competitors[$current_date]["$comp_user_id"]['account_name'] = $a['account_name'];
                    $competitors[$current_date]["$comp_user_id"]['username'] = $a['username'];
                    $competitors[$current_date]["$comp_user_id"]['user_id'] = $a['user_id'];
                    $competitors[$current_date]["$comp_user_id"]['profile_calcs'] = array();
                }
            }
        }

        $num_competitors = count($competitors);

        $comp_user_ids_string = "";
        $comp_account_ids_string = "";
        $comp_domains_string = "";

        $comp_toggle_vars = array();
        $comp_toggle_check = array();
        $comp_toggle_js = array();
        $comp_toggle_html = array();

        $comp_count = 0;
        foreach($competitors[$current_date] as $c) {

            $this_user_id = $c['user_id'];

            if($comp_user_ids_string == ""){
                if($c['user_id']!=0){
                    $comp_user_ids_string = $c['user_id'];
                }
            } else {
                if($c['user_id']!=0){
                    $comp_user_ids_string .= ",".$c['user_id'];
                }
            }

            if($comp_account_ids_string == ""){
                if($c['account_id']!=""){
                    $comp_account_ids_string = $c['account_id'];
                }
            } else {
                if($c['account_id']!=""){
                    $comp_account_ids_string .= ",".$c['account_id'];
                }
            }

            //create date interval object
            $start = new DateTime();
            $end   = new DateTime();
            $start->setTimestamp($limit_date);
            $end->setTimestamp($current_date);
            $period = new DatePeriod($start, new DateInterval('P1D'), $end);

            //populate all dates for profile calcs with 0s to start.
            foreach($period as $dt) {

                //get timestamp for each day
                $this_date = $dt->getTimestamp();

                if(!isset($competitors["$this_date"])){
                    $competitors["$this_date"] = array();
                }

                if(!isset($competitors["$this_date"]["$this_user_id"])){
                    $competitors["$this_date"]["$this_user_id"] = array();
                }

                if(!isset($competitors["$this_date"]["$this_user_id"]['profile_calcs'])){
                    $competitors["$this_date"]["$this_user_id"]['profile_calcs'] = array();
                    $competitors["$this_date"]["$this_user_id"]['profile_calcs']['timestamp'] = $this_date;
                    $competitors["$this_date"]["$this_user_id"]['profile_calcs']['follower_count'] = 0;
                    $competitors["$this_date"]["$this_user_id"]['profile_calcs']['pin_count'] = 0;
                }
            }


            //create javascript handlers and html checkboxes to be able to show/hide each competitor from the chart
            if($c['user_id']!=0){

                if(strlen($c['username']) > 15){
                    $short_username = substr($c['username'],0,14)."..";
                } else {
                    $short_username = $c['username'];
                }

                $this_comp_var = "
                                var comp".$comp_count."Box = document.getElementById('comp".$comp_count."-toggle');";

                $this_comp_check = "
                                if(!comp".$comp_count."Box.checked){
                                    viewNew.hideColumns([".($comp_count*3+1)."]);
                                }";

                $this_comp_js = "
                                comp".$comp_count."Box.onclick = function() {
                                    if(!comp".$comp_count."Box.checked){
                                        lastingView = new google.visualization.DataView(metric);
                                        lastingView.hideColumns([".($comp_count*3+1)."]);
                                        colorArray.splice($comp_count, 1);
                                        drawChart(lastingView);
                                    } else {
                                        lastingView = new google.visualization.DataView(metric);
                                        var currView = lastingView.getViewColumns();
                                        currView.splice(".($comp_count*3+1).", 0, ".($comp_count*3+1).");
                                        colorArray.splice($comp_count, 0, colorArrayDefault[$comp_count]);
                                        lastingView.setColumns(currView);
                                        drawChart(lastingView);
                                    }
                                }";

                $this_comp_html = "
                                <input id='comp".$comp_count."-toggle' type='checkbox' value='comp".$comp_count."-toggle' checked>$short_username</input>";

                array_push($comp_toggle_vars, $this_comp_var);
                array_push($comp_toggle_check, $this_comp_check);
                array_push($comp_toggle_js, $this_comp_js);
                array_push($comp_toggle_html, $this_comp_html);
            }
            $comp_count++;
        }


        $acc = "select a.account_id, b.* from user_accounts a
                left join calcs_profile_history b on a.user_id=b.user_id
                where a.user_id in($comp_user_ids_string) and a.org_id='$cust_org_id' and date >= $limit_date";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $this_account_id = $a['account_id'];
            $this_user_id = $a['user_id'];
            $this_date = $a['date'];

            $competitors["$this_date"][$this_user_id]['profile_calcs'] = array();
            $competitors["$this_date"][$this_user_id]['profile_calcs']['timestamp'] = $this_date;
            $competitors["$this_date"][$this_user_id]['profile_calcs']['follower_count'] = $a['follower_count'];
            $competitors["$this_date"][$this_user_id]['profile_calcs']['following_count'] = $a['following_count'];
            $competitors["$this_date"][$this_user_id]['profile_calcs']['follow_ratio'] = ($a['follower_count']/$a['following_count']);
            $competitors["$this_date"][$this_user_id]['profile_calcs']['board_count'] = $a['board_count'];
            $competitors["$this_date"][$this_user_id]['profile_calcs']['pin_count'] = $a['pin_count'];
            $competitors["$this_date"][$this_user_id]['profile_calcs']['repin_count'] = $a['repin_count'];
            $competitors["$this_date"][$this_user_id]['profile_calcs']['like_count'] = $a['like_count'];
            $competitors["$this_date"][$this_user_id]['profile_calcs']['comment_count'] = $a['comment_count'];
            $competitors["$this_date"][$this_user_id]['profile_calcs']['is_estimate'] = $a['estimate'];
            $competitors["$this_date"][$this_user_id]['profile_calcs']['pins_atleast_one_repin'] = $a['pins_atleast_one_repin'];
            $competitors["$this_date"][$this_user_id]['profile_calcs']['pins_atleast_one_like'] = $a['pins_atleast_one_like'];
            $competitors["$this_date"][$this_user_id]['profile_calcs']['pins_atleast_one_comment'] = $a['pins_atleast_one_comment'];
            $competitors["$this_date"][$this_user_id]['profile_calcs']['pins_atleast_one_engage'] = $a['pins_atleast_one_engage'];
            $competitors["$this_date"][$this_user_id]['profile_calcs']['repins_per_pin'] = ($a['repin_count']/$a['pin_count']);
            $competitors["$this_date"][$this_user_id]['profile_calcs']['repins_per_follower'] = ($a['repin_count']/$a['follower_count']);
            $competitors["$this_date"][$this_user_id]['profile_calcs']['repins_per_pin_per_follower'] = ($a['repin_count']/$a['pin_count']/$a['follower_count']*1000);


        }


////------------------ PROFILE METRICS ------------------////


        $comp_grid = array();


        //setup javascript datatables for profile metrics chart
        $followers_table =
            "var followers = new google.visualization.DataTable();
            followers.addColumn('date', 'Date');
            ";

        $pins_table =
            "var pins = new google.visualization.DataTable();
            pins.addColumn('date', 'Date');
            ";

        $repins_table =
            "var repins = new google.visualization.DataTable();
            repins.addColumn('date', 'Date');
            ";

        $likes_table =
            "var likes = new google.visualization.DataTable();
            likes.addColumn('date', 'Date');
            ";

        $comments_table =
            "var comments = new google.visualization.DataTable();
            comments.addColumn('date', 'Date');
            ";


        $comp_count=0;
        foreach($competitors[$current_date] as $cd){

            $followers_table .= "followers.addColumn('number', '" . $cd['username'] . "');
                         followers.addColumn({type:'boolean',role:'certainty'});
                         followers.addColumn({type:'boolean',role:'scope'});";
            $pins_table .= "pins.addColumn('number', '" . $cd['username'] . "');
                    pins.addColumn({type:'boolean',role:'certainty'});
                    pins.addColumn({type:'boolean',role:'scope'});";
            $repins_table .= "repins.addColumn('number', '" . $cd['username'] . "');
                      repins.addColumn({type:'boolean',role:'certainty'});
                      repins.addColumn({type:'boolean',role:'scope'});";
            $likes_table .= "likes.addColumn('number', '" . $cd['username'] . "');
                     likes.addColumn({type:'boolean',role:'certainty'});
                     likes.addColumn({type:'boolean',role:'scope'});";
            $comments_table .= "comments.addColumn('number', '" . $cd['username'] . "');
                        comments.addColumn({type:'boolean',role:'certainty'});
                        comments.addColumn({type:'boolean',role:'scope'});";



            $this_user_id = $cd['user_id'];
            $this_username = $cd['username'];

            $this_pin_change = $competitors[$current_date][$this_user_id]['profile_calcs']['pin_count']-$competitors[$last_date][$this_user_id]['profile_calcs']['pin_count'];
            if($this_pin_change < 0) {
                $acc = "select count(*) as count from data_pins_new where user_id = $this_user_id and created_at >= $last_date";
                $acc_res = mysql_query($acc,$conn) or die(mysql_error());
                while ($a = mysql_fetch_array($acc_res)) {
                    $this_pin_change = $a['count'];
                }
            }


            $this_comp_grid = "
                            <tr>
                                <td class='comp-label-column' data-toggle='tooltip' data-container='body' data-original-title='click to toggle $this_username in the chart above' data-placement='left'>
                                    <label>
                                        <span class='comp-icon active'>
                                            <i class='icon-check'></i>
                                        </span>
                                        <span class='comp-label-text'>
                                            <strong>".$comp_toggle_html[$comp_count]."</strong>
                                        </span>
                                    </label>
                                </td>
                                <td class='comp-profile-column follower-column active'>".($competitors[$current_date][$this_user_id]['profile_calcs']['follower_count']-$competitors[$last_date][$this_user_id]['profile_calcs']['follower_count'])."</td>

                                <td class='comp-profile-column pin-column'>".($this_pin_change)."</td>

                                <td class='comp-profile-column repin-column'>".($competitors[$current_date][$this_user_id]['profile_calcs']['repin_count']-$competitors[$last_date][$this_user_id]['profile_calcs']['repin_count'])."</td>

                                <td class='comp-profile-column like-column'>".($competitors[$current_date][$this_user_id]['profile_calcs']['like_count']-$competitors[$last_date][$this_user_id]['profile_calcs']['like_count'])."</td>

                                <td class='comp-profile-column comment-column'>".($competitors[$current_date][$this_user_id]['profile_calcs']['comment_count']-$competitors[$last_date][$this_user_id]['profile_calcs']['comment_count'])."</td>



                                <td class='virality-column'>".@number_format((($competitors[$current_date][$this_user_id]['profile_calcs']['repin_count']-$competitors[$last_date][$this_user_id]['profile_calcs']['repin_count'])/($this_pin_change)),2)."</td>

                                <td class='audience-engagement-column'>".@number_format((($competitors[$current_date][$this_user_id]['profile_calcs']['repin_count']-$competitors[$last_date][$this_user_id]['profile_calcs']['repin_count'])/($this_pin_change)/(($competitors[$current_date][$this_user_id]['profile_calcs']['follower_count']+$competitors[$last_date][$this_user_id]['profile_calcs']['follower_count'])/2))*1000,2)."</td>

                            </tr>";

            array_push($comp_grid, $this_comp_grid);

            $comp_count++;
        }

        //add dummy column to the table at the end to be able to identify the datatable by something
        $followers_table .= "followers.addColumn('number', 'followers');
                     ";
        $pins_table .= "pins.addColumn('number', 'pins');
                ";
        $repins_table .= "repins.addColumn('number', 'repins');
                  ";
        $likes_table .= "likes.addColumn('number', 'likes');
                 ";
        $comments_table .= "comments.addColumn('number', 'comments');
                    ";

        //setup the javascript data tables for the google chart
        $followers_print = "";
        $pins_print = "";
        $repins_print = "";
        $likes_print = "";
        $comments_print = "";

        krsort($competitors);



        foreach($competitors as $dt => $cp){

            $chart_time_js = $dt*1000;

            if($followers_print==""){
                $followers_print =
                    "var date = new Date($chart_time_js);
             followers.addRow([date";
            } else {
                $followers_print .=
                    "var date = new Date($chart_time_js);
             followers.addRow([date";
            }

            if($pins_print==""){
                $pins_print =
                    "var date = new Date($chart_time_js);
             pins.addRow([date";
            } else {
                $pins_print .=
                    "var date = new Date($chart_time_js);
             pins.addRow([date";
            }

            if($repins_print==""){
                $repins_print =
                    "var date = new Date($chart_time_js);
             repins.addRow([date";
            } else {
                $repins_print .=
                    "var date = new Date($chart_time_js);
             repins.addRow([date";
            }

            if($likes_print==""){
                $likes_print =
                    "var date = new Date($chart_time_js);
             likes.addRow([date";
            } else {
                $likes_print .=
                    "var date = new Date($chart_time_js);
             likes.addRow([date";
            }

            if($comments_print==""){
                $comments_print =
                    "var date = new Date($chart_time_js);
             comments.addRow([date";
            } else {
                $comments_print .=
                    "var date = new Date($chart_time_js);
             comments.addRow([date";
            }

            foreach($cp as $comp_id){

                //determine whether estimate or not
                if($comp_id['profile_calcs']['is_estimate']==1){
                    $chart_certainty = 'false';
                    $chart_scope = 'false';
                } else {
                    $chart_certainty = 'true';
                    $chart_scope = 'true';
                }

                if(!$comp_id['profile_calcs']['follower_count']){
                    $chart_follower_count = 0;
                } else {
                    $chart_follower_count = $comp_id['profile_calcs']['follower_count'];
                }

                if(!$comp_id['profile_calcs']['pin_count']){
                    $chart_pin_count = 0;
                } else {
                    $chart_pin_count = $comp_id['profile_calcs']['pin_count'];
                }

                if(!$comp_id['profile_calcs']['repin_count']){
                    $chart_repin_count = 0;
                } else {
                    $chart_repin_count = $comp_id['profile_calcs']['repin_count'];
                }

                if(!$comp_id['profile_calcs']['like_count']){
                    $chart_like_count = 0;
                } else {
                    $chart_like_count = $comp_id['profile_calcs']['like_count'];
                }

                if(!$comp_id['profile_calcs']['comment_count']){
                    $chart_comment_count = 0;
                } else {
                    $chart_comment_count = $comp_id['profile_calcs']['comment_count'];
                }

                $followers_print .= ", {$chart_follower_count}, {$chart_certainty}, {$chart_scope} ";
                $pins_print .= ", {$chart_pin_count}, {$chart_certainty}, {$chart_scope}";
                $repins_print .= ", {$chart_repin_count}, {$chart_certainty}, {$chart_scope}";
                $likes_print .= ", {$chart_like_count}, {$chart_certainty}, {$chart_scope}";
                $comments_print .= ", {$chart_comment_count}, {$chart_certainty}, {$chart_scope}";




            }

            $followers_print .= ", 0]);
        ";
            $pins_print .=", 0]);
        ";
            $repins_print .=", 0]);
        ";
            $likes_print .=", 0]);
        ";
            $comments_print .=", 0]);
        ";

        }


        $new_curr_chart_date = $current_date * 1000;
        $new_last_chart_date = $last_date * 1000;

        $this_time = "<span class='time-left muted'>$current_chart_label</span>";
        $last_time = "<span class='time-right muted'>$old_chart_label</span>";


        $is_profile = true;
        $datePicker = true;



        if ($range == "Day") {
            $day_pill = "class=\"active\"";
        } else if ($range == "Week") {
            $week_pill = "class=\"active\"";
        } else if ($range == "2Weeks") {
            $week2_pill = "class=\"active\"";
        } else if ($range == "Month") {
            $month_pill = "class=\"active\"";
        } else {
            $custom_range = "<li class='active'><a href='/competitors/benchmarks/custom/$start_date/$end_date'>Custom Range</a></li>";
        }


        $competitor_data_vars = array(
            'competitors'          => $competitors,
            'comp_grid'            => $comp_grid,
            'followers_table'      => $followers_table,
            'pins_table'           => $pins_table,
            'repins_table'         => $repins_table,
            'likes_table'          => $likes_table,
            'comments_table'       => $comments_table,
            'followers_print'      => $followers_print,
            'pins_print'           => $pins_print,
            'repins_print'         => $repins_print,
            'likes_print'          => $likes_print,
            'comments_print'       => $comments_print,
            'new_last_chart_date'  => $new_last_chart_date,
            'new_curr_chart_date'  => $new_curr_chart_date,
            'comp_toggle_vars'     => $comp_toggle_vars,
            'comp_toggle_check'    => $comp_toggle_check,
            'comp_toggle_js'       => $comp_toggle_js,
            'this_time'            => $this_time,
            'last_time'            => $last_time,
            'day_pill'             => $day_pill,
            'week_pill'            => $week_pill,
            'week2_pill'           => $week2_pill,
            'month_pill'           => $month_pill,
            'custom_range'         => $custom_range

        );

        return array_merge($vars, $competitor_data_vars);

    }


    /**
     *
     */
    public function showBenchmarks($range, $start_date = false, $end_date = false)
    {

        $vars = $this->checkCompetitorPermissions($range);
        $vars = $this->parseDateSelection($vars,$range,$start_date,$end_date);

        if(Session::get('has_competitors')){
            $vars = $this->prepCompetitorData($vars);
        }


        $this->layout->body_id = 'competitor-benchmarks';
        $vars['nav_competitor_benchmarks_class'] .= ' active';
        $vars['report_url'] = 'competitors/benchmarks';


        $this->layout_defaults['page'] = 'comp_benchmarks';
        $this->layout_defaults['top_nav_title'] = 'Competitor Benchmarks';
        $this->layout->top_navigation = $this->buildTopNavigation();
        $this->layout->side_navigation = $this->buildSideNavigation('competitor_benchmarks');

        $this->logged_in_customer->recordEvent(
                                 UserHistory::VIEW_REPORT,
                                 $parameters = array(
                                     'report' => 'competitor',
                                 )
        );

        $this->layout->sub_navigation = View::make('analytics.components.sub_nav.measure', $vars);

        $this->layout->main_content = View::make('analytics.pages.comp_benchmarks', $vars);

    }

    /**
     *
     */
    public function showBenchmarksDefault()
    {
        return $this->showBenchmarks("Week");
    }

}
