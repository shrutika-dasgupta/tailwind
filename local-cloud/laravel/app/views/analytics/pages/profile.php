<?php

ini_set('display_errors', 'off');
error_reporting(0);

$page = "Profile";






//************************************************************************************//
//************************************************************************************//
//************************************************************************************//
//------------------------------- PROFILE COMPLETENESS -------------------------------//
//************************************************************************************//
//************************************************************************************//
//************************************************************************************//


$profile_percentage = 0;

$profile_completeness = array();
$profile_completeness['boards'] = array();
$profile_completeness['num_boards'] = 0;
$profile_completeness['boards_less_than_ten_pins'] = 0;
$profile_completeness['boards_no_categories'] = 0;
$profile_completeness['boards_no_descriptions'] = 0;

$acc = "select * from data_boards where user_id=$cust_user_id";
$acc_res = mysql_query($acc, $conn) or die(mysql_error());
while ($a = mysql_fetch_array($acc_res)) {

    $board_id                                    = $a['board_id'];
    $profile_completeness['boards']["$board_id"] = array();

    //check whether each of their boards has a category
    if ($a['category'] == "") {
        $profile_completeness['boards_no_categories'] -= 1;
        $profile_completeness['boards']["$board_id"]['no_category']     = true;
        $profile_completeness['boards']["$board_id"]['url']             = $a['url'];
        $profile_completeness['boards']["$board_id"]['name']            = $a['name'];
        $profile_completeness['boards']["$board_id"]['url']             = $a['url'];
        $profile_completeness['boards']["$board_id"]['description']     = $a['description'];
        $profile_completeness['boards']["$board_id"]['image_cover_url'] = $a['image_cover_url'];
        $profile_completeness['boards']["$board_id"]['pin_count']       = $a['pin_count'];
        $profile_completeness['boards']["$board_id"]['follower_count']  = $a['follower_count'];
        $profile_completeness['boards']["$board_id"]['created_at']      = $a['created_at'];
    }

    //check whether each of their boards has at least 10 pins
    if ($a['pin_count'] < 10) {
        $profile_completeness['boards_less_than_ten_pins'] -= 1;
        $profile_completeness['boards']["$board_id"]['ten_pins']        = true;
        $profile_completeness['boards']["$board_id"]['url']             = $a['url'];
        $profile_completeness['boards']["$board_id"]['name']            = $a['name'];
        $profile_completeness['boards']["$board_id"]['url']             = $a['url'];
        $profile_completeness['boards']["$board_id"]['description']     = $a['description'];
        $profile_completeness['boards']["$board_id"]['image_cover_url'] = $a['image_cover_url'];
        $profile_completeness['boards']["$board_id"]['pin_count']       = $a['pin_count'];
        $profile_completeness['boards']["$board_id"]['follower_count']  = $a['follower_count'];
        $profile_completeness['boards']["$board_id"]['created_at']      = $a['created_at'];
    }

    //check whether each of their boards has a description
    if ($a['description'] == "") {
        $profile_completeness['boards_no_descriptions'] -= 1;
        $profile_completeness['boards']["$board_id"]['no_description']  = true;
        $profile_completeness['boards']["$board_id"]['url']             = $a['url'];
        $profile_completeness['boards']["$board_id"]['name']            = $a['name'];
        $profile_completeness['boards']["$board_id"]['url']             = $a['url'];
        $profile_completeness['boards']["$board_id"]['description']     = $a['description'];
        $profile_completeness['boards']["$board_id"]['image_cover_url'] = $a['image_cover_url'];
        $profile_completeness['boards']["$board_id"]['pin_count']       = $a['pin_count'];
        $profile_completeness['boards']["$board_id"]['follower_count']  = $a['follower_count'];
        $profile_completeness['boards']["$board_id"]['created_at']      = $a['created_at'];
    }
}


$profile_completeness['no_description'] = false;
$profile_completeness['no_facebook'] = false;
$profile_completeness['no_twitter'] = false;
$profile_completeness['no_website'] = false;
$profile_completeness['no_domain_verified'] = false;
$profile_completeness['no_location'] = false;
$profile_completeness['no_image'] = false;


$acc = "select * from data_profiles_new where user_id=$cust_user_id";
$acc_res = mysql_query($acc, $conn) or die(mysql_error());
while ($a = mysql_fetch_array($acc_res)) {

    if ($a['about'] == "") {
        $profile_completeness['no_description'] = true;
    } else {
        $profile_percentage += 10;
    }

    if ($a['facebook_url'] == "") {
        $profile_completeness['no_facebook'] = true;
    } else {
        $profile_percentage += 3;
    }

    if ($a['twitter_url'] == "") {
        $profile_completeness['no_twitter'] = true;
    } else {
        $profile_percentage += 3;
    }

    if ($a['domain_url'] == "" && $a['website_url'] == "") {
        $profile_completeness['no_website'] = true;
    } else {
        $profile_percentage += 8;
    }

    if ($a['domain_verified'] == 0) {
        $profile_completeness['no_domain_verified'] = true;
    } else {
        $profile_percentage += 8;
    }

    if ($a['location'] == "") {
        $profile_completeness['location'] = true;
    } else {
        $profile_percentage += 3;
    }

    if (strpos($a['image'], "user/default_75.png") !== false) {
        $profile_completeness['no_image'] = true;
    } else {
        $profile_percentage += 15;
    }

    if ($a['board_count'] < 10) {
        @$profile_percentage += $a['num_boards'] * 5;
    } else {
        $profile_percentage += 50;
    }
}

$profile_percentage += min(-10, $profile_completeness['boards_less_than_ten_pins']);
$profile_percentage += min(-10, $profile_completeness['boards_no_categories']);
$profile_percentage += min(-10, $profile_completeness['boards_no_descriptions']);


foreach ($profile_completeness['boards'] as $k => $board) {
    //TODO: create feed of tasks to do to complete profile
}


//************************************************************************************//
//************************************************************************************//
//************************************************************************************//
//------------------------------- SET ALL DATE & TIMES -------------------------------//
//************************************************************************************//
//************************************************************************************//
//************************************************************************************//


$profile_calcs = array();


//************************************************************************************//
//************************************************************************************//
//************************************************************************************//
//-------------------------------- ALL  ACCOUNT STATS --------------------------------//
//************************************************************************************//
//************************************************************************************//
//************************************************************************************//


//get all calcs	by date
$acc = "select *, DATE(FROM_UNIXTIME(`date`)) AS pDate from calcs_profile_history where user_id = $cust_user_id $date_limit_clause group by pDate order by `date` desc";
$acc_res = mysql_query($acc, $conn) or die(mysql_error());
while ($a = mysql_fetch_array($acc_res)) {
    $query_date          = $a['date'];
    $query_date_tomorrow = strtotime("+1 day", $query_date);


    if (isset($profile_calcs["$query_date_tomorrow"])) {
        $profile_calcs["$query_date_tomorrow"]['daily_followers'] = $profile_calcs["$query_date_tomorrow"]['follower_count'] - $a['follower_count'];
        $profile_calcs["$query_date_tomorrow"]['daily_pins']      = $profile_calcs["$query_date_tomorrow"]['pin_count'] - $a['pin_count'];
        $profile_calcs["$query_date_tomorrow"]['daily_repins']    = $profile_calcs["$query_date_tomorrow"]['repin_count'] - $a['repin_count'];
        $profile_calcs["$query_date_tomorrow"]['daily_likes']     = $profile_calcs["$query_date_tomorrow"]['like_count'] - $a['like_count'];
        $profile_calcs["$query_date_tomorrow"]['daily_comments']  = $profile_calcs["$query_date_tomorrow"]['comment_count'] - $a['comment_count'];
    }


    $profile_calcs["$query_date"]                             = array();
    $profile_calcs["$query_date"]['estimate']                 = $a['estimate'];
    $profile_calcs["$query_date"]['timestamp']                = $a['date'];
    $profile_calcs["$query_date"]['chart_date']               = $a['pDate'];
    $profile_calcs["$query_date"]['follower_count']           = $a['follower_count'];
    $profile_calcs["$query_date"]['daily_followers']          = 0;
    $profile_calcs["$query_date"]['following_count']          = $a['following_count'];
    $profile_calcs["$query_date"]['follow_ratio']             = ($a['follower_count'] / $a['following_count']);
    $profile_calcs["$query_date"]['board_count']              = $a['board_count'];
    $profile_calcs["$query_date"]['pin_count']                = $a['pin_count'];
    $profile_calcs["$query_date"]['repin_count']              = $a['repin_count'];
    $profile_calcs["$query_date"]['like_count']               = $a['like_count'];
    $profile_calcs["$query_date"]['comment_count']            = $a['comment_count'];
    $profile_calcs["$query_date"]['pins_atleast_one_repin']   = $a['pins_atleast_one_repin'];
    $profile_calcs["$query_date"]['pins_atleast_one_like']    = $a['pins_atleast_one_like'];
    $profile_calcs["$query_date"]['pins_atleast_one_comment'] = $a['pins_atleast_one_comment'];
    $profile_calcs["$query_date"]['pins_atleast_one_engage']  = $a['pins_atleast_one_engage'];
    @$profile_calcs["$query_date"]['repins_per_pin'] = ($a['repin_count'] / $a['pin_count']);
    @$profile_calcs["$query_date"]['repins_per_follower'] = ($a['repin_count'] / $a['follower_count']);
    @$profile_calcs["$query_date"]['repins_per_pin_per_follower'] = ($a['repin_count'] / $a['pin_count'] / $a['follower_count'] * 1000);
    @$profile_calcs["$query_date"]['influence_score'] = ((0.08 * LOG10($a['follower_count'])) + (0.15 * LOG10(($a['repin_count'] / $a['pin_count']))) + (0.005 * LOG10($a['reach']))) * 100;
    @$profile_calcs["$query_date"]['engagement_rate'] = ($a['pins_atleast_one_repin'] / $a['pin_count']) * 100;

}



end($profile_calcs);
$last_day = key($profile_calcs);
//$last_day = strtotime($last_day);
reset($profile_calcs);


$day1 = $current_date;
$day2 = $last_date;
//add growth values to array
if ($day2 < $last_day) {
    $day2            = $last_day;
    $last_date       = $last_day;
    $last_date_print = date("m/d/Y", $last_date);
}


@$profile_calcs[$day1]['follower_count_growth'] = $profile_calcs[$day1]['follower_count'] - $profile_calcs[$day2]['follower_count'];

