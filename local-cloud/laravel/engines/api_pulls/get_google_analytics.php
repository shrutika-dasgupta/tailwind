<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

 ini_set('memory_limit', '5000M');
 ini_set('max_execution_time', '5000');
include('../legacy/classes/pinterest.php');
include('../legacy/includes/functions.php');
include("../legacy/classes/crawl.php");
include("../legacy/classes/googleanalytics.php");
include('../../bootstrap/bootstrap.php');

use Pinleague\CLI;

Log::setLog(__FILE__, 'CLI');


try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        Log::notice("Engine is running");
        CLI::stop();
        exit;
    }

    $engine->start();

    Log::info('Engine started');

    $DBH = DatabaseInstance::DBO();

    Log::debug('Getting users to pull google analytics data for.');
    $traffic_array = StatusTraffic::getAnalyticsToPull();


    if ($traffic_array == null) {
        Log::notice('No analytics to update.');
        $engine->complete();
        exit;
    }

    /**
     * This variable checks to see if the last_pulled date is the same day
     * if not, it increments the next_pull_date/start_date by one
     */
    $today_updated = false;

    foreach ($traffic_array as $traffic) {

        $timezone = $traffic['timezone'];

        date_default_timezone_set($timezone);
        $date_today = date('Y-m-d', time());

        /**
         * @var $end_time The time that is the next_pull time and also,
         *                the end case for the while loop .
         */
        $end_time = strtotime("+1 day" . $date_today);

        if (empty($traffic['profile'])) {
            updateTrafficEngineRun($traffic, $end_time);
            Log::notice('This traffic_id has no profile selected.  Moving on...');
            continue;
        }

        /**
         * We initialize google analytics with the user token and profile_id
         */
        $analytics = new GoogleAnalytics($traffic['token']);
        $analytics->setProfile($traffic['profile']);

        if (($analytics->profile != null) && ($analytics->profile != "")) {
            $start_date = getNextPullDate($traffic, $today_updated);
            $finish_date = getFinishDateFromStartDate($start_date);

            CLI::write("start date: $start_date || finish date: $finish_date");

            $runs = 0;

            /** The $end_time that has been set here is the last allowed date that can
             * be pulled for a user. The while loops end case is set to such that it keeps
             * pulling the date for each day frame and stops once the finish_date
             * exceeds beyond the set end_time
             */
            while ($end_time > $finish_date) {

                $save_traffic_range = 0;

                $runs++;
                if ($runs % 5 == 0) {
                    sleep(1);
                }

                sleep(0.1);

                $save_traffic_start = microtime(true);

                Log::debug('Pull data from Google Analytics API for date: ' . date("m-d-Y", $start_date) . ' - ' . date("m-d-Y", $finish_date));
                savePinterestReferralTraffic($start_date, $finish_date, $traffic, $analytics);

                $save_traffic_stop = microtime(true);
                $save_traffic_range = $save_traffic_stop - $save_traffic_start;
                Log::debug("Time taken for calculating saving traffic : {$save_traffic_range}");

                Log::debug('Saving google analytics data for date: ' . date("m-d-Y", $start_date) . ' - ' . date("m-d-Y", $finish_date));
                saveTrafficRun($start_date, $traffic);

                $start_date = getNextPullDate($traffic, $today_updated);
                $finish_date = getFinishDateFromStartDate($start_date);
            }
        }

        Log::debug('Update last_run in status_analytics table');
        updateTrafficEngineRun($traffic, $end_time);
    }

    $engine->complete();

    CLI::seconds();

    CLI::h1(Log::info('Complete'));

    CLI::write(Log::runtime() . 'total runtime');
    CLI::write(Log::memory() . ' peak memory usage');

}
catch (Exception $e) {

    CLI::alert($e->getMessage());
    Log::error($e);
    $engine->fail();
    CLI::stop();
}

