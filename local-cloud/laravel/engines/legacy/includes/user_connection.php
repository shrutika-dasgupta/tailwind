<?php
$cust_id = $_SESSION['cust_id'];
$key = $_SESSION['key'];

//$cust_id = 672;
//$key = '7ad4f79d53144c3776f0b6cc2c42bb492bc815e1';


$acc2 = "select * from users where cust_id = \"$cust_id\"";

$acc2_res = mysql_query($acc2,$conn) or die(mysql_error());
while ($b = mysql_fetch_array($acc2_res)) {
    $pk                = $b['password'];
    $cust_first_name   = $b['first_name'];
    $cust_last_name    = $b['last_name'];
    $cust_email        = $b['email'];
    $cust_timestamp    = $b['timestamp'];
    $cust_id           = $b['cust_id'];
    $cust_org_id       = $b['org_id'];
    $cust_is_admin     = $b['is_admin'];
    $cust_type         = $b['type'];
    $cust_invited_by   = $b['invited_by'];
    $cust_timezone     = $b['timezone'];
    $cust_display_name = $cust_first_name . " " . $cust_last_name;
}

if ((!$cust_id) || ($key != $pk)) {
    header("Location: /login.php");
    exit;
}

unset($pk);

//set timezone
//	$acc = "SET time_zone='$cust_timezone'";
//	$acc_res = mysql_query($acc,$conn) or die(mysql_error());


/*------------------------------------------------------------------------------------------------
/	Check for Admin Account
/-------------------------------------------------------------------------------------------------*/

$is_admin_account = false;
if($cust_id==474){
    $is_admin_account = true;

    $all_accounts = array();
    $ad_count = 0;
    $acc = "select a.cust_id, a.first_name, a.last_name, a.email, a.org_id, a.is_admin, a.type, a.invited_by,
            a.timestamp, b.org_name, b.plan, c.account_id, c.account_name, c.username, c.user_id, c.competitor_of,
            c.track_type, c.created_at, d.domain
			from users a, user_organizations b, user_accounts c, user_accounts_domains d
			where a.org_id = b.org_id
			and b.org_id=c.org_id
			and (c.competitor_of=0 or c.competitor_of is NULL)
			and c.account_id=d.account_id
			and c.track_type!='orphan'";
    $acc_res = mysql_query($acc,$conn) or die(mysql_error());
    while ($a = mysql_fetch_array($acc_res)) {

        $username = $a['username'];

        $all_accounts["$username"] = array();

        $all_accounts["$username"]['cust_id']      = $a['cust_id'];
        $all_accounts["$username"]['email']        = $a['email'];
        $all_accounts["$username"]['first_name']   = $a['first_name'];
        $all_accounts["$username"]['last_name']    = $a['last_name'];
        $all_accounts["$username"]['is_admin']     = $a['is_admin'];
        $all_accounts["$username"]['type']         = $a['type'];
        $all_accounts["$username"]['invited_by']   = $a['invited_by'];
        $all_accounts["$username"]['timestamp']    = $a['timestamp'];

        $all_accounts["$username"]['org_id']       = $a['org_id'];
        $all_accounts["$username"]['plan']         = $a['plan'];
        $all_accounts["$username"]['org_name']     = $a['org_name'];

        $all_accounts["$username"]['account_id']   = $a['account_id'];
        $all_accounts["$username"]['account_name'] = $a['account_name'];
        $all_accounts["$username"]['username']     = $username;
        $all_accounts["$username"]['user_id']      = $a['user_id'];
        $all_accounts["$username"]['track_type']   = $a['track_type'];
        $all_accounts["$username"]['created_at']   = $a['created_at'];

        $all_accounts["$username"]['domain'] = $a['domain'];

        $ad_count++;
    }

    //if this is an admin account, get all sub-accounts
    if($_GET['user']){

        $_SESSION['admin_cust_id'] = $all_accounts[$_GET['user']]['cust_id'];
        $_SESSION['admin_first_name'] = $all_accounts[$_GET['user']]['first_name'];
        $_SESSION['admin_last_name'] = $all_accounts[$_GET['user']]['last_name'];
        $_SESSION['admin_email'] = $all_accounts[$_GET['user']]['email'];
        $_SESSION['admin_is_admin'] = $all_accounts[$_GET['user']]['is_admin'];
        $_SESSION['admin_type'] = $all_accounts[$_GET['user']]['type'];
        $_SESSION['admin_invited_by'] = $all_accounts[$_GET['user']]['invited_by'];
        $_SESSION['admin_timestamp'] = $all_accounts[$_GET['user']]['timestamp'];
        $_SESSION['admin_org_id'] = $all_accounts[$_GET['user']]['org_id'];
        $_SESSION['admin_plan'] = $all_accounts[$_GET['user']]['plan'];
    } else {
        if(!$_SESSION['admin_cust_id']){
            $_SESSION['admin_cust_id'] = $cust_id;
            $_SESSION['admin_first_name'] = $cust_first_name;
            $_SESSION['admin_last_name'] = $cust_last_name;
            $_SESSION['admin_email'] = $cust_email;
            $_SESSION['admin_is_admin'] = $cust_is_admin;
            $_SESSION['admin_type'] = $cust_type;
            $_SESSION['admin_invited_by'] = $cust_invited_by;
            $_SESSION['admin_timestamp'] = $cust_timestamp;
            $_SESSION['admin_org_id'] = $cust_org_id;
            $_SESSION['admin_plan'] = $cust_plan;
        }
    }

    $cust_id = $_SESSION['admin_cust_id'];
    $cust_org_id = $_SESSION['admin_org_id'];
    $cust_plan = $_SESSION['admin_plan'];
    $cust_first_name = $_SESSION['admin_first_name'];
    $cust_last_name = $_SESSION['admin_last_name'];
    $cust_display_name = $cust_first_name." ".$cust_last_name;
    $cust_email = $_SESSION['admin_email'];
    $cust_is_admin = $_SESSION['admin_is_admin'];
    $cust_type = $_SESSION['admin_type'];
    $cust_invited_by = $_SESSION['admin_invited_by'];
    $cust_timestamp = $_SESSION['admin_timestamp'];
}