@$profile_calcs[$day1]['following_count_growth'] = $profile_calcs[$day1]['following_count'] - $profile_calcs[$day2]['following_count'];

@$profile_calcs[$day1]['follow_ratio_growth'] = $profile_calcs[$day1]['follow_ratio'] - $profile_calcs[$day2]['follow_ratio'];

@$profile_calcs[$day1]['board_count_growth'] = $profile_calcs[$day1]['board_count'] - $profile_calcs[$day2]['board_count'];

@$profile_calcs[$day1]['pin_count_growth'] = $profile_calcs[$day1]['pin_count'] - $profile_calcs[$day2]['pin_count'];

@$profile_calcs[$day1]['repin_count_growth'] = $profile_calcs[$day1]['repin_count'] - $profile_calcs[$day2]['repin_count'];

@$profile_calcs[$day1]['like_count_growth'] = $profile_calcs[$day1]['like_count'] - $profile_calcs[$day2]['like_count'];

@$profile_calcs[$day1]['comment_count_growth'] = $profile_calcs[$day1]['comment_count'] - $profile_calcs[$day2]['comment_count'];

@$profile_calcs[$day1]['pins_atleast_one_repin_growth'] = $profile_calcs[$day1]['pins_atleast_one_repin'] - $profile_calcs[$day2]['pins_atleast_one_repin'];

@$profile_calcs[$day1]['pins_atleast_one_like_growth'] = $profile_calcs[$day1]['pins_atleast_one_like'] - $profile_calcs[$day2]['pins_atleast_one_like'];

@$profile_calcs[$day1]['pins_atleast_one_comment_growth'] = $profile_calcs[$day1]['pins_atleast_one_comment'] - $profile_calcs[$day2]['pins_atleast_one_comment'];

@$profile_calcs[$day1]['pins_atleast_one_engage_growth'] = $profile_calcs[$day1]['pins_atleast_one_engage'] - $profile_calcs[$day2]['pins_atleast_one_engage'];

@$profile_calcs[$day1]['repins_per_pin_growth'] = $profile_calcs[$day1]['repins_per_pin'] - $profile_calcs[$day2]['repins_per_pin'];

@$profile_calcs[$day1]['repins_per_follower_growth'] = $profile_calcs[$day1]['repins_per_follower'] - $profile_calcs[$day2]['repins_per_follower'];

@$profile_calcs[$day1]['repins_per_pin_per_follower_growth'] = $profile_calcs[$day1]['repins_per_pin_per_follower'] - $profile_calcs[$day2]['repins_per_pin_per_follower'];


//period 1 growth percentage
if (isset($profile_calcs[$last_date]['follower_count'])) {
    @$profile_calcs[$day1]['follower_count_growth_perc'] = ($profile_calcs[$day1]['follower_count'] - $profile_calcs[$day2]['follower_count']) / $profile_calcs[$day2]['follower_count'];

    @$profile_calcs[$day1]['following_count_growth_perc'] = ($profile_calcs[$day1]['following_count'] - $profile_calcs[$day2]['following_count']) / $profile_calcs[$day2]['following_count'];

    @$profile_calcs[$day1]['follow_ratio_growth_perc'] = ($profile_calcs[$day1]['follow_ratio'] - $profile_calcs[$day2]['follow_ratio']) / $profile_calcs[$day2]['follow_ratio'];

    @$profile_calcs[$day1]['board_count_growth_perc'] = ($profile_calcs[$day1]['board_count'] - $profile_calcs[$day2]['board_count']) / $profile_calcs[$day2]['board_count'];

    @$profile_calcs[$day1]['pin_count_growth_perc'] = ($profile_calcs[$day1]['pin_count'] - $profile_calcs[$day2]['pin_count']) / $profile_calcs[$day2]['pin_count'];

    @$profile_calcs[$day1]['repin_count_growth_perc'] = ($profile_calcs[$day1]['repin_count'] - $profile_calcs[$day2]['repin_count']) / $profile_calcs[$day2]['repin_count'];

    @$profile_calcs[$day1]['like_count_growth_perc'] = ($profile_calcs[$day1]['like_count'] - $profile_calcs[$day2]['like_count']) / $profile_calcs[$day2]['like_count'];

    @$profile_calcs[$day1]['comment_count_growth_perc'] = ($profile_calcs[$day1]['comment_count'] - $profile_calcs[$day2]['comment_count']) / $profile_calcs[$day2]['comment_count'];

    @$profile_calcs[$day1]['pins_atleast_one_repin_growth_perc'] = ($profile_calcs[$day1]['pins_atleast_one_repin'] - $profile_calcs[$day2]['pins_atleast_one_repin']) / $profile_calcs[$day2]['pins_atleast_one_repin'];

    @$profile_calcs[$day1]['pins_atleast_one_like_growth_perc'] = ($profile_calcs[$day1]['pins_atleast_one_like'] - $profile_calcs[$day2]['pins_atleast_one_like']) / $profile_calcs[$day2]['pins_atleast_one_like'];

    @$profile_calcs[$day1]['pins_atleast_one_comment_growth_perc'] = ($profile_calcs[$day1]['pins_atleast_one_comment'] - $profile_calcs[$day2]['pins_atleast_one_comment']) / $profile_calcs[$day2]['pins_atleast_one_comment'];

    @$profile_calcs[$day1]['pins_atleast_one_engage_growth_perc'] = ($profile_calcs[$day1]['pins_atleast_one_engage'] - $profile_calcs[$day2]['pins_atleast_one_engage']) / $profile_calcs[$day2]['pins_atleast_one_engage'];

    @$profile_calcs[$day1]['repins_per_pin_growth_perc'] = ($profile_calcs[$day1]['repins_per_pin'] - $profile_calcs[$day2]['repins_per_pin']) / $profile_calcs[$day2]['repins_per_pin'];

    @$profile_calcs[$day1]['repins_per_follower_growth_perc'] = ($profile_calcs[$day1]['repins_per_follower'] - $profile_calcs[$day2]['repins_per_follower']) / $profile_calcs[$day2]['repins_per_follower'];

    @$profile_calcs[$day1]['repins_per_pin_per_follower_growth_perc'] = ($profile_calcs[$day1]['repins_per_pin_per_follower'] - $profile_calcs[$day2]['repins_per_pin_per_follower']) / $profile_calcs[$day2]['repins_per_pin_per_follower'];
}


$day1 = $last_date;
$day2 = $compare_date;
//add growth values to array
if ($day2 < $last_day) {
    $day2         = $last_day;
    $compare_date = $last_day;
    $stop_calcs   = true;
}

$profile_calcs[$day1]['follower_count_growth'] = $profile_calcs[$day1]['follower_count'] - $profile_calcs[$day2]['follower_count'];

$profile_calcs[$day1]['following_count_growth'] = $profile_calcs[$day1]['following_count'] - $profile_calcs[$day2]['following_count'];

$profile_calcs[$day1]['follow_ratio_growth'] = $profile_calcs[$day1]['follow_ratio'] - $profile_calcs[$day2]['follow_ratio'];

$profile_calcs[$day1]['board_count_growth'] = $profile_calcs[$day1]['board_count'] - $profile_calcs[$day2]['board_count'];

$profile_calcs[$day1]['pin_count_growth'] = $profile_calcs[$day1]['pin_count'] - $profile_calcs[$day2]['pin_count'];

$profile_calcs[$day1]['repin_count_growth'] = $profile_calcs[$day1]['repin_count'] - $profile_calcs[$day2]['repin_count'];

$profile_calcs[$day1]['like_count_growth'] = $profile_calcs[$day1]['like_count'] - $profile_calcs[$day2]['like_count'];

$profile_calcs[$day1]['comment_count_growth'] = $profile_calcs[$day1]['comment_count'] - $profile_calcs[$day2]['comment_count'];

$profile_calcs[$day1]['pins_atleast_one_repin_growth'] = $profile_calcs[$day1]['pins_atleast_one_repin'] - $profile_calcs[$day2]['pins_atleast_one_repin'];

$profile_calcs[$day1]['pins_atleast_one_like_growth'] = $profile_calcs[$day1]['pins_atleast_one_like'] - $profile_calcs[$day2]['pins_atleast_one_like'];

$profile_calcs[$day1]['pins_atleast_one_comment_growth'] = $profile_calcs[$day1]['pins_atleast_one_comment'] - $profile_calcs[$day2]['pins_atleast_one_comment'];

$profile_calcs[$day1]['pins_atleast_one_engage_growth'] = $profile_calcs[$day1]['pins_atleast_one_engage'] - $profile_calcs[$day2]['pins_atleast_one_engage'];

$profile_calcs[$day1]['repins_per_pin_growth'] = $profile_calcs[$day1]['repins_per_pin'] - $profile_calcs[$day2]['repins_per_pin'];

$profile_calcs[$day1]['repins_per_follower_growth'] = $profile_calcs[$day1]['repins_per_follower'] - $profile_calcs[$day2]['repins_per_follower'];

$profile_calcs[$day1]['repins_per_pin_per_follower_growth'] = $profile_calcs[$day1]['repins_per_pin_per_follower'] - $profile_calcs[$day2]['repins_per_pin_per_follower'];


