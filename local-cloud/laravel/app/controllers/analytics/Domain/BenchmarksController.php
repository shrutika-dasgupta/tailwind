<?php

namespace Analytics\Domain;

use Carbon\Carbon,
    Config,
    DatePeriod,
    DateInterval,
    DateTime,
    DB,
    Input,
    Log,
    Pin,
    Pins,
    Redirect,
    Illuminate\Http\RedirectResponse as RedirectResponse,
    Request,
    Response,
    Route,
    Session,
    StatusKeyword,
    StatusDomain,
    StatusTraffic,
    Topic,
    URL,
    UserAccountKeyword,
    UserAccountKeywordException,
    UserAccountsDomain,
    UserAccountsDomainException,
    UserAccountTag,
    UserAnalytic,
    UserHistory,
    View;

/**
 * domain controller.
 * 
 * @author Alex
 * @author Daniel
 *
 * @package Analytics
 */
class BenchmarksController extends BaseController
{

    /*
    |--------------------------------------------------------------------------
    | DOMAIN COMPETITOR BENCHMARKS
    |--------------------------------------------------------------------------
    */

    /**
     * Check feature permissions user has for the competitors report
     *
     * @author Alex
     *
     */
    public function checkCompetitorPermissions($range)
    {
        $vars = $this->baseLegacyVariables();
        extract($vars);

        /*
         * Redirect to upgrade page if feature not available
         */
        if (!$customer->hasFeature('comp_bench_domains')) {
            $ref = http_build_query(
                array(
                     'ref'  => 'comp_bench_domains',
                     'plan' => $customer->plan()->plan_id
                )
            );
            header('location:/upgrade?'.$ref);
            exit;
        }

        $custom_range = "";
        $custom_date_state      = "";
        $custom_button          = "<button style='margin-right: 17px; margin-left: 10px;' onclick=\"var start_date=$('#datepickerFrom').val();var end_date=$('#datepickerTo').val();window.location = '/competitors/benchmarks/custom/'+start_date+'/'+end_date;\" class=\"btn\">Change Date</button>";
        $custom_button_disabled = "";

        if ($customer->hasFeature('comp_bench_history_all')) {
            $day_limit         = 730;
            $date_limit_clause = "and date >= ".strtotime("-$day_limit days", getFlatDate(time()))."";
        } else {
            if ($customer->hasFeature('comp_bench_history_180')) {
                $day_limit = 365;
                $date_limit_clause = "and date >= ".strtotime("-$day_limit days", getFlatDate(time()))."";
            } else {
                if ($customer->hasFeature('comp_bench_history_90')) {
                    $day_limit = 90;
                    $date_limit_clause = "and date >= ".strtotime("-$day_limit days", getFlatDate(time()))."";
                } else {
                    $day_limit = 2;
                    $date_limit_clause = "and date >= ".strtotime("-$day_limit days", getFlatDate(time()))."";
                    $range     = "Day";

                    $custom_date_state      = "inactive";
                    $custom_button          = "";
                    $custom_button_disabled = "<button style='margin: 7px 17px 0 10px;' class=\"btn disabled pull-right\">Change Date</button>";
                }
            }
        }

        $max_data_age = strtotime("-$day_limit days", getFlatDate(time()));

        $permission_check_vars = array(
            'date_limit_clause'      => $date_limit_clause,
            'day_limit'              => $day_limit,
            'custom_range'           => $custom_range,
            'custom_date_state'      => $custom_date_state,
            'custom_button'          => $custom_button,
            'custom_button_disabled' => $custom_button_disabled,
            'range'                  => $range,
            'max_data_age'           => $max_data_age

        );

        return array_merge($vars, $permission_check_vars);
    }

    /**
     * Parse date selections made by the user
     *
     * @author Alex
     */
    public function parseDateSelection($vars, $range, $start_date = false, $end_date = false)
    {
        extract($vars);

        //get the last date calcs were completed for this profile
        $acc = "select * from status_domains where domain='$cust_domain'";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $cache_timestamp = $a['last_calced'];
        }