/*------------------------------------------------------------------------------------------------
/	GET ORG AND ACCOUNT DETAILS
/-------------------------------------------------------------------------------------------------*/

//get organization details
$acc = "select * from user_organizations where org_id = '$cust_org_id'";
$acc_res = mysql_query($acc,$conn) or die(mysql_error());
while ($a = mysql_fetch_array($acc_res)) {
    $cust_org_name = $a['org_name'];
    $cust_org_type = $a['org_type'];
    $cust_plan_id = $a['plan'];
    $cust_chargify_id = $a['chargify_id'];
}

//get plan name
$acc = "select * from user_plans where id = '$cust_plan_id'";
$acc_res = mysql_query($acc,$conn) or die(mysql_error());
while ($a = mysql_fetch_array($acc_res)) {
    $cust_plan_name = $a['name'];
}

$cust_accounts = array();
//get account details
$acc = "select * from user_accounts a left join user_industries b on a.industry_id = b.industry_id
	where a.org_id = '$cust_org_id' and (a.track_type='user' or a.track_type='free')
	and (competitor_of=0 or competitor_of is NULL);";
$acc_res = mysql_query($acc,$conn) or die(mysql_error());
while ($a = mysql_fetch_array($acc_res)) {
    $cust_account_id = $a['account_id'];

    $cust_accounts[] = array(
        'account_id'    => $a['account_id'],
        'account_name'  => $a['account_name'],
        'org_id'        => $a['org_id'],
        'username'      => $a['username'],
        'user_id'       => $a['user_id'],
        'industry_id'   => $a['industry_id'],
        'industry_name' => $a['industry'],
        'account_type'  => $a['account_type'],
        'created_at'    => $a['created_at'],
        'competitor_of' => $a['competitor_of'],
        'track_type'    => $a['track_type']
    );
}