//period 2 growth percentage
if (isset($profile_calcs[$last_date]['follower_count'])) {
    $profile_calcs[$day1]['follower_count_growth_perc'] = ($profile_calcs[$day1]['follower_count'] - $profile_calcs[$day2]['follower_count']) / $profile_calcs[$day2]['follower_count'];

    $profile_calcs[$day1]['following_count_growth_perc'] = ($profile_calcs[$day1]['following_count'] - $profile_calcs[$day2]['following_count']) / $profile_calcs[$day2]['following_count'];

    $profile_calcs[$day1]['follow_ratio_growth_perc'] = ($profile_calcs[$day1]['follow_ratio'] - $profile_calcs[$day2]['follow_ratio']) / $profile_calcs[$day2]['follow_ratio'];

    $profile_calcs[$day1]['board_count_growth_perc'] = ($profile_calcs[$day1]['board_count'] - $profile_calcs[$day2]['board_count']) / $profile_calcs[$day2]['board_count'];

    $profile_calcs[$day1]['pin_count_growth_perc'] = ($profile_calcs[$day1]['pin_count'] - $profile_calcs[$day2]['pin_count']) / $profile_calcs[$day2]['pin_count'];

    @$profile_calcs[$day1]['repin_count_growth_perc'] = ($profile_calcs[$day1]['repin_count'] - $profile_calcs[$day2]['repin_count']) / $profile_calcs[$day2]['repin_count'];

    @$profile_calcs[$day1]['like_count_growth_perc'] = ($profile_calcs[$day1]['like_count'] - $profile_calcs[$day2]['like_count']) / $profile_calcs[$day2]['like_count'];

    @$profile_calcs[$day1]['comment_count_growth_perc'] = ($profile_calcs[$day1]['comment_count'] - $profile_calcs[$day2]['comment_count']) / $profile_calcs[$day2]['comment_count'];

    @$profile_calcs[$day1]['pins_atleast_one_repin_growth_perc'] = ($profile_calcs[$day1]['pins_atleast_one_repin'] - $profile_calcs[$day2]['pins_atleast_one_repin']) / $profile_calcs[$day2]['pins_atleast_one_repin'];

    @$profile_calcs[$day1]['pins_atleast_one_like_growth_perc'] = ($profile_calcs[$day1]['pins_atleast_one_like'] - $profile_calcs[$day2]['pins_atleast_one_like']) / $profile_calcs[$day2]['pins_atleast_one_like'];

    @$profile_calcs[$day1]['pins_atleast_one_comment_growth_perc'] = ($profile_calcs[$day1]['pins_atleast_one_comment'] - $profile_calcs[$day2]['pins_atleast_one_comment']) / $profile_calcs[$day2]['pins_atleast_one_comment'];

    @$profile_calcs[$day1]['pins_atleast_one_engage_growth_perc'] = ($profile_calcs[$day1]['pins_atleast_one_engage'] - $profile_calcs[$day2]['pins_atleast_one_engage']) / $profile_calcs[$day2]['pins_atleast_one_engage'];

    @$profile_calcs[$day1]['repins_per_pin_growth_perc'] = ($profile_calcs[$day1]['repins_per_pin'] - $profile_calcs[$day2]['repins_per_pin']) / $profile_calcs[$day2]['repins_per_pin'];

    @$profile_calcs[$day1]['repins_per_follower_growth_perc'] = ($profile_calcs[$day1]['repins_per_follower'] - $profile_calcs[$day2]['repins_per_follower']) / $profile_calcs[$day2]['repins_per_follower'];

    @$profile_calcs[$day1]['repins_per_pin_per_follower_growth_perc'] = ($profile_calcs[$day1]['repins_per_pin_per_follower'] - $profile_calcs[$day2]['repins_per_pin_per_follower']) / $profile_calcs[$day2]['repins_per_pin_per_follower'];
}


$day1 = $compare_date;
$day2 = $compare2_date;
//add growth values to array
if ($day2 < $last_day) {
    $day2          = $last_day;
    $compare2_date = $last_day;
    $stop_calcs    = true;
}
$profile_calcs[$day1]['follower_count_growth'] = $profile_calcs[$day1]['follower_count'] - $profile_calcs[$day2]['follower_count'];

$profile_calcs[$day1]['following_count_growth'] = $profile_calcs[$day1]['following_count'] - $profile_calcs[$day2]['following_count'];

$profile_calcs[$day1]['follow_ratio_growth'] = $profile_calcs[$day1]['follow_ratio'] - $profile_calcs[$day2]['follow_ratio'];

$profile_calcs[$day1]['board_count_growth'] = $profile_calcs[$day1]['board_count'] - $profile_calcs[$day2]['board_count'];

$profile_calcs[$day1]['pin_count_growth'] = $profile_calcs[$day1]['pin_count'] - $profile_calcs[$day2]['pin_count'];

$profile_calcs[$day1]['repin_count_growth'] = $profile_calcs[$day1]['repin_count'] - $profile_calcs[$day2]['repin_count'];

$profile_calcs[$day1]['like_count_growth'] = $profile_calcs[$day1]['like_count'] - $profile_calcs[$day2]['like_count'];

$profile_calcs[$day1]['comment_count_growth'] = $profile_calcs[$day1]['comment_count'] - $profile_calcs[$day2]['comment_count'];

$profile_calcs[$day1]['pins_atleast_one_repin_growth'] = $profile_calcs[$day1]['pins_atleast_one_repin'] - $profile_calcs[$day2]['pins_atleast_one_repin'];

$profile_calcs[$day1]['pins_atleast_one_like_growth'] = $profile_calcs[$day1]['pins_atleast_one_like'] - $profile_calcs[$day2]['pins_atleast_one_like'];

$profile_calcs[$day1]['pins_atleast_one_comment_growth'] = $profile_calcs[$day1]['pins_atleast_one_comment'] - $profile_calcs[$day2]['pins_atleast_one_comment'];

$profile_calcs[$day1]['pins_atleast_one_engage_growth'] = $profile_calcs[$day1]['pins_atleast_one_engage'] - $profile_calcs[$day2]['pins_atleast_one_engage'];

$profile_calcs[$day1]['repins_per_pin_growth'] = $profile_calcs[$day1]['repins_per_pin'] - $profile_calcs[$day2]['repins_per_pin'];

$profile_calcs[$day1]['repins_per_follower_growth'] = $profile_calcs[$day1]['repins_per_follower'] - $profile_calcs[$day2]['repins_per_follower'];

$profile_calcs[$day1]['repins_per_pin_per_follower_growth'] = $profile_calcs[$day1]['repins_per_pin_per_follower'] - $profile_calcs[$day2]['repins_per_pin_per_follower'];


//period 3 growth percentage
if (isset($profile_calcs[$last_date]['follower_count'])) {
    $profile_calcs[$day1]['follower_count_growth_perc'] = ($profile_calcs[$day1]['follower_count'] - $profile_calcs[$day2]['follower_count']) / $profile_calcs[$day2]['follower_count'];

    $profile_calcs[$day1]['following_count_growth_perc'] = ($profile_calcs[$day1]['following_count'] - $profile_calcs[$day2]['following_count']) / $profile_calcs[$day2]['following_count'];

    $profile_calcs[$day1]['follow_ratio_growth_perc'] = ($profile_calcs[$day1]['follow_ratio'] - $profile_calcs[$day2]['follow_ratio']) / $profile_calcs[$day2]['follow_ratio'];

    $profile_calcs[$day1]['board_count_growth_perc'] = ($profile_calcs[$day1]['board_count'] - $profile_calcs[$day2]['board_count']) / $profile_calcs[$day2]['board_count'];

    $profile_calcs[$day1]['pin_count_growth_perc'] = ($profile_calcs[$day1]['pin_count'] - $profile_calcs[$day2]['pin_count']) / $profile_calcs[$day2]['pin_count'];

    @$profile_calcs[$day1]['repin_count_growth_perc'] = ($profile_calcs[$day1]['repin_count'] - $profile_calcs[$day2]['repin_count']) / $profile_calcs[$day2]['repin_count'];

    @$profile_calcs[$day1]['like_count_growth_perc'] = ($profile_calcs[$day1]['like_count'] - $profile_calcs[$day2]['like_count']) / $profile_calcs[$day2]['like_count'];

    @$profile_calcs[$day1]['comment_count_growth_perc'] = ($profile_calcs[$day1]['comment_count'] - $profile_calcs[$day2]['comment_count']) / $profile_calcs[$day2]['comment_count'];

    @$profile_calcs[$day1]['pins_atleast_one_repin_growth_perc'] = ($profile_calcs[$day1]['pins_atleast_one_repin'] - $profile_calcs[$day2]['pins_atleast_one_repin']) / $profile_calcs[$day2]['pins_atleast_one_repin'];

    @$profile_calcs[$day1]['pins_atleast_one_like_growth_perc'] = ($profile_calcs[$day1]['pins_atleast_one_like'] - $profile_calcs[$day2]['pins_atleast_one_like']) / $profile_calcs[$day2]['pins_atleast_one_like'];

    @$profile_calcs[$day1]['pins_atleast_one_comment_growth_perc'] = ($profile_calcs[$day1]['pins_atleast_one_comment'] - $profile_calcs[$day2]['pins_atleast_one_comment']) / $profile_calcs[$day2]['pins_atleast_one_comment'];

    @$profile_calcs[$day1]['pins_atleast_one_engage_growth_perc'] = ($profile_calcs[$day1]['pins_atleast_one_engage'] - $profile_calcs[$day2]['pins_atleast_one_engage']) / $profile_calcs[$day2]['pins_atleast_one_engage'];

    @$profile_calcs[$day1]['repins_per_pin_growth_perc'] = ($profile_calcs[$day1]['repins_per_pin'] - $profile_calcs[$day2]['repins_per_pin']) / $profile_calcs[$day2]['repins_per_pin'];

    @$profile_calcs[$day1]['repins_per_follower_growth_perc'] = ($profile_calcs[$day1]['repins_per_follower'] - $profile_calcs[$day2]['repins_per_follower']) / $profile_calcs[$day2]['repins_per_follower'];

    @$profile_calcs[$day1]['repins_per_pin_per_follower_growth_perc'] = ($profile_calcs[$day1]['repins_per_pin_per_follower'] - $profile_calcs[$day2]['repins_per_pin_per_follower']) / $profile_calcs[$day2]['repins_per_pin_per_follower'];
}


