<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');


ini_set('memory_limit', '5000M');
ini_set('max_execution_time', '5000');
include('classes/pinterest.php');
// include('includes/connection.php');
include('includes/functions.php');
include ("classes/crawl.php");
include('../../bootstrap/bootstrap.php');

use Pinleague\CLI;
use Pinleague\PinterestException;

Log::setLog(__FILE__);

try {
CLI::h1('Starting Program');
$engine = new Engine(__FILE__);

if ($engine->running()) {
    throw new EngineException('Engine is running');
} else {
$engine->start();

$start = microtime(true);

CLI::write(Log::info('Engine started'));

$conn = DatabaseInstance::mysql_connect();

$DBH = DatabaseInstance::DBO();

$pinterest = Pinterest::getInstance();

    //CLI::write(Log::notice('Queue Keywords'));
//queueKeywords($conn);

    CLI::write(Log::notice('Queue Domains'));
    queueBrandSources($conn);
    
CLI::write(Log::info('Queue Profiles into status_profiles table'));
queueNewProfileUsers($pinterest, $conn);

CLI::write(Log::info('Queue Google Analytics profiles into status_traffic table'));
queueAnalytics();

CLI::write(Log::info('Queue User Followers records into status_profile_followers table'));
queuePullFollowersForUsers();

CLI::write(Log::info('Queue User Pins records into status_profile_pins table'));
queuePullPinsForUsers();

CLI::write(Log::info('Queue Done'));

$engine->complete();

    CLI::write(Log::runtime(). 'total runtime');
    CLI::write(Log::memory().' peak memory usage');

CLI::h1(Log::info('Complete'));
}
}
catch (EngineException $e) {

    CLI::alert($e->getMessage());
    CLI::stop();

}
catch (PinterestException $e) {

    CLI::alert($e->getMessage());
    Log::error($e);
    $engine->complete();
    CLI::stop();


} catch (PDOException $e) {

    CLI::alert($e->getMessage());
    Log::error($e);
    $engine->fail();
    CLI::stop();

} catch (Exception $e) {
    CLI::alert($e->getMessage());
    Log::error($e);
    $engine->fail();
    CLI::stop();
}