        //see how old this account is
        $cust_timestamp = getFlatDate($cust_timestamp);
        if (round(($cache_timestamp - $cust_timestamp)/60/60/24) < 14) {
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

        if ($limit_date < $max_data_age) {
            $limit_date = $max_data_age;
        }

        $cache_timestamp_print = date("m-d-Y", $cache_timestamp);
        $last_date_print = date("m-d-Y", $last_date);
        $current_date_print = date("m-d-Y", $current_date);
        $compare_date_print = date("m-d-Y", $compare_date);
        $compare2_date_print = date("m-d-Y", $compare2_date);

        $tomorrow_date = strtotime("+1 day",$current_date);
        $hours_until_charts = ceil(number_format(($tomorrow_date - time())/60/60,1));
        if ($hours_until_charts == 1) {
            $hours_until_charts = "an hour";
        } else {
            $hours_until_charts = "".$hours_until_charts." hours";
        }

        $profile_date_vars = array(
            'cache_timestamp'           => $cache_timestamp,
            'cache_timestamp_print'     => $cache_timestamp_print,
            'cust_timestamp'            => $cust_timestamp,
            'fresh_account'             => $fresh_account,
            'current_date'              => strtotime("-1 day",$current_date),
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
    protected function prepCompetitorData($vars)
    {
        extract($vars);

        $competitors = array();
        $competitors["$current_date"] = array();
        $comp_domains = array();
        $comp_domains["$current_date"] = array();
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

            if ($comp_domain!="" && !is_null($comp_domain)) {
                $comp_domains[$current_date]["$comp_domain"] = array();
                $comp_domains[$current_date]["$comp_domain"]['account_id'] = $a['account_id'];
                $comp_domains[$current_date]["$comp_domain"]['account_name'] = $a['account_name'];
                $comp_domains[$current_date]["$comp_domain"]['username'] = $a['username'];
                $comp_domains[$current_date]["$comp_domain"]['user_id'] = $a['user_id'];
                $comp_domains[$current_date]["$comp_domain"]['domain'] = $a['domain'];
                $comp_domains[$current_date]["$comp_domain"]['domain_calcs'] = array();
            }
        }

        $num_competitors = count($competitors);

        $comp_user_ids_string    = "";
        $comp_account_ids_string = "";
        $comp_domains_string     = "";

        $compdom_toggle_vars  = array();
        $compdom_toggle_check = array();
        $compdom_toggle_js    = array();
        $compdom_toggle_html  = array();

        $domain_count = 0;
        foreach ($comp_domains[$current_date] as $c) {
            $this_domain = $c['domain'];

            if ($comp_domains_string == "") {
                if ($c['domain']!="") {
                    $comp_domains_string = "'".$c['domain']."'";
                }
            } else {
                if ($c['domain']!="") {
                    $comp_domains_string .= ",'".$c['domain']."'";
                }
            }

            if ($comp_account_ids_string == "") {
                if ($c['account_id']!="") {
                    $comp_account_ids_string = $c['account_id'];
                }
            } else {
                if ($c['account_id']!="") {
                    $comp_account_ids_string .= ",".$c['account_id'];
                }
            }

            //if domain exists, populate values into master array for domain calculation data
            if ($this_domain!="" && !is_null($this_domain)) {
                //create date interval object
                $start = new DateTime();
                $end   = new DateTime();
                $start->setTimestamp($limit_date);
                $end->setTimestamp($current_date);
                $period = new DatePeriod($start, new DateInterval('P1D'), $end);

                //populate all dates for domain calcs with 0s to start.
                foreach ($period as $dt) {
                    $this_date = $dt->getTimestamp();

                    if (!isset($comp_domains["$this_date"])) {
                        $comp_domains["$this_date"] = array();
                    }

                    if (!isset($comp_domains["$this_date"]["$this_domain"])) {
                        $comp_domains["$this_date"]["$this_domain"] = array();
                    }

                    if (!isset($comp_domains["$this_date"]["$this_domain"]['domain_calcs'])) {
                        $comp_domains["$this_date"]["$this_domain"]['domain_calcs'] = array();
                        $comp_domains["$this_date"]["$this_domain"]['domain_calcs']['timestamp'] = $this_date;
                        $comp_domains["$this_date"]["$this_domain"]['domain_calcs']['daily_pinner_count'] = 0;
                        $comp_domains["$this_date"]["$this_domain"]['domain_calcs']['daily_pin_count'] = 0;
                        $comp_domains["$this_date"]["$this_domain"]['domain_calcs']['daily_reach'] = 0;
                    }
                }

                //create javascript handlers and html checkboxes to be able to show/hide each competitor from the chart

                if (strlen($c['domain']) > 15) {
                    $short_domain = substr($c['domain'],0,14)."..";
                } else {
                    $short_domain = $c['domain'];
                }

                $this_compdom_var = "
                                var compDom".$domain_count."Box = document.getElementById('compDom".$domain_count."-toggle');";

                $this_compdom_check = "
                                if(!compDom".$domain_count."Box.checked){
                                    viewNewDom.hideColumns([".($domain_count+1)."]);
                                }";

                $this_compdom_js = "
                                compDom".$domain_count."Box.onclick = function() {
                                    if(!compDom".$domain_count."Box.checked){
                                        lastingViewDom = new google.visualization.DataView(metricDom);
                                        lastingViewDom.hideColumns([".($domain_count+1)."]);
                                        colorArrayDom.splice($domain_count, 1);
                                        drawChartDom(lastingViewDom);
                                    } else {
                                        lastingViewDom = new google.visualization.DataView(metricDom);
                                        var currView = lastingViewDom.getViewColumns();
                                        currView.splice(".($domain_count+1).", 0, ".($domain_count+1).");
                                        colorArrayDom.splice($domain_count, 0, colorArrayDefault[$domain_count]);
                                        lastingViewDom.setColumns(currView);
                                        drawChartDom(lastingViewDom);
                                    }
                                }";

                $this_compdom_html = "
                                <input id='compDom".$domain_count."-toggle' type='checkbox' value='comD".$domain_count."-toggle' checked>$short_domain</input>";


                array_push($compdom_toggle_vars, $this_compdom_var);
                array_push($compdom_toggle_check, $this_compdom_check);
                array_push($compdom_toggle_js, $this_compdom_js);
                array_push($compdom_toggle_html, $this_compdom_html);


                $domain_count++;
            }
        }

        //get daily pin and pinner count
        $acc = "SELECT domain, date AS mentioned_day
                , sum(pin_count) AS pin_count
                , sum(pinner_count) AS pinner_count
                , sum(repin_count) AS repin_count
                , sum(like_count) AS like_count
                , sum(comment_count) AS comment_count
                , sum(reach) AS reach
                FROM cache_domain_daily_counts
                WHERE domain in ($comp_domains_string)
                $date_limit_clause
                GROUP BY domain, mentioned_day
                ORDER BY mentioned_day DESC";
        $acc_res = mysql_query($acc, $conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $this_date = $a['mentioned_day'];
            $daily_pin_count = $a['pin_count'];
            $daily_pinner_count = $a['pinner_count'];
            $daily_reach  = $a['reach'];
            $this_domain = $a['domain'];

            $comp_domains[$this_date][$this_domain]['domain_calcs']['daily_pin_count'] = $daily_pin_count;
            $comp_domains[$this_date][$this_domain]['domain_calcs']['daily_pinner_count'] = $daily_pinner_count;
            $comp_domains[$this_date][$this_domain]['domain_calcs']['daily_reach'] = $daily_reach;
        }


//            $acc = "select a.account_id, c.* from user_accounts a left join (user_accounts_domains b, calcs_domain_history c) on (a.account_id=b.account_id and b.domain=c.domain) where a.account_id in ($comp_account_ids_string) and a.org_id='$cust_org_id' and date > $limit_date";
//            $acc_res = mysql_query($acc,$conn) or die(mysql_error());
//            while ($a = mysql_fetch_array($acc_res)) {
//                $this_domain = $a['domain'];
//
//                if($this_domain!="" && !is_null($this_domain)){
//                    $this_date = $a['date'];
//                    $this_date_tomorrow = strtotime("+1 day", $this_date);
//
//                    $comp_domains[$this_date][$this_domain]['domain_calcs']['domain_mentions'] = $a['domain_mentions'];
//                    $comp_domains[$this_date][$this_domain]['domain_calcs']['unique_domain_pinners'] = $a['unique_domain_pinners'];
//                    $comp_domains[$this_date][$this_domain]['domain_calcs']['domain_reach'] = $a['domain_reach'];
//                    $comp_domains[$this_date][$this_domain]['domain_calcs']['domain_impressions'] = $a['domain_impressions'];
//                    $comp_domains[$this_date][$this_domain]['domain_calcs']['reach_per_pin'] = ($a['domain_reach']/$a['domain_mentions']);
//                    $comp_domains[$this_date][$this_domain]['domain_calcs']['impressions_per_pin'] = ($a['domain_impressions']/$a['domain_mentions']);
//
//                    if(isset($comp_domains[$this_date_tomorrow][$this_domain]['domain_calcs'])){
//                        @$comp_domains[$this_date_tomorrow][$this_domain]['domain_calcs']['daily_impressions'] = $comp_domains[$this_date_tomorrow][$this_domain]['domain_calcs']['domain_impressions'] - $a['domain_impressions'];
//                    }
//
//                    if(isset($comp_domains[$this_date_tomorrow][$this_domain]['domain_calcs'])){
//                        @$comp_domains[$this_date_tomorrow][$this_domain]['domain_calcs']['daily_reach'] = $comp_domains[$this_date_tomorrow][$this_domain]['domain_calcs']['domain_reach'] - $a['domain_reach'];
//                    }
//                }
//            }


        //add up the new daily organic pins and new daily organic pinners for each time period
        foreach ($comp_domains[$current_date] as $c_domain) {
            $this_domain = $c_domain['domain'];

            if ($this_domain!="" && !is_null($this_domain)) {
                $comp_domains[$current_date][$this_domain]['domain_calcs']['total_pin_count'] = 0;
                $comp_domains[$current_date][$this_domain]['domain_calcs']['total_pinner_count'] = 0;
                $comp_domains[$current_date][$this_domain]['domain_calcs']['total_reach'] = 0;
                $comp_domains[$last_date][$this_domain]['domain_calcs']['total_pin_count'] = 0;
                $comp_domains[$last_date][$this_domain]['domain_calcs']['total_pinner_count'] = 0;
                $comp_domains[$last_date][$this_domain]['domain_calcs']['total_reach'] = 0;
                $comp_domains[$compare_date][$this_domain]['domain_calcs']['total_pin_count'] = 0;
                $comp_domains[$compare_date][$this_domain]['domain_calcs']['total_pinner_count'] = 0;
                $comp_domains[$compare_date][$this_domain]['domain_calcs']['total_reach'] = 0;

                foreach ($comp_domains as $dt => $cd) {
                    $this_date = $dt;
                    if ($this_date == $current_date) {
                        for ($i = 0; $i < $day_range; $i++) {
                            $new_date = strtotime("-$i days", $this_date);

                            @$comp_domains[$this_date][$this_domain]['domain_calcs']['total_pin_count'] += $comp_domains[$new_date][$this_domain]['domain_calcs']['daily_pin_count'];
                            @$comp_domains[$this_date][$this_domain]['domain_calcs']['total_pinner_count'] += $comp_domains[$new_date][$this_domain]['domain_calcs']['daily_pinner_count'];
                            @$comp_domains[$this_date][$this_domain]['domain_calcs']['total_reach'] += $comp_domains[$new_date][$this_domain]['domain_calcs']['daily_reach'];
                        }
                    }
                }

                foreach ($comp_domains as $dt => $cd) {
                    $this_date = $dt;

                    $comp_domains[$this_date][$this_domain]['domain_calcs']['avg_pin_count'] = number_format($comp_domains[$this_date][$this_domain]['domain_calcs']['total_pin_count']/$day_range,2, '.', '');
                    $comp_domains[$this_date][$this_domain]['domain_calcs']['avg_pinner_count'] = number_format($comp_domains[$this_date][$this_domain]['domain_calcs']['total_pinner_count']/$day_range,2, '.', '');
                }
            }
        }


////------------------ DOMAIN METRICS ------------------////

        $compdom_grid = array();

        //setup javascript datatables for domain metrics chart
        $new_pins_table =
            "var newPins = new google.visualization.DataTable();
            newPins.addColumn('date', 'Date');
            ";

        $new_pinners_table =
            "var newPinners = new google.visualization.DataTable();
            newPinners.addColumn('date', 'Date');
            ";

        $new_reach_table =
            "var newReach = new google.visualization.DataTable();
            newReach.addColumn('date', 'Date');
            ";

//            $avg_pins_table =
//                "var avgPins = new google.visualization.DataTable();
//                avgPins.addColumn('date', 'Date');
//                ";

//            $avg_pinners_table =
//                "var avgPinners = new google.visualization.DataTable();
//                avgPinners.addColumn('date', 'Date');
//                ";

//		$unique_pinners_table =
//		"var uniquePinners = new google.visualization.DataTable();
//		uniquePinners.addColumn('date', 'Date');
//		";


        $domain_count=0;
        foreach ($comp_domains[$current_date] as $cd) {
            $this_domain = $cd['domain'];
            if ($this_domain != '' && !is_null($this_domain)) {

                $new_pins_table .= "newPins.addColumn('number', '" . $cd['domain'] . "');
        ";
                $new_pinners_table .= "newPinners.addColumn('number', '" . $cd['domain'] . "');
        ";
                $new_reach_table .= "newReach.addColumn('number', '" . $cd['domain'] . "');
        ";
//                    $avg_pins_table .= "avgPins.addColumn('number', '" . $cd['domain'] . "');
//            ";
//                    $avg_pinners_table .= "avgPinners.addColumn('number', '" . $cd['domain'] . "');
//            ";
//				$unique_pinners_table .= "uniquePinners.addColumn('number', '" . $cd['domain'] . "');
//				";

                $this_compdom_grid = "
                            <tr>
                                <td class='comp-label-column' data-toggle='tooltip' data-container='body' data-original-title='click to toggle $this_domain in the chart above' data-placement='left'>
                                    <label>
                                        <span class='comp-icon active'>
                                            <i class='icon-check'></i>
                                        </span>
                                        <span class='comp-label-text'>
                                            <strong>".$compdom_toggle_html[$domain_count]."</strong>
                                        </span>
                                    </label>
                                </td>
                                <td class='comp-domain-column new-pins-column active'>".number_format($comp_domains[$current_date][$this_domain]['domain_calcs']['total_pin_count'],0)."</td>

                                <td class='comp-domain-column new-pinners-column'>".number_format($comp_domains[$current_date][$this_domain]['domain_calcs']['total_pinner_count'],0)."</td>

                                <td class='comp-domain-column new-reach-column'>".number_format($comp_domains[$current_date][$this_domain]['domain_calcs']['total_reach'],0)."</td>

                                <td class='comp-domain-column avg-pins-column'>".$comp_domains[$current_date][$this_domain]['domain_calcs']['avg_pin_count']."</td>

                                <td class='comp-domain-column reach-per-pin-column'>".number_format($comp_domains[$current_date][$this_domain]['domain_calcs']['total_reach']/$comp_domains[$current_date][$this_domain]['domain_calcs']['total_pin_count'],0)."</td>

                            </tr>";

                array_push($compdom_grid, $this_compdom_grid);

                $domain_count++;
            }
        }

        //add dummy column to the table at the end to be able to identify the datatable by something
        $new_pins_table .= "newPins.addColumn('number', 'newPins');
";
        $new_pinners_table .= "newPinners.addColumn('number', 'newPinners');
";
        $new_reach_table .= "newReach.addColumn('number', 'newReach');
";
//            $avg_pins_table .= "avgPins.addColumn('number', 'avgPins');
//    ";
//            $avg_pinners_table .= "avgPinners.addColumn('number', 'avgPinners');
//    ";
//		$unique_pinners_table .= "uniquePinners.addColumn('number', 'uniquePinners');
//		";


        //setup the javascript data tables for the google chart
        $new_pins_print = "";
        $new_pinners_print = "";
        $new_reach_print = "";
//            $avg_pins_print = "";
//            $avg_pinners_print = "";
        $unique_pinners_print = "";

        krsort($comp_domains);
        foreach ($comp_domains as $dt => $cp) {
            $chart_time_js = $dt*1000;

            if ($new_pins_print=="") {
                $new_pins_print =
                    "var date = new Date($chart_time_js);
                    newPins.addRow([date";
            } else {
                $new_pins_print .=
                    "var date = new Date($chart_time_js);
                    newPins.addRow([date";
            }

            if ($new_pinners_print=="") {
                $new_pinners_print =
                    "var date = new Date($chart_time_js);
                    newPinners.addRow([date";
            } else {
                $new_pinners_print .=
                    "var date = new Date($chart_time_js);
                    newPinners.addRow([date";
            }

            if ($new_reach_print=="") {
                $new_reach_print =
                    "var date = new Date($chart_time_js);
                    newReach.addRow([date";
            } else {
                $new_reach_print .=
                    "var date = new Date($chart_time_js);
                    newReach.addRow([date";
            }

//                if($avg_pins_print==""){
//                    $avg_pins_print =
//                        "var date = new Date($chart_time_js);
//                        avgPins.addRow([date";
//                } else {
//                    $avg_pins_print .=
//                        "var date = new Date($chart_time_js);
//                        avgPins.addRow([date";
//                }

//                if($avg_pinners_print==""){
//                    $avg_pinners_print =
//                        "var date = new Date($chart_time_js);
//                        avgPinners.addRow([date";
//                } else {
//                    $avg_pinners_print .=
//                        "var date = new Date($chart_time_js);
//                        avgPinners.addRow([date";
//                }

//			if($unique_pinners_print==""){
//				$unique_pinners_print =
//				"var date = new Date($chart_time_js);
//				 uniquePinners.addRow([date";
//			} else {
//				$unique_pinners_print .=
//				"var date = new Date($chart_time_js);
//				 uniquePinners.addRow([date";
//			}

            foreach ($cp as $comp_id) {
                if(!$comp_id['domain_calcs']['daily_pin_count']){
                    $chart_daily_pin_count = 0;
                } else {
                    $chart_daily_pin_count = $comp_id['domain_calcs']['daily_pin_count'];
                }

                if(!$comp_id['domain_calcs']['daily_pinner_count']){
                    $chart_daily_pinner_count = 0;
                } else {
                    $chart_daily_pinner_count = $comp_id['domain_calcs']['daily_pinner_count'];
                }

                if(!$comp_id['domain_calcs']['daily_reach']){
                    $chart_daily_reach = 0;
                } else {
                    $chart_daily_reach = $comp_id['domain_calcs']['daily_reach'];
                }

//                    if(!$comp_id['domain_calcs']['avg_pin_count']){
//                        $chart_avg_pin_count = 0;
//                    } else {
//                        $chart_avg_pin_count = $comp_id['domain_calcs']['avg_pin_count'];
//                    }

//                    if(!$comp_id['domain_calcs']['avg_pinner_count']){
//                        $chart_avg_pinner_count = 0;
//                    } else {
//                        $chart_avg_pinner_count = $comp_id['domain_calcs']['avg_pinner_count'];
//                    }

//				if(!$comp_id['domain_calcs']['unique_domain_pinners']){
//					$chart_unique_pinner_count = 0;
//				} else {
//					$chart_unique_pinner_count = $comp_id['domain_calcs']['unique_domain_pinners'];
//				}

                $new_pins_print .= ", {$chart_daily_pin_count}";
                $new_pinners_print .= ", {$chart_daily_pinner_count}";
                $new_reach_print .= ", {$chart_daily_reach}";
//                    $avg_pins_print .= ", {$chart_avg_pin_count}";
//                    $avg_pinners_print .= ", {$chart_avg_pinner_count}";
//				      $unique_pinners_print .= ", {$chart_unique_pinner_count}";
            }

            $new_pins_print .= ", 0]);
    ";
            $new_pinners_print .=", 0]);
    ";
            $new_reach_print .=", 0]);
    ";
//                $avg_pins_print .=", 0]);
//        ";
//                $avg_pinners_print .=", 0]);
//        ";
//			      $unique_pinners_print .=", 0]);
//		  ";

        }


        $new_curr_chart_date = $current_date * 1000;
        $new_last_chart_date = $last_date * 1000;

        $this_time = "<span class='time-left muted'>$current_chart_label</span>";
        $last_time = "<span class='time-right muted'>$old_chart_label</span>";

        $is_profile = true;
        $datePicker = true;

        $competitor_data_vars = array(
            'competitors'          => $competitors,
            'compdom_grid'         => $compdom_grid,
            'domain_count'         => $domain_count,
            'new_last_chart_date'  => $new_last_chart_date,
            'new_curr_chart_date'  => $new_curr_chart_date,
            'new_pins_table'       => $new_pins_table,
            'new_pinners_table'    => $new_pinners_table,
            'new_reach_table'      => $new_reach_table,
//            'avg_pins_table'       => $avg_pins_table,
//            'avg_pinners_table'    => $avg_pinners_table,
            'new_pins_print'       => $new_pins_print,
            'new_pinners_print'    => $new_pinners_print,
            'new_reach_print'      => $new_reach_print,
//            'avg_pins_print'       => $avg_pins_print,
//            'avg_pinners_print'    => $avg_pinners_print,
            'compdom_toggle_vars'  => $compdom_toggle_vars,
            'compdom_toggle_check' => $compdom_toggle_check,
            'compdom_toggle_js'    => $compdom_toggle_js,
            'this_time'            => $this_time,
            'last_time'            => $last_time
        );

        return array_merge($vars, $competitor_data_vars);
    }

    public function showBenchmarks($range, $start_date = false, $end_date = false)
    {
        Log::setLog(__FILE__,'Reporting','Domain_Benchmarks');

        $this->secureQuery();

        $vars = $this->checkCompetitorPermissions($range);
        $vars = $this->parseDateSelection($vars,$range,$start_date,$end_date);

        $vars['nav_domain_benchmarks_class'] .= " active";
        $vars['type'] = 'benchmarks';

        $this->buildLayout('domain-benchmarks');

        if(Session::get('has_competitors')){
            $vars = $this->prepCompetitorData($vars);
        }

        $this->logged_in_customer->recordEvent(
            UserHistory::VIEW_REPORT,
            $parameters = array(
                'report' => 'domain competitors',
            )
        );
        $vars = array_merge($vars,$this->baseLegacyVariables());

        $this->layout->sub_navigation = View::make('analytics.components.sub_nav.domain', $vars);
        $this->layout->main_content = View::make('analytics.pages.domain.benchmarks', $vars);
    }

    public function showBenchmarksDefault()
    {
        $this->secureQuery();
        return $this->showBenchmarks("Week");
    }
}