//Create formatted values of calcs
foreach ($profile_calcs as $p) {


    $date                             = $p['timestamp'];
    $chart_date                       = $p['chart_date'];
    $profile_calcs_formatted["$date"] = array();

    $profile_calcs_formatted["$date"]['follower_count']              = formatNumber($p['follower_count']);
    $profile_calcs_formatted["$date"]['following_count']             = formatNumber($p['following_count']);
    $profile_calcs_formatted["$date"]['follow_ratio']                = formatRatio($p['follow_ratio']);
    $profile_calcs_formatted["$date"]['board_count']                 = formatNumber($p['board_count']);
    $profile_calcs_formatted["$date"]['pin_count']                   = formatNumber($p['pin_count']);
    $profile_calcs_formatted["$date"]['repin_count']                 = formatNumber($p['repin_count']);
    $profile_calcs_formatted["$date"]['like_count']                  = formatNumber($p['like_count']);
    $profile_calcs_formatted["$date"]['comment_count']               = formatNumber($p['comment_count']);
    $profile_calcs_formatted["$date"]['pins_atleast_one_repin']      = formatNumber($p['pins_atleast_one_repin']);
    $profile_calcs_formatted["$date"]['pins_atleast_one_like']       = formatNumber($p['pins_atleast_one_like']);
    $profile_calcs_formatted["$date"]['pins_atleast_one_comment']    = formatNumber($p['pins_atleast_one_comment']);
    $profile_calcs_formatted["$date"]['pins_atleast_one_engage']     = formatNumber($p['pins_atleast_one_engage']);
    $profile_calcs_formatted["$date"]['repins_per_pin']              = formatAbsoluteKPI($p['repins_per_pin']);
    $profile_calcs_formatted["$date"]['repins_per_follower']         = formatAbsoluteKPI($p['repins_per_follower']);
    $profile_calcs_formatted["$date"]['repins_per_pin_per_follower'] = formatAbsoluteKPI($p['repins_per_pin_per_follower']);


    if ($date == $current_date || $date == $last_date || $date == $compare_date) {
        if ($p['follower_count_growth'] || $p['pin_count_growth'] || $p['repin_count_growth'] || $p['like_count_growth'] || $p['comment_count_growth'] || $p['following_count_growth'] || $p['board_count_growth']) {
            $profile_calcs_formatted["$date"]['follower_count_growth']              = formatAbsolute($p['follower_count_growth']);
            $profile_calcs_formatted["$date"]['following_count_growth']             = formatAbsolute($p['following_count_growth']);
            $profile_calcs_formatted["$date"]['follow_ratio_growth']                = formatAbsoluteRatio($p['follow_ratio_growth']);
            $profile_calcs_formatted["$date"]['board_count_growth']                 = formatAbsolute($p['board_count_growth']);
            $profile_calcs_formatted["$date"]['pin_count_growth']                   = formatAbsolute($p['pin_count_growth']);
            $profile_calcs_formatted["$date"]['repin_count_growth']                 = formatAbsolute($p['repin_count_growth']);
            $profile_calcs_formatted["$date"]['like_count_growth']                  = formatAbsolute($p['like_count_growth']);
            $profile_calcs_formatted["$date"]['comment_count_growth']               = formatAbsolute($p['comment_count_growth']);
            $profile_calcs_formatted["$date"]['pins_atleast_one_repin_growth']      = formatAbsolute($p['pins_atleast_one_repin_growth']);
            $profile_calcs_formatted["$date"]['pins_atleast_one_like_growth']       = formatAbsolute($p['pins_atleast_one_like_growth']);
            $profile_calcs_formatted["$date"]['pins_atleast_one_comment_growth']    = formatAbsolute($p['pins_atleast_one_comment_growth']);
            $profile_calcs_formatted["$date"]['pins_atleast_one_engage_growth']     = formatAbsolute($p['pins_atleast_one_engage_growth']);
            $profile_calcs_formatted["$date"]['repins_per_pin_growth']              = formatAbsoluteKPI($p['repins_per_pin_growth']);
            $profile_calcs_formatted["$date"]['repins_per_follower_growth']         = formatAbsoluteKPI($p['repins_per_follower_growth']);
            $profile_calcs_formatted["$date"]['repins_per_pin_per_follower_growth'] = formatAbsoluteKPI($p['repins_per_pin_per_follower_growth']);
        } else {
            $profile_calcs_formatted["$date"]['follower_count_growth']              = formatAbsolute(0);
            $profile_calcs_formatted["$date"]['following_count_growth']             = formatAbsolute(0);
            $profile_calcs_formatted["$date"]['follow_ratio_growth']                = formatAbsolute(0);
            $profile_calcs_formatted["$date"]['board_count_growth']                 = formatAbsolute(0);
            $profile_calcs_formatted["$date"]['pin_count_growth']                   = formatAbsolute(0);
            $profile_calcs_formatted["$date"]['repin_count_growth']                 = formatAbsolute(0);
            $profile_calcs_formatted["$date"]['like_count_growth']                  = formatAbsolute(0);
            $profile_calcs_formatted["$date"]['comment_count_growth']               = formatAbsolute(0);
            $profile_calcs_formatted["$date"]['pins_atleast_one_repin_growth']      = formatAbsolute(0);
            $profile_calcs_formatted["$date"]['pins_atleast_one_like_growth']       = formatAbsolute(0);
            $profile_calcs_formatted["$date"]['pins_atleast_one_comment_growth']    = formatAbsolute(0);
            $profile_calcs_formatted["$date"]['pins_atleast_one_engage_growth']     = formatAbsolute(0);
            $profile_calcs_formatted["$date"]['repins_per_pin_growth']              = formatAbsoluteKPI(0);
            $profile_calcs_formatted["$date"]['repins_per_follower_growth']         = formatAbsoluteKPI(0);
            $profile_calcs_formatted["$date"]['repins_per_pin_per_follower_growth'] = formatAbsoluteKPI(0);
        }

        if ($p['follower_count_growth_perc'] || $p['pin_count_growth_perc'] || $p['repin_count_growth_perc'] || $p['like_count_growth_perc'] || $p['comment_count_growth_perc'] || $p['following_count_growth_perc'] || $p['board_count_growth_perc']) {
            $profile_calcs_formatted["$date"]['follower_count_growth_perc']              = formatPercentage($p['follower_count_growth_perc']);
            $profile_calcs_formatted["$date"]['following_count_growth_perc']             = formatPercentage($p['following_count_growth_perc']);
            $profile_calcs_formatted["$date"]['follow_ratio_growth_perc']                = formatPercentage($p['follow_ratio_growth']);
            $profile_calcs_formatted["$date"]['board_count_growth_perc']                 = formatPercentage($p['board_count_growth_perc']);
            $profile_calcs_formatted["$date"]['pin_count_growth_perc']                   = formatPercentage($p['pin_count_growth_perc']);
            $profile_calcs_formatted["$date"]['repin_count_growth_perc']                 = formatPercentage($p['repin_count_growth_perc']);
            $profile_calcs_formatted["$date"]['like_count_growth_perc']                  = formatPercentage($p['like_count_growth_perc']);
            $profile_calcs_formatted["$date"]['comment_count_growth_perc']               = formatPercentage($p['comment_count_growth_perc']);
            $profile_calcs_formatted["$date"]['pins_atleast_one_repin_growth_perc']      = formatPercentage($p['pins_atleast_one_repin_growth_perc']);
            $profile_calcs_formatted["$date"]['pins_atleast_one_like_growth_perc']       = formatPercentage($p['pins_atleast_one_like_growth_perc']);
            $profile_calcs_formatted["$date"]['pins_atleast_one_comment_growth_perc']    = formatPercentage($p['pins_atleast_one_comment_growth_perc']);
            $profile_calcs_formatted["$date"]['pins_atleast_one_engage_growth_perc']     = formatPercentage($p['pins_atleast_one_engage_growth_perc']);
            $profile_calcs_formatted["$date"]['repins_per_pin_growth_perc']              = formatPercentage($p['repins_per_pin_growth_perc']);
            $profile_calcs_formatted["$date"]['repins_per_follower_growth_perc']         = formatPercentage($p['repins_per_follower_growth_perc']);
            $profile_calcs_formatted["$date"]['repins_per_pin_per_follower_growth_perc'] = formatPercentage($p['repins_per_pin_per_follower_growth_perc']);
        } else {
            $profile_calcs_formatted["$date"]['follower_count_growth_perc']              = formatPercentage('na');
            $profile_calcs_formatted["$date"]['following_count_growth_perc']             = formatPercentage('na');
            $profile_calcs_formatted["$date"]['follow_ratio_growth_perc']                = formatAbsolute('na');
            $profile_calcs_formatted["$date"]['board_count_growth_perc']                 = formatPercentage('na');
            $profile_calcs_formatted["$date"]['pin_count_growth_perc']                   = formatPercentage('na');
            $profile_calcs_formatted["$date"]['repin_count_growth_perc']                 = formatPercentage('na');
            $profile_calcs_formatted["$date"]['like_count_growth_perc']                  = formatPercentage('na');
            $profile_calcs_formatted["$date"]['comment_count_growth_perc']               = formatPercentage('na');
            $profile_calcs_formatted["$date"]['pins_atleast_one_repin_growth_perc']      = formatPercentage('na');
            $profile_calcs_formatted["$date"]['pins_atleast_one_like_growth_perc']       = formatPercentage('na');
            $profile_calcs_formatted["$date"]['pins_atleast_one_comment_growth_perc']    = formatPercentage('na');
            $profile_calcs_formatted["$date"]['pins_atleast_one_engage_growth_perc']     = formatPercentage('na');
            $profile_calcs_formatted["$date"]['repins_per_pin_growth_perc']              = formatPercentage('na');
            $profile_calcs_formatted["$date"]['repins_per_follower_growth_perc']         = formatPercentage('na');
            $profile_calcs_formatted["$date"]['repins_per_pin_per_follower_growth_perc'] = formatPercentage('na');
        }
    }
}


$boards = array();
$acc = "select * from calcs_board_history where user_id='$cust_user_id' and date=$cache_timestamp";
$acc_res = mysql_query($acc, $conn) or die(mysql_error());
while ($a = mysql_fetch_array($acc_res)) {
    $date     = $a['date'];
    $board_id = $a['board_id'];

    $boards["$board_id"]                           = array();
    $boards["$board_id"]['name']                   = $a['name'];
    $boards["$board_id"]['url']                    = $a['url'];
    $boards["$board_id"]['pins']                   = $a['pins'];
    $boards["$board_id"]['followers']              = $a['followers'];
    $boards["$board_id"]['repins']                 = $a['repins'];
    $boards["$board_id"]['pins_atleast_one_repin'] = $a['pins_atleast_one_repin'];
    $boards["$board_id"]['virality']               = number_format($a['repins'] / $a['pins'], 2);
    $boards["$board_id"]['engagement']             = number_format($a['repins'] / $a['pins'] / $a['followers'] * 1000, 2);
    $boards["$board_id"]['engagement_rate']        = number_format(($a['pins_atleast_one_repin'] / $a['pins']) * 100, 1);
}


$new_curr_chart_date = $current_date * 1000;
$new_last_chart_date = $last_date * 1000;


if (isset($_GET['csv'])) {


    echo 'date,board_count,pin_count,follower_count,following_count,repin_count,like_count,comment_count,virality_score,engagement_score,engagement_rate,historical_estimate' . "\n";

    usort($profile_calcs, function ($a, $b) {
        $t = "timestamp";

        if ($a["$t"] < $b["$t"]) {
            return 1;
        } else if ($a["$t"] == $b["$t"]) {
            return 0;
        } else {
            return -1;
        }
    });

    foreach ($profile_calcs as $data) {


        if ($data['timestamp'] >= $last_date && $data['timestamp'] <= $current_date) {
            $date             = $data['chart_date'];
            $board_count      = $data['board_count'];
            $pin_count        = $data['pin_count'];
            $follower_count   = $data['follower_count'];
            $following_count  = $data['following_count'];
            $repin_count      = $data['repin_count'];
            $like_count       = $data['like_count'];
            $comment_count    = $data['comment_count'];
            $virality_score   = number_format($data['repins_per_pin'], 1, '.', '');
            $engagement_score = number_format($data['repins_per_pin_per_follower'], 3, '.', '');
            $engagement_rate  = number_format($data['engagement_rate'], 3, '.', '');
            $estimate         = array_get($data, 'estimate') ? 'Yes' : '';

            echo "$date,$board_count,$pin_count,$follower_count,$following_count,$repin_count,$like_count,$comment_count,$virality_score,$engagement_score,$engagement_rate,$estimate" . "\n";
        }
    }
    exit;
}


