<?php namespace Analytics\Domain;

use
    Log,
    UserHistory,
    Session,
    Redirect,
    View;

/**
 * Class TrafficController
 *
 * @package Analytics
 */
class TrafficController extends BaseController
{
    /**
     * Construct
     *
     * @author  Will
     */
    public function __construct()
    {

        parent::__construct();

        Log::setLog(__FILE__, 'Reporting', 'Traffic_Report');
    }

    /**
     *
     */
    public function downloadTraffic($start_date, $end_date, $type)
    {
        return $start_date . $end_date . $type;
    }


    /**
     * Check feature permissions user has for the competitors report
     *
     * @author Alex
     *
     */
    public function checkTrafficPermissions($range){

        $vars = $this->baseLegacyVariables();

        /*
         * Redirect to upgrade page if feature not available
         */
        extract($vars);
        if(!$customer->hasFeature('nav_traffic')){

            $query_string = http_build_query(
                array(
                     'ref'  => 'traffic',
                     'plan' => $customer->plan()->plan_id
                )
            );
            header('location:/upgrade?'.$query_string);
            exit;
        }

        $last_date_print = '';
        $current_date_print='';
        $current_date = 0;
        $is_free_account = false;
        $calcs_found = false;

        if ($customer->hasFeature('traffic_history_all')) {
            $date_limit_clause = "";
            $day_limit         = 730;
            $popover_custom_date = "";
        } else {
            if ($customer->hasFeature('traffic_history_180')) {
                $day_limit         = 181;
                $date_limit_clause = "and date >= " . strtotime("-$day_limit days", getFlatDate(time())) . "";
                $popover_custom_date = "";
            } else {
                if ($customer->hasFeature('traffic_history_90')) {
                    $day_limit         = 91;
                    $date_limit_clause = "and date >= " . strtotime("-$day_limit days", getFlatDate(time())) . "";
                    $popover_custom_date = "";
                } else {
                    $day_limit         = 9;
                    $range             = "Week";
                    $is_free_account   = true;
                    $date_limit_clause = "and date >= " . strtotime("-$day_limit days", getFlatDate(time())) . "";
                    $popover_custom_date = createPopover("#reportrange", "click", "bottom", "<span class=\"text-success\"><strong>Upgrade to Unlock</strong></span>", "boards-custom-date-wrapper",
                        $customer->plan()->plan_id, "<strong><ul><li>Get historical data on every board</li><li>Filter using custom date ranges</li></ul>");
                }
            }
        }


        if ($customer->hasFeature('traffic_date_7_days')) {
            $week_link = "href='/traffic/Week'";
            $week_pill = "";
        } else {
            $week_link = "";
            $week_pill = "class='inactive'";
        }

        if ($customer->hasFeature('traffic_date_14_days')) {
            $week2_link = "href='/traffic/2Weeks'";
            $week2_pill = "";
        } else {
            $week2_link = "";
            $week2_pill = "class='inactive'";
        }

        if ($customer->hasFeature('traffic_date_30_days')) {
            $month_link = "href='/traffic/Month'";
            $month_pill = "";
        } else {
            $month_link = "";
            $month_pill = "class='inactive'";
        }

        if ($customer->hasFeature('traffic_date_custom')) {
            $custom_date_state      = "";
            $custom_datepicker_from = "id=\"datepickerFrom\"";
            $custom_datepicker_to   = "id=\"datepickerTo\"";
            $custom_button          = "<button style='margin-right: 17px; margin-left: 10px;' onclick=\"var start_date=$('#datepickerFrom').val();var end_date=$('#datepickerTo').val();window.location = '/traffic/custom/'+start_date+'/'+end_date;\" class=\"btn\">Change Date</button>";
            $custom_button_disabled = "";
        } else {
            $custom_date_state      = "inactive";
            $custom_datepicker_from = "";
            $custom_datepicker_to   = "";
            $custom_button          = "";
            $custom_button_disabled = "<button style='margin: 7px 17px 0 10px;' class=\"btn disabled pull-right\">Change Date</button>";
        }

        /*
        if ($customer->hasFeature('profile_data_export')) {
            //TODO: need to build feature
        } else {

        }
        */

        /*
         * Refresh whether this use has google analytics synced.
         */
        $has_ga = hasAnalytics($cust_account_id, $conn);
        if ($has_ga) {
            Session::put('has_analytics', 1);
            if (!analyticsReady($cust_account_id, $conn)) {
                Session::put('has_analytics', 2);
                $account_analytics_ready = false;
                $has_analytics_profile   = false;
                if (!getAnalyticsProfile($cust_account_id, $conn)) {
                    $has_analytics_profile = false;
                }
            } else {
                $account_analytics_ready = true;
                $has_analytics_profile   = true;
            }
        } else {
            Session::put('has_analytics', 0);
        }


        if (!$has_ga) {

            $report_overlay = "
            <div class='report-overlay'>
                <div class='report-loading' style='text-align:center'>
                        <h1>Please Sync your Google Analytics to enable this report!</h1>
                        <h3 class='muted'>Looks like there's no Google Analytics associated with this account.</h3>
                        <br>
                        <a class='btn btn-large btn-success' href='/settings/google-analytics'>Sync Google Analytics →</a>
                        <br>
                        <hr>
                </div>
            </div>";

        } else if (!$account_analytics_ready) {

            $report_overlay = "
            <div class='report-overlay'>
                <div class='report-loading' style='text-align:center'>
                    <img src='/img/loading.gif'><br>
                    <h1>Your Traffic & Revenue data is on its way!</h1>
                    <br>
                    <h3 class='muted'>If you just recently synced your Google Analytics profile, please give us at least a few hours to begin processing all of your data :)</h3>
                    <br>
                    <hr>
                </div>
            </div>";

        } else {
            if (!$has_analytics_profile) {

                $report_overlay = "
            <div class='report-overlay'>
                <div class='report-loading' style='text-align:center'>
                        <h1>Your Google Analytics Account is Synced!</h1>
                        <h3 class='muted'>Please choose the appropriate Google Analytics Profile to complete your integration process.</h3>
                        <br>
                        <a class='btn btn-large btn-success' href='/settings/google-analytics'>Go Choose Google Analytics Profile →</a>
                        <br>
                        <hr>
                </div>
            </div>";
            } else {
                $report_overlay = "";
            }
        }


