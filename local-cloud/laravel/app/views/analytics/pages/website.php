<?php ini_set('display_errors', 'off');
error_reporting(0);


$page = "Website";

$customer = User::find($cust_id);

$week_link              = "href='/website?range=Week'";
$week_pill              = "";
$week2_link             = "href='/website?range=2Weeks'";
$week2_pill             = "";
$month_link             = "href='/website?range=Month'";
$month_pill             = "";
$custom_date_state      = "";
$custom_datepicker_from = "id=\"datepickerFrom\"";
$custom_datepicker_to   = "id=\"datepickerTo\"";
$custom_button          = "<button style='margin-right: 17px; margin-left: 10px;' type=\"submit\" class=\"btn\">Change Date</button>";
$custom_button_disabled = "";

if ($customer->hasFeature('website_history_all')) {
    $date_limit_clause = "10000000";

} else {
    if ($customer->hasFeature('website_history_180')) {
        $day_limit         = 365;
        $date_limit_clause = strtotime("-$day_limit days", getFlatDate(time()));
    } else {
        if ($customer->hasFeature('website_history_90')) {
            $day_limit         = 90;
            $chart_hide        = "";
            $is_free_account   = false;
            $date_limit_clause = strtotime("-$day_limit days", getFlatDate(time()));
        } else {
            $day_limit         = 0;
            $is_free_account   = true;
            $date_limit_clause = strtotime("-$day_limit days", getFlatDate(time()));
            $_GET['range']     = "None";


//                $day_link = "";
//                $day_pill = "class='inactive'";
            $week_link              = "";
            $week_pill              = "class='inactive'";
            $week2_link             = "";
            $week2_pill             = "class='inactive'";
            $month_link             = "";
            $month_pill             = "class='inactive'";
            $custom_date_state      = "inactive";
            $custom_datepicker_to   = "";
            $custom_datepicker_from = "";
            $custom_button          = "";
            $custom_button_disabled = "<button style='margin: 7px 17px 0 10px;' class=\"btn disabled pull-right\">Change Date</button>";
        }
    }
}

if ($customer->hasFeature('website_date_export')) {
    //TODO: need to build feature
} else {

}