$this_time = "<span class='time-left muted'>$current_chart_label</span>";
$last_time = "<span class='time-right muted'>$old_chart_label</span>";


$is_profile = true;
$datePicker = true;


//************************************************************************************//
//************************************************************************************//
//************************************************************************************//
//----------------------------- FULL ACCOUNT FRONT END -------------------------------//
//************************************************************************************//
//************************************************************************************//
//************************************************************************************//





?>
<div class='clearfix'></div>
<div>

<?= $export_popover; ?>
<?= $popover_custom_date; ?>

<?php


if (isset($_GET['upgrade'])) {
    $new_plan = $_GET['upgrade'];

    if ($new_plan == 2) {
        $product_name = " to the <strong>Lite plan</strong>";
    } else if ($new_plan == 3) {
        $product_name = " to the <strong>Professional plan</strong>";
    } else {
        $product_name = "";
    }

    ?>
    <div class='alert alert-success'>
        <button type='button' class='close' data-dismiss='alert'>&times;</button>
        <strong>Success!</strong>
        You've Upgraded your Subscription<?= $product_name; ?>!
    </div>
<?php
}
?>

<div class='accordion' id='accordion3' style='margin-bottom:25px'>
<div id='collapseTwo' class='accordion-body collapse in'>
<div class='accordion-inner'>

<div class="row dashboard" style='margin-bottom:-10px;'>

<div style='text-align:left;'>

<div class="row" style='margin:10px 0 10px 30px;'>

<div class='feature-wrap'>

<div id="followers-toggle-dash" class="feature feature-left active">

    <div>
        <div class='feature-stat'>
            <?= $profile_calcs_formatted[$current_date]['follower_count']; ?>
        </div>
    </div>
    <h4> Followers </h4>

    <div class='feature-growth'>
        <span class='time'>
            <?= $current_name; ?>
        </span>
        <span class='growth'>
        <?= $profile_calcs_formatted[$current_date]['follower_count_growth']; ?>
        </span>
    </div>
</div>

<div id="pins-toggle-dash" class="feature feature-middle">
    <div>
        <div class='feature-stat'>
            <?= $profile_calcs_formatted[$current_date]['pin_count']; ?>
        </div>
    </div>
    <h4> Pins </h4>

    <div class='feature-growth'>
        <span class='time'><?= $current_name; ?></span>
                    <span class='growth'>
                        <?= $profile_calcs_formatted[$current_date]['pin_count_growth']; ?>
                    </span>
    </div>
</div>

<div id="repins-toggle-dash" class="feature feature-middle">
    <div>
        <div class='feature-stat'>
            <?= $profile_calcs_formatted[$current_date]['repin_count']; ?>
        </div>
    </div>

    <h4>Repins</h4>

    <div class='feature-growth'>
            <span class='time'>
                <?= $current_name; ?>
            </span>
            <span class='growth'>
                <?= $profile_calcs_formatted[$current_date]['repin_count_growth']; ?>
            </span>
    </div>
</div>

<?php
print "
                            <div id=\"likes-toggle-dash\" class=\"feature feature-right\">
                                <div>
                                    <div class='feature-stat''>" . @$profile_calcs_formatted[$current_date]['like_count'] . "
                                    </div>
                                </div>
                                <h4>Likes</h4>
                                <div class='feature-growth'>
                                    <span class='time'>$current_name</span>
                                    <span class='growth'>" . @$profile_calcs_formatted[$current_date]['like_count_growth'] . "</span>
                                </div>
                            </div>";

//								print "
//								<div id=\"comments-toggle-dash\" class=\"feature feature-right\" style='text-align:center; cursor:hand;cursor: pointer;'>
//									<h4>Comments</h4>
//									<div>
//										<div class='feature-stat''>" . $profile_calcs_formatted[$current_date]['comment_count'] . "</div>
//										<div class='feature-growth'>
//											<span class='left'>$this_time " . $profile_calcs_formatted[$current_date]['comment_count_growth'] . " " . $profile_calcs_formatted[$current_date]['comment_count_growth_perc'] . "</span>
//											<span class='right'>$last_time " . $profile_calcs_formatted[$last_date]['comment_count_growth'] . " " . $profile_calcs_formatted[$last_date]['comment_count_growth_perc'] . "</span>
//										</div>
//									</div>
//								</div>";

print "</div>";

print "</div>";

print "</div>";


if ($days_of_calcs == 1) {
    ?>
    <script>

        $(document).ready(function () {
            $('.feature-wrap-charts').addClass('chart-hide');
            $('.profile-chart-wrapper').prepend('<div class="chart-not-ready alert alert-info">Unlocking your charts in T minus <?php echo $hours_until_charts; ?>... <a data-toggle="popover" data-container="body" data-content="Each day, we take a snapshot of your analytics (at 12am CST) so you can track your growth over time.  As soon as your next snapshot is created, you\'ll be able to start measuring your progress right here!" data-placement="bottom"><i class="icon-info-2"></i> Learn more.</a></div>');
        });

    </script>



<?php
}


echo $chart_hide;

print "<div class=\"profile-chart-wrapper\" style='margin-left:30px'>";

?>

<div class="chart-upgrade well hidden">
    <strong>Upgrade to Unlock</strong><br>&nbsp;
    <ul>
        <li><strong>Get Historical Charts</strong> for Engagement Metrics<br> like Repins and Likes
        </li>
        <li><strong>Track 90+ days of history</strong><br> across your entire dashboard</li>
    </ul>
    <a class="btn-link" href="/upgrade?ref=profile_chart">
        <button class="btn btn-success btn-block">
            <i class="icon-arrow-right"></i> Learn More
        </button>
    </a>
    <a class="btn-link" style="margin-top:5px; display: block"  href="/try/pro">
        <button class="btn btn-small btn-block">
            or Try the Pro Demo
        </button>
    </a>
</div>

<?php

print '<div class="chart-not-ready alert alert-info hidden">Upgrade to Pro to see your engagement history! <a data-toggle="popover" data-container="body" data-content="Get Your Engagement History and more by upgrading to a Pro account today!  (find out how to go Pro for free! -->)" data-placement="bottom"><i class="icon-info-2"></i> Learn more.</a></div>';

print "<div class=\"row\" style='margin-left:0px;'>";

print "<div class=\"feature-wrap-charts\" style='text-align:left; margin-bottom: 20px;'>";


//-------------------------// REPIN CHART //---------------------------//
?>

<script type='text/javascript' src='https://www.google.com/jsapi'></script>

<script type='text/javascript'>
google.load('visualization', '1.1', {packages: ['corechart', 'controls']});

google.setOnLoadCallback(drawVisualization);


