<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

ini_set('memory_limit', '5000M');
ini_set('max_execution_time', '5000');
include('classes/pinterest.php');
include('includes/functions.php');
include ("classes/crawl.php");
include ("classes/googleanalytics.php");
include('../../bootstrap/bootstrap.php');

use Pinleague\CLI;
Log::setLog(__FILE__);

$conn = DatabaseInstance::mysql_connect();

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        throw new EngineException('Engine is running');
    } else {
        $engine->start();
        $start = microtime(true);

        CLI::write(Log::info('Engine started'));

        CLI::write(Log::debug('Getting users to pull google analytics data for.'));
        $traffic_array = getAnalyticsToPull(flat_date('day'), $conn);


        if ($traffic_array == null) {
            CLI::write(Log::notice('No analytics to update.'));
            $engine->complete();
            exit;
        }

        $today_updated = false;
        foreach($traffic_array as $traffic){

            if (empty($traffic['profile'])) {
                updateTrafficEngineRun($traffic, $conn);
                CLI::write(Log::notice('This traffic_id has no profile selected.  Moving on...'));
                continue;
            }

            $analytics = new GoogleAnalytics($traffic['token']);
            $analytics->setProfile($traffic['profile']);

            if (($analytics->profile != null) && ($analytics->profile != "")) {
                $start_date = getNextPullDate($traffic, $conn, $today_updated);
                $finish_date = getFinishDateFromStartDate($start_date);

                /**
                 * If we're starting by pulling today's data (all other historical data already
                 * pulled), then we want to actually make sure to update yesterday's data as well,
                 * because yesterday it would have only saved
                 * a partial count of the metrics (pulled before the day was over).
                 */
                if ($start_date == getFlatDate(time())) {
                    $start_date = strtotime("-1 day", $start_date);
                    $finish_date = getFinishDateFromStartDate($start_date);
                }

                CLI::write("start date: $start_date || finish date: $finish_date");

                $runs = 0;
                while (getFlatDate(strtotime("+1 day", time())) > $finish_date) {
                    $runs++; if ($runs%5 == 0) { sleep(1); }

                    sleep(0.1);

                    CLI::write(Log::debug('Pull data from Google Analytics API for date: ' . date("m-d-Y", $start_date) . ' - ' . date("m-d-Y", $finish_date)));
                    savePinterestReferralTraffic($start_date, $finish_date, $traffic, $analytics, $conn);

                    CLI::write(Log::debug('Saving google analytics data for date: ' . date("m-d-Y", $start_date) . ' - ' . date("m-d-Y", $finish_date)));
                    saveTrafficRun($start_date, $traffic, $conn);

                    /**
                     * Again, if we started by updating only today's data,
                     * this start_date and finish_date will actually be offset backwards in time
                     * by one day.  So we want to simply offset them forwards by one day.
                     *
                     * Also, there will already be a record for today's date in the
                     * status_traffic_history table, so if we want to use the getNextPullDate
                     * method only if we haven't already pulled all historical data. 
                     */
                    if ($today_updated) {
                        $start_date = strtotime("+1 day", $start_date);
                        $finish_date = strtotime("+1 day", $finish_date);
                    } else {
                        $start_date = getNextPullDate($traffic, $conn, $today_updated);
                        $finish_date = getFinishDateFromStartDate($start_date);
                    }

                }
            }

            CLI::write(Log::debug('Update last_run in status_analytics table'));
            updateTrafficEngineRun($traffic, $conn);
        }

        $engine->complete();

        $end   = microtime(true);
        $range = $end - $start;
        CLI::seconds($range);

        CLI::h1(Log::info('Complete'));

        CLI::write(Log::runtime(). 'total runtime');
        CLI::write(Log::memory().' peak memory usage');
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

function savePinterestReferralTraffic($start, $finish ,$traffic, $analytics, $conn) {

    $google_start = getGoogleDateFormat($start);
    $google_finish = getGoogleDateFormat($finish);

    $url = "https://www.googleapis.com/analytics/v3/data/ga?key=AIzaSyA2hnhXoVCy4mdtPdwT2rpbqOZBzmkkZMM&ids=$analytics->profile&dimensions=ga%3Asource%2Cga%3AreferralPath%2Cga%3AsocialNetwork%2Cga%3AlandingPagePath&metrics=ga%3Avisitors%2Cga%3Avisits%2Cga%3Apageviews%2Cga%3AnewVisits%2Cga%3Atransactions%2Cga%3AtransactionRevenue&segment=dynamic%3A%3Aga%3Asource%3D~pinterest&filters=ga%3Amedium%3D%3Dreferral&sort=-ga%3Apageviews&start-date=$google_start&end-date=$google_finish&max-results=10000";

    $return = $analytics->call($url);
    $data = json_decode($return, true);

    $id = $traffic['id'];
    $time = time();
    if (!array_key_exists("totalsForAllResults", $data)) {

        $sql = "INSERT INTO data_traffic (traffic_id, date, visits, visitors, new_visits, pageviews, transactions, revenue, timestamp) VALUES (\"$id\", \"$start\", \"0\", \"0\", \"0\", \"0\", \"0\", \"0\", \"$time\")
			  ON DUPLICATE KEY UPDATE visits = '0', visitors = '0', new_visits = '0', pageviews = '0', transactions = '0', revenue = '0', timestamp = '$time'";

        $resu = mysql_query($sql, $conn);
    } else {

        $total_visitors = $data["totalsForAllResults"]['ga:visitors'];
        $total_visits = $data["totalsForAllResults"]['ga:visits'];
        $total_pageviews = $data["totalsForAllResults"]['ga:pageviews'];
        $total_newVisits = $data["totalsForAllResults"]['ga:newVisits'];
        $total_transactions = $data["totalsForAllResults"]['ga:transactions'];
        $total_revenue = $data["totalsForAllResults"]['ga:transactionRevenue'];

        $sql = "INSERT INTO data_traffic (traffic_id, date, visits, visitors, new_visits, pageviews, transactions, revenue, timestamp) VALUES (\"$id\", \"$start\", \"$total_visits\", \"$total_visitors\", \"$total_newVisits\", \"$total_pageviews\", \"$total_transactions\", \"$total_revenue\", \"$time\")
			  ON DUPLICATE KEY UPDATE visits = '$total_visits', visitors = '$total_visitors', new_visits = '$total_newVisits', pageviews = '$total_pageviews', transactions = '$total_transactions', revenue = '$total_revenue', timestamp = '$time'";

        $resu = mysql_query($sql, $conn);
    }

    if (!array_key_exists("rows", $data)) {
        return;
    }

    CLI::write(Log::debug('Parsing Google Analytics Data for individual pins'));
    $pins = array();
    $pages = array();
    foreach($data["rows"] as $row) {



        $source = strtolower($row[0]);
        if (($source != "pinterest.com") && ($source != "m.pinterest.com")) {
            continue;
        }

        $refer = strtolower($row[1]);
        $page = fixPage(strtolower($row[3]));

        $visitors = $row[4];
        $visits = $row[5];
        $pageviews = $row[6];
        $new_visits = $row[7];
        $transactions = $row[8];
        $revenue = $row[9];

        if (isPinRefer($refer)) {
            $pin_id = getPinID($refer);
            if ($pin_id > 0) {
                if (!array_key_exists($pin_id, $pins)) {
                    $pins["$pin_id"] = array();
                    $pins["$pin_id"]['visits'] = 0;
                    $pins["$pin_id"]['visitors'] = 0;
                    $pins["$pin_id"]['new_visits'] = 0;
                    $pins["$pin_id"]['pageviews'] = 0;
                    $pins["$pin_id"]['transactions'] = 0;
                    $pins["$pin_id"]['revenue'] = 0;
                }

                $pins["$pin_id"]['visits'] += $visits;
                $pins["$pin_id"]['visitors'] += $visitors;
                $pins["$pin_id"]['new_visits'] += $new_visits;
                $pins["$pin_id"]['pageviews'] += $pageviews;
                $pins["$pin_id"]['transactions'] += $transactions;
                $pins["$pin_id"]['revenue'] += $revenue;
            }
        }

        if (($page) && ($page != "")) {
            if (!array_key_exists($page, $pages)) {
                $pages["$page"] = array();
                $pages["$page"]['visits'] = 0;
            }
            $pages["$page"]['visits'] += $visits;
        }
    }

    if (count($pins) > 0) {
        ksort($pins);
        queuePinsToBeTracked(array_keys($pins), $conn);

        $inserts = "";
        foreach($pins as $pinid => $d) {
            $pinid = mysql_real_escape_string($pinid);
            $visits = mysql_real_escape_string($d['visits']);
            $visitors = mysql_real_escape_string($d['visitors']);
            $new_visits = mysql_real_escape_string($d['new_visits']);
            $pageviews =  mysql_real_escape_string($d['pageviews']);
            $transactions = mysql_real_escape_string($d['transactions']);
            $revenue = mysql_real_escape_string($d['revenue']);

            $time = time();

            if ($inserts == "") {
                $inserts .= "INSERT INTO data_traffic_pins (traffic_id, date, pin_id, visits, visitors, new_visits, pageviews, transactions, revenue, timestamp) VALUES (\"$id\", \"$start\", \"$pinid\", \"$visits\", \"$visitors\", \"$new_visits\", \"$pageviews\", \"$transactions\", \"$revenue\", \"$time\")";
            } else {
                $inserts .= ",
					(\"$id\", \"$start\", \"$pinid\", \"$visits\", \"$visitors\", \"$new_visits\", \"$pageviews\", \"$transactions\", \"$revenue\", \"$time\")";
            }
        }

        if ($inserts != "") {
            $inserts .= "
				ON DUPLICATE KEY UPDATE visits = VALUES(visits), visitors = VALUES(visitors), new_visits = VALUES(new_visits), pageviews = VALUES(pageviews), transactions = VALUES(transactions), revenue = VALUES(revenue), timestamp = VALUES(timestamp)";
            $resu = mysql_query($inserts, $conn);
        }
    }

    if (count($pages) > 0) {
        $inserts = "";
        foreach($pages as $page => $d) {
            $page = mysql_real_escape_string($page);
            $visits = mysql_real_escape_string($d['visits']);

            $time = time();


            if ($inserts == "") {
                $inserts .= "INSERT INTO data_traffic_pages (traffic_id, date, page, visits, timestamp) VALUES (\"$id\", \"$start\", \"$page\", \"$visits\", \"$time\")";
            } else {
                $inserts .= ",
					(\"$id\", \"$start\", \"$page\", \"$visits\", \"$time\")";
            }
        }

        if ($inserts != "") {
            $inserts .= "
				ON DUPLICATE KEY UPDATE visits = VALUES(visits), timestamp = VALUES(timestamp);";
            $resu = mysql_query($inserts, $conn);
        }
    }

}

function fixPage($page) {
    if ($page == "(not set)") {
        return "";
    }

    if (strpos($page, "?") === false) {
        return $page;
    } else {
        $spot = strpos($page, "?");
        return substr($page, 0, $spot);
    }
}

function queuePinsToBeTracked($pins, $conn) {
    $inserts = "";



    foreach($pins as $p) {
        $p = mysql_real_escape_string($p);

        /*
         * only insert pins with an id > 10000000000 because any pin_ids below this are from
         * Pinterest's legacy pin_id system, which is no longer in use and cannot be used to
         * retrieve data
         */
        if($p > 10000000000){
            $time = time();
            if ($inserts == "") {
                $inserts = "INSERT IGNORE into data_pins_new (pin_id, last_pulled, track_type, timestamp) values (\"$p\", \"0\", \"traffic\", \"$time\")";
            } else {
                $inserts .= ",
                    (\"$p\", \"0\", \"traffic\", \"$time\")";
            }
        }
    }

    if ($inserts != "") {
        $resu = mysql_query($inserts, $conn);
    }
}

function getPinID($refer) {
    $pin_id = str_replace("pin", "", $refer);
    $pin_id = str_replace("/", "", $pin_id);
    return $pin_id;
}

function isPinRefer($refer) {
    if (strpos($refer, "/pin/") === false) {
        return false;
    } else {
        return true;
    }
}

function saveTrafficRun($date, $traffic, $conn) {
    $id = $traffic['id'];

    $time = time();
    $sql = "REPLACE into status_traffic_history VALUES (\"$id\", \"$date\", \"$time\")";
    $resu = mysql_query($sql, $conn);
}

function updateTrafficEngineRun($traffic, $conn) {
    $id = $traffic['id'];

    $time = time();
    $sql = "update status_traffic set last_pulled = '$time', timestamp = '$time' where traffic_id = '$id'";
    $resu = mysql_query($sql, $conn);
}

function removeOldTrafficData($id, $date, $conn) {
    $sql = "delete from data_traffic_pages where date = '$date' AND traffic_id = '$id'";
    $resu = mysql_query($sql, $conn);

    $sql = "delete from data_traffic_pins where date = '$date' AND traffic_id = '$id'";
    $resu = mysql_query($sql, $conn);

    $sql = "delete from data_traffic where date = '$date' AND traffic_id = '$id'";
    $resu = mysql_query($sql, $conn);
}

function getFinishDateFromStartDate($start_date) {
    return getFlatDate(strtotime("+1 day", $start_date)) - 1;
}

function getNextPullDate($traffic, $conn, &$today_updated) {
    $last_date = getLastPullDate($traffic, $conn);

    if($last_date == getFlatDate(time()) && !$today_updated){
        $today_updated = true;
        return getFlatDate($last_date);
    } else {
        return getFlatDate(strtotime("+1 day",$last_date));
    }
}

function getLastPullDate($traffic, $conn) {
    $id = $traffic['id'];

    $last_timestamp = 0;
    $acc = "select date from status_traffic_history where traffic_id = '$id' order by date desc limit 1";
    $acc_res = mysql_query($acc,$conn) or die(mysql_error());
    while ($a = mysql_fetch_array($acc_res)) {
        $last_timestamp = $a['date'];
    }

    if ($last_timestamp == 0) {
        $last_timestamp = mktime(0,0,0,8,31,2011);
    }

    return $last_timestamp;
}

function getAnalyticsToPull($t, $conn) {

    $traffic = null;

    /*
     * First, check to see if there are any newly added profiles to pull traffic for.
     * If these are found, we want to pull them one at a time since there will be a lot of data
     * coming in
     */
    $acc = "select * from status_traffic where last_pulled = 0 and profile != '' order by last_pulled asc limit 1";
    $acc_res = mysql_query($acc,$conn) or die(mysql_error());
    while ($a = mysql_fetch_array($acc_res)) {
        $id = $a['traffic_id'];

        if($traffic == null){
            $traffic = array();
        }
        $traffic_ids[] = $id;

        $traffic["$id"] = array();

        $traffic["$id"]['id'] = $a['traffic_id'];
        $traffic["$id"]['user_id'] = $a['user_id'];
        $traffic["$id"]['profile'] = $a['profile'];
        $traffic["$id"]['token'] = $a['token'];

        $account_id = $a['account_id'];

        CLI::write($account_id);
    }

    if($traffic != null){
        CLI::write(Log::debug("pulling traffic data for new account", $traffic_ids));
        return $traffic;
    }

    /*
     * If there are no new accounts to pull data for, we will pull 30 existing accounts that
     * need to be updated and grab their data together.
     */
    $acc = "select * from status_traffic where last_pulled <= '$t' and profile != '' order by last_pulled asc limit 10";
    $acc_res = mysql_query($acc,$conn) or die(mysql_error());
    while ($a = mysql_fetch_array($acc_res)) {
        $id = $a['traffic_id'];

        if($traffic == null){
            $traffic = array();
        }
        $traffic_ids[] = $id;

        $traffic["$id"] = array();

        $traffic["$id"]['id'] = $a['traffic_id'];
        $traffic["$id"]['user_id'] = $a['user_id'];
        $traffic["$id"]['profile'] = $a['profile'];
        $traffic["$id"]['token'] = $a['token'];

        $account_id = $a['account_id'];

        CLI::write($account_id);
    }

    CLI::write(Log::debug("pulling traffic data for accounts that need to be updated", $traffic_ids));

    return $traffic;
}

function getGoogleDateFormat($t) {
    return date("Y-m-d", $t);
}

?>