if ($cust_domain != "") {

    //get the last time that this domain was pulled for pins
    $acc = "select * from status_domains where domain='$cust_domain'";
    $acc_res = mysql_query($acc, $conn) or die(mysql_error() . __LINE__);
    while ($a = mysql_fetch_array($acc_res)) {
        $cache_timestamp = $a['last_calced'];
    }

    $has_domain_pins = false;
    $acc = "select pin_id from data_pins_new where domain = '$cust_domain' limit 1";
    $acc_res = mysql_query($acc,$conn) or die(mysql_error());
    while ($a = mysql_fetch_array($acc_res)) {
        $has_domain_pins = true;
    }

    if (!$has_domain_pins){
        print "
			<div class=\"\" style='margin-bottom:10px;'>";
        print "<div class='clearfix'></div>";

        print "<div class='alert alert-error'><strong>Whoops!</strong> Looks like we haven't found any pins from <strong>$cust_domain</strong> on Pinterest yet!
        <br>Try pinning something and you should see this report fill up within the next 24 hours!</div>";

        print "</div>";

        print "<div class='clearfix'></div>";

        print "<div class='clearfix'></div>";
    } else {






        //see how old this account is
        $cust_timestamp = getFlatDate($cust_timestamp);
        if (round(($cache_timestamp - $cust_timestamp) / 60 / 60 / 24) < 14) {
            $fresh_account = true;
        }

        //set custom date range values
        if ((isset($_GET['sdate'])) && (isset($_GET['fdate']))) {
            $last_date    = getTimestampFromDate($_GET['sdate']);
            $current_date = getTimestampFromDate($_GET['fdate']);

            if($current_date > flat_date('day')){
                $current_date = flat_date('day');
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

            if (isset($_GET['range'])) {
                $range = $_GET['range'];
                if (!$range) {
                    $range = "Week";
                }
            } else {
                $range = 'Week';
            }


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
            }
    //            else if ($range == "Day") {
    //                $current_date = getFlatDate($cache_timestamp);
    //                $last_date = getFlatDate(strtotime("-1 day",$cache_timestamp));
    //                $last_date_fallback = getFlatDate(strtotime("-2 days",$cache_timestamp));
    //                $compare_date = getFlatDate(strtotime("-2 days",$cache_timestamp));
    //                $compare2_date = getFlatDate(strtotime("-3 days",$cache_timestamp));
    //                $current_name = "Today";
    //                $old_name = "Yesterday";
    //                $older_name = "2 Days Ago";
    //                $current_chart_label = "Past Day";
    //                $old_chart_label = "Prior Day";
    //                $older_chart_label = "2 Days Prior";
    //                $chart_time_label = "Daily";
    //            }
        }

        $last_date_print     = date("m/d/Y", $last_date);
        $current_date_print  = date("m/d/Y", $current_date);
        $compare_date_print  = date("m/d/Y", $compare_date);
        $compare2_date_print = date("m/d/Y", $compare2_date);


        $website_calcs = array();
        $daily_pins    = array();


        $acc = "SELECT date AS mentioned_day
            , sum(pin_count) as pin_count
            , sum(pinner_count) as pinner_count
            , sum(repin_count) as repin_count
            , sum(like_count) as like_count
            , sum(comment_count) as comment_count
            , sum(reach) as reach
            FROM cache_domain_daily_counts
            WHERE domain='$cust_domain'
            AND date > $date_limit_clause
            and date <= $current_date
            GROUP BY mentioned_day
            ORDER BY mentioned_day DESC";
        $acc_res = mysql_query($acc, $conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $query_date      = $a['mentioned_day'];

            if (!isset($website_calcs["$query_date"])) {
                $website_calcs["$query_date"]                        = array();
                $website_calcs["$query_date"]['timestamp']           = $query_date;
                $website_calcs["$query_date"]['daily_pin_count']     = $a['pin_count'];
                $website_calcs["$query_date"]['daily_pinner_count']  = $a['pinner_count'];
                $website_calcs["$query_date"]['daily_repin_count']   = $a['repin_count'];
                $website_calcs["$query_date"]['daily_like_count']    = $a['like_count'];
                $website_calcs["$query_date"]['daily_comment_count'] = $a['comment_count'];
                $website_calcs["$query_date"]['daily_reach']         = $a['reach'];
            } else {
                $website_calcs["$query_date"]['daily_pin_count']     = $a['pin_count'];
                $website_calcs["$query_date"]['daily_pinner_count']  = $a['pinner_count'];
                $website_calcs["$query_date"]['daily_repin_count']   = $a['repin_count'];
                $website_calcs["$query_date"]['daily_like_count']    = $a['like_count'];
                $website_calcs["$query_date"]['daily_comment_count'] = $a['comment_count'];
                $website_calcs["$query_date"]['daily_reach']         = $a['reach'];
            }
        }

        krsort($website_calcs);

        $total_pin_count = 0;

        foreach ($website_calcs as $wc) {
            array_push($daily_pins, $wc['daily_pin_count']);
            $total_pin_count += $wc['daily_pin_count'];
        }

        $total_pin_count_formatted = formatNumber($total_pin_count);

        $daily_pins_avgs = regularAverage($daily_pins, $day_range);

        $total_pinner_count = 0;
        $daily_pinners = array();
        foreach ($website_calcs as $wc) {
            array_push($daily_pinners, $wc['daily_pinner_count']);
            $total_pinner_count += $wc['daily_pinner_count'];
        }

        $total_pinner_count_formatted = formatNumber($total_pinner_count);

        $daily_pinners_avgs = regularAverage($daily_pinners, $day_range);

        //get all calcs	by date
        $acc = "select *, DATE(FROM_UNIXTIME(`date`)) AS pDate
            from calcs_domain_history
            where domain='$cust_domain'
            and date >= $date_limit_clause
            and date <= $current_date
            group by pDate
            order by `date` desc";
        $acc_res = mysql_query($acc, $conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $query_date          = $a['date'];
            $query_date_tomorrow = strtotime("+1 day", $query_date);


            if (isset($website_calcs["$query_date_tomorrow"])) {
                @$website_calcs["$query_date_tomorrow"]['daily_impressions'] = $website_calcs["$query_date_tomorrow"]['domain_impressions'] - $a['domain_impressions'];
                @$website_calcs["$query_date_tomorrow"]['daily_reach'] = $website_calcs["$query_date_tomorrow"]['domain_reach'] - $a['domain_reach'];
            }


            if (!isset($website_calcs["$query_date"])) {
                $website_calcs["$query_date"]                          = array();
                $website_calcs["$query_date"]['timestamp']             = $a['date'];
                $website_calcs["$query_date"]['domain_mentions']       = $a['domain_mentions'];
                $website_calcs["$query_date"]['unique_domain_pinners'] = $a['unique_domain_pinners'];
                $website_calcs["$query_date"]['domain_reach']          = $a['domain_reach'];
                $website_calcs["$query_date"]['domain_impressions']    = $a['domain_impressions'];
                $website_calcs["$query_date"]['reach_per_pin']         = ($a['domain_reach'] / $a['domain_mentions']);
                $website_calcs["$query_date"]['impressions_per_pin']   = ($a['domain_impressions'] / $a['domain_mentions']);

            } else {
                $website_calcs["$query_date"]['domain_mentions']       = $a['domain_mentions'];
                $website_calcs["$query_date"]['unique_domain_pinners'] = $a['unique_domain_pinners'];
                $website_calcs["$query_date"]['domain_reach']          = $a['domain_reach'];
                $website_calcs["$query_date"]['domain_impressions']    = $a['domain_impressions'];
                @$website_calcs["$query_date"]['reach_per_pin']         = ($a['domain_reach'] / $a['domain_mentions']);
                @$website_calcs["$query_date"]['impressions_per_pin']   = ($a['domain_impressions'] / $a['domain_mentions']);
            }

        }
        krsort($website_calcs);


        //calculate growth in pins and pinner based on daily activity
        $period_domain_mention_change = 0;
        $period_unique_pinners_change = 0;

        for ($i = $current_date; $i >= $last_date; $i = strtotime("-1 day", $i)) {
            if (isset($website_calcs[$i]['daily_pin_count'])) {

                $period_domain_mention_change += $website_calcs[$i]['daily_pin_count'];
                $period_unique_pinners_change += $website_calcs[$i]['daily_pinner_count'];
            }
        }

        $period_domain_mention_change = formatAbsolute($period_domain_mention_change);
        $period_unique_pinners_change = formatAbsolute($period_unique_pinners_change);


        $acc = "SELECT sum(pin_count) as pin_count
            FROM cache_domain_daily_counts
            WHERE domain='$cust_domain'
            and date <= $current_date";
        $acc_res = mysql_query($acc, $conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $total_pin_count_to_date = $a['pin_count'];
        }

        $total_pin_count_to_date_formatted = formatNumber($total_pin_count_to_date);




        //add growth values to array
        @$website_calcs[$current_date]['domain_mentions_growth'] = $website_calcs[$current_date]['domain_mentions'] - $website_calcs[$last_date]['domain_mentions'];

        @$website_calcs[$current_date]['unique_domain_pinners_growth'] = $website_calcs[$current_date]['unique_domain_pinners'] - $website_calcs[$last_date]['unique_domain_pinners'];

        @$website_calcs[$current_date]['domain_reach_growth'] = $website_calcs[$current_date]['domain_reach'] - $website_calcs[$last_date]['domain_reach'];

        @$website_calcs[$current_date]['domain_impressions_growth'] = $website_calcs[$current_date]['domain_impressions'] - $website_calcs[$last_date]['domain_impressions'];

        @$website_calcs[$current_date]['daily_impressions_growth'] = $website_calcs[$current_date]['daily_impressions'] - $website_calcs[$last_date]['daily_impressions'];

        @$website_calcs[$current_date]['daily_reach_growth'] = $website_calcs[$current_date]['daily_reach'] - $website_calcs[$last_date]['daily_reach'];

        @$website_calcs[$compare_date]['reach_per_pin_growth'] = $website_calcs[$compare_date]['reach_per_pin'] - $website_calcs[$last_date]['reach_per_pin'];

        @$website_calcs[$compare_date]['impressions_per_pin_growth'] = $website_calcs[$compare_date]['impressions_per_pin'] - $website_calcs[$last_date]['impressions_per_pin'];


        //period 1 growth percentage
        if ($website_calcs[$last_date]['domain_mentions']) {
            $website_calcs[$current_date]['domain_mentions_growth_perc'] = ($website_calcs[$current_date]['domain_mentions'] - $website_calcs[$last_date]['domain_mentions']) / $website_calcs[$last_date]['domain_mentions'];

            $website_calcs[$current_date]['unique_domain_pinners_growth_perc'] = ($website_calcs[$current_date]['unique_domain_pinners'] - $website_calcs[$last_date]['unique_domain_pinners']) / $website_calcs[$last_date]['unique_domain_pinners'];

            $website_calcs[$current_date]['domain_reach_growth_perc'] = ($website_calcs[$current_date]['domain_reach'] - $website_calcs[$last_date]['domain_reach']) / $website_calcs[$last_date]['domain_reach'];

            $website_calcs[$current_date]['domain_impressions_growth_perc'] = ($website_calcs[$current_date]['domain_impressions'] - $website_calcs[$last_date]['domain_impressions']) / $website_calcs[$last_date]['domain_impressions'];

            $website_calcs[$current_date]['daily_impressions_growth_perc'] = ($website_calcs[$current_date]['daily_impressions'] - $website_calcs[$last_date]['daily_impressions']) / $website_calcs[$last_date]['daily_impressions'];

            $website_calcs[$current_date]['daily_reach_growth_perc'] = ($website_calcs[$current_date]['daily_reach'] - $website_calcs[$last_date]['daily_reach']) / $website_calcs[$last_date]['daily_reach'];

            $website_calcs[$current_date]['reach_per_pin_growth_perc'] = ($website_calcs[$current_date]['reach_per_pin'] - $website_calcs[$last_date]['reach_per_pin']) / $website_calcs[$last_date]['reach_per_pin'];

            $website_calcs[$current_date]['impressions_per_pin_growth_perc'] = ($website_calcs[$current_date]['impressions_per_pin'] - $website_calcs[$last_date]['impressions_per_pin']) / $website_calcs[$last_date]['impressions_per_pin'];
        }

        //growth period 2
        $website_calcs[$last_date]['domain_mentions_growth'] = $website_calcs[$last_date]['domain_mentions'] - $website_calcs[$compare_date]['domain_mentions'];

        $website_calcs[$last_date]['unique_domain_pinners_growth'] = $website_calcs[$last_date]['unique_domain_pinners'] - $website_calcs[$compare_date]['unique_domain_pinners'];

        $website_calcs[$last_date]['domain_reach_growth'] = $website_calcs[$last_date]['domain_reach'] - $website_calcs[$compare_date]['domain_reach'];

        $website_calcs[$last_date]['domain_impressions_growth'] = $website_calcs[$last_date]['domain_impressions'] - $website_calcs[$compare_date]['domain_impressions'];

        $website_calcs[$last_date]['daily_impressions_growth'] = $website_calcs[$last_date]['daily_impressions'] - $website_calcs[$compare_date]['daily_impressions'];

        $website_calcs[$last_date]['daily_reach_growth'] = $website_calcs[$last_date]['daily_reach'] - $website_calcs[$compare_date]['daily_reach'];

        $website_calcs[$last_date]['reach_per_pin_growth'] = $website_calcs[$last_date]['reach_per_pin'] - $website_calcs[$compare_date]['reach_per_pin'];

        $website_calcs[$last_date]['impressions_per_pin_growth'] = $website_calcs[$last_date]['impressions_per_pin'] - $website_calcs[$compare_date]['impressions_per_pin'];


        //period 2 growth percentage

        if ($website_calcs[$compare_date]['domain_mentions']) {
            $website_calcs[$last_date]['domain_mentions_growth_perc'] = ($website_calcs[$last_date]['domain_mentions'] - $website_calcs[$compare_date]['domain_mentions']) / $website_calcs[$compare_date]['domain_mentions'];

            $website_calcs[$last_date]['unique_domain_pinners_growth_perc'] = ($website_calcs[$last_date]['unique_domain_pinners'] - $website_calcs[$compare_date]['unique_domain_pinners']) / $website_calcs[$compare_date]['unique_domain_pinners'];

            $website_calcs[$last_date]['domain_reach_growth_perc'] = ($website_calcs[$last_date]['domain_reach'] - $website_calcs[$compare_date]['domain_reach']) / $website_calcs[$compare_date]['domain_reach'];

            $website_calcs[$last_date]['domain_impressions_growth_perc'] = ($website_calcs[$last_date]['domain_impressions'] - $website_calcs[$compare_date]['domain_impressions']) / $website_calcs[$compare_date]['domain_impressions'];

            $website_calcs[$last_date]['daily_impressions_growth_perc'] = ($website_calcs[$last_date]['daily_impressions'] - $website_calcs[$compare_date]['daily_impressions']) / $website_calcs[$compare_date]['daily_impressions'];

            $website_calcs[$last_date]['daily_reach_growth_perc'] = ($website_calcs[$last_date]['daily_reach'] - $website_calcs[$compare_date]['daily_reach']) / $website_calcs[$compare_date]['daily_reach'];

            $website_calcs[$last_date]['reach_per_pin_growth_perc'] = ($website_calcs[$last_date]['reach_per_pin'] - $website_calcs[$compare_date]['reach_per_pin']) / $website_calcs[$compare_date]['reach_per_pin'];

            $website_calcs[$last_date]['impressions_per_pin_growth_perc'] = ($website_calcs[$last_date]['impressions_per_pin'] - $website_calcs[$compare_date]['impressions_per_pin']) / $website_calcs[$compare_date]['impressions_per_pin'];

        }


        //growth period 3
        $website_calcs[$compare_date]['domain_mentions_growth'] = $website_calcs[$compare_date]['domain_mentions'] - $website_calcs[$compare2_date]['domain_mentions'];

        $website_calcs[$compare_date]['unique_domain_pinners_growth'] = $website_calcs[$compare_date]['unique_domain_pinners'] - $website_calcs[$compare2_date]['unique_domain_pinners'];

        $website_calcs[$compare_date]['domain_reach_growth'] = $website_calcs[$compare_date]['domain_reach'] - $website_calcs[$compare2_date]['domain_reach'];

        $website_calcs[$compare_date]['domain_impressions_growth'] = $website_calcs[$compare_date]['domain_impressions'] - $website_calcs[$compare2_date]['domain_impressions'];

        $website_calcs[$compare_date]['daily_impressions_growth'] = $website_calcs[$compare_date]['daily_impressions'] - $website_calcs[$compare2_date]['daily_impressions'];

        $website_calcs[$compare_date]['daily_reach_growth'] = $website_calcs[$compare_date]['daily_reach'] - $website_calcs[$compare2_date]['daily_reach'];

        $website_calcs[$compare_date]['reach_per_pin_growth'] = $website_calcs[$compare_date]['reach_per_pin'] - $website_calcs[$compare2_date]['reach_per_pin'];

        $website_calcs[$compare_date]['impressions_per_pin_growth'] = $website_calcs[$compare_date]['impressions_per_pin'] - $website_calcs[$compare2_date]['impressions_per_pin'];


        //period 3 growth percentage

        if ($website_calcs[$compare2_date]['domain_mentions']) {
            $website_calcs[$compare_date]['domain_mentions_growth_perc'] = ($website_calcs[$last_date]['domain_mentions'] - $website_calcs[$compare2_date]['domain_mentions']) / $website_calcs[$compare2_date]['domain_mentions'];

            $website_calcs[$compare_date]['unique_domain_pinners_growth_perc'] = ($website_calcs[$compare_date]['unique_domain_pinners'] - $website_calcs[$compare2_date]['unique_domain_pinners']) / $website_calcs[$compare2_date]['unique_domain_pinners'];

            $website_calcs[$compare_date]['domain_reach_growth_perc'] = ($website_calcs[$compare_date]['domain_reach'] - $website_calcs[$compare2_date]['domain_reach']) / $website_calcs[$compare2_date]['domain_reach'];

            $website_calcs[$compare_date]['domain_impressions_growth_perc'] = ($website_calcs[$compare_date]['domain_impressions'] - $website_calcs[$compare2_date]['domain_impressions']) / $website_calcs[$compare2_date]['domain_impressions'];

            $website_calcs[$compare_date]['daily_impressions_growth_perc'] = ($website_calcs[$compare_date]['daily_impressions'] - $website_calcs[$compare2_date]['daily_impressions']) / $website_calcs[$compare2_date]['daily_impressions'];

            $website_calcs[$compare_date]['daily_reach_growth_perc'] = ($website_calcs[$compare_date]['daily_reach'] - $website_calcs[$compare2_date]['daily_reach']) / $website_calcs[$compare2_date]['daily_reach'];

            $website_calcs[$compare_date]['reach_per_pin_growth_perc'] = ($website_calcs[$compare_date]['reach_per_pin'] - $website_calcs[$compare2_date]['reach_per_pin']) / $website_calcs[$compare2_date]['reach_per_pin'];

            $website_calcs[$compare_date]['impressions_per_pin_growth_perc'] = ($website_calcs[$compare_date]['impressions_per_pin'] - $website_calcs[$compare2_date]['impressions_per_pin']) / $website_calcs[$compare2_date]['impressions_per_pin'];

        }


        //Create formatted values of calcs
        foreach ($website_calcs as $p) {

            $date                             = $p['timestamp'];
            $website_calcs_formatted["$date"] = array();

            @$website_calcs_formatted["$date"]['domain_mentions'] = formatNumber($p['domain_mentions']);
            @$website_calcs_formatted["$date"]['unique_domain_pinners'] = formatNumber($p['unique_domain_pinners']);
            @$website_calcs_formatted["$date"]['domain_reach'] = formatNumber($p['domain_reach']);
            @$website_calcs_formatted["$date"]['domain_impressions'] = formatNumber($p['domain_impressions']);
            @$website_calcs_formatted["$date"]['daily_impressions'] = formatNumber($p['daily_impressions']);
            @$website_calcs_formatted["$date"]['daily_reach'] = formatNumber($p['daily_reach']);
            @$website_calcs_formatted["$date"]['reach_per_pin'] = formatAbsoluteKPI($p['reach_per_pin']);
            @$website_calcs_formatted["$date"]['impressions_per_pin'] = formatAbsoluteKPI($p['impressions_per_pin']);
            @$website_calcs_formatted["$date"]['daily_pin_count'] = formatNumber($p['daily_pin_count']);
            @$website_calcs_formatted["$date"]['daily_pinner_count'] = formatNumber($p['daily_pinner_count']);


            if ($date == $current_date || $date == $last_date || $date == $compare_date) {
                if ($p['domain_mentions_growth']) {
                    @$website_calcs_formatted["$date"]['domain_mentions_growth'] = formatAbsolute($p['domain_mentions_growth']);
                    @$website_calcs_formatted["$date"]['unique_domain_pinners_growth'] = formatAbsolute($p['unique_domain_pinners_growth']);
                    @$website_calcs_formatted["$date"]['domain_reach_growth'] = formatAbsolute($p['domain_reach_growth']);
                    @$website_calcs_formatted["$date"]['domain_impressions_growth'] = formatAbsolute($p['domain_impressions_growth']);
                    @$website_calcs_formatted["$date"]['daily_impressions_growth'] = formatAbsolute($p['daily_impressions_growth']);
                    @$website_calcs_formatted["$date"]['daily_reach_growth'] = formatAbsolute($p['daily_reach_growth']);
                    @$website_calcs_formatted["$date"]['reach_per_pin_growth'] = formatAbsoluteKPI($p['reach_per_pin_growth']);
                    @$website_calcs_formatted["$date"]['impressions_per_pin_growth'] = formatAbsoluteKPI($p['impressions_per_pin_growth']);

                } else {
                    @$website_calcs_formatted["$date"]['domain_mentions_growth'] = formatAbsolute(0);
                    @$website_calcs_formatted["$date"]['unique_domain_pinners_growth'] = formatAbsolute(0);
                    @$website_calcs_formatted["$date"]['domain_reach_growth'] = formatAbsolute(0);
                    @$website_calcs_formatted["$date"]['domain_impressions_growth'] = formatAbsolute(0);
                    @$website_calcs_formatted["$date"]['daily_impressions_growth'] = formatAbsolute(0);
                    @$website_calcs_formatted["$date"]['daily_reach_growth'] = formatAbsolute(0);
                    @$website_calcs_formatted["$date"]['reach_per_pin_growth'] = formatAbsoluteKPI(0);
                    @$website_calcs_formatted["$date"]['impressions_per_pin_growth'] = formatAbsoluteKPI(0);
                }

                if ($p['domain_mentions_growth_perc']) {
                    $website_calcs_formatted["$date"]['domain_mentions_growth_perc']       = formatPercentage($p['domain_mentions_growth_perc']);
                    $website_calcs_formatted["$date"]['unique_domain_pinners_growth_perc'] = formatPercentage($p['unique_domain_pinners_growth_perc']);
                    $website_calcs_formatted["$date"]['domain_reach_growth_perc']          = formatPercentage($p['domain_reach_growth_perc']);
                    $website_calcs_formatted["$date"]['domain_impressions_growth_perc']    = formatPercentage($p['domain_impressions_growth_perc']);
                    $website_calcs_formatted["$date"]['daily_impressions_growth_perc']     = formatPercentage($p['daily_impressions_growth_perc']);
                    $website_calcs_formatted["$date"]['daily_reach_growth_perc']           = formatPercentage($p['daily_reach_growth_perc']);
                    $website_calcs_formatted["$date"]['reach_per_pin_growth_perc']         = formatPercentage($p['reach_per_pin_growth_perc']);
                    $website_calcs_formatted["$date"]['impressions_per_pin_growth_perc']   = formatPercentage($p['impressions_per_pin_growth_perc']);
                } else {
                    $website_calcs_formatted["$date"]['domain_mentions_growth_perc']       = formatPercentage('na');
                    $website_calcs_formatted["$date"]['unique_domain_pinners_growth_perc'] = formatPercentage('na');
                    $website_calcs_formatted["$date"]['domain_reach_growth_perc']          = formatPercentage('na');
                    $website_calcs_formatted["$date"]['domain_impressions_growth_perc']    = formatPercentage('na');
                    $website_calcs_formatted["$date"]['daily_impressions_growth_perc']     = formatPercentage('na');
                    $website_calcs_formatted["$date"]['daily_reach_growth_perc']           = formatPercentage('na');
                    $website_calcs_formatted["$date"]['reach_per_pin_growth_perc']         = formatPercentage('na');
                    $website_calcs_formatted["$date"]['impressions_per_pin_growth_perc']   = formatPercentage('na');
                }
            }
        }

        $pins = array();
        //get latest pins sample
        $acc2 = "select * from data_pins_new where domain='$cust_domain' order by created_at desc limit 12";
        $acc2_res = mysql_query($acc2, $conn) or die(mysql_error() . __LINE__);
        while ($b = mysql_fetch_array($acc2_res)) {

            $pin_id = $b['pin_id'];

            //set pins array
            $pins["$pin_id"]                   = array();
            $pins["$pin_id"]['pin_id']         = $b['pin_id'];
            $pins["$pin_id"]['user_id']        = $b['user_id'];
            $pins["$pin_id"]['board_id']       = $b['board_id'];
            $pins["$pin_id"]['domain']         = $b['domain'];
            $pins["$pin_id"]['method']         = $b['method'];
            $pins["$pin_id"]['is_repin']       = $b['is_repin'];
            $pins["$pin_id"]['image_url']      = $b['image_url'];
            $pins["$pin_id"]['link']           = $b['link'];
            $pins["$pin_id"]['description']    = preg_replace('/[^A-Za-z0-9 ]/', ' ', $b['description']);
            $pins["$pin_id"]['dominant_color'] = $b['dominant_color'];
            $pins["$pin_id"]['repin_count']    = $b['repin_count'];
            $pins["$pin_id"]['like_count']     = $b['like_count'];
            $pins["$pin_id"]['comment_count']  = $b['comment_count'];
            $pins["$pin_id"]['created_at']     = $b['created_at'];
            $pins["$pin_id"]['image_id']       = $b['image_id'];
        }

        $pinners = array();
        //get latest pins sample
        $acc2 = "select * from cache_domain_influencers where domain='$cust_domain' and username!='$cust_username' order by rand() limit 9";
        $acc2_res = mysql_query($acc2, $conn) or die(mysql_error() . __LINE__);
        while ($b = mysql_fetch_array($acc2_res)) {

            $pinner_id = $b['influencer_user_id'];

            //set pins array
            $pinners["$pinner_id"]                    = array();
            $pinners["$pinner_id"]['pinner_id']       = $b['influencer_user_id'];
            $pinners["$pinner_id"]['username']        = $b['username'];
            $pinners["$pinner_id"]['domain_mentions'] = $b['domain_mentions'];
            $pinners["$pinner_id"]['first_name']      = $b['first_name'];
            $pinners["$pinner_id"]['last_name']       = $b['last_name'];
            $pinners["$pinner_id"]['follower_count']  = $b['follower_count'];
            $pinners["$pinner_id"]['image']           = $b['image'];
            $pinners["$pinner_id"]['website']         = $b['website'];
            $pinners["$pinner_id"]['website_label']   = str_replace("http://", "", str_replace("www.", "", $b['website']));
            $pinners["$pinner_id"]['facebook']        = $b['facebook'];
            $pinners["$pinner_id"]['twitter']         = $b['twitter'];
            $pinners["$pinner_id"]['location']        = preg_replace('/[^A-Za-z0-9 ]/', ' ', $b['location']);
            $pinners["$pinner_id"]['pin_count']       = $b['pin_count'];
        }

        //get top pinners sample


        $this_time = "<span class='time-left muted'>$current_chart_label</span>";
        $last_time = "<span class='time-right muted'>$old_chart_label</span>";


        $is_profile = true;
        $datePicker = true;

        //	print "<pre>";
        //	print_r($website_calcs_formatted);
        //	print "</pre>";


        if ($range == "Week") {
            $week_pill = "class=\"active\"";
        } else if ($range == "2Weeks") {
            $week2_pill = "class=\"active\"";
        } else if ($range == "Month") {
            $month_pill = "class=\"active\"";
        }

        print "<div class='clearfix'></div>";
        print "<div class=''>";


        print "<div class='accordion' id='accordion3' style='margin-bottom:25px'>
              <div class='accordion-group' style='margin-bottom:25px'>
                <div class='accordion-heading'>
                  <div class='accordion-toggle' data-parent='#accordion3' href='#collapseTwo' style='cursor:default'>";


        print "
                            <div class=\"pull-left\" style='text-align:left;'>";

        print "<div class=\"\" style='text-align:right;margin-right:15px'>";
        print "
                                        <ul class=\"nav nav-pills pull-right\" style=''>
                                          <li class='date-label'><span class='date-label'>Date Range:</span> &nbsp;</li>
                                          <li $week_pill><a $week_link>7 Days</a></li>
                                          <li $week2_pill><a $week2_link>14 Days</a></li>
                                          <li $month_pill><a $month_link>30 Days</a></li>

                                        </ul>";
        print "</div>";
        print "</div>";


        print "<div class=\"pull-right $custom_date_state\"'>";
        print "<div class=\"\" style='text-align:right;'>";
        print "$custom_button_disabled";
        print "<form class='form-search pull-right' action='/website' method='GET'
                                        style='margin-bottom:0px; padding-top:8px;'>";
        print "<span class='date-label'>
                                                Custom Range:
                                           </span>&nbsp;
                                           <input name='sdate' class=\"input-small\" type=\"text\"
                                           $custom_datepicker_from value=\"$last_date_print\"> -
                                           <input name='fdate' class=\"input-small\" type=\"text\"
                                           $custom_datepicker_to value=\"$current_date_print\">
                                           $custom_button";
        print "</form>";
        print "</div>";
        print "</div>";


        print "
                  </div>
                </div>";

        print "<div class='clearfix section-header'></div>";

        print "
                <div id='collapseTwo' class='accordion-body collapse in'>
                     <div class='accordion-inner'>";

        print "
                <div class=\"row dashboard\">";

        print "<div class=\"\" style='text-align:center;'>";

        print "<div class=\"row\" style='margin:10px 0 10px 30px;'>";

        print "<div class='feature-wrap'>

                                    <div id=\"site-pins-toggle-dash\" class=\"feature feature-left half active\" style='text-align:center; cursor: hand;'>
                                        <div>
                                            <div class='feature-stat'>$total_pin_count_to_date_formatted
                                            </div>
                                        </div>
                                        <h4> Total Pins </h4>
                                        <div class='feature-growth'>
                                            <span class='time'>$current_name</span>
                                            <span class='growth'>$period_domain_mention_change</span>
                                        </div>
                                    </div>";

        print "
                                    <div id=\"pinners-toggle-dash\" class=\"feature feature-right half\" style='text-align:center; cursor: hand;'>
                                        <div>
                                            <div class='feature-stat'>" . $website_calcs_formatted[$current_date]['unique_domain_pinners'] .  "
                                            </div>
                                        </div>
                                        <h4> Total Unique Pinners </h4>
                                        <div class='feature-growth'>
                                            <span class='time'>$current_name</span>
                                            <span class='growth'>$period_unique_pinners_change</span>
                                        </div>
                                    </div>";

        //							print "
        //							<div class=\"feature feature-right-third\" style='text-align:center;'>
        //								<h4>Reach</h4>
        //								<div>
        //									<div class='feature-stat'>" . $website_calcs_formatted[$current_date]['domain_reach'] . "</div>
        //									<div class='feature-growth'>
        //										<span class='left'>$this_time " . $website_calcs_formatted[$current_date]['domain_reach_growth'] . " " . $website_calcs_formatted[$current_date]['domain_reach_growth_perc'] . "</span>
        //										<span class='right'>$last_time " . $website_calcs_formatted[$last_date]['domain_reach_growth'] . " " . $website_calcs_formatted[$last_date]['domain_reach_growth_perc'] . "</span>
        //									</div>
        //								</div>
        //							</div>";

        print "	</div>";

        print "	</div>";

        print "	</div>";

        print "	<div class=\"\" style='margin-left:30px'>";

        print "	<div class=\"row\" style='margin-left:0px;'>";

        print "	<div class=\"feature-wrap-charts-web\" style='text-align:left; margin-bottom:40px'>";


        $new_curr_chart_date = $current_date * 1000;
        $new_last_chart_date = $last_date * 1000;

        if ($range == "Day") {
            $new_curr_chart_date = strtotime("-1 day", $current_date) * 1000;
            $new_last_chart_date = strtotime("-2 days", $current_date) * 1000;
        }

        //-------------------------// WEBSITE CHART //---------------------------//

        print "<script type='text/javascript' src='https://www.google.com/jsapi'></script>
                                        <script type='text/javascript'>

                                          google.load('visualization', '1.1', {packages: ['corechart', 'controls']});

                                          google.setOnLoadCallback(drawVisualization);



                                          function drawVisualization() {
                                                var dashboard2 = new google.visualization.Dashboard(document.getElementById('website_chart_div'));

                                                var control2 = new google.visualization.ControlWrapper({
                                                  'controlType': 'ChartRangeFilter',
                                                  'containerId': 'control2',
                                                  'options': {
                                                    // Filter by the date axis.
                                                    'filterColumnIndex': 0,
                                                    'ui': {
                                                      'chartType': 'AreaChart',
                                                      'chartOptions': {
                                                        'chartArea': {'width': '80%'},
                                                        'hAxis': {'baselineColor': 'none'},
                                                        'series': {0:{color: '#D77E81'}},
                                                        'curveType':'function',
                                                        'animation':{
                                                            'duration': 500,
                                                            'easing': 'inAndOut'
                                                         }
                                                      },


                                                      // 1 day in milliseconds = 24 * 60 * 60 * 1000 = 86,400,000
                                                      'minRangeSize': 86400000
                                                    }
                                                  },
                                                  // Initial range:
                                                  'state': {'range': {'start': new Date($new_last_chart_date), 'end': new Date($new_curr_chart_date)}}
                                                });

                                                var chart2 = new google.visualization.ChartWrapper({
                                                  'chartType': 'AreaChart',
                                                  'containerId': 'chart2',
                                                  'options': {
                                                    // Use the same chart area width as the control for axis alignment.
                                                    'chartArea': {'left':'0px','top':'30px','height': '80%', 'width': '80%'},
                                                    'hAxis': {'slantedText': false},
                                                    'legend': {'position': 'top'},
                                                    'series': {0:{color: '#D77E81'}},
                                                    'curveType':'function',
                                                    'animation':{
                                                        'duration': 500,
                                                        'easing': 'inAndOut'
                                                     },
                                                     'title':'Number of Pins Created from $cust_domain'
                                                  }

                                                });



                                                var data = new google.visualization.DataTable();
                                                   data.addColumn('date', 'Date');
                                                   data.addColumn('number', 'Pins');
                                                   data.addColumn('number', 'Pinners');";

        $max_reach_per_pin   = 0;
        $total_reach_per_pin = 0;
        $rpp_counter         = 0;

        foreach ($website_calcs as $d) {

            @$chart_domain_mentions = $d['domain_mentions'];
            @$chart_domain_impressions = $d['domain_impressions'];
            @$chart_daily_pin_count = $d['daily_pin_count'];
            @$chart_daily_pinner_count = $d['daily_pinner_count'];
            @$chart_daily_reach = $d['daily_reach'];
            @$chart_daily_impressions = $d['daily_impressions'];
            $chart_time    = $d['timestamp'];
            $chart_time_js = $chart_time * 1000;

            if (isset($d['reach_per_pin'])) {
                if ($d['reach_per_pin'] > $max_reach_per_pin) {
                    $max_reach_per_pin = $d['reach_per_pin'];
                }

                $total_reach_per_pin += $d['reach_per_pin'];
                $rpp_counter++;
            }


            if (isset($chart_daily_pin_count) && isset($chart_daily_pinner_count)) {
                if($chart_time > $date_limit_clause){
                print
                    "var date = new Date($chart_time_js);
                                                         data.addRow([date, {$chart_daily_pin_count}, {$chart_daily_pinner_count}]);";

                }
            }

        }

        $avg_reach_per_pin = number_format($total_reach_per_pin / $rpp_counter, 0);

        print "




                                            var pinBox = document.getElementById('site-pins-toggle');
                                            var pinnerBox = document.getElementById('pinners-toggle');
                                            //var reachBox = document.getElementById('reach-toggle');

                                            function drawChart() {



                                                // Disabling the buttons while the chart is drawing.
                                                pinBox.checked = false;
                                                pinnerBox.checked = false;
                                               // reachBox.checked = false;

                                                //google.visualization.events.addListener(chart, 'ready', function() {
                                                      // Check and enable only relevant boxes.


                                                      pinBox.checked = view.getViewColumns().indexOf(1) != -1;

                                                      pinnerBox.checked = view.getViewColumns().indexOf(2) != -1;

                                                    //  reachBox.checked = view.getViewColumns().indexOf(3) != -1;


                                                   // });






                                                dashboard2.bind(control2, chart2);
                                                dashboard2.draw(view);




                                            }


                                            pinBox.onclick = function() {
                                                //adding pins
                                                if(pinBox.checked){
                                                    view.setColumns([0,1]);
                                                    chart2.setOption('series', [{'color':'#D77E81'}]);
                                                    chart2.setOption('title', 'Number of Pins Created from $cust_domain');
                                                    control2.setOption('ui',{'chartOptions':{'series': [{'color':'#D77E81'}],'chartArea': {'width': '80%'}}, 'chartType': 'AreaChart'});
                                                    chart2.draw(view);
                                                    control2.draw(view);
                                                    drawChart();
                                                }
                                            }

                                            pinnerBox.onclick = function() {
                                                //adding repins
                                                if(pinnerBox.checked){
                                                    view.setColumns([0,2]);
                                                    chart2.setOption('series', [{'color':'#5792B3'}]);
                                                    chart2.setOption('title', 'Number of People Pinning from $cust_domain');
                                                    control2.setOption('ui',{'chartOptions':{'series': [{'color': '#5792B3'}],'chartArea': {'width': '80%'}}, 'chartType': 'AreaChart'});
                                                    drawChart();
                                                }
                                            }

        //								    reachBox.onclick = function() {
                                                //adding likes
        //								    	if(reachBox.checked){
        //								    		view.setColumns([0,3]);
        //								    		chart2.setOption('series', [{'color':'#FF9900'}]);
        //								    		control2.setOption('ui',{'chartOptions':{'series': [{'color': '#FF9900'}],'chartArea': {'width': '80%'}}, 'chartType': 'AreaChart'});
        //											drawChart();
        //								    	}
        //								    }



                                            var view = new google.visualization.DataView(data);
                                            view.setColumns([0,1]);

                                            drawChart();

                                          }
                                        </script>";

        print "		<div id='website_chart_div' style='float:left; width:67%;'>
                                                    <div id='chart2' style='width: 100%; height: 180px;'></div>
                                                    <div id='control2' style='width: 100%; height: 30px;'></div>
                                                </div>	";


    //    $curr_rpp = number_format($website_calcs[$current_date]['reach_per_pin'],0);
    //    $past_rpp = $website_calcs[$last_date]['reach_per_pin'];
    //    $max_rpp = number_format($max_reach_per_pin);
    //    $rppc = $curr_rpp - $past_rpp;
    //    $rpp_chart = $past_rpp;
    //    $rppc_chart = abs($rppc);
    //
    //    if($rppc < 0){
    //        $rppc_color = "max";
    //        $rpp_chart = $curr_rpp;
    //        $rppc_arrow = "";
    //    }
    //    else{
    //        $rppc_color = "success";
    //        $rppc_arrow = "arrow-up";
    //    }
    //
    //    if($curr_rpp == $max_profile_reach_per_pin){
    //        $rpp_max_color = "text-glow'>New";
    //    } else {
    //        $rpp_max_color = "muted-more'>";
    //    }
    //
    //    $perc_max_reach_per_pin = ($rpp_chart/$max_reach_per_pin)*100;
    //    $perc_avg_reach_per_pin = ($avg_reach_per_pin/$max_reach_per_pin)*100;
    //    $perc_rppc = ($rppc_chart/$max_reach_per_pin)*100-0.2;


        //daily stats
        $max_daily_pins_avg  = number_format(max($daily_pins_avgs),1,".","");
        $curr_daily_pins_avg = $daily_pins_avgs[0];
        $avg_daily_pins_avg  = number_format((array_sum($daily_pins_avgs) / count($daily_pins_avgs)), 1);
        if ($curr_daily_pins_avg == $max_daily_pins_avg) {
            $daily_pins_max_color = "text-glow'>(New";
        } else {
            $daily_pins_max_color = "muted-more'>(";
        }


        $max_daily_pinners_avg  = number_format(max($daily_pinners_avgs),1,".","");
        $curr_daily_pinners_avg = $daily_pinners_avgs[0];
        $avg_daily_pinners_avg  = number_format((array_sum($daily_pinners_avgs) / count($daily_pinners_avgs)), 1);
        if ($curr_daily_pinners_avg == $max_daily_pinners_avg) {
            $daily_pinners_max_color = "text-glow'>(New";
        } else {
            $daily_pinners_max_color = "muted-more'>(";
        }

        print
            "<div class='pins-gauge active span' style='height: 150px; margin-left:0px'>
                                        <div style='margin:0 auto; text-align:center;'>
                                            <h4>
                                                Average Pins / Day
                                                <a class='gauge-icon' data-toggle='popover' data-container='body' data-original-title='<strong>Average Pins per Day</strong>' data-content=\"<strong>How is it measured?</strong><br>Average <em class='muted'>Pins per day</em> over the previous $day_range days.<br><br><strong>What does it mean?</strong><br>On average, how many items are being pinned from your website on a daily basis. <br><br><small>This gauge displays how high your average pins per day ratio is over the last $day_range days, and compares it to where this ratio has been each of the previous 30 days. A full gauge means you've reached a new 30-day high and the faint '30-Day High' label will light up!</small>\" data-placement='left'><i id='gauge-icon' class='icon-help'></i></a>
                                            </h4>
                                        </div>
                                        <div class='muted' style='margin:-10px auto 15px; text-align:center;'><small>($day_range-day Average)</small></div>
                                        <div class='website-gauge-stats' style='margin-top:40px;'>
                                            <h1 id='pins-value'>$curr_daily_pins_avg</h1>
                                        </div>
                                        <div class='website-gauge-stats' style='margin-top:60px;'>
                                            <!-- <div style='width:70px; margin:0 auto;'>
                                                <h4 style='margin-bottom:0px;border-top:1px solid #777; padding-top:3px;color:#999'>$avg_daily_pins_avg</h4>
                                            </div>
                                            <div style='width:95px; margin:-2px auto;'>
                                                <small class='muted' style='margin-top:-15px;'>30-Day Avg.</small>
                                            </div>	-->
                                        </div>
                                        <div style='position: absolute;margin-top: 11%;margin-left: 17%;'>
                                            <small class='muted-more'>$max_daily_pins_avg</small>
                                            <small class='$daily_pins_max_color 30-Day High)</small>
                                        </div>
                                        <canvas id='pins-gauge' class='profile-gauge-canvas'></canvas>
                                        <script type='text/javascript'>
                                            var opts = {
                                              'lines': 12, // The number of lines to draw
                                              'angle': 0.18, // The length of each line
                                              'lineWidth': 0.12, // The line thickness
                                              'pointer': {
                                                'length': 0.9, // The radius of the inner circle
                                                'strokeWidth': 0.0, // The rotation offset
                                                'color': '#000000' // Fill color
                                              },
                                              'colorStart': '#D77E81',   // Colors
                                              'colorStop': '#E7B2B4',    // just experiment with them
                                              'strokeColor': '#EEEEEE',   // to see which ones work best for you
                                              'generateGradient': true
                                            };
                                            var target = document.getElementById('pins-gauge'); // your canvas element
                                            var gauge = new Donut(target).setOptions(opts); // create sexy gauge!
                                            gauge.maxValue = $max_daily_pins_avg; // set max gauge value
                                            gauge.animationSpeed = 32; // set animation speed (32 is default value)
                                            gauge.set($curr_daily_pins_avg); // set actual value
                                            var textRenderer = new TextRenderer(document.getElementById('pins-value'));
                                            textRenderer.render = function(gauge){
                                                percentage = gauge.displayedValue;
                                                var n= (percentage).toFixed(1).toString().split('.');
                                                //Comma-fies the first part
                                                n[0] = n[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                                                //Combines the two sections
                                                this.el.innerHTML = n.join('.');
                                            };
                                            gauge.setTextField(textRenderer);
                                            //gauge.setTextField(document.getElementById('pins-value'));
                                        </script>
                                    </div>";

        print
            "<div class='pinners-gauge span' style='height: 150px; margin-left:0px'>
                                        <div style='margin:0 auto; text-align:center;'>
                                            <h4>
                                                Average Pinners / Day
                                                <a class='gauge-icon' data-toggle='popover' data-container='body' data-original-title='<strong>Average Pinners per Day</strong>' data-content=\"<strong>How is it measured?</strong><br>Average <em class='muted'>Pinners per day</em> over the previous $day_range days.<br><br><strong>What does it mean?</strong><br>On average, how many people have pinned from your website on a daily basis. <br><br><small>This gauge displays how high your average pinners per day ratio is over the last $day_range days, and compares it to where this ratio has been each of the previous 30 days. A full gauge means you've reached a new 30-day high and the faint '30-Day High' label will light up!</small>\" data-placement='left'><i id='gauge-icon' class='icon-help'></i></a>
                                            </h4>
                                        </div>
                                        <div class='muted' style='margin:-10px auto 15px; text-align:center;'><small>($day_range-day Average)</small></div>
                                        <div class='website-gauge-stats' style='margin-top:40px;'>
                                            <h1 id='pinners-value'>$curr_daily_pinners_avg</h1>
                                        </div>
                                        <div class='website-gauge-stats' style='margin-top:60px;'>
                                            <!-- <div style='width:70px; margin:0 auto;'>
                                                <h4 style='margin-bottom:0px;border-top:1px solid #777; padding-top:3px;color:#999'>$avg_daily_pinners_avg</h4>
                                            </div>
                                            <div style='width:70px; margin:-5px auto;'>
                                                <small class='muted' style='margin-top:-15px;'>30-Day Avg.</small>
                                            </div>	-->
                                        </div>
                                        <div style='position: absolute;margin-top: 11%;margin-left: 17%;'>
                                            <small class='muted-more'>$max_daily_pinners_avg</small>
                                            <small class='$daily_pinners_max_color 30-Day High)</small>
                                        </div>
                                        <canvas id='pinners-gauge' class='profile-gauge-canvas'></canvas>
                                        <script type='text/javascript'>
                                            var opts = {
                                              'lines': 12, // The number of lines to draw
                                              'angle': 0.18, // The length of each line
                                              'lineWidth': 0.12, // The line thickness
                                              'pointer': {
                                                'length': 0.9, // The radius of the inner circle
                                                'strokeWidth': 0.0, // The rotation offset
                                                'color': '#000000' // Fill color
                                              },
                                              'colorStart': '#4E7E98',   // Colors
                                              'colorStop': '#79ABC6',    // just experiment with them
                                              'strokeColor': '#EEEEEE',   // to see which ones work best for you
                                              'generateGradient': true
                                            };
                                            var target2 = document.getElementById('pinners-gauge'); // your canvas element
                                            var gauge2 = new Donut(target2).setOptions(opts); // create sexy gauge!
                                            gauge2.maxValue = $max_daily_pinners_avg; // set max gauge value
                                            gauge2.animationSpeed = 32; // set animation speed (32 is default value)
                                            gauge2.set($curr_daily_pinners_avg); // set actual value
                                            var textRenderer = new TextRenderer(document.getElementById('pinners-value'));
                                            textRenderer.render = function(gauge2){
                                                percentage = gauge2.displayedValue;
                                                var n= (percentage).toFixed(1).toString().split('.');
                                                //Comma-fies the first part
                                                n[0] = n[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                                                //Combines the two sections
                                                this.el.innerHTML = n.join('.');
                                            };
                                            gauge2.setTextField(textRenderer);
                                            //gauge2.setTextField(document.getElementById('pinners-value'));
                                        </script>
                                    </div>";


    //    print
    //        "<div class='reach-gauge span' style='height: 150px; margin-left:0px'>
    //                                    <div style='margin:0 auto; text-align:center;'><h4>Reach / Pin</h4></div>
    //                                    <div style='position:absolute;margin-top:30px; text-align:center;width:300px'>
    //                                        <h2>$curr_rpp</h2>
    //                                    </div>
    //                                    <div style='position:absolute;margin-top:70px; text-align:center;width:300px'>
    //                                        <div style='width:70px; margin:0 auto;'>
    //                                            <h4 style='margin-bottom:0px;border-top:1px solid #777; padding-top:3px;color:#999'>$avg_reach_per_pin</h4>
    //                                        </div>
    //                                        <div style='width:70px; margin:-5px auto;'>
    //                                            <small class='muted' style='margin-top:-15px;'>Average</small>
    //                                        </div>
    //                                    </div>
    //                                    <div style='position: absolute;margin-top: 130px;margin-left: 190px;'>
    //                                        <small class='$rpp_max_color 30-Day High</small>
    //                                    </div>
    //                                    <canvas id='reach-gauge'></canvas>
    //                                    <script type='text/javascript'>
    //                                        var opts = {
    //                                          'lines': 12, // The number of lines to draw
    //                                          'angle': 0.18, // The length of each line
    //                                          'lineWidth': 0.12, // The line thickness
    //                                          'pointer': {
    //                                            'length': 0.9, // The radius of the inner circle
    //                                            'strokeWidth': 0.0, // The rotation offset
    //                                            'color': '#000000' // Fill color
    //                                          },
    //                                          'colorStart': '#6F6EA0',   // Colors
    //                                          'colorStop': '#C0C0DB',    // just experiment with them
    //                                          'strokeColor': '#EEEEEE',   // to see which ones work best for you
    //                                          'generateGradient': true
    //                                        };
    //                                        var target3 = document.getElementById('reach-gauge'); // your canvas element
    //                                        var gauge3 = new Donut(target3).setOptions(opts); // create sexy gauge!
    //                                        gauge3.maxValue = $max_rpp; // set max gauge value
    //                                        gauge3.animationSpeed = 32; // set animation speed (32 is default value)
    //                                        gauge3.set($curr_rpp); // set actual value
    //                                    </script>
    //                                </div>";


        print "</div>";

        print "<hr>";

        print "
                                <div class=\"feature-wrap-chart-controls\" style='text-align:left;position: absolute; left:-99999px;'>";

        print "
                                    <form>
                                        <input id='site-pins-toggle' type='radio' value='site-pins-toggle'> Pins </input>
                                        <input id='pinners-toggle' type='radio' value='pinners-toggle'> Pinners </input>
                                        <!-- <input id='reach-toggle' type='radio' value='reach-toggle'> Reach </input> -->
                                    </form>
                                </div>

                                <div class=\"\" style='text-align:left;position: absolute; left:-99999px;'>";

        print "
                                    <form>
                                        <input id='site-pins-toggle2' type='radio' value='site-pins-toggle'> Pins </input>
                                        <input id='pinners-toggle2' type='radio' value='pinners-toggle'> Pinners </input>
                                        <!-- <input id='reach-toggle2' type='radio' value='reach-toggle'> Reach </input> -->
                                    </form>
                                </div>";

        print "
                            </div>";


        print "
                            <div class='sample-latest-pins active'>
                                <div id='site-pins-toggle-dash' class='sample-pins-title active'>
                                    <h4>Trending pins from $cust_domain...
                                        <div class='pull-right'>
                                            <a href='/pins/domain/trending'>
                                                <button class='btn' style='margin-top: -5px;'>See All Trending Pins →</button></a>
                                        </div>
                                    </h4>
                                </div>

                                <div class='sample-pins-wrapper'>

                                    <div id='pinsCarousel' class='carousel slide' data-interval='false'>
                                      <ol class='carousel-indicators'>
                                        <li data-target='#pinsCarousel' data-slide-to='0' class='active'></li>
                                        <li data-target='#pinsCarousel' data-slide-to='1'></li>
                                        <li data-target='#pinsCarousel' data-slide-to='2'></li>
                                      </ol>
                                      <!-- Carousel items -->
                                      <div class='carousel-inner'>
                                        <div class='active item row-fluid'>
                                            <div class='feature-wrap-samples'>";

        $sample_pin_count = 0;
        foreach ($pins as $p) {

            if ($sample_pin_count == 3 || $sample_pin_count == 7 || $sample_pin_count == 11) {
                $right = "right";
            } else {
                $right = "";
            }

            print "
                                                <div class='sample-pin $right'>
                                                    <div class='sample-pin-image'>
                                                        <a target='_blank' href='http://pinterest.com/pin/" . $p['pin_id'] . "/'>
                                                            <img src='" . $p['image_url'] . "'>
                                                        </a>
                                                    </div>
                                                    <div class='sample-pin-desc'>
                                                        <small class='muted'>" . $p['description'] . "</small>
                                                    </div>
                                                    <a target='_blank' href='" . $p['link'] . "'>
                                                        <div class='sample-pin-link'>$cust_domain...
                                                        </div>
                                                    </a>
                                                </div>";


            $sample_pin_count++;
            if ($sample_pin_count == 4 || $sample_pin_count == 8) {
                print "
                                                        </div>
                                                    </div>
                                                    <div class='item row-fluid'>
                                                        <div class='feature-wrap-samples'>";
            }
        }

        print "
                                            </div>
                                        </div>
                                      </div>
                                      <!-- Carousel nav -->
                                      <a class='carousel-control left' href='#pinsCarousel' data-slide='prev'>&lsaquo;</a>
                                      <a class='carousel-control right' href='#pinsCarousel' data-slide='next'>&rsaquo;</a>
                                    </div>

                                </div>
                                <div class='sample-pins-more'>
                                </div>

                            </div>";


        print "
                            <div class='sample-latest-pinners'>
                                <div id='pinners-toggle-dash' class='sample-pinners-title active'>
                                    <h4>Meet Active Pinners from $cust_domain...
                                        <div class='pull-right'>
                                            <a href='/influencers/domain-pinners'>
                                                <button class='btn' style='margin-top: -5px;'>Meet More Active Pinners →</button></a>
                                        </div>
                                    </h4>
                                </div>

                                <div class='sample-pinners-wrapper'>

                                    <div id='pinnersCarousel' class='carousel slide' data-interval='false'>
                                      <ol class='carousel-indicators'>
                                        <li data-target='#pinnersCarousel' data-slide-to='0' class='active'></li>
                                        <li data-target='#pinnersCarousel' data-slide-to='1'></li>
                                        <li data-target='#pinnersCarousel' data-slide-to='2'></li>
                                      </ol>
                                      <!-- Carousel items -->
                                      <div class='carousel-inner'>
                                        <div class='active item row-fluid'>
                                            <div class='feature-wrap-samples'>";

        $sample_pinner_count = 0;
        foreach ($pinners as $p) {

            if ($sample_pinner_count == 2 || $sample_pinner_count == 5 || $sample_pinner_count == 8) {
                $right = "right";
            } else {
                $right = "";
            }

            print "
                                                <div class='sample-pinner $right'>
                                                    <a target='_blank' href='http://pinterest.com/" . $p['username'] . "/'>
                                                        <div class='sample-pinner-meta pull-left'>
                                                            <div class='sample-pinner-image'>
                                                                <img src='" . $p['image'] . "'>
                                                            </div>
                                                            <div class='sample-pinner-meta-wrapper'>
                                                                <div class='sample-pinner-name'>" . $p['first_name'] . " " . $p['last_name'] . "
                                                                </div>";

            if ($p['location'] != "") {
                print "
                                                                    <div class='sample-pinner-location'>
                                                                        <span style='font-size:16px; color:#555'>
                                                                            <i class='icon-location'></i>
                                                                        </span>
                                                                        " . $p['location'] . "
                                                                    </div>";
            }

            print "
                                                                </div>
                                                            </div>
                                                        </a>
                                                    <div class='clear-fix'></div>";

            if ($p['facebook'] != "" || $p['twitter'] != "" || $p['website'] != "") {
                print "
                                                        <div class='sample-pinner-social influencers'>
                                                            " . (($p["facebook"] != "") ? "<a target=_blank href='http://facebook.com/" . $p['facebook'] . "' class='social-icons facebook'> <i class='icon-facebook-2'></i> </a>" : " ") . "
                                                            " . (($p["twitter"] != "") ? "<a target=_blank href='http://twitter.com/" . $p['twitter'] . "' class='social-icons twitter'> <i class='icon-twitter-2'></i> </a> " : " ") . "
                                                            " . (($p["website"] != "") ? "<a target=_blank href='" . $p['website'] . "' class='social-icons website'> <i class='icon-earth'></i> &nbsp; " . $p['website_label'] . "</a>" : " ") . "
                                                        </div>";
            }

            print "
                                                    <div class='sample-pinner-stats'>
                                                        <div>
                                                            <span class='pull-left' style='width:50%'>
                                                                <h2>" . $p['domain_mentions'] . "</h2>
                                                            </span>
                                                            <span class='pull-right' style='width:50%'>
                                                                <h2>" . $p['follower_count'] . "</h2>
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <span class='pull-left' style='width:50%'>
                                                                <small class='muted'>Pins from $cust_domain</small>
                                                            </span>
                                                            <span class='pull-right' style='width:50%'>
                                                                <small class='muted'>Followers</small>
                                                            </span>
                                                        </div>
                                                    </div>";

            print "
                                                </div>";


            $sample_pinner_count++;
            if ($sample_pinner_count == 3 || $sample_pinner_count == 6) {
                print "
                                                        </div>
                                                    </div>
                                                    <div class='item row-fluid'>
                                                        <div class='feature-wrap-samples'>";
            }
        }

        print "
                                            </div>
                                        </div>
                                      </div>
                                      <!-- Carousel nav -->
                                      <a class='carousel-control left' href='#pinnersCarousel' data-slide='prev'>&lsaquo;</a>
                                      <a class='carousel-control right' href='#pinnersCarousel' data-slide='next'>&rsaquo;</a>
                                    </div>

                                </div>
                                <div class='sample-pinners-more'>
                                </div>

                            </div>";

        print "
                    </div>";

        print "
                </div>";

        print "
            </div>";


        //					print "<div class=\"span\" style='margin-bottom:50px;'>";
        //
        //						print "<div class=\"row\" style='margin-bottom:10px; margin-top:20px;'>";
        //
        //							print "<div class=\"span\">";
        //
        //
        //
        //							print"	</div>
        //								</div>";
        //
        //						print "<div class=\"row\" style='margin-bottom:10px;'>";
        //
        //							print "<div class=\"span5\">";

        //								print "<div class=\"\" style='text-align:center;'>
        //											<h4 style='color:#08c'>Repins/Follower";
        //
        //												if($curr_rpf == $max_impressions_per_pin){
        //													print "<span class='label label-important' style='position:absolute; margin-left: 47px;'>New All-time High!</span></h4>";
        //												} else { print "</h4>"; }
        //
        //									print "<div class='progress' style='height:50px;'>
        //											  	<div class='bar' style='width:".$perc_max_repin_follower."%;'></div>
        //											  	<div class='bar bar-".$rpfc_color."' style='width:".$perc_rpfc."%;'  rel='tooltip' data-placement='top' data-original-title='<i class=\"icon-$rpfc_arrow\"></i> ". number_format($rpfc,2)." $current_name'>
        //											  		<span class='pull-right curr-label'>" . $curr_rpf ." &nbsp;&nbsp; </span>
        //											  	</div>
        //											</div>
        //
        //											<h4>
        //												<span class='pull-right'>
        //													<span class='muted at-high-label'>All-time High:</span> $max_impressions_per_pin
        //												</span>
        //											</h4>
        //										</div>";

        //							print "</div>";
        //
        //
        //
        //
        //
        //						print "</div>";
        //
        //					print "</div>";

        print "</div>";

        print "</div>";

        print "</div>";

        print "</div>";

    }
} else {


    print "
			<div class=\"\" style='margin-bottom:10px;'>";
    print "<div class='clearfix'></div>";

    if (isset($_GET['e'])) {

        if ($_GET['e'] == 2) {
            print "<div class='alert alert-error'><strong>Whoops!</strong> Something went wrong and we were not able to add your domain.  Please make sure it was typed in correctly and try again.  If this problem persists, please contact us by clicking the <i class='icon-help'></i> icon in the upper-right corner and we'll straighten it out for you right away!</div>";
        } elseif ($_GET['e'] == 3) {
            print "<div class='alert alert-error'><strong>Whoops!</strong> Please enter a domain first!</div>";
        }

    }

    print "<h3 style='font-weight:normal; text-align:center'>This report is based on pinning activity coming from your domain.</h3>
            <h4 style='text-align:center'>Please enter the domain you'd like to track in order to enable this report.</h4>";
    print "</div>";

    print "<div class='clearfix'></div>";

    print "<div class='clearfix'></div>";

    print "<div class=\"brand-mentions\">";
    print "<div class=\"\">";

    print "<div class='row no-site' style='margin-top:50px'>
							<center>
								<form action='/website/add' method='POST' class=\"\">
									<fieldset>

										<div class=\"control-group\">
											<div class=\"controls\">
												<div class=\"input-prepend input-append\">
												    <span class=\"add-on\"><i class=\"icon-earth\"></i> http:// </span>
													<input class=\"input-xlarge\" style='margin-left: -4px;' data-minlength='0' value=\"$cust_domain\" id=\"appendedInputButton\" type=\"text\" name='domain' placeholder='e.g. \"mysite.com\"' pattern='^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}$'>
                                                    <input type='hidden' name='page' value='website'>
													<button type=\"submit\" class=\"btn btn-success\"'>
														Add Your Domain
													</button>

												</div>
											</div>
											<div class=\"form-actions\">
												<center>
													<span class='muted' style='width:50%'>
														<small class='muted'>\"http://\" and \"www\" not required. Only domains and subdomains can be tracked.
														<br>Sub-directories cannot currently be tracked on Pinterest.
														<br><span class='text-success'><strong>Trackable:</strong></span> etsy.com, macys.com, yoursite.tumblr.com
														<br><span class='text-error'><strong>Not Trackable:</strong></span> etsy.com/shop/mystore, macys.com/mens-clothing</small>
													</span>
												</center>
											</div>

									</fieldset>
								</form>
							</center>
							</div>";


    print "</div>";
    print "</div>";
}

?>

<script type="text/javascript">

    jQuery(document).ready(function($){

        $('#site-pins-toggle').click(function () {
            $('.pins-gauge').addClass('active');
            $('.pinners-gauge').removeClass('active');
            $('.reach-gauge').removeClass('active');
            $('.sample-latest-pins').addClass('active');
            $('.sample-latest-pinners').removeClass('active');
        });
        $('#pinners-toggle').click(function () {
            $('.pins-gauge').removeClass('active');
            $('.pinners-gauge').addClass('active');
            $('.reach-gauge').removeClass('active');
            $('.sample-latest-pins').removeClass('active');
            $('.sample-latest-pinners').addClass('active');
        });
        $('#reach-toggle').click(function () {
            $('.pins-gauge').removeClass('active');
            $('.pinners-gauge').removeClass('active');
            $('.reach-gauge').addClass('active');
        });

        $("#site-pins-toggle-dash").on('click', function () {
            $("#site-pins-toggle").trigger('click');
            $("#site-pins-toggle").trigger('click');
            $("#site-pins-toggle-dash").addClass('active');
            $("#pinners-toggle-dash").removeClass('active');
            gauge.set(0);
            gauge.set(<?php echo $curr_daily_pins_avg ?>);
        });
        $("#pinners-toggle-dash").on('click', function () {
            $("#pinners-toggle").trigger('click');
            $("#pinners-toggle").trigger('click');
            $("#site-pins-toggle-dash").removeClass('active');
            $("#pinners-toggle-dash").addClass('active');
            gauge2.set(0);
            gauge2.set(<?php echo $curr_daily_pinners_avg ?>);
        });
    });

</script>

<?php




function regularAverage($counts, $day_range){

    $averages = array();
    //get 7 day moving average of daily_pin_count for each of the last 30 days
    for ($i = 0; $i < 30; $i++) {


        if (array_key_exists((int)$day_range, $counts)) {

            $total = 0;
            //add up pin_counts from last X days
            for ($j = 0; $j < $day_range+1; $j++) {
                $total += $counts[$j];
            }

            //get average if all X days of data present, otherwise break the loop
            array_push($averages, ($total / $day_range));

            //remove first entry
            array_shift($counts);
        } else {
            break;
        }
    }

    return $averages;
}



function movingAverage($counts, $day_range)
{

    $averages = array();
    //get 7 day moving average of daily_pin_count for each of the last 30 days
    for ($i = 0; $i < 30; $i++) {
        if (array_key_exists(6, $counts)) {

            $total = 0;
            //add up pin_counts from last 7 days
            for ($j = 0; $j < $day_range; $j++) {
                $total += $counts[$j];
            }

            //get average if all 7 days of data present, otherwise break the loop
            array_push($averages, number_format(($total / 7), 1));

            //remove first entry
            array_shift($counts);
        } else {
            break;
        }
    }

    return $averages;
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

function timesort($a, $b)
{
    $t = "chart_date";

    if ($a["$t"] > $b["$t"]) {
        return 1;
    } else if ($a["$t"] == $b["$t"]) {
        return 0;
    } else {
        return -1;
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

function formatRatio($x)
{
    if (!$x) {
        return "-";
    } else {
        return number_format($x, 1) . "<span style='color:#777'>:1</span>";
    }
}

function formatPercentage($x)
{
    $x = $x * 100;
    if ($x >= 0) {
        return "<span style='color: green;font-weight:normal;font-size:12px'>(" . number_format($x, 0) . "%)</span>";
    } else if ($x < 0) {
        return "<span style='color: #aaa;font-weight:normal;font-size:12px'>(" . number_format($x, 0) . "%)</span>";
    } else if ($x == "na") {
        return "<span style='color: #aaa;font-weight:normal;font-size:12px'>(--%)</span>";
    }
}

function formatAbsoluteRatio($x)
{
    if ($x > 0) {
        return "<span class='pos'><i class='icon-arrow-up'></i>" . number_format($x, 0) . "</span><span style='color:#777'>:1</span>";
    } elseif ($x == 0) {
        return "<span class='neg'> &nbsp;--</span><span class='muted'>:1</span>";
    } else {
        return "<span class='neg'><i class='icon-arrow-down'></i>" . number_format($x, 0) . "</span><span style='color:#777'>:1</span>";
    }
}

function formatAbsoluteKPI($x)
{
    if ($x > 0) {
        return "<span style='color: green;'><i class='icon-arrow-up'></i>" . number_format($x, 0) . "</span>";
    } elseif ($x == 0) {
        return "<span style='color: #aaa;'> &nbsp;--</span>";
    } else {
        return "<span style='color: #aaa;'><i class='icon-arrow-down'></i>" . number_format($x, 0) . "</span>";
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


?>