if(array_key_exists('account', $_GET)){
    if($cust_plan_id == 1){
        $_GET['account'] = 0;
    }
}



$cust_accounts_count = count($cust_accounts);
$is_multi_account = false;
if($cust_accounts_count > 1){
    $is_multi_account = true;

    if(array_key_exists('account', $_GET)){
        $cust_account_num=$_GET['account'];
        $_SESSION['cust_account_num'] = $cust_account_num;
    } else {
        if($_SESSION['cust_account_num']){
            $cust_account_num = $_SESSION['cust_account_num'];
        } else {
            $cust_account_num = 0;
        }
    }
} else {
    if(array_key_exists('account', $_GET)){
        $cust_account_num=$_GET['account'];
    } else {
        $cust_account_num = 0;
    }
}

//get all domain info for each account
foreach($cust_accounts as $k => $ac){
    $account_id = $ac['account_id'];

    $acc = "select * from user_accounts_domains where account_id = '$account_id'";
    $acc_res = mysql_query($acc,$conn) or die(mysql_error());
    while ($a = mysql_fetch_array($acc_res)) {
        $domain = $a['domain'];

        $cust_accounts[$k]['domains'] = array();
        $cust_accounts[$k]['domains'][] = $domain;
    }
}


//get profile picture if we don't already have it
if(!$_SESSION['image'] || ($_SESSION['cust_user_id'] != $cust_accounts[$cust_account_num]['user_id'])){
    $acc = "select image from data_profiles_new where user_id = " . $cust_accounts[$cust_account_num]['user_id'] . " limit 1";
    $acc_res = mysql_query($acc,$conn) or die(mysql_error());
    while ($a = mysql_fetch_array($acc_res)) {
        $cust_image = $a['image'];
        $_SESSION['image'] = $cust_image;
    }
}


//get account info
$cust_account_id          = $cust_accounts[$cust_account_num]['account_id'];
$cust_account_name        = $cust_accounts[$cust_account_num]['account_name'];
$cust_account_type        = $cust_accounts[$cust_account_num]['account_type'];
$cust_account_track_type  = $cust_accounts[$cust_account_num]['track_type'];
$cust_user_id             = $cust_accounts[$cust_account_num]['user_id'];
$cust_username            = $cust_accounts[$cust_account_num]['username'];
$cust_industry            = $cust_accounts[$cust_account_num]['industry_name'];
$cust_industry_id         = $cust_accounts[$cust_account_num]['industry_id'];
$cust_account_created_at  = $cust_accounts[$cust_account_num]['created_at'];

$_SESSION['cust_user_id'] = $cust_user_id;


//get domain info
$domains = $cust_accounts[$cust_account_num]['domains'];
$cust_domains_count = count($domains);

//		$cust_domains = array();
//		foreach($domains as $d){
//			array_push($cust_domains, $d['domain']);
//		}

$cust_domain = $cust_accounts[$cust_account_num]['domains'][0];



/*------------------------------------------------------------------------------------------------
/	DO ACCOUNT FEATURE CHECKS
/-------------------------------------------------------------------------------------------------*/

//check if user has google analytics integrated
if(!$_SESSION['has_analytics'] || $is_admin_account){
    $has_ga = hasAnalytics($cust_account_id, $conn);
    if($has_ga){
        $_SESSION['has_analytics']=1;
        if(!analyticsReady($cust_account_id, $conn)){
            $_SESSION['has_analytics']=2;
        }
    } else {
        $_SESSION['has_analytics']=0;
    }
}

//check if the user has competitors or not
$has_competitors = false;
$acc = "select count(*) from user_accounts where competitor_of='$cust_account_id' AND track_type != 'orphan'";
$acc_res = mysql_query($acc,$conn) or die(mysql_error());
while ($a = mysql_fetch_array($acc_res)) {
    if($a['count(*)'] > 0){
        $has_competitors = true;
    }
    $cust_num_competitors = $a['count(*)'];
}