function drawVisualization() {
    var dashboard2 = new google.visualization.Dashboard(document.getElementById('repin_chart_div'));

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
                    'series': {0: {color: '#5792B3'}},
                    'curveType': 'function',
                    'animation': {
                        'duration': 500,
                        'easing': 'inAndOut'
                    },
                    'annotations': {
                        'style': 'line'
                    }
                },


                // 1 day in milliseconds = 24 * 60 * 60 * 1000 = 86,400,000
                'minRangeSize': 86400000
            }
        },
        // Initial range:
        'state': {'range': {'start': new Date(<?=$new_last_chart_date;?>), 'end': new Date(<?=$new_curr_chart_date;?>)}}
    });

    var chart2 = new google.visualization.ChartWrapper({
        'chartType': 'AreaChart',
        'containerId': 'chart2',
        'options': {
            // Use the same chart area width as the control for axis alignment.
            'chartArea': {'left': '0px', 'top': '30px', 'height': '80%', 'width': '80%'},
            'hAxis': {'slantedText': false},
            'legend': {'position': 'top'},
            'series': {0: {color: '#5792B3'}},
            'curveType': 'function',
            'animation': {
                'duration': 500,
                'easing': 'inAndOut'
            },
            'areaOpacity': 0.6,
            'annotations': {
                'style': 'line'
            }
        }

    });


    var data = new google.visualization.DataTable();
    data.addColumn('date', 'Date');
    data.addColumn('number', 'Followers');
    data.addColumn('number', 'Pins');
    data.addColumn('number', 'Repins');
    data.addColumn('number', 'Likes');
    data.addColumn('number', 'Repins per Pin');
    data.addColumn({type:'boolean',role:'certainty'});
    data.addColumn({type:'boolean',role:'scope'});
    data.addColumn({type:'string',role:'annotation'});
    data.addColumn({type:'string',role:'annotationText'});

    <?php

    $max_profile_repins_per_pin = 0;
    $total_profile_repins_per_pin = 0;

    $max_repins_per_follower = 0;
    $total_repins_per_follower = 0;

    $max_repins_per_pin_per_follower = 0;
    $total_repins_per_pin_per_follower = 0;

    $max_repin_engagement = 0;

    $rpp_counter = 0;

    $estimate_threshold = 0;
    foreach ($profile_calcs as $d) {

        $chart_date_format            = date("m/d", strtotime($d['chart_date']));
        $chart_profile_followers      = $d['follower_count'];
        $chart_profile_repins         = $d['repin_count'];
        $chart_time                   = $d['timestamp'];
        $chart_time_js                = $chart_time * 1000;
        $chart_profile_pins           = $d['pin_count'];
        $chart_profile_likes          = $d['like_count'];
        $chart_profile_comments       = $d['comment_count'];
        $chart_pins_atleast_one_repin = $d['pins_atleast_one_repin'];


        if($d['estimate']==1){
            $chart_certainty = 'false';
            $chart_scope = 'false';
            $estimate_threshold++;
        } else {
            $chart_certainty = 'true';
            $chart_scope = 'true';
        }

        if($estimate_threshold == 1){
            $chart_annotation = "' ↑ Estimated ↑ '";
            $chart_annotation_text = "'All data prior to this point is estimated based on your activity and Pinterest trends.'";
        } else {
            $chart_annotation = 'null';
            $chart_annotation_text = 'null';
        }


        @$check_repins_per_pin = number_format($chart_profile_repins / $chart_profile_pins, 1);

        @$check_repins_per_follower = number_format($chart_profile_repins / $chart_profile_followers, 2);

        @$check_repins_per_pin_per_follower = number_format($d['repins_per_pin_per_follower'], 2);

        @$check_repin_engagement = number_format($chart_pins_atleast_one_repin / $chart_profile_pins * 100, 1);


        if ($check_repins_per_pin > $max_profile_repins_per_pin) {
            $max_profile_repins_per_pin = $check_repins_per_pin;
        }

        if ($check_repin_engagement > $max_repin_engagement) {
            $max_repin_engagement = $check_repin_engagement;
        }

        if ($check_repins_per_follower > $max_repins_per_follower) {
            $max_repins_per_follower = $check_repins_per_follower;
        }

        if ($check_repins_per_pin_per_follower > $max_repins_per_pin_per_follower) {
            $max_repins_per_pin_per_follower = $check_repins_per_pin_per_follower;
        }


        $total_repins_per_follower += $check_repins_per_follower;

        $total_repins_per_pin_per_follower += $check_repins_per_pin_per_follower;

        $total_profile_repins_per_pin += $check_repins_per_pin;
        $rpp_counter++;


        if (!isset($chart_profile_following)) {
            $chart_profile_following = 0;
        }

        if (($chart_profile_followers != 0) || $chart_time != $cust_timestamp) {
            if ($chart_time_js != 0) {
                print
                "var date = new Date($chart_time_js);
                data.addRow([date, {$chart_profile_followers}, {$chart_profile_pins}, {$chart_profile_repins}, {$chart_profile_likes}, {$chart_profile_comments}, {$chart_certainty}, {$chart_scope}, {$chart_annotation}, {$chart_annotation_text}]);";
            }
        }
    }

    $avg_profile_repins_per_pin = number_format($total_profile_repins_per_pin / $rpp_counter, 2);

    $avg_repins_per_follower = number_format($total_repins_per_follower / $rpp_counter, 2);

    $avg_repins_per_pin_per_follower = number_format($total_repins_per_pin_per_follower / $rpp_counter, 2);

    ?>

    var pinBox = document.getElementById('pins-toggle');
    var likeBox = document.getElementById('likes-toggle');
    var repinBox = document.getElementById('repins-toggle');
    var commentBox = document.getElementById('comments-toggle');
    var followerBox = document.getElementById('followers-toggle');

    function drawChart() {

        // Disabling the buttons while the chart is drawing.
        pinBox.checked = false;
        likeBox.checked = false;
        repinBox.checked = false;
        commentBox.checked = false;
        followerBox.checked = false;

        //google.visualization.events.addListener(chart, 'ready', function() {
        // Check and enable only relevant boxes.

        followerBox.checked = view.getViewColumns().indexOf(1) != -1;

        pinBox.checked = view.getViewColumns().indexOf(2) != -1;

        repinBox.checked = view.getViewColumns().indexOf(3) != -1;

        likeBox.checked = view.getViewColumns().indexOf(4) != -1;

        commentBox.checked = view.getViewColumns().indexOf(5) != -1;

        // });


        dashboard2.bind(control2, chart2);
        dashboard2.draw(view);


    }


    pinBox.onclick = function () {

        //adding pins
        if (pinBox.checked) {

            view.setColumns([0, 2, 6, 7, 8, 9]);
            chart2.setOption('series', [
                {'color': '#40343a'}
            ]);
            chart2.setOption('areaOpacity', 0.3);
            control2.setOption('ui', {'chartOptions': {'series': [
                {'color': '#40343a'}
            ], 'chartArea': {'width': '80%'}}, 'chartType': 'AreaChart'});
            chart2.draw(view);
            control2.draw(view);
            drawChart();
        }
    }

    repinBox.onclick = function () {
        //adding repins
        if (repinBox.checked) {

            view.setColumns([0, 3, 6, 7, 8, 9]);
            chart2.setOption('series', [
                {'color': '#D77E81'}
            ]);
            chart2.setOption('areaOpacity', 0.3);
            control2.setOption('ui', {'chartOptions': {'series': [
                {'color': '#D77E81'}
            ], 'chartArea': {'width': '80%'}}, 'chartType': 'AreaChart'});
            drawChart();


        }
    }

    likeBox.onclick = function () {
        //adding likes
        if (likeBox.checked) {

            view.setColumns([0, 4, 6, 7, 8, 9]);
            chart2.setOption('series', [
                {'color': '#FF9900'}
            ]);
            chart2.setOption('areaOpacity', 0.3);
            control2.setOption('ui', {'chartOptions': {'series': [
                {'color': '#FF9900'}
            ], 'chartArea': {'width': '80%'}}, 'chartType': 'AreaChart'});
            drawChart();


        }
    }

    commentBox.onclick = function () {

        //adding comments
        if (commentBox.checked) {

            view.setColumns([0, 5, 6, 7, 8, 9]);
            chart2.setOption('series', [
                {'color': '#DDD116'}
            ]);
            chart2.setOption('areaOpacity', 0.3);
            control2.setOption('ui', {'chartOptions': {'series': [
                {'color': '#DDD116'}
            ], 'chartArea': {'width': '80%'}}, 'chartType': 'AreaChart'});
            drawChart();


        }
    }

    followerBox.onclick = function () {
        //adding followers
        if (followerBox.checked) {

            view.setColumns([0, 1, 6, 7, 8, 9]);
            chart2.setOption('series', [
                {'color': '#5792B3'}
            ]);
            chart2.setOption('areaOpacity', 0.3);
            control2.setOption('ui', {'chartOptions': {'series': [
                {'color': '#5792B3'}
            ], 'chartArea': {'width': '80%'}}, 'chartType': 'AreaChart'});
            drawChart();


        }
    }

    var view = new google.visualization.DataView(data);
    view.setColumns([0, 1, 6, 7, 8, 9]);

    drawChart();

}
</script>

<?php

print "		<div id='repin_chart_div' style='float:left; width:$main_chart_width;'>
      <div id='chart2' style='width: 100%; height: 180px;'></div>
      <div id='control2' style='width: 100%; height: 30px;'></div>
  </div>	";

//-------------------------// BAR CHART //---------------------------//
if ($days_of_calcs > 2) {


    print "<script type='text/javascript' src='https://www.google.com/jsapi'></script>
<script type='text/javascript'>

google.load('visualization', '1', {packages:['corechart']});
 google.setOnLoadCallback(drawChart);
 function drawChart() {
// Create and populate the data table.
var data = new google.visualization.DataTable();
   data.addColumn('date', 'Date');
   data.addColumn('number', 'Followers');
   data.addColumn('number', 'Pins');
   data.addColumn('number', 'Repins');
   data.addColumn('number', 'Likes');
   data.addColumn('number', 'Comments');";


    foreach ($profile_calcs as $d) {

        if ($d['timestamp'] > $last_date) {
            $chart_date_format     = date("m/d", strtotime($d['chart_date']));
            $chart_daily_followers = $d['daily_followers'];
            $chart_daily_repins    = $d['daily_repins'];
            $chart_daily_pins      = $d['daily_pins'];
            $chart_daily_likes     = $d['daily_likes'];
            $chart_daily_comments  = $d['daily_comments'];
            $chart_time            = $d['timestamp'];
            $chart_time_js         = $chart_time * 1000;


            if ($chart_time_js != 0) {
                print
                    "var date = new Date($chart_time_js);
           data.addRow([date, {$chart_daily_followers}, {$chart_daily_pins}, {$chart_daily_repins}, {$chart_daily_likes}, {$chart_daily_comments}]);";
            }
        }
    }


//									print 	"   [' ', '$older_chart_label' , '$old_chart_label', '$current_chart_label'],
//											    [' ', " . ($profile_calcs[$compare_date]['follower_count_growth']) . ", ". ($profile_calcs[$last_date]['follower_count_growth']) . ", " . ($profile_calcs[$current_date]['follower_count_growth']) . "],
//											    [' ', " . ($profile_calcs[$compare_date]['pin_count_growth']) . ", ". ($profile_calcs[$last_date]['pin_count_growth']) . ", " . ($profile_calcs[$current_date]['pin_count_growth']) . "],
//											    [' ', " . ($profile_calcs[$compare_date]['repin_count_growth']) . ", ". ($profile_calcs[$last_date]['repin_count_growth']) . ", " . ($profile_calcs[$current_date]['repin_count_growth']) . "],
//											    [' ', " . ($profile_calcs[$compare_date]['like_count_growth']) . ", ". ($profile_calcs[$last_date]['like_count_growth']) . ", " . ($profile_calcs[$current_date]['like_count_growth']) . "],
//											    [' ', " . ($profile_calcs[$compare_date]['comment_count_growth']) . ", ". ($profile_calcs[$last_date]['comment_count_growth']) . ", " . ($profile_calcs[$current_date]['comment_count_growth']) . "],
//											  ]);";

// Create and draw the visualization.

    print "
var options = {
  'vAxis':{'minValue':0,'textPosition':'out','gridlines':{'color':'transparent'}},
  'hAxis':{'gridlines':{'color':'transparent'},'format':'M/d','allowContainerBoundaryTextCufoff':true,'maxAlternation':1},
  'legend':{'position': 'top'},
  'title':'Daily Follower Growth',
  'chartArea':{'left':'0px','top':18,'width':'80%','height':'80%'},
  'colors':['#79ABC6'],
  'bar':{'groupWidth':'85%'},
  'animation':{
      'duration': 500,
      'easing': 'inAndOut'
   }
};

var pinBox = document.getElementById('pins-toggle2');
var likeBox = document.getElementById('likes-toggle2');
var repinBox = document.getElementById('repins-toggle2');
var commentBox = document.getElementById('comments-toggle2');
var followerBox = document.getElementById('followers-toggle2');


function drawChart() {

    // Disabling the buttons while the chart is drawing.
    pinBox.checked = false;
    likeBox.checked = false;
    repinBox.checked = false;
    commentBox.checked = false;
    followerBox.checked = false;
    google.visualization.events.addListener(chart2, 'ready',
        function() {
          // Check and enable only relevant boxes.
          pinBox.checked = view.getViewColumns().indexOf(2) != -1;

          repinBox.checked = view.getViewColumns().indexOf(3) != -1;

          likeBox.checked = view.getViewColumns().indexOf(4) != -1;

          commentBox.checked = view.getViewColumns().indexOf(5) != -1;

          followerBox.checked = view.getViewColumns().indexOf(1) != -1;

        });





      //options['isStacked'] = false;

      chart2.draw(view, options);
}



  pinBox.onclick = function() {
      //adding pins
      if(pinBox.checked){
          view.setColumns([0,2]);
          options['colors'] = ['#b6b6b6','#969696'];
          options['title'] = 'Daily Pin Activity'
          drawChart();
      }
  }

  repinBox.onclick = function() {
      //adding repins
      if(repinBox.checked){
          view.setColumns([0,3]);
          options['colors'] = ['#E7B2B4','#D77E81'];
          options['title'] = 'Daily Repins Growth'
          drawChart();
      }
  }

  likeBox.onclick = function() {
      //adding likes
      if(likeBox.checked){
          view.setColumns([0,4]);
          options['colors'] = ['#FFC267','#FF9900'];
          options['title'] = 'Daily Likes Growth'
          drawChart();
      }
  }

  commentBox.onclick = function() {

      //adding comments
      if(commentBox.checked){
          view.setColumns([0,5]);
          options['colors'] = ['#EBE474','#DDD116'];
          options['title'] = 'Daily Comments Growth'
          drawChart();
      }
  }

  followerBox.onclick = function() {
      //adding repins per pin
      if(followerBox.checked){
          view.setColumns([0,1]);
          options['colors'] = ['#79ABC6','#4E7E98'];
          options['title'] = 'Daily Follower Growth'
          drawChart();
      }
  }


  var view = new google.visualization.DataView(data);
  view.setColumns([0,1]);

  var chart2 = new google.visualization.ColumnChart(document.getElementById('repin_bars'))


  drawChart();

}
</script>";


    print "<div id='repin_bars' style='float:right; width: 25%; height: 220px; margin-bottom: -45px; margin-right:3%;'></div>";

}