        if ($range == "Week") {
            $week_pill = "class='active'";
        } else if ($range == "2Weeks") {
            $week2_pill = "class='active'";
        } else if ($range == "Month") {
            $month_pill = "class='active'";
        } else {
            $week_pill = "class='active'";
        }




        $permission_check_vars = array_merge(array(
            'day_limit'               => $day_limit,
            'date_limit_clause'       => $date_limit_clause,
            'is_free_account'         => $is_free_account,
            'week_link'               => $week_link,
            'week_pill'               => $week_pill,
            'week2_link'              => $week2_link,
            'week2_pill'              => $week2_pill,
            'month_link'              => $month_link,
            'month_pill'              => $month_pill,
            'custom_date_state'       => $custom_date_state,
            'custom_datepicker_from'  => $custom_datepicker_from,
            'custom_datepicker_to'    => $custom_datepicker_to,
            'custom_button'           => $custom_button,
            'custom_button_disabled'  => $custom_button_disabled,
            'range'                   => $range,
            'last_date_print'         => $last_date_print,
            'current_date_print'      => $current_date_print,
            'current_date'            => $current_date,
            'calcs_found'             => $calcs_found,
            'account_analytics_ready' => $account_analytics_ready,
            'has_analytics_profile'   => $has_analytics_profile,
            'report_overlay'          => $report_overlay,
            'popover_custom_date'     => $popover_custom_date
        ),$this->baseLegacyVariables());

        return array_merge($vars, $permission_check_vars);

    }

    /**
     * Parse date selections made by the user
     *
     * @author Alex
     */
    public function parseDateSelection($vars, $range, $start_date = false, $end_date = false){

        extract($vars);

        $date_limit = strtotime("-$day_limit days", getFlatDate(time()));

        //get the last date calcs were completed for this profile
        $acc = "select * from status_traffic where account_id='$cust_account_id' and profile!='' and profile is not null";
        $acc_res = mysql_query($acc, $conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $cache_timestamp = strtotime("-1 day", $a['last_pulled']);
            $cust_traffic_id = $a['traffic_id'];
        }

        //set custom date range values
        if ($range == "custom" && $start_date && $end_date) {
            $last_date    = getTimestampFromDate($start_date);
            $current_date = getTimestampFromDate($end_date);

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


            if ($fresh_account) {
                $range = "Week";
            } else {
                $range = "Month";
            }

            //set standard periodic date range values
            //set standard periodic date range values
            if ($range == "Month") {
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
            } else if ($range == "2Weeks") {
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
            } else {
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
                $range               = "Week";
            }
        }

        $cache_timestamp_print = date("m-d-Y", $cache_timestamp);
        $last_date_print       = date("m-d-Y", $last_date);
        $current_date_print    = date("m-d-Y", $current_date);
        $compare_date_print    = date("m-d-Y", $compare_date);
        $compare2_date_print   = date("m-d-Y", $compare2_date);

        $traffic_date_vars = array(
            'date_limit'                => $date_limit,
            'cache_timestamp'           => $cache_timestamp,
            'cust_traffic_id'           => $cust_traffic_id,
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
            'cache_timestamp_print'     => $cache_timestamp_print,
            'last_date_print'           => $last_date_print,
            'current_date_print'        => $current_date_print,
            'compare_date_print'        => $compare_date_print,
            'compare2_date_print'       => $compare2_date_print
        );

        return array_merge($vars, $traffic_date_vars);

    }


    /**
     *
     */
    public function showTraffic($range, $start_date = false, $end_date = false)
    {

        $vars = $this->checkTrafficPermissions($range);
        $vars = $this->parseDateSelection($vars,$range,$start_date,$end_date);

        $this->layout->body_id = 'traffic';
        $vars['nav_traffic_class'] .= ' active';
        $vars['report_url'] = 'traffic';
        $vars['type'] = 'traffic';

        if($this->isDemo()) {
            $this->layout->alert = '<div class="alert alert-info">
            <button class="close" data-dismiss="alert" style="border: 0; background-color: transparent;">×</button>
            <strong>Note:</strong> This report is displaying demo data.</div>';
        }
        $this->layout_defaults['page'] = 'traffic';
        $this->layout_defaults['top_nav_title'] = 'Traffic & Revenue';
        $this->layout->top_navigation = $this->buildTopNavigation();
        $this->layout->side_navigation = $this->buildSideNavigation('traffic');

        $this->layout->sub_navigation = View::make('analytics.components.sub_nav.domain', $vars);

        $this->layout->main_content    = View::make('analytics.pages.traffic', $vars);

        $this->logged_in_customer->recordEvent(
                                 UserHistory::VIEW_REPORT,
                                 $parameters = array(
                                     'report' => 'traffic'
                                 )
        );

    }

    /**
     *
     */
    public function showTrafficDefault()
    {
        return $this->showTraffic("Week");
    }

}