function queueAnalytics() {

    $DBH = DatabaseInstance::DBO();
    $DBH->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $acc = $DBH->query(
               "SELECT * FROM user_analytics
                WHERE track_type != 'orphan'
                AND track_type != 'profile_does_not_exist'
                AND track_type != 'unauthorized_token'
                AND
                (
                  account_id NOT IN
                  (
                    SELECT account_id FROM status_traffic
                  )
                  OR profile NOT IN
                  (
                    SELECT profile FROM status_traffic
                  )
                  OR token NOT IN
                  (
                    SELECT token FROM status_traffic
                  )
                  OR account_id IN
                  (
                    SELECT account_id FROM status_traffic
                    WHERE timezone IS NULL
                    AND profile != ''
                    AND (
                      track_type != 'unauthorized_token'
                      AND track_type != 'profile_does_not_exist'
                      OR track_type is NULL
                    )
                  )
                )
                AND profile != ''
                AND profile IS NOT NULL
                GROUP BY
                user_id, org_id, account_id, profile")->fetchAll();

    $sql = "";
    foreach($acc as $a){

        $user_id           = $a['user_id'];
        $org_id            = $a['org_id'];
        $account_id        = $a['account_id'];
        $profile           = $a['profile'];
        $token             = $a['token'];
        $timezone          = $a['timezone'];
        $currency          = $a['currency'];
        $eCommerceTracking = $a['eCommerceTracking'];
        $websiteUrl        = $a['websiteUrl'];
        $track_type        = $a['track_type'];
        $timestamp         = time();

        if($sql == ""){
            $sql = "INSERT into status_traffic
                    (user_id, org_id, account_id, profile, token, timezone, currency, eCommerceTracking, websiteUrl, last_pulled, last_calced, track_type, added_at, timestamp)
                    VALUES
                    ($user_id, $org_id, $account_id, '$profile', '$token', '$timezone', '$currency', '$eCommerceTracking', '$websiteUrl', 0, 0, '$track_type', $timestamp, $timestamp)";
        } else {
            $sql .= ",
                ($user_id, $org_id, $account_id, '$profile', '$token', '$timezone', '$currency', '$eCommerceTracking', '$websiteUrl', 0, 0, '$track_type', $timestamp, $timestamp)";
        }
    }

    if($sql != ""){

        $sql .= "
            ON DUPLICATE KEY UPDATE token = values(token), timezone = values(timezone), currency = values(currency), eCommerceTracking = values(eCommerceTracking), websiteUrl = values(websiteUrl), track_type = values(track_type)";

        $DBH->query($sql);
    }
}

function queueBrandSources($conn) {

    $inserts_user = "";
    $inserts_competitor = "";
    $inserts_free = "";
    $inserts_pinmail = "";
    $acc = "select a.domain as domain, b.track_type as track_type from user_accounts_domains a, user_accounts b where a.account_id = b.account_id and a.domain != ''";
    $acc_res = mysql_query($acc,$conn) or die(mysql_error());
    while ($a = mysql_fetch_array($acc_res)) {
        $domain = cleanSource($a['domain']);

        if(empty($domain)){
            continue;
        }

        $track_type = $a['track_type'];

        $time = time();
        if ($domain != "") {
            if ($track_type == "user") {
                if ($inserts_user == "") {
                    $inserts_user = "INSERT INTO status_domains (domain, last_pulled, last_calced, pins_per_day, track_type, timestamp) VALUES (\"$domain\", \"0\", \"0\", \"0\", \"$track_type\", \"$time\")";
                } else {
                    $inserts_user .= ",
						(\"$domain\", \"0\", \"0\", \"0\", \"$track_type\", \"$time\")";
                }
            } else if ($track_type == "competitor") {
                if ($inserts_competitor == "") {
                    $inserts_competitor = "INSERT IGNORE INTO status_domains (domain, last_pulled, last_calced, pins_per_day, track_type, timestamp) VALUES (\"$domain\", \"0\", \"0\", \"0\", \"$track_type\", \"$time\")";
                } else {
                    $inserts_competitor .= ",
						(\"$domain\", \"0\", \"0\", \"0\", \"$track_type\", \"$time\")";
                }
            } else if ($track_type == "free") {
                if ($inserts_free == "") {
                    $inserts_free = "INSERT IGNORE INTO status_domains (domain, last_pulled, last_calced, pins_per_day, track_type, timestamp) VALUES (\"$domain\", \"0\", \"0\", \"0\", \"$track_type\", \"$time\")";
                } else {
                    $inserts_free .= ",
						(\"$domain\", \"0\", \"0\", \"0\", \"$track_type\", \"$time\")";
                }
            } else if ($track_type == "pinmail") {
                if ($inserts_pinmail == "") {
                    $inserts_pinmail = "INSERT IGNORE INTO status_domains (domain, last_pulled, last_calced, pins_per_day, track_type, timestamp) VALUES (\"$domain\", \"0\", \"0\", \"0\", \"$track_type\", \"$time\")";
                } else {
                    $inserts_pinmail .= ",
						(\"$domain\", \"0\", \"0\", \"0\", \"$track_type\", \"$time\")";
                }
            }
        }

    }

    if ($inserts_user != "") {
        $inserts_user .= "
			ON DUPLICATE KEY UPDATE track_type = VALUES(track_type)";
        $resu = mysql_query($inserts_user, $conn);
    }

    if ($inserts_competitor != "") {
        $resu = mysql_query($inserts_competitor, $conn);
    }

    if ($inserts_free != "") {
        $resu = mysql_query($inserts_free, $conn);
    }

    if ($inserts_pinmail != "") {
        $resu = mysql_query($inserts_pinmail, $conn);
    }
}

function queueKeywords($conn) {

    $inserts_user = "";
    $inserts_competitor = "";
    $inserts_free = "";
    $inserts_pinmail = "";
    $acc = "select a.keyword as keyword, b.track_type as track_type from user_accounts_keywords a, user_accounts b where a.account_id = b.account_id and a.keyword != ''";
    $acc_res = mysql_query($acc,$conn) or die(mysql_error());
    while ($a = mysql_fetch_array($acc_res)) {
        $keyword = cleanSource($a['keyword']);
        $track_type = $a['track_type'];

        $time = time();
        if ($keyword != "") {
            if ($track_type == "user") {
                if ($inserts_user == "") {
                    $inserts_user = "INSERT INTO status_keywords (keyword, last_pulled, last_calced, pins_per_day, track_type, added_at, timestamp) VALUES (\"$keyword\", \"0\", \"0\", \"0\", \"$track_type\", \"$time\", \"$time\")";
                } else {
                    $inserts_user .= ",
						(\"$keyword\", \"0\", \"0\", \"0\", \"$track_type\", \"$time\", \"$time\")";
                }
            } else if ($track_type == "competitor") {
                if ($inserts_competitor == "") {
                    $inserts_competitor = "INSERT IGNORE INTO status_keywords (keyword, last_pulled, last_calced, pins_per_day, track_type, added_at, timestamp) VALUES (\"$keyword\", \"0\", \"0\", \"0\", \"$track_type\", \"$time\", \"$time\")";
                } else {
                    $inserts_competitor .= ",
						(\"$keyword\", \"0\", \"0\", \"0\", \"$track_type\", \"$time\", \"$time\")";
                }
            } else if ($track_type == "free") {
                if ($inserts_free == "") {
                    $inserts_free = "INSERT IGNORE INTO status_keywords (keyword, last_pulled, last_calced, pins_per_day, track_type, added_at, timestamp) VALUES (\"$keyword\", \"0\", \"0\", \"0\", \"$track_type\", \"$time\", \"$time\")";
                } else {
                    $inserts_free .= ",
						(\"$keyword\", \"0\", \"0\", \"0\", \"$track_type\", \"$time\", \"$time\")";
                }
            } else if ($track_type == "pinmail") {
                if ($inserts_pinmail == "") {
                    $inserts_pinmail = "INSERT IGNORE INTO status_keywords (keyword, last_pulled, last_calced, pins_per_day, track_type, added_at, timestamp) VALUES (\"$keyword\", \"0\", \"0\", \"0\", \"$track_type\", \"$time\", \"$time\")";
                } else {
                    $inserts_pinmail .= ",
						(\"$keyword\", \"0\", \"0\", \"0\", \"$track_type\", \"$time\", \"$time\")";
                }
            }
        }

    }

    if ($inserts_user != "") {
        $inserts_user .= "
			ON DUPLICATE KEY UPDATE track_type = VALUES(track_type)";
        $resu = mysql_query($inserts_user, $conn);
    }

    if ($inserts_competitor != "") {
        $resu = mysql_query($inserts_competitor, $conn);
    }

    if ($inserts_free != "") {
        $resu = mysql_query($inserts_free, $conn);
    }

    if ($inserts_pinmail != "") {
        $resu = mysql_query($inserts_pinmail, $conn);
    }
}

function cleanSource($source) {
    if (!(strpos($source, "/") === false)) {
        $spot = strpos($source, "/");

        $source = substr($source, 0, $spot);
    }

    $source = strtolower(trim($source));

    if ($source == "pinterest.com") {
        return "";
    }

    if (strpos($source, " ")){
        return "";
    }

    return $source;
}

function queueNewProfileUsers($pinterest, $conn) {

    $acc = "select username, user_id, track_type from user_accounts where username NOT IN (select username from status_profiles) AND username != ''";
    $acc_res = mysql_query($acc,$conn) or die(mysql_error());
    while ($a = mysql_fetch_array($acc_res)) {
        $track_type = $a['track_type'];
        if ($track_type == "") {
            $track_type = "user";
        }

        $username = mysql_real_escape_string($a['username']);

        $found = false;
        $expiration = strtotime("-1 month");
        $acc2 = "select user_id from map_username_to_user_id where username = '$username'";
        $acc2_res = mysql_query($acc2,$conn) or die(mysql_error());
        while ($b = mysql_fetch_array($acc2_res)) {
            $found = true;
            $user_id = $b['user_id'];
        }

        if (!$found) {
            $user_id = mysql_real_escape_string($pinterest->getUserIDFromUsername($username));

            $time = time();
            $acc2 = "REPLACE into map_username_to_user_id VALUES (\"$username\", \"$user_id\", \"$time\")";
            $acc2_res = mysql_query($acc2,$conn) or die(mysql_error());
        }

        if($a['user_id']==0){
            $acc2 = "UPDATE user_accounts set user_id='$user_id' where username='$username'";
            $acc2_res = mysql_query($acc2,$conn) or die(mysql_error());
        }

        $track_type = mysql_real_escape_string($track_type);

        if ($user_id != 0) {
            $time = time();
            $acc2 = "
				INSERT into status_profiles
				(user_id, username, last_calced, last_pulled, last_pulled_boards, track_type, timestamp)
				VALUES ('$user_id', '$username', '0', '0', '0', '$track_type', '$time')
				ON DUPLICATE KEY UPDATE track_type = VALUES(track_type)";
            $acc2_res = mysql_query($acc2,$conn) or die(mysql_error());
        }
    }
}

function queuePullFollowersForUsers() {
    $DBH = DatabaseInstance::DBO();
    $DBH->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    /*
     * Insert new user_ids into status_profile_followers table (track_type 'user' only)
     */
    $acc = $DBH->query(
               "select user_id, track_type
                from status_profiles where track_type = \"user\"
                AND user_id NOT IN (
                  select user_id from status_profile_followers
                  where track_type != \"user\"
                );")->fetchAll();

    $sql = "";

    foreach($acc as $a){

        $user_id = $a['user_id'];
        $track_type = $a['track_type'];
        $timestamp = time();

        if($sql == ""){
            $sql = "INSERT into status_profile_followers
                    (user_id, last_pulled, track_type, timestamp)
                    VALUES
                    ($user_id, 0, '$track_type', $timestamp)";
        } else {
            $sql .= ",
                ($user_id, 0, '$track_type', $timestamp)";
        }
    }

    if($sql != ""){
        $sql .= "
            ON DUPLICATE KEY UPDATE track_type = VALUES(track_type)";

        $DBH->query($sql);
    }

    /*
     * Insert new user_ids into status_profile_followers table (all other track_types)
     */
    $acc = $DBH->query(
               "select user_id, track_type
                from status_profiles
                where (track_type = 'competitor' or track_type = 'free' or track_type = 'pinmail')
                AND user_id NOT IN (
                  select user_id from status_profile_followers
                );")->fetchAll();

    $sql = "";
    foreach($acc as $a){

        $user_id = $a['user_id'];
        $track_type = $a['track_type'];
        $timestamp = time();

        if($sql == ""){
            $sql = "INSERT into status_profile_followers
                    (user_id, last_pulled, track_type, timestamp)
                    VALUES
                    ($user_id, 0, '$track_type', $timestamp)";
        } else {
            $sql .= ",
                ($user_id, 0, '$track_type', $timestamp)";
        }
    }

    if($sql != ""){
        $sql .= "
            ON DUPLICATE KEY UPDATE
            track_type = IF(VALUES(track_type)='competitor', 'competitor', track_type)";
        $DBH->query($sql);
    }

}

function queuePullPinsForUsers() {

    $DBH = DatabaseInstance::DBO();
    $DBH->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    /*
     * Insert new user_ids into status_profile_pins table (track_type 'user' only)
     */
    $acc = $DBH->query(
               "select user_id, track_type
                from status_profiles where track_type = 'user'
                AND user_id NOT IN (
                  select user_id from status_profile_pins
                  where track_type != 'user'
                );")->fetchAll();


    $sql = "";
    foreach($acc as $a){

        $user_id = $a['user_id'];
        $track_type = $a['track_type'];
        $timestamp = time();

        if($sql == ""){
            $sql = "INSERT into status_profile_pins
                    (user_id, last_pulled, track_type, timestamp)
                    VALUES
                    ($user_id, 0, '$track_type', $timestamp)";
        } else {
            $sql .= ",
                ($user_id, 0, '$track_type', $timestamp)";
        }
    }

    if($sql != ""){
        $sql .= "
            ON DUPLICATE KEY UPDATE track_type = VALUES(track_type)";
        $DBH->query($sql);
    }

    /*
     * Insert new user_ids into status_profile_followers table (all other track_types)
     */
    $acc = $DBH->query(
               "select user_id, track_type
                from status_profiles
                where (track_type = 'competitor' or track_type = 'free' or track_type = 'pinmail')
                AND user_id NOT IN (
                  select user_id from status_profile_pins
                );")->fetchAll();

    $sql = "";
    foreach($acc as $a){

        $user_id = $a['user_id'];
        $track_type = $a['track_type'];
        $timestamp = time();

        if($sql == ""){
            $sql = "INSERT into status_profile_pins
                    (user_id, last_pulled, track_type, timestamp)
                    VALUES
                    ($user_id, 0, '$track_type', $timestamp)";
        } else {
            $sql .= ",
                ($user_id, 0, '$track_type', $timestamp)";
        }
    }

    if($sql != ""){
        $sql .= "
            ON DUPLICATE KEY UPDATE
            track_type = IF(VALUES(track_type)='competitor', 'competitor', track_type)";
        $DBH->query($sql);
    }

}


?>