function savePinterestReferralTraffic($start, $finish, $traffic, $analytics)
{

    /**
     * We need to convert the time from epoch to DD-MM-YYYY format to be use in
     * the google analytics URL
     */
    $google_start  = getGoogleDateFormat($start);
    $google_finish = getGoogleDateFormat($finish);

    $url = "https://www.googleapis.com/analytics/v3/data/ga?key=AIzaSyA2hnhXoVCy4mdtPdwT2rpbqOZBzmkkZMM&ids=$analytics->profile&dimensions=ga%3AdeviceCategory%2Cga%3AlandingPagePath%2Cga%3Acountry%2Cga%3Ahour%2Cga%3AfullReferrer%2Cga%3Aregion%2Cga%3AsocialNetwork&metrics=ga%3Ausers%2Cga%3AnewUsers%2Cga%3Asessions%2Cga%3Abounces%2Cga%3AavgSessionDuration%2Cga%3Apageviews%2Cga%3ApageviewsPerSession%2Cga%3AuniquePageviews%2Cga%3Atransactions%2Cga%3AtransactionRevenue&segment=gaid%3A%3A-8&sort=-ga%3Apageviews&start-date=$google_start&end-date=$google_finish&max-results=10000";

    /**
     * The flag that is used to see if we got all the results for a certain
     * timeframe. The maximum results that we have set for each request is
     * 10000.
     */
    $next_link_present = true;

    $id   = $traffic['id'];
    $user_timezone = $traffic['timezone'];

    $time = time();

    $save_pins = new Pins();

    /**
     * The initialization of collections for various tables and also,
     * creating array to compute the aggregations.
     */
    $data_traffics             = new DataTrafficNews();
    $data_traffic_pins         = new DataTrafficPinsNews();
    $data_traffic_pages        = new DataTrafficPagesNews();
    $data_traffic_daily_counts = new DataTrafficDailyCounts();

    $data_traffic             = [];
    $data_traffic_pin         = [];
    $data_traffic_page        = [];
    $data_traffic_daily_count = [];

    while ($next_link_present == true) {

        /** Making the call to google analytics and decoding the returned
         * JSON
         */
        $send_request_start = microtime(true);

        $return = $analytics->call($url);
        $data   = json_decode($return, true);

        $send_request_stop = microtime(true);
        $send_request_range = $send_request_stop - $send_request_start;
        Log::debug("Time taken for making request to GA: {$send_request_range}");

        if ($data["totalResults"] == 0) {
            Log::notice("No data for traffic_id {$id} between {$google_start} and {$google_finish}");

        }

        if (array_key_exists("nextLink", $data)) {
            $url = $data["nextLink"];
        } else {
            $next_link_present = false;
        }

        if (!array_key_exists("rows", $data)) {
            return;
        }

        $row_total                = 0;
        $data_traffic_total       = 0;
        $data_traffic_pin_total   = 0;
        $data_traffic_page_total  = 0;
        $data_traffic_daily_total = 0;

        Log::debug('Parsing Google Analytics Data for individual pins');

        /** Reference: This is the order in which we receive the data from Google
         *             Analytics
         *
         *   [0] => "ga:deviceCategory"
         *   [1] => “ga:landingPagePath” —> page
         *   [2] => "ga:country"
         *   [3] => "ga:hour"
         *   [4] => “ga:fullReferrer” —> use full referrer to get pin_id
         *   [5] => "ga:region"
         *   [6] => "ga:socialNetwork" --> network
         *   [7] => "ga:users" --> visitors
         *   [8] => "ga:newUsers" --> new_visits
         *   [9] => "ga:sessions" --> visits
         *   [10] => "ga:bounces"
         *   [11] => "ga:avgSessionDuration" --> time_on_site
         *   [12] => "ga:pageviews"
         *   [13] => "ga:pageviewsPerSession" --> pageviews_per_visit
         *   [14] => "ga:uniquePageviews"
         *   [15] => "ga:transactions"
         *   [16] => "ga:transactionRevenue"
         */


        /**
         * In the following for loop we iterate over "rows" which we have gotten
         * back from different GA. Each row consists of dimensions (page, country,
         * region, etc.) and metrics (users, new_users, sessions, etc.)
         *
         * In the following tables we slice and aggregate the data based on the dimensions
         * decided for each of them.
         *
         * data_traffic_new - (device, network, hour)
         * data_traffic_pins_new - (device, hour)
         * data_traffic_pages_new - (device, page, network, date)
         * data_traffic_daily_counts - (country, region, device, network)
         *
         */
        foreach ($data["rows"] as $row) {

            $row_start = microtime(true);
            /** If the social network is returned as "(not set)" we set the
             * value to "None". This is because "network" is a primary key is
             * some tables and hence, we can't set it to NULL
             */
            if ($row[6] == "(not set)") {
                $row[6] = 'None';
            }

            $user_time = $google_start . " " . $row[3] . ":00:00 " . $user_timezone;

            /**
             * Get the user_time, which is the flat_date along with the hour we get back from
             * GA and user_timezone
             */
            $user_epoch = strtotime($user_time);

            $data_traffic_key = $row[0] . $row[6] . $user_epoch;
            $data_traffic_pins_key  = $row[0] . $user_epoch;
            $data_traffic_pages_key = $row[0] . $row[1] . $row[6] . $start;
            $data_traffic_counts_key = $row[2] . $row[5] . $row[0] . $row[6] . $start;

            $data_traffic_start = microtime(true);
            if (!array_key_exists($data_traffic_key, $data_traffic)) {

                $data_traffic[$data_traffic_key] = new DataTrafficNew();

                $data_traffic[$data_traffic_key]->traffic_id            = $id;
                $data_traffic[$data_traffic_key]->hour                  = $user_epoch;
                $data_traffic[$data_traffic_key]->device                = $row[0];
                $data_traffic[$data_traffic_key]->network               = $row[6];
                $data_traffic[$data_traffic_key]->full_referrer         = $row[4];
                $data_traffic[$data_traffic_key]->users                 = $row[7];
                $data_traffic[$data_traffic_key]->new_users             = $row[8];
                $data_traffic[$data_traffic_key]->sessions              = $row[9];
                $data_traffic[$data_traffic_key]->bounces               = $row[10];
                $data_traffic[$data_traffic_key]->time_on_site          = $row[11];
                $data_traffic[$data_traffic_key]->pageviews             = $row[12];
                $data_traffic[$data_traffic_key]->pageviews_per_session = $row[13];
                $data_traffic[$data_traffic_key]->unique_pageviews      = $row[14];
                $data_traffic[$data_traffic_key]->transactions          = $row[15];
                $data_traffic[$data_traffic_key]->revenue               = $row[16];

            } else {

                $data_traffic[$data_traffic_key]->users += $row[7];
                $data_traffic[$data_traffic_key]->new_users += $row[8];
                $data_traffic[$data_traffic_key]->sessions += $row[9];
                $data_traffic[$data_traffic_key]->bounces += $row[10];
                $data_traffic[$data_traffic_key]->time_on_site += $$row[11];
                $data_traffic[$data_traffic_key]->pageviews += $row[12];
                $data_traffic[$data_traffic_key]->pageviews_per_session += $row[13];
                $data_traffic[$data_traffic_key]->unique_pageviews += $row[14];
                $data_traffic[$data_traffic_key]->transactions += $row[15];
                $data_traffic[$data_traffic_key]->revenue += $row[16];
            }

            $data_traffic_stop = microtime(true);
            $data_traffic_range = $data_traffic_stop- $data_traffic_start;
            $data_traffic_total += $data_traffic_range;

            /** The variable converts the full_referrer to lower case.
             * This is done in order to get the pin_ids from the URL
             *
             */

            $data_traffic_pin_start = microtime(true);
            $refer = strtolower($row[4]);

            if (isPinRefer($refer)) {

                $pin_id = getPinID($refer);
                $distinct_pin_ids[$pin_id] = $pin_id;


                /*
                 * only insert pins with an id > 10000000000 because any pin_ids below this are from
                 * Pinterest's legacy pin_id system, which is no longer in use and cannot be used to
                 * retrieve data
                 */
                if ($pin_id > 10000000000) {

                    /**
                     * Make sure we're only adding distinct pin_ids to the collection that
                     * we're going to save, so we don't bloat the bulk insert with a ton of
                     * duplicate records.
                     */
                    if (!array_key_exists($pin_id, $distinct_pin_ids)) {
                        $save_pin = new Pin();
                        $save_pin->pin_id = $pin_id;
                        $save_pins->add($save_pin);
                    }

                    if (!array_key_exists($data_traffic_pins_key, $data_traffic_pin)) {

                        $data_traffic_pin[$data_traffic_pins_key] = new DataTrafficPinsNew();

                        $data_traffic_pin[$data_traffic_pins_key]->traffic_id            = $id;
                        $data_traffic_pin[$data_traffic_pins_key]->pin_id                = $pin_id;
                        $data_traffic_pin[$data_traffic_pins_key]->user_id               = 0;
                        $data_traffic_pin[$data_traffic_pins_key]->board_id              = 0;
                        $data_traffic_pin[$data_traffic_pins_key]->category              = '';
                        $data_traffic_pin[$data_traffic_pins_key]->hour                  = $user_epoch;
                        $data_traffic_pin[$data_traffic_pins_key]->device                = $row[0];
                        $data_traffic_pin[$data_traffic_pins_key]->users                 = $row[7];
                        $data_traffic_pin[$data_traffic_pins_key]->new_users             = $row[8];
                        $data_traffic_pin[$data_traffic_pins_key]->sessions              = $row[9];
                        $data_traffic_pin[$data_traffic_pins_key]->bounces               = $row[10];
                        $data_traffic_pin[$data_traffic_pins_key]->time_on_site          = $row[11];
                        $data_traffic_pin[$data_traffic_pins_key]->pageviews             = $row[12];
                        $data_traffic_pin[$data_traffic_pins_key]->pageviews_per_session = $row[13];
                        $data_traffic_pin[$data_traffic_pins_key]->unique_pageviews      = $row[14];
                        $data_traffic_pin[$data_traffic_pins_key]->transactions          = $row[15];
                        $data_traffic_pin[$data_traffic_pins_key]->revenue               = $row[16];

                    } else {

                        $data_traffic_pin[$data_traffic_pins_key]->users += $row[7];
                        $data_traffic_pin[$data_traffic_pins_key]->new_users += $row[8];
                        $data_traffic_pin[$data_traffic_pins_key]->sessions += $row[9];
                        $data_traffic_pin[$data_traffic_pins_key]->bounces += $row[10];
                        $data_traffic_pin[$data_traffic_pins_key]->time_on_site += $$row[11];
                        $data_traffic_pin[$data_traffic_pins_key]->pageviews += $row[12];
                        $data_traffic_pin[$data_traffic_pins_key]->pageviews_per_session += $row[13];
                        $data_traffic_pin[$data_traffic_pins_key]->unique_pageviews += $row[14];
                        $data_traffic_pin[$data_traffic_pins_key]->transactions += $row[15];
                        $data_traffic_pin[$data_traffic_pins_key]->revenue += $row[16];
                    }
                }
            }

            $data_traffic_pin_stop = microtime(true);
            $data_traffic_pin_range = $data_traffic_pin_stop - $data_traffic_pin_start;
            $data_traffic_pin_total += $data_traffic_pin_range;


            $data_traffic_page_start = microtime(true);
            $page = fixPage($row[1]);

            if (!empty($page)) {


                if (!array_key_exists($data_traffic_pages_key, $data_traffic_page)) {

                    $data_traffic_page[$data_traffic_pages_key] = new DataTrafficPagesNew();

                    $data_traffic_page[$data_traffic_pages_key]->traffic_id            = $id;
                    $data_traffic_page[$data_traffic_pages_key]->page                  = $row[1];
                    $data_traffic_page[$data_traffic_pages_key]->date                  = $start;
                    $data_traffic_page[$data_traffic_pages_key]->device                = $row[0];
                    $data_traffic_page[$data_traffic_pages_key]->network               = $row[6];
                    $data_traffic_page[$data_traffic_pages_key]->full_referrer         = $row[4];
                    $data_traffic_page[$data_traffic_pages_key]->users                 = $row[7];
                    $data_traffic_page[$data_traffic_pages_key]->new_users             = $row[8];
                    $data_traffic_page[$data_traffic_pages_key]->sessions              = $row[9];
                    $data_traffic_page[$data_traffic_pages_key]->bounces               = $row[10];
                    $data_traffic_page[$data_traffic_pages_key]->time_on_site          = $row[11];
                    $data_traffic_page[$data_traffic_pages_key]->pageviews             = $row[12];
                    $data_traffic_page[$data_traffic_pages_key]->pageviews_per_session = $row[13];
                    $data_traffic_page[$data_traffic_pages_key]->unique_pageviews      = $row[14];
                    $data_traffic_page[$data_traffic_pages_key]->transactions          = $row[15];
                    $data_traffic_page[$data_traffic_pages_key]->revenue               = $row[16];

                } else {

                    $data_traffic_page[$data_traffic_pages_key]->users += $row[7];
                    $data_traffic_page[$data_traffic_pages_key]->new_users += $row[8];
                    $data_traffic_page[$data_traffic_pages_key]->sessions += $row[9];
                    $data_traffic_page[$data_traffic_pages_key]->bounces += $row[10];
                    $data_traffic_page[$data_traffic_pages_key]->time_on_site += $$row[11];
                    $data_traffic_page[$data_traffic_pages_key]->pageviews += $row[12];
                    $data_traffic_page[$data_traffic_pages_key]->pageviews_per_session += $row[13];
                    $data_traffic_page[$data_traffic_pages_key]->unique_pageviews += $row[14];
                    $data_traffic_page[$data_traffic_pages_key]->transactions += $row[15];
                    $data_traffic_page[$data_traffic_pages_key]->revenue += $row[16];
                }
            }

            $data_traffic_page_stop = microtime(true);
            $data_traffic_page_range = $data_traffic_page_stop - $data_traffic_page_start;
            $data_traffic_page_total += $data_traffic_page_range;


            $data_traffic_daily_start = microtime(time);
            if (!array_key_exists($data_traffic_counts_key, $data_traffic_page)) {

                $data_traffic_daily_count[$data_traffic_counts_key]                        = new DataTrafficDailyCount();
                $data_traffic_daily_count[$data_traffic_counts_key]->traffic_id            = $id;
                $data_traffic_daily_count[$data_traffic_counts_key]->date                  = $start;
                $data_traffic_daily_count[$data_traffic_counts_key]->device                = $row[0];
                $data_traffic_daily_count[$data_traffic_counts_key]->network               = $row[6];
                $data_traffic_daily_count[$data_traffic_counts_key]->country               = $row[2];
                $data_traffic_daily_count[$data_traffic_counts_key]->region                = $row[5];
                $data_traffic_daily_count[$data_traffic_counts_key]->users                 = $row[7];
                $data_traffic_daily_count[$data_traffic_counts_key]->new_users             = $row[8];
                $data_traffic_daily_count[$data_traffic_counts_key]->sessions              = $row[9];
                $data_traffic_daily_count[$data_traffic_counts_key]->bounces               = $row[10];
                $data_traffic_daily_count[$data_traffic_counts_key]->time_on_site          = $row[11];
                $data_traffic_daily_count[$data_traffic_counts_key]->pageviews             = $row[12];
                $data_traffic_daily_count[$data_traffic_counts_key]->pageviews_per_session = $row[13];
                $data_traffic_daily_count[$data_traffic_counts_key]->unique_pageviews      = $row[14];
                $data_traffic_daily_count[$data_traffic_counts_key]->transactions          = $row[15];
                $data_traffic_daily_count[$data_traffic_counts_key]->revenue               = $row[16];
                $data_traffic_daily_count[$data_traffic_counts_key]->added_at              = $time;
            } else {

                $data_traffic_daily_count[$data_traffic_counts_key]->users += $row[7];
                $data_traffic_daily_count[$data_traffic_counts_key]->new_users += $row[8];
                $data_traffic_daily_count[$data_traffic_counts_key]->sessions += $row[9];
                $data_traffic_daily_count[$data_traffic_counts_key]->bounces += $row[10];
                $data_traffic_daily_count[$data_traffic_counts_key]->time_on_site += $$row[11];
                $data_traffic_daily_count[$data_traffic_counts_key]->pageviews += $row[12];
                $data_traffic_daily_count[$data_traffic_counts_key]->pageviews_per_session += $row[13];
                $data_traffic_daily_count[$data_traffic_counts_key]->unique_pageviews += $row[14];
                $data_traffic_daily_count[$data_traffic_counts_key]->transactions += $row[15];
                $data_traffic_daily_count[$data_traffic_counts_key]->revenue += $row[16];
            }

            $data_traffic_daily_stop = microtime(true);
            $data_traffic_daily_range = $data_traffic_daily_stop- $data_traffic_daily_start;
            $data_traffic_daily_total += $data_traffic_daily_range;

            $row_stop = microtime(true);
            $row_range = $row_stop - $row_start;
            $row_total += $row_range;
        }

        Log::debug("Time taken for all rows : {$row_total}");
//        Log::debug("Time taken for data traffic: {$data_traffic_total}");
//        Log::debug("Time taken for traffic pins : {$data_traffic_pin_total}");
//        Log::debug("Time taken for traffic page: {$data_traffic_page_total}");
//        Log::debug("Time taken for traffic daily count: {$data_traffic_daily_total}");
    }

    $unwind_for_start = microtime(true);
     /**
      * Adding the models in to collections
      */

    foreach($data_traffic as $traffic) {

        $data_traffics->add($traffic);
    }

    foreach($data_traffic_pin as $traffic_pins) {

        $data_traffic_pins->add($traffic_pins);
    }

    foreach($data_traffic_page as $traffic_pages) {

        $data_traffic_pages->add($traffic_pages);
    }

    foreach($data_traffic_daily_count as $daily_counts) {

        $data_traffic_daily_counts->add($daily_counts);
    }

    $unwind_for_stop = microtime(true);
    $unwind_for_range = $unwind_for_stop - $unwind_for_start;
    Log::debug("The for loops to get models into collections: {$unwind_for_range}");

    $save_collections_start = microtime(true);
    /**
     * Saving pins to data_pins_new table. Doing the "Insert Ignore" in this
     * fashion because the collections insert NULL to every column that is
     * not specified
     */
    $save_pins->sortByPrimaryKeys();

    $sql           = "";
    $insert_values = array();

    foreach($save_pins as $pin){

        array_push(
              $insert_values,
              $pin->pin_id,
              0,
              "traffic",
              time()
        );

        if($sql == ""){
            $sql = "INSERT IGNORE into data_pins_new
            (pin_id, last_pulled, track_type, timestamp)
            VALUES
            (?, ?, ?, ?)";
        } else {
            $sql .= ",
            (?, ?, ?, ?)";
        }
    }

    if($sql != ""){
        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->prepare($sql);
        $STH->execute($insert_values);
    }

    try {
        $save_pins->insertIgnoreDB();
    }
    catch (CollectionException $e) {
        Log::notice("No pins to save to data_pins_new");
    }

    /**
     * Save the collections to the DB
     */

    var_dump("Size of data_traffics: " . count($data_traffics));
    Log::debug('Saving data_traffic to DB');
    try {
        $data_traffics->insertUpdateDB();
    } catch (CollectionException $e) {
        Log::notice("No data to save to data traffic");
    }

    Log::debug('Saving data_traffic_pins to DB');
    try {
        $data_traffic_pins->insertUpdateDB();
    } catch (CollectionException $e) {
        Log::notice("No pins to save to data traffic pins");
    }

    Log::debug('Saving data_traffic_pages to DB');
    try {
        $data_traffic_pages->insertUpdateDB();
    } catch (CollectionException $e) {
        Log::notice("No pages to save to data traffic pages");
    }

    Log::debug('Saving data_traffic_daily_counts to DB');
    try {
        $data_traffic_daily_counts->insertUpdateDB();
    } catch (CollectionException $e) {
        Log::notice("No pages to save to data traffic pages");
    }

    $save_collections_stop = microtime(true);
    $save_collections_range = $save_collections_stop - $save_collections_start;
    Log::debug("Time taken to save collections: {$save_collections_range}");
}

