<?php namespace Analytics;

use Log,
    Session,
    UserHistory,
    View;

/**
 * Class ProfileController
 *
 * @package Analytics
 */
class ProfileController extends BaseController
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

        Log::setLog(__FILE__, 'Reporting', 'Profile_Report');
    }

    /**
     * Downloads the CSV export for the profile page
     *
     * @author  Alex
     */
    public function downloadProfile($range, $start_date = false, $end_date = false, $type = "csv")
    {

        $vars = $this->checkProfilePermissions($range);
        $vars = $this->parseDateSelection($vars,$range,$start_date,$end_date);




        $html = View::make('analytics.pages.profile', $vars);


        $date = date("F-j-Y");

        $this->logged_in_customer->recordEvent(
                                 UserHistory::EXPORT_REPORT,
                                 $parameters = array(
                                     'report' => 'profile'
                                 )
        );

        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"Tailwind-Analytics-Profile-data-$date.csv\"");
        echo $html;

    }


    /**
     * Check feature permissions user has for the profile report
     *
     * @author Alex
     *
     */
    public function checkProfilePermissions($range){

        $vars = $this->baseLegacyVariables();
        extract($vars);

        if (Session::get('days_of_calcs',0) <= 1 && !$this->isDemo()) {

            $chart_not_ready = "
                                    $('.feature-wrap-charts').addClass('chart-hide');
                                    $('.profile-chart-wrapper').prepend('<div class=\"chart-not-ready alert alert-info\">Unlocking your charts in T minus $hours_until_charts... <a data-toggle=\"popover\" data-container=\"body\" data-content=\"Each day, we take a snapshot of your analytics (at 12am CST) so you can track your growth over time.  As soon as your next snapshot is created, you\'ll be able to start measuring your progress right here!\" data-placement=\"bottom\"><i class=\"icon-info-2\"></i> Learn more.</a></div>');
                                ";

        } else {
            $chart_not_ready = "";
        }


        //get features
        if ($customer->hasFeature('profile_history_all')) {
            $day_limit           = 1000;
            $date_limit_clause   = "";
            $popover_custom_date = "";
            $popover_basic_date  = "";
            $chart_hide          = "";
            $is_free_account     = false;
        } else {
            if ($customer->hasFeature('profile_history_180')) {
                $day_limit           = 365;
                $date_limit_clause   = "and date >= " . strtotime("-$day_limit days", getFlatDate(time())) . "";
                $popover_custom_date = "";
                $popover_basic_date  = "";
                $chart_hide          = "";
                $is_free_account     = false;
            } else {
                if ($customer->hasFeature('profile_history_90')) {
                    $day_limit           = 90;
                    $chart_hide          = "";
                    $is_free_account     = false;
                    $date_limit_clause   = "and date >= " . strtotime("-$day_limit days", getFlatDate(time())) . "";
                    $popover_custom_date = "";
                    $popover_basic_date  = "";
                } else {
                    $day_limit         = 7;
                    $is_free_account   = true;
                    $date_limit_clause = "and date >= " . strtotime("-$day_limit days", getFlatDate(time())) . "";
                    $range             = "Week";

                    $popover_custom_date = createPopover("#reportrange", "hover", "bottom", "<span class=\"text-success\"><strong>Upgrade to Unlock</strong></span>", "profile_date_range",
                        $customer->plan()->plan_id, "<strong><ul><li>Get more historical data</li><li>Filter using custom date ranges</li></ul>");

                    $chart_hide = "
                    <script>
                        $(document).ready(function() {

                            $('#followers-toggle-dash').on('click', function(){
                                $('.feature-wrap-charts').removeClass('chart-hide-complete');
                                $('.chart-upgrade').addClass('hidden');
                                $chart_not_ready
                            });

                            $('#pins-toggle-dash').on('click', function(){
                                $('.feature-wrap-charts').removeClass('chart-hide-complete');
                                $('.chart-upgrade').addClass('hidden');
                                $chart_not_ready
                            });

                            $('#repins-toggle-dash').on('click', function(){
                                $('.feature-wrap-charts').addClass('chart-hide-complete');
                                $('.chart-upgrade').removeClass('hidden');
                            });

                            $('#likes-toggle-dash').on('click', function(){
                                $('.feature-wrap-charts').addClass('chart-hide-complete');
                                $('.chart-upgrade').removeClass('hidden');
                            });

                        });
                    </script>";
                }
            }
        }


        if ($customer->hasFeature('profile_date_7_days')) {
            $week_link = "href='/profile/Week'";
            $week_pill = "";
        } else {
            $week_link = "";
            $week_pill = "class='inactive'";
        }

        if ($customer->hasFeature('profile_date_14_days')) {
            $week2_link = "href='/profile/2Weeks'";
            $week2_pill = "";
        } else {
            $week2_link = "";
            $week2_pill = "class='inactive'";
        }

        if ($customer->hasFeature('profile_date_30_days')) {
            $month_link = "href='/profile/Month'";
            $month_pill = "";
        } else {
            $month_link = "";
            $month_pill = "class='inactive'";
        }

        if ($customer->hasFeature('profile_date_custom')) {
            $custom_date_state      = "";
            $custom_datepicker_from = "id=\"datepickerFrom\"";
            $custom_datepicker_to   = "id=\"datepickerTo\"";
            $custom_button          = "<button style='margin-right: 17px; margin-left: 10px;' onclick=\"var start_date=$('#datepickerFrom').val();var end_date=$('#datepickerTo').val();window.location = '/profile/custom/'+start_date+'/'+end_date;\" class=\"btn\">Change Date</button>";
            $custom_button_disabled = "";
        } else {
            $custom_date_state      = "inactive";
            $custom_datepicker_from = "";
            $custom_datepicker_to   = "";
            $custom_button          = "";
            $custom_button_disabled = "<button style='margin: 7px 17px 0 10px;' class=\"btn disabled pull-right\">Change Date</button>";
        }

        if ($customer->hasFeature('profile_date_chart_repins')) {

        } else {

        }

        if ($customer->hasFeature('profile_date_chart_likes')) {

        } else {

        }

        if ($customer->hasFeature('profile_date_gauges')) {

        } else {

        }

        if ($customer->hasFeature('profile_export')) {
            if (strpos($_SERVER['REQUEST_URI'], '?')) {
                $csv_url = "href=\"" . $_SERVER['REQUEST_URI'] . "&csv=1\"";
            } else {
                $csv_url = "href=\"" . $_SERVER['REQUEST_URI'] . "?csv=1\"";
            }
            $export_class         = "";
            $export_popover       = "";
            $export_view_class    = "";
            $export_pushout_class = "";
        } else {
            $csv_url              = "";
            $export_class         = "disabled";
            $export_view_class    = "inactive";
            $export_pushout_class = "push-out";
            $export_popover       = createPopover(".profile-export", "hover", "bottom", "<span class=\"text-success\"><strong>Need to Export your Data?</strong></span>", "profile_export",
                $customer->plan()->plan_id, "Upgrade to enable exporting data across your dashboard.<ul><li><strong>Instantly download CSV files</strong> of any report</li><li><strong>Take your data with you</strong> anywhere it needs to go!</li></ul>");
        }

        $permission_check_vars = array_merge(array(
            'chart_not_ready'        => $chart_not_ready,
            'day_limit'              => $day_limit,
            'date_limit_clause'      => $date_limit_clause,
            'popover_custom_date'    => $popover_custom_date,
            'popover_basic_date'     => $popover_basic_date,
            'chart_hide'             => $chart_hide,
            'is_free_account'        => $is_free_account,
            'week_link'              => $week_link,
            'week_pill'              => $week_pill,
            'week2_link'             => $week2_link,
            'week2_pill'             => $week2_pill,
            'month_link'             => $month_link,
            'month_pill'             => $month_pill,
            'custom_date_state'      => $custom_date_state,
            'custom_datepicker_from' => $custom_datepicker_from,
            'custom_datepicker_to'   => $custom_datepicker_to,
            'custom_button'          => $custom_button,
            'custom_button_disabled' => $custom_button_disabled,
            'csv_url'                => $csv_url,
            'export_class'           => $export_class,
            'export_view_class'      => $export_view_class,
            'export_pushout_class'   => $export_pushout_class,
            'export_popover'         => $export_popover,
            'range'                  => $range
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
        $acc = "select * from status_profiles where user_id='$cust_user_id'";
        $acc_res = mysql_query($acc, $conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $cache_timestamp = $a['last_calced'];
        }

        //see how old this account is
        $cust_timestamp = getFlatDate($cust_timestamp);
        if (round(($cache_timestamp - $cust_timestamp) / 60 / 60 / 24) < 14) {
            $fresh_account = true;
        }

        //set custom date range values
        if ($range == "custom" && $start_date && $end_date) {

            $last_date    = getTimestampFromDate($start_date);
            $current_date = getTimestampFromDate($end_date);

//            ppx($last_date . " " . $current_date);



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

            $range = "";

        } else {


            //set standard periodic date range values
            if ($range == "Month" && !$is_free_account) {
                $current_date        = getFlatDate($cache_timestamp);
                $last_date           = getFlatDate(strtotime("-1 month", $cache_timestamp));
                $last_date_fallback  = getFlatDate(strtotime("-1 month 1 day", $cache_timestamp));
                $compare_date        = getFlatDate(strtotime("-2 months", $cache_timestamp));
                $compare2_date       = getFlatDate(strtotime("-3 months", $cache_timestamp));
                $limit_date          = getFlatDate(strtotime("-3 months", $cache_timestamp));
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
                $compare2_date       = getFlatDate(strtotime("-21 days", $cache_timestamp));
                $limit_date          = getFlatDate(strtotime("-28 days", $cache_timestamp));
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
                $compare2_date       = getFlatDate(strtotime("-1 days", $cache_timestamp));
                $limit_date          = getFlatDate(strtotime("-7 days", $cache_timestamp));
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


        $tomorrow_date = strtotime("+1 day", $current_date);
        $hours_until_charts = ceil(number_format(($tomorrow_date - time()) / 60 / 60, 1));
        if ($hours_until_charts == 1) {
            $hours_until_charts = "1 hour";
        } else {
            $hours_until_charts = "" . $hours_until_charts . " hours";
        }

        $days_of_calcs = Session::get('days_of_calcs',0);


        //setup notes to show users when there's not enough history for their date range selection
        if ($range == "Week") {
            if ($days_of_calcs < 7) {
                if ($days_of_calcs == 2) {
                    $current_name = "Last 24 Hours";
                } else {
                    $current_name = "Last $days_of_calcs Days";
                }

                $not_enough_history_week   = "
									<span class='gauge-icon' data-toggle='popover' data-container='body' data-content=\"<strong>Showing $current_name.</strong> Your account is only $days_of_calcs days old!  Keep stopping by and building more history!\" data-placement='bottom'><i id='gauge-icon' class='icon-new'></i></span>
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
									<span class='gauge-icon' data-toggle='popover' data-container='body' data-content=\"<strong>Showing $current_name.</strong> Your account is only $days_of_calcs days old!  Keep stopping by and building more history!\" data-placement='bottom'><i id='gauge-icon' class='icon-new'></i></span>
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
									<span class='gauge-icon' data-toggle='popover' data-container='body' data-content=\"<strong>Showing $current_name.</strong> Your account is only $days_of_calcs days old!  Keep stopping by and building more history!\" data-placement='bottom'><i id='gauge-icon' class='icon-new'></i></span>
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
         * Set the chart width (show only cumulative chart if less than 3 days of history)
         */
        if ($range == "Week") {
            $week_pill        = "class=\"active\"";
            $main_chart_width = "70%";
        } else if ($range == "2Weeks") {
            $week2_pill       = "class=\"active\"";
            $main_chart_width = "70%";
        } else if ($range == "Month") {
            $month_pill       = "class=\"active\"";
            $main_chart_width = "70%";
        } else {
            $main_chart_width = "70%";
        }

        if ($days_of_calcs <= 2) {
            $main_chart_width = "100%";
        }


        $profile_date_vars = array(
            'date_limit'                => $date_limit,
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
            'range'                     => $range,
            'last_date_fallback'        => $last_date_fallback,
            'limit_date'                => $limit_date,
            'chart_time_label'          => $chart_time_label,
            'cache_timestamp_print'     => $cache_timestamp_print,
            'last_date_print'           => $last_date_print,
            'current_date_print'        => $current_date_print,
            'compare_date_print'        => $compare_date_print,
            'compare2_date_print'       => $compare2_date_print,
            'tomorrow_date'             => $tomorrow_date,
            'hours_until_charts'        => $hours_until_charts,
            'days_of_calcs'             => $days_of_calcs,
            'not_enough_history_week'   => $not_enough_history_week,
            'not_enough_history_2weeks' => $not_enough_history_2weeks,
            'not_enough_history_month'  => $not_enough_history_month,
            'main_chart_width'          => $main_chart_width,
            'week_pill'                 => $week_pill,
            'week2_pill'                => $week2_pill,
            'month_pill'                => $month_pill
        );

        return array_merge($vars, $profile_date_vars);

    }



    /**
     * Shows the profile page
     *
     * @author  Alex
     * @author  Will
     */
    public function showProfile($range, $start_date = false, $end_date = false)
    {

        if (isset($_GET['csv'])) {
            return $this->downloadProfile($range, $start_date, $end_date, 'csv');
        }


        $vars = $this->checkProfilePermissions($range);
        $vars = $this->parseDateSelection($vars,$range,$start_date,$end_date);


        /*
        |--------------------------------------------------------------------------
        | Header Vars
        |--------------------------------------------------------------------------
        */
        /*
         * Insert necessary variables into the <head> tag
         */
        $head_vars = array(
            'is_profile' => true,
            'cust_domain' => $vars['cust_domain'],
            'cust_source' => $vars['cust_source']
        );

        $this->layout_defaults['page']          = 'profile';
        $this->layout_defaults['top_nav_title'] = 'Your Profile';
        $this->layout->top_navigation           = $this->buildTopNavigation();

        $this->layout->body_id = 'profile';
        $vars['nav_profile_class'] .= ' active';
        $vars['report_url'] = 'profile';

        $this->layout->side_navigation = $this->buildSideNavigation('profile');

        $this->layout->head .= View::make('analytics.components.head.site_tour', $head_vars);
        $this->layout->head .= View::make('analytics.components.head.gauge');

        $this->logged_in_customer->recordEvent(
                                 UserHistory::VIEW_REPORT,
                                 $parameters = array(
                                     'report' => 'profile'
                                 )
        );

        $this->layout->sub_navigation = View::make('analytics.components.sub_nav.measure', $vars);

        $this->layout->main_content = View::make('analytics.pages.profile', $vars);

        $this->layout->pre_body_close .= View::make('analytics.components.pre_body_close.profile');
        $this->layout->pre_body_close .= View::make('analytics.components.pre_body_close.translation_prompt', array('customer' => $vars['customer']));
    }

    /**
     * Shows the default profile page
     *
     * @author  Alex
     * @author  Will
     */
    public function showProfileDefault()
    {
        //whatever the default page should show should go here
        return $this->showProfile('Week', '', '');
    }

}