$first_day = false;
$cust_account_age = round((time() - $cust_account_created_at)/60/60);
if($cust_account_age < 1){
    $cust_account_age_print = "less than an hour old";
} elseif($cust_account_age < 12){
    $cust_account_age_print = "only a few hours old";
} elseif($cust_account_age < 24){
    $cust_account_age_print = "less than a day old";
} else {
    $cust_account_age_print = ($cust_account_age/24)." days old";
}
if($cust_account_age < 12){

    $first_day = true;
    if($_GET['refresh']==1){
        $sql = "update status_profiles set last_calced=0 where user_id='$cust_user_id'";
        $resu = mysql_query($sql, $conn);
        $sql = "update status_domains set last_calced=0 where domain='$cust_domain'";
        $resu = mysql_query($sql, $conn);
    }
}

//see how old this account is
if(round((time() - $cust_account_created_at)/60/60/24) < 14){
    $fresh_account = true;
}



/*------------------------------------------------------------------------------------------------
/	GET ACCOUNT STATS FOR INTERCOM
/-------------------------------------------------------------------------------------------------*/

if(!$_SESSION['follower_count']){

    $acc = "select max(date), count(*) from calcs_profile_history where user_id='$cust_user_id' order by date desc limit 1";
    $acc_res = mysql_query($acc,$conn) or die(mysql_error());
    while ($a = mysql_fetch_array($acc_res)) {
        $last_profile_calc_date = $a['max(date)'];
        Session::put('days_of_calcs',$a['count(*)']);
    }

    $acc = "select date from calcs_domain_history where domain='$cust_domain' order by date desc limit 1";
    $acc_res = mysql_query($acc,$conn) or die(mysql_error());
    while ($a = mysql_fetch_array($acc_res)) {
        $last_domain_calc_date = $a['date'];
    }

    $competitor_count = 0;
    $acc = "select a.*, b.*, c.*, d.* from user_accounts a, user_accounts_domains b, calcs_profile_history c, calcs_domain_history d where a.account_id=b.account_id and a.user_id=c.user_id and b.domain=d.domain and a.org_id='$cust_org_id' and c.date='$last_profile_calc_date' and d.date='$last_domain_calc_date'";
    $acc_res = mysql_query($acc,$conn) or die(mysql_error());
    while ($a = mysql_fetch_array($acc_res)) {


        if($competitor_count==0){
            $_SESSION['cust_profile_pins'] = $a['pin_count'];
            $_SESSION['follower_count'] = $a['follower_count'];
            $_SESSION['total_profile_pins'] = $a['pin_count'];
            $_SESSION['total_profile_pins'] += $a['domain_mentions'];
        } else if($competitor_count==1) {
            $_SESSION['total_competitor_pins'] = $a['pin_count'];
            $_SESSION['total_competitor_pins'] += $a['domain_mentions'];
        } else {
            $_SESSION['total_competitor_pins'] += $a['pin_count'];
            $_SESSION['total_competitor_pins'] += $a['domain_mentions'];
        }

        $competitor_count++;
    }

    $_SESSION['total_pins'] = $_SESSION['total_profile_pins'] + $_SESSION['total_competitor_pins'];
    $competitor_count--;

}



if(!$_SESSION['industries']){

    $_SESSION['industries'] = array();

    $acc = "select * from user_industries";
    $acc_res = mysql_query($acc,$conn) or die(mysql_error());
    while ($a = mysql_fetch_array($acc_res)) {
        $industry_id = $a['industry_id'];
        $industry_name = $a['industry'];

        $_SESSION['industries']["$industry_id"] = array();
        $_SESSION['industries']["$industry_id"]['id'] = $industry_id;
        $_SESSION['industries']["$industry_id"]['name'] = $industry_name;

    }
}

//asort($_SESSION['industries'][]['name']);







//TODO: get industry




?>