print "</div>";


print "	<div class=\"feature-wrap-chart-controls\" style='text-align:left;position: absolute; left:-99999px;'>";
print "
<form>
      <input id='pins-toggle' type='radio' value='pins-toggle'> Pins </input>
      <input id='repins-toggle' type='radio' value='repins-toggle'> Repins </input>
      <input id='likes-toggle' type='radio' value='likes-toggle'> Likes </input>
      <input id='comments-toggle' type='radio' value='comments-toggle'> Comments </input>
      <input id='followers-toggle' type='radio' value='followers-toggle'> Repins/Pin </input>
  </form>
</div>

<div class=\"\" style='text-align:left;position: absolute; left:-99999px;'>";
print "
  <form>
      <input id='pins-toggle2' type='radio' value='pins-toggle'> Pins </input>
      <input id='repins-toggle2' type='radio' value='repins-toggle'> Repins </input>
      <input id='likes-toggle2' type='radio' value='likes-toggle'> Likes </input>
      <input id='comments-toggle2' type='radio' value='comments-toggle'> Comments </input>
      <input id='followers-toggle2' type='radio' value='followers-toggle'> Repins/Pin </input>
  </form>
</div>";

print "</div>";

print "<hr>";

print "</div>";

print "</div>";


$curr_rpf = number_format($profile_calcs[$current_date]['repins_per_follower'], 1);
$past_rpf = $profile_calcs[$last_date]['repins_per_follower'];
$rpfc = $curr_rpf - $past_rpf;
$rpf_chart = $past_rpf;
$rpfc_chart = abs($rpfc);

if ($rpfc < 0) {
    $rpfc_color = "max";
    $rpf_chart  = $curr_rpf;
    $rpfc_arrow = "";
} else {
    $rpfc_color = "success";
    $rpfc_arrow = "arrow-up";
}

if ($curr_rpf == $max_repins_per_follower) {
    $rpf_max_color = "text-glow";
} else {
    $rpf_max_color = "muted";
}

$curr_rpp = number_format($profile_calcs[$current_date]['repins_per_pin'], 1);
$past_rpp = $profile_calcs[$last_date]['repins_per_pin'];
$rppc = $curr_rpp - $past_rpp;
$rpp_chart = $past_rpp;
$rppc_chart = abs($rppc);

if ($rppc < 0) {
    $rppc_color = "max";
    $rpp_chart  = $curr_rpp;
    $rppc_arrow = "";
} else {
    $rppc_color = "success";
    $rppc_arrow = "arrow-up";
}

if ($curr_rpp == $max_profile_repins_per_pin) {
    $rpp_max_color = "text-glow'>(New";
} else {
    $rpp_max_color = "muted-more'>(";
}

$curr_rpppf = number_format($profile_calcs[$current_date]['repins_per_pin_per_follower'], 2);
$past_rpppf = $profile_calcs[$last_date]['repins_per_pin_per_follower'];
$rpppfc = $curr_rpppf - $past_rpppf;
$rpppf_chart = $past_rpppf;
$rpppfc_chart = abs($rpppfc);

if ($rpppfc < 0) {
    $rpppfc_color = "max";
    $rpppf_chart  = $curr_rpppf;
    $rpppfc_arrow = "";
} else {
    $rpppfc_color = "success";
    $rpppfc_arrow = "arrow-up";
}

if ($curr_rpppf == $max_repins_per_pin_per_follower) {
    $rpppf_max_color = "text-glow'>(New";
} else {
    $rpppf_max_color = "muted-more'>(";
}

@$perc_max_repin_follower = ($rpf_chart / $max_repins_per_follower) * 100;
@$perc_avg_repin_follower = ($avg_repins_per_follower / $max_repins_per_follower) * 100;
@$perc_rpfc = ($rpfc_chart / $max_repins_per_follower) * 100;

@$perc_max_repin_pin = ($rpp_chart / $max_profile_repins_per_pin) * 100;
@$perc_avg_repin_pin = ($avg_profile_repins_per_pin / $max_profile_repins_per_pin) * 100;
@$perc_rppc = ($rppc_chart / $max_profile_repins_per_pin) * 100 - 0.2;

@$perc_max_repin_pin_follower = ($rpppf_chart / $max_repins_per_pin_per_follower) * 100;
@$perc_avg_repin_pin_follower = ($avg_repins_per_pin_per_follower / $max_repins_per_pin_per_follower) * 100;
@$perc_rpppfc = ($rpppfc_chart / $max_repins_per_pin_per_follower) * 100 - 0.2;


$total_engage_inc = $profile_calcs[$current_date]['pins_atleast_one_engage'] - $profile_calcs[$current_date]['pins_atleast_one_repin'];

$non_engaged_pins = abs($profile_calcs[$current_date]['pin_count'] - $profile_calcs[$current_date]['pins_atleast_one_engage']);

$total_engage_perc = number_format(($profile_calcs[$current_date]['pins_atleast_one_engage'] / $profile_calcs[$current_date]['pin_count'] * 100), 1);

$repin_engage_perc = number_format(($profile_calcs[$current_date]['pins_atleast_one_repin'] / $profile_calcs[$current_date]['pin_count'] * 100), 1);

if ($repin_engage_perc > 100) {
    $repin_engage_perc = 100;
}

if ($repin_engage_perc == $max_repin_engagement) {
    $engage_max_color = "text-glow'>(New";
} else {
    $engage_max_color = "muted-more'>(";
}


//gauges

if (!$is_free_account) {


    print "<div class=\"\">";

    print "<div class=\"row-fluid\" style='margin-bottom:10px; margin-top:20px;'>";

    print "<div class=\"feature-wrap\">";

    print
        "<div class='profile-gauge' style=''>
  <div style='margin:0 auto; text-align:center;margin-bottom:10px;'>
      <h4 style='margin-bottom:0px'>Virality Score
          <a class='gauge-icon' data-toggle='popover' data-container='body' data-original-title='<strong>Virality Score</strong>' data-content=\"<strong>How is it measured?</strong><br><em class='muted'>Virality Score = Total Repins ÷ Total Pins</em><br><br><strong>What does it mean?</strong><br>How much your pins are being repinned across your profile.<br><br><div class='alert'><small>This gauge displays how high your virality is compared to each of the last 30 days.  <strong>A full gauge means you've reached a new 30-day high!</strong></small></div>\" data-placement='right'><i id='gauge-icon' class='icon-help'></i></a>
      </h4>
      <small class='muted'>Repins / Pin</small>
  </div>
  <div class='profile-gauge-stats' style='margin-top:30px;'>
      <h2 id='virality-value'>$curr_rpp</h2>
  </div>
  <div class='profile-gauge-stats' style='margin-top:70px;'>
      <div style='width:70px; margin:0 auto;'>
          <h4 style='margin-bottom:0px;border-top:1px solid #777; padding-top:3px;color:#999'>$avg_profile_repins_per_pin</h4>
      </div>
      <div style='width:95px; margin:-5px auto;'>
          <small class='muted' style='margin-top:-15px;'>30-Day Avg.</small>
      </div>
  </div>
  <div class='profile-gauge-max'>
      <small class='muted-more'>$max_profile_repins_per_pin</small>
      <small class='$rpp_max_color 30-Day High)</small>
  </div>
  <canvas id='repins_per_pin' class='profile-gauge-canvas'></canvas>
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
        'colorStart': '#6F6EA0',   // Colors
        'colorStop': '#C0C0DB',    // just experiment with them
        'strokeColor': '#EEEEEE',   // to see which ones work best for you
        'generateGradient': true
      };
      var target = document.getElementById('repins_per_pin'); // your canvas element
      var gauge = new Donut(target).setOptions(opts); // create sexy gauge!
      gauge.maxValue = $max_profile_repins_per_pin; // set max gauge value
      gauge.animationSpeed = 32; // set animation speed (32 is default value)
      gauge.set($curr_rpp); // set actual value
      var textRenderer = new TextRenderer(document.getElementById('virality-value'))
      textRenderer.render = function(gauge){
         percentage = gauge.displayedValue;
         this.el.innerHTML = (percentage).toFixed(2);
      };
      gauge.setTextField(textRenderer);
      //gauge.setTextField(document.getElementById('virality-value'));
  </script>