function fixPage($page)
{
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


function getPinID($refer)
{
    $pin_id = str_replace("pinterest.com/pin", "", $refer);
    $pin_id = str_replace("/", "", $pin_id);

    return $pin_id;
}

function isPinRefer($refer)
{
    if (strpos($refer, "/pin/") === false) {
        return false;
    } else {
        return true;
    }
}

function saveTrafficRun($date, $traffic)
{
    $id = $traffic['id'];

    $DBH = DatabaseInstance::DBO();

    $time = time();

    $STH = $DBH->prepare("REPLACE into status_traffic_history_new
                          VALUES (:id, :date, :time)");

    $STH->execute([":id"  => $id,
                  ":date" => $date,
                  ":time" => $time]);
}

/**
 * @param $traffic
 * @param $end_time
 *
 */
function updateTrafficEngineRun($traffic, $end_time)
{
    $id = $traffic['id'];

    $DBH = DatabaseInstance::DBO();

    $STH = $DBH->prepare("UPDATE status_traffic
                          SET next_pull = :next_pull, timestamp = :timestamp
                          WHERE traffic_id = :traffic_id");

    $STH->execute([":next_pull" => $end_time,
                   ":timestamp"   => time(),
                   ":traffic_id"    => $id]);
}

function getFinishDateFromStartDate($start_date)
{
    return getFlatDate(strtotime("+1 day", $start_date));
}

/**
 * @param $traffic
 * @param $today_updated
 *
 * @return int
 */
function getNextPullDate($traffic, &$today_updated)
{
    $last_date = getLastPullDate($traffic);

    if ($last_date == getFlatDate(time()) && !$today_updated) {
        $today_updated = true;

        return getFlatDate($last_date);
    } else {
        return getFlatDate(strtotime("+1 day", $last_date));
    }
}

/**
 * @param $traffic
 *
 * @return int
 */
function getLastPullDate($traffic)
{
    $id = $traffic['id'];

    $DBH = DatabaseInstance::DBO();

    $timezone = $traffic['timezone'];

    date_default_timezone_set($timezone);

    $last_timestamp = 0;

    $acc = "";

    $STH = $DBH->prepare("SELECT date
                          FROM status_traffic_history_new
                          WHERE traffic_id = :id
                          ORDER BY date DESC
                          LIMIT 1");

    $STH->execute([":id" => $id]);

    $last_dates = $STH->fetchAll();

    foreach ($last_dates as $last_date) {
        $last_timestamp = $last_date->date;
    }

    if ($last_timestamp == 0) {
        $last_timestamp = mktime(0, 0, 0, 8, 31, 2011);
    }

    return $last_timestamp;
}

function getGoogleDateFormat($t)
{
    return date("Y-m-d", $t);
}

?>