</div>";


    print
        "<div class='profile-gauge' style='height: 180px;'>
  <div style='margin:0 auto; text-align:center;margin-bottom:10px;'>
      <h4 style='margin-bottom:0px'>Engagement Score
          <a class='gauge-icon' data-toggle='popover' data-container='body' data-original-title='<strong>Engagement Score</strong>' data-content=\"<strong>How is it measured?</strong><br><em class='muted'>Engagement Score = Total Repins ÷ Total Pins ÷ (1000 Followers)</em><br><br><strong>What does it mean?</strong><br>Engagement Score is a measure of your Audience's Engagement with your pins, giving you insight into how much interaction your pins are receiving on a 'per-Follower' basis.<br><br><div class='alert'><small>This gauge displays how high your Engagement Score is compared to each of the last 30 days.  <strong>A full gauge means you've reached a new 30-day high!</strong></small></div>\" data-placement='left'><i id='gauge-icon' class='icon-help'></i></a>
      </h4>
      <small class='muted'>Repins / Pin / Follower</small>
  </div>
  <div class='profile-gauge-stats' style='margin-top:30px;'>
      <h2 id='attention-value'>$curr_rpppf</h2>
  </div>
  <div class='profile-gauge-stats' style='margin-top:70px;'>
      <div style='width:70px; margin:0 auto;'>
          <h4 style='margin-bottom:0px;border-top:1px solid #777; padding-top:3px;color:#999'>$avg_repins_per_pin_per_follower</h4>
      </div>
      <div style='width:95px; margin:-5px auto;'>
          <small class='muted' style='margin-top:-15px;'>30-Day Avg.</small>
      </div>
  </div>
  <div class='profile-gauge-max'>
      <small class='muted-more'>$max_repins_per_pin_per_follower</small>
      <small class='$rpppf_max_color 30-Day High)</small>
  </div>
  <canvas id='repins_per_follower' class='profile-gauge-canvas'></canvas>
  <script type='text/javascript'>
      var opts2 = {
        'lines': 12, // The number of lines to draw
        'angle': 0.18, // The length of each line
        'lineWidth': 0.12, // The line thickness
        'pointer': {
          'length': 0.9, // The radius of the inner circle
          'strokeWidth': 0.0, // The rotation offset
          'color': '#000000' // Fill color
        },
        'colorStart': '#6F6EA0',   // Colors
        'colorStop': '#C0C0DB',    // just experiment with them
        'strokeColor': '#EEEEEE',   // to see which ones work best for you
        'generateGradient': true
      };
      var target2 = document.getElementById('repins_per_follower'); // your canvas element
      var gauge2 = new Donut(target2).setOptions(opts2); // create sexy gauge!
      gauge2.maxValue = $max_repins_per_pin_per_follower; // set max gauge value
      gauge2.animationSpeed = 32; // set animation speed (32 is default value)
      gauge2.set($curr_rpppf); // set actual value
      var textRenderer = new TextRenderer(document.getElementById('attention-value'))
      textRenderer.render = function(gauge2){
         percentage = gauge2.displayedValue;
         this.el.innerHTML = (percentage).toFixed(2);
      };
      gauge2.setTextField(textRenderer);
      //gauge2.setTextField(document.getElementById('attention-value'));
  </script>
</div>";


    print
        "<div class='profile-gauge-right' style='height: 180px;'>
  <div style='margin:0 auto; text-align:center;margin-bottom:10px;'>
      <h4 style='margin-bottom:0px'>Engagement Rate
          <a class='gauge-icon' data-toggle='popover' data-container='body' data-original-title='<strong>Engagement Rate</strong>' data-content='<strong>How is it measured?</strong><br>Engagement Rate is measured by counting how many of your pins have at least 1 repin vs. no repins.<br><br><strong>What does it mean?</strong><br>Your Engagement Rate is a simple way for you to see how well your content is resonating with your audience. <br><br><div class=\"alert\"><small>This gauge displays your current Engagement rate out of 100% (100% means that all of your pins have at least 1 repin).</small></div>' data-placement='left'><i id='gauge-icon' class='icon-help'></i></a>
      </h4>
      <small class='muted'>% of pins w/ at least 1 repin</small>
  </div>
  <div class='profile-gauge-stats' style='margin-top:10px;'>
      <h2 id='engage-value' class='engagement-label' style='margin-bottom:0px;'>$repin_engage_perc<span style='font-size:20px'>%</span></h2>
  </div>
  <div class='profile-gauge-stats' style='margin-top:70px;'>

  </div>
  <div class='profile-gauge-max'>
      <small class='muted-more'>$max_repin_engagement%</small>
      <small class='$engage_max_color 30-Day High)</small>
  </div>
  <canvas id='engagement_rate' style='margin-top:-22px;' class='profile-gauge-canvas'></canvas>
  <script type='text/javascript'>
      var opts3 = {
        'lines': 12, // The number of lines to draw
        'angle': 0.18, // The length of each line
        'lineWidth': 0.40, // The line thickness
        'pointer': {
          'length': 0.75, // The radius of the inner circle
          'strokeWidth': 0.035, // The rotation offset
          'color': 'rgba(25,25,25,0.7)' // Fill color
        },
        'colorStart': '#3D9D3D',   // Colors
        'colorStop': '#E2F0E2',    // just experiment with them
        'strokeColor': '#EEEEEE',   // to see which ones work best for you
        'generateGradient': true
      };
      var target3 = document.getElementById('engagement_rate'); // your canvas element
      var gauge3 = new Gauge(target3).setOptions(opts3); // create sexy gauge!
      gauge3.maxValue = 100; // set max gauge value
      gauge3.animationSpeed = 32; // set animation speed (32 is default value)
      gauge3.set($repin_engage_perc); // set actual value
      var textRenderer = new TextRenderer(document.getElementById('engage-value'))
      textRenderer.render = function(gauge3){
         percentage = gauge3.displayedValue / gauge3.maxValue
         this.el.innerHTML = (percentage * 100).toFixed(1) + '%'
      };
      gauge3.setTextField(textRenderer);
      //gauge3.setTextField(document.getElementById('engage-value'));
      //target3.height = 100; // adjust height
      //gauge3.setOptions();
      //gauge3.render();
  </script>
</div>";

    print"	</div>
</div>";

    print "</div>";


} else {


    print "<div class=\"row dashboard\" style='text-align:left;'>";

    print "<div class=\"row\" style='margin:10px 0 10px 30px;'>";

    print "<div class='feature-wrap'>";


    print "
  <div id=\"followers-toggle-dash\" class=\"feature opaque feature-left third no-pointer\">
      <h4>Virality Score
          <a class='gauge-icon'
          data-toggle='popover'
          data-container='body'
          data-original-title='<strong>Virality Score</strong>'
          data-content=\"<strong>How is it measured?</strong>
              <br><em class='muted'>Virality Score = Total Repins ÷ Total Pins</em>
              <br><br><strong>What does it mean?</strong>
              <br>How much your pins are being repinned across your profile.\"
          data-placement='right'>
              <i id='gauge-icon' class='icon-help'></i>
          </a>
      </h4>
      <div>
          <div class='feature-stat'>$curr_rpp</div>
          <small class='muted'>Repins / Pin</small>
      </div>
  </div>";

    print "
  <div id=\"pins-toggle-dash\" class=\"feature opaque feature-middle third no-pointer\">
      <h4>Engagement Score
          <a class='gauge-icon'
          data-toggle='popover'
          data-container='body'
          data-original-title='<strong>Engagement Score</strong>'
          data-content=\"<strong>How is it measured?</strong>
              <br><em class='muted'>Engagement Score = Total Repins ÷ Total Pins
              ÷ Followers</em>
              <br><br><strong>What does it mean?</strong>
              <br>Engagement Score is a measure of your Audience's Engagement with
              your pins, giving you insight into how much interaction your pins are
              receiving from each Follower.\"
          data-placement='left'>
              <i id='gauge-icon' class='icon-help'></i>
          </a>
      </h4>
      <div>
          <div class='feature-stat'>$curr_rpppf</div>
          <small class='muted'>Repins / Pin / Follower</small>
      </div>
  </div>";

    print "
  <div id=\"likes-toggle-dash\" class=\"feature opaque feature-right third no-pointer\">
      <h4 style='color:#57B35E;'>Engagement Rate
          <a class='gauge-icon'
          data-toggle='popover'
          data-container='body'
          data-original-title='<strong>Engagement Rate</strong>'
          data-content='<strong>How is it measured?</strong>
              <br>Engagement Rate is measured by counting how many of your pins
              have at least 1 repin vs. no repins.
              <br><br><strong>What does it mean?</strong>
              <br>Your Engagement Rate is a simple way for you to see how well
              your content is resonating with your audience.'
          data-placement='left'>
              <i id='gauge-icon' class='icon-help'></i>
          </a>
      </h4>
      <div>
          <div class='feature-stat''>$repin_engage_perc%</div>
          <small class='muted'>% of pins w/ at least 1 repin</small>
      </div>
  </div>";

    print "</div>";

    print "</div>";

    print "</div>";

}


print "</div>";

print "</div>";

print "</div>";

print "</div>";


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

function viral_sort($a, $b)
{
    $t = "virality";

    if ($a["$t"] < $b["$t"]) {
        return 1;
    } else if ($a["$t"] == $b["$t"]) {
        return 0;
    } else {
        return -1;
    }
}

function engage_sort($a, $b)
{
    $t = "engagement";

    if ($a["$t"] < $b["$t"]) {
        return 1;
    } else if ($a["$t"] == $b["$t"]) {
        return 0;
    } else {
        return -1;
    }
}

function engage_rate_sort($a, $b)
{
    $t = "engagement_rate";

    if ($a["$t"] < $b["$t"]) {
        return 1;
    } else if ($a["$t"] == $b["$t"]) {
        return 0;
    } else {
        return -1;
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
        return "<span style='color: #549E54;font-weight:normal;font-size:12px'>(" . number_format($x, 1) . "%)</span>";
    } else if ($x < 0) {
        return "<span style='color: #aaa;font-weight:normal;font-size:12px'>(" . number_format($x, 1) . "%)</span>";
    } else if ($x == "na") {
        return "<span style='color: #aaa;font-weight:normal;font-size:12px'>(--%)</span>";
    }
}

function formatAbsoluteRatio($x)
{
    if ($x > 0) {
        return "<span class='pos'><i class='icon-arrow-up'></i>" . number_format($x, 1) . "</span><span style='color:#777'>:1</span>";
    } elseif ($x == 0) {
        return "<span class='neg'> &nbsp;--</span><span class='muted'>:1</span>";
    } else {
        return "<span class='neg'><i class='icon-arrow-down'></i>" . number_format($x, 1) . "</span><span style='color:#777'>:1</span>";
    }
}

function formatAbsoluteKPI($x)
{
    if ($x > 0) {
        return "<span style='color: green;'><i class='icon-arrow-up'></i>" . number_format($x, 2) . "</span>";
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


?>
