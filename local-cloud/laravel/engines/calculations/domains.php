<?php
//EXPLANATION
//finds which calcs to pull from
//                status_domains -> last_calced

//inserts data into the tables
//                calcs_domain_history
//                cache_domain_influencers
//                cache_domain_pins

error_reporting(E_ALL);
ini_set('display_errors', 'On');
ini_set('max_execution_time', '50000');
//include('../legacy/classes/pinterest.php');
//include('../legacy/includes/connection.php');
include('../legacy/includes/functions.php');

include('../../bootstrap/bootstrap.php');

use Pinleague\CLI;
use Pinleague\Pinterest;
use Pinleague\PinterestException;

Log::setLog(__FILE__);

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    CLI::write(Log::info('Checking how many domains scripts are currently running'));
    exec("ps aux| grep 'domains.php'", $processes_domains);
    exec("ps aux| grep 'keywords.php'", $processes_keywords);

    // The reason we are checking for 11 processes here is
    // because we want to make sure there are no more than 5 domains calcs scripts running
    // at any time.
    //
    // So we have to check for:
    // 0. ps aux| grep 'domains.php' process (1)
    // 1. grep 'domains.php' process (1)
    // 2. The parent process for each domains.php script coming from the crontab (5)
    // 3. The actual domains.php process (5)
    // 4. The parent process and script for this current instance of the script (2)
    // If it finds more than 13 running, then we stop the script and wait until next time.
    CLI::write(Log::info(count($processes_domains) . " domains.php processes running.", $processes_domains));
    CLI::write(Log::info(count($processes_keywords) . " keywords.php processes running.", $processes_keywords));

    if(count($processes_keywords) < 6){
        $threshold = 24;
    } else {
        $threshold = 14;
    }

    /*
     * Check number of processes that are running, and if there are low numbers, increase the
     * number of domains we run in each script so that we're not creating any "slack"
     */
    if (count($processes_domains) < 7) {
        $limit = 50;
    } else if (count($processes_domains) < 9) {
        $limit = 30;
    } else {
        $limit = 20;
    }


    CLI::write(Log::info("Setting script threshold to " . $threshold . " concurrent scripts.", $processes_keywords));

    if(count($processes_domains) > $threshold){
        CLI::write(Log::warning(
                      "Running too many Domain Calculation processes..Stopping script. " . count($processes_domains) . " domains.php processes running."
                          , $processes_domains
            )
        );
        CLI::stop();
    }


    $engine->start();

    $start = microtime(true);

    CLI::write(Log::info('Engine started'));

    $DBH = DatabaseInstance::DBO();
    $conn = DatabaseInstance::mysql_connect();
    $pinterest = Pinterest::getInstance();


    CLI::write(Log::debug('Grabbing User domains to run calcs'));
    $domains = getDomainsForCalcs("user", getFlatDate(time()), $limit);
    if (count($domains) == 0) {

        CLI::write(Log::debug('No more User domains.  Grabbing Competitor domains.'));
        $domains = getDomainsForCalcs("competitor", getFlatDate(time()), $limit);
        if (count($domains) == 0) {

            CLI::write(Log::debug('No more Competitor domains.  Grabbing Free domains.'));
            $domains = getDomainsForCalcs("free", getFlatDate(time()), $limit);
            if (count($domains) == 0) {
                $domains = getDomainsForCalcs("pinmail", getFlatDate(time()), $limit);
            }
        }
    }

    if (count($domains) == 0) {
        CLI::write(Log::notice('No more domains to run calcs.'));
        $engine->complete();
        exit;
    }


    /*
     * Start by logging all of the domains we're about to run calculations for
     */
    $log_context = array();
    foreach ($domains as $domain){
        $this_domain = $domain['domain'];
        $log_context["$this_domain"] = $this_domain;
    }
    CLI::write(Log::notice("Starting Domain Calcs for $limit domains", $log_context));

    /*
     * Iterate through each domain and run through all calculations
     */
    $log_context = array();

    foreach ($domains as $domain) {

        $log_context['domain'] = $domain['domain'];


        CLI::write(Log::debug('Starting Domain Calcs for domain', $log_context));

        CLI::write(Log::debug('Starting domain pins.', $log_context));
            calculateDomainPins($domain);
        CLI::write(Log::info('Finished domain pins.', $log_context));

        CLI::write(Log::debug('Starting domain influencers.', $log_context));
            calculateDomainInfluencers($domain);
        CLI::write(Log::info('Finished domain influencers.', $log_context));

        CLI::write(Log::debug('Starting domain history calculations.', $log_context));
            calculateDomainHistory($domain);
        CLI::write(Log::info('Finished domain history calculations.', $log_context));

        CLI::write(Log::debug('Starting daily domain data.', $log_context));
            calculateDailyDomainData($domain);
        CLI::write(Log::info('Finished daily domain data.', $log_context));

        CLI::write(Log::debug('Finished All Calculations for domain', $log_context));
    }
    $engine->complete();

    CLI::write(Log::runtime(). 'total runtime');
    CLI::write(Log::memory().' peak memory usage');

    CLI::h1(Log::info('Complete'));
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
function calculateDomainInfluencers($domain)
{

    $DBH = DatabaseInstance::DBO();
    $DBH->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $domain = mysql_real_escape_string($domain['domain']);

    $periods = array(0, 60, 30, 14, 7);


    $log_context = array();
    $log_context['domain'] = $domain['domain'];
    /*
    * run domain top pinners for each time period
    *
    */
    foreach ($periods as $period) {

        $log_context['period'] = $period;

        CLI::write(Log::debug("Calculating Domain Influencers", $log_context));

        $start = microtime(true);

        $select_array = array();
        $acc          = $DBH->query("SELECT
                    '$domain'
                    , '$period'
                    , c.user_id
                    , c.username
                    , q1.count as domain_mentions
                    , c.first_name
                    , c.last_name
                    , c.follower_count
                    , c.following_count
                    , c.image
                    , c.website_url
                    , c.facebook_url
                    , c.twitter_url
                    , c.location
                    , c.board_count
                    , c.pin_count
                    , c.like_count
                    , c.created_at
                    , unix_timestamp(now())
                FROM
                    (select  user_id, count(pin_id) as count
                    from data_pins_new
                    " . ($period == 0 ? "use index (domain_user_id_idx)" : "use index (domain_created_at_user_id_idx)") . "
                    where domain = '$domain'
                    " . ($period == 0 ? "" : "and created_at > " . strtotime("-$period Days", getFlatDate(time()))) . "
                    GROUP BY user_id
                    ORDER BY count(pin_id) desc
                    limit " . ($period == 0 ? "500" : "100") . ")
                as q1
                    left join data_profiles_new c
                    on q1.user_id = c.user_id")->fetchAll();

        foreach($acc as $a){

            $user_id = $a['user_id'];

            $select_array["$user_id"]                    = array();
            $select_array["$user_id"]['period']          = $period;
            $select_array["$user_id"]['user_id']         = $user_id;
            $select_array["$user_id"]['username']        = str_replace("'","\'", $a['username']);
            $select_array["$user_id"]['count']           = $a['domain_mentions'];
            $select_array["$user_id"]['first_name']      = mysql_real_escape_string($a['first_name']);
            $select_array["$user_id"]['last_name']       = mysql_real_escape_string($a['last_name']);
            $select_array["$user_id"]['follower_count']  = $a['follower_count'];
            $select_array["$user_id"]['following_count'] = $a['following_count'];
            $select_array["$user_id"]['image']           = mysql_real_escape_string($a['image']);
            $select_array["$user_id"]['website']         = mysql_real_escape_string($a['website_url']);
            $select_array["$user_id"]['facebook']        = $a['facebook_url'];
            $select_array["$user_id"]['twitter']         = $a['twitter_url'];
            $select_array["$user_id"]['location']        = mysql_real_escape_string($a['location']);
            $select_array["$user_id"]['board_count']     = $a['board_count'];
            $select_array["$user_id"]['pin_count']       = $a['pin_count'];
            $select_array["$user_id"]['like_count']      = $a['like_count'];
            $select_array["$user_id"]['created_at']      = $a['created_at'];
            $select_array["$user_id"]['timestamp']       = time();
        }

        $end = microtime(true);

        $log_context['run_time'] = ($end - $start);

        CLI::write(Log::debug("Completed Domain Influencers ($domain):: $period days:: " . ($end - $start) . " seconds.", $log_context));

        unset($log_context['run_time']);

        /*
         * Remove previous data from cache_domain_influencers table once we've queried the new
         * data to replace it
         */
        removeCurrentDomainInfluencerData($domain, $period);
        CLI::write(Log::debug("Removed old domain influencer data", $log_context));

        $acc = "";
        foreach ($select_array as $sel) {

            if ($acc == "") {
                $acc .= "INSERT into cache_domain_influencers
                    (
                    domain
                    , period
                    , influencer_user_id
                    , username
                    , domain_mentions
                    , first_name
                    , last_name
                    , follower_count
                    , following_count
                    , image
                    , website
                    , facebook
                    , twitter
                    , location
                    , board_count
                    , pin_count
                    , like_count
                    , created_at
                    , timestamp
                    )

                    VALUES
                    ('$domain'
                    , '$period'
                    , '" . $sel['user_id'] . "'
                    , '" . $sel['username'] . "'
                    , '" . $sel['count'] . "'
                    , '" . $sel['first_name'] . "'
                    , '" . $sel['last_name'] . "'
                    , '" . $sel['follower_count'] . "'
                    , '" . $sel['following_count'] . "'
                    , '" . $sel['image'] . "'
                    , '" . $sel['website'] . "'
                    , '" . $sel['facebook'] . "'
                    , '" . $sel['twitter'] . "'
                    , '" . $sel['location'] . "'
                    , '" . $sel['board_count'] . "'
                    , '" . $sel['pin_count'] . "'
                    , '" . $sel['like_count'] . "'
                    , '" . $sel['created_at'] . "'
                    , '" . $sel['timestamp'] . "'
                    )";
            } else {
                $acc .= ",
                    ('$domain'
                    , '$period'
                    , '" . $sel['user_id'] . "'
                    , '" . $sel['username'] . "'
                    , '" . $sel['count'] . "'
                    , '" . $sel['first_name'] . "'
                    , '" . $sel['last_name'] . "'
                    , '" . $sel['follower_count'] . "'
                    , '" . $sel['following_count'] . "'
                    , '" . $sel['image'] . "'
                    , '" . $sel['website'] . "'
                    , '" . $sel['facebook'] . "'
                    , '" . $sel['twitter'] . "'
                    , '" . $sel['location'] . "'
                    , '" . $sel['board_count'] . "'
                    , '" . $sel['pin_count'] . "'
                    , '" . $sel['like_count'] . "'
                    , '" . $sel['created_at'] . "'
                    , '" . $sel['timestamp'] . "'
                    )";
            }
        }

        if ($acc != "") {
            $acc .= "
            ON DUPLICATE KEY UPDATE
            username = VALUES(username),
            domain_mentions = VALUES(domain_mentions),
            first_name = VALUES(first_name),
            last_name = VALUES(last_name),
            follower_count = VALUES(follower_count),
            following_count = VALUES(following_count),
            image = VALUES(image),
            website = VALUES(website),
            facebook = VALUES(facebook),
            twitter = VALUES(twitter),
            location = VALUES(location),
            board_count = VALUES(board_count),
            pin_count = VALUES(pin_count),
            like_count = VALUES(like_count),
            created_at = VALUES(created_at),
            timestamp = VALUES(timestamp)";

            $DBH->query($acc);
        }


        CLI::write(Log::debug("Inserting user_ids into status_footprint table", $log_context));
        $acc = "";
        foreach ($select_array as $sel) {

            if ($acc == "") {
                $acc .= "INSERT IGNORE into status_footprint
                    (user_id, track_type, last_run)
                    VALUES
                    ('" . $sel['user_id'] . "', 'influencer', 0)";
            } else {
                $acc .= ",
                    ('" . $sel['user_id'] . "', 'influencer', 0)";
            }
        }

        if ($acc != "") {
            $DBH->query($acc);
        }

        CLI::write(Log::debug("Inserted Domain Influencers Calculations", $log_context));

    }
}

function calculateDomainPins($domain)
{
    $DBH = DatabaseInstance::DBO();
    $DBH->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);


    $domain = mysql_real_escape_string($domain['domain']);


    /**
     * TODO: This query requires "optimizer_switch=index_condition_pushdown=off"
     * TODO: option to be set until bug is fixed in MariaDB / TokuDB.
     */

    $log_context = array();
    $log_context['domain'] = $domain['domain'];

    /*
     * For each period (all-time, 7 days, 14 days, 30 days) cache the top pin results for
     * this domain based on most repins, most likes and most comments.
     */
    $sorts   = array("repin_count", "like_count", "comment_count");
    $periods = array(0, 60, 30, 14, 7);

    foreach ($periods as $period) {

        $log_context['period'] = $period;

        /*
         * Remove previous data from cache_domain_pins table for this period/domain
         */
        removeCurrentDomainPinData($domain, $period);

        CLI::write(Log::debug("Calculating Domain Pins for period ($period).", $log_context));

        foreach ($sorts as $sort) {

            $log_context['sort'] = $sort;

            $start = microtime(true);

            $select_array = array();
            $acc          = $DBH->query("SELECT
                    domain
                    , pin_id
                    , user_id
                    , board_id
                    , method
                    , is_repin
                    , parent_pin
                    , via_pinner
                    , origin_pin
                    , origin_pinner
                    , image_url
                    , link
                    , description
                    , dominant_color
                    , repin_count
                    , like_count
                    , comment_count
                    , created_at
                    , unix_timestamp(now())

                FROM
                    data_pins_new
                    " . ($period == 0 ? "use index (domain_repin_count_idx)" : "use index (domain_created_at_idx)" ) . "
                WHERE
                    domain = '$domain'
                    " . ($period == 0 ? "" : "and created_at > " . strtotime("-$period Days", getFlatDate(time()))) . "
                ORDER BY $sort desc limit 100")->fetchAll();

            foreach($acc as $a){

                $pin_id = $a['pin_id'];

                if ($a['parent_pin'] == "") {
                    $parent_pin = 0;
                    $via_pinner = 0;
                } else {
                    $parent_pin = $a['parent_pin'];
                    $via_pinner = $a['via_pinner'];
                }

                if ($a['origin_pin'] == "") {
                    $origin_pin    = 0;
                    $origin_pinner = 0;
                } else {
                    $origin_pin    = $a['origin_pin'];
                    $origin_pinner = $a['origin_pinner'];
                }

                $select_array["$pin_id"]                   = array();
                $select_array["$pin_id"]['pin_id']         = $pin_id;
                $select_array["$pin_id"]['user_id']        = $a['user_id'];
                $select_array["$pin_id"]['board_id']       = $a['board_id'];
                $select_array["$pin_id"]['method']         = $a['method'];
                $select_array["$pin_id"]['is_repin']       = $a['is_repin'];
                $select_array["$pin_id"]['parent_pin']     = $parent_pin;
                $select_array["$pin_id"]['via_pinner']     = $via_pinner;
                $select_array["$pin_id"]['origin_pin']     = $origin_pin;
                $select_array["$pin_id"]['origin_pinner']  = $origin_pinner;
                $select_array["$pin_id"]['image_url']      = mysql_real_escape_string($a['image_url']);
                $select_array["$pin_id"]['link']           = mysql_real_escape_string($a['link']);
                $select_array["$pin_id"]['description']    = mysql_real_escape_string($a['description']);
                $select_array["$pin_id"]['dominant_color'] = mysql_real_escape_string($a['dominant_color']);
                $select_array["$pin_id"]['repin_count']    = $a['repin_count'];
                $select_array["$pin_id"]['like_count']     = $a['like_count'];
                $select_array["$pin_id"]['comment_count']  = $a['comment_count'];
                $select_array["$pin_id"]['created_at']     = $a['created_at'];
                $select_array["$pin_id"]['timestamp']      = time();
            }

            $end = microtime(true);

            $log_context['run_time'] = ($end - $start);

            CLI::write(Log::debug("Completed Domain Pins ($domain):: $period days, sorted by $sort:: " . ($end - $start) . " seconds.", $log_context));

            unset($log_context['run_time']);

            $acc = "";
            foreach ($select_array as $sel) {

                if ($acc == "") {
                    $acc .= "INSERT INTO
                cache_domain_pins
                 (domain
                    , period
                    , pin_id
                    , user_id
                    , board_id
                    , method
                    , is_repin
                    , parent_pin
                    , via_pinner
                    , origin_pin
                    , origin_pinner
                    , image_url
                    , link
                    , description
                    , dominant_color
                    , repin_count
                    , like_count
                    , comment_count
                    , created_at
                    , timestamp)

                        VALUES
                        ('$domain'
                        , '$period'
                        , '" . $sel['pin_id'] . "'
                        , '" . $sel['user_id'] . "'
                        , '" . $sel['board_id'] . "'
                        , '" . $sel['method'] . "'
                        , '" . $sel['is_repin'] . "'
                        , '" . $sel['parent_pin'] . "'
                        , '" . $sel['via_pinner'] . "'
                        , '" . $sel['origin_pin'] . "'
                        , '" . $sel['origin_pinner'] . "'
                        , '" . $sel['image_url'] . "'
                        , '" . $sel['link'] . "'
                        , '" . $sel['description'] . "'
                        , '" . $sel['dominant_color'] . "'
                        , '" . $sel['repin_count'] . "'
                        , '" . $sel['like_count'] . "'
                        , '" . $sel['comment_count'] . "'
                        , '" . $sel['created_at'] . "'
                        , '" . $sel['timestamp'] . "'
                        )";
                } else {
                    $acc .= ",
                        ('$domain'
                        , '$period'
                        , '" . $sel['pin_id'] . "'
                        , '" . $sel['user_id'] . "'
                        , '" . $sel['board_id'] . "'
                        , '" . $sel['method'] . "'
                        , '" . $sel['is_repin'] . "'
                        , '" . $sel['parent_pin'] . "'
                        , '" . $sel['via_pinner'] . "'
                        , '" . $sel['origin_pin'] . "'
                        , '" . $sel['origin_pinner'] . "'
                        , '" . $sel['image_url'] . "'
                        , '" . $sel['link'] . "'
                        , '" . $sel['description'] . "'
                        , '" . $sel['dominant_color'] . "'
                        , '" . $sel['repin_count'] . "'
                        , '" . $sel['like_count'] . "'
                        , '" . $sel['comment_count'] . "'
                        , '" . $sel['created_at'] . "'
                        , '" . $sel['timestamp'] . "'
                        )";
                }
            }

            if ($acc != "") {
                $acc .= "
                ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id),
                board_id = VALUES(board_id),
                method = VALUES(method),
                is_repin = VALUES(is_repin),
                parent_pin = VALUES(parent_pin),
                via_pinner = VALUES(via_pinner),
                origin_pin = VALUES(origin_pin),
                origin_pinner = VALUES(origin_pinner),
                image_url = VALUES(image_url),
                link = VALUES(link),
                description = VALUES(description),
                repin_count = VALUES(repin_count),
                like_count = VALUES(like_count),
                comment_count = VALUES(comment_count),
                created_at = VALUES(created_at),
                timestamp = VALUES(timestamp)";

                $DBH->query($acc);
            }

            CLI::write(Log::debug("Inserted Domain Pins Calculations.", $log_context));
        }
    }
}

function calculateDailyDomainData($domain)
{

    $DBH = DatabaseInstance::DBO();
    $DBH->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $domain = mysql_real_escape_string($domain['domain']);
    $website_calcs = array();
    $current_date  = getFlatDate(time());

    $log_context = array();
    $log_context['domain'] = $domain['domain'];

    /*
     * get the oldest pin date and fill in any days we dont have pins/pinners for with 0s
     */
    $acc = $DBH->query(
               "select min(created_at) from data_pins_new
               where domain='$domain' and created_at > 100000000")->fetchAll();

    foreach($acc as $a){
        $oldest_date_with_pins = $a['min(created_at)'];
    }

    if (!empty($oldest_date_with_pins)) {
        $oldest_date_with_pins = getFlatDate($oldest_date_with_pins);
    } else {
        $oldest_date_with_pins = strtotime("-1 day", $current_date);
    }

    /*
     * Create empty values for each day since the oldest pin date, so that days without any pins
     * or pinners can have 0 values.
     */
    $start = new DateTime();
    $end   = new DateTime();
    $start->setTimestamp($oldest_date_with_pins);
    $end->setTimestamp(strtotime("+1 day", $current_date));
    $period = new DatePeriod($start, new DateInterval('P1D'), $end);

    //populate all dates with 0s to start
    foreach ($period as $dt) {

        //get timestamp for each day
        $dtt = $dt->getTimestamp();
        if (!isset($website_calcs["$dtt"])) {
            $website_calcs["$dtt"]                           = array();
            $website_calcs["$dtt"][0]                        = array();
            $website_calcs["$dtt"][0]['timestamp']           = $dtt;
            $website_calcs["$dtt"][0]['is_repin']            = 0;
            $website_calcs["$dtt"][0]['daily_pin_count']     = 0;
            $website_calcs["$dtt"][0]['daily_pinner_count']  = 0;
            $website_calcs["$dtt"][0]['daily_repin_count']   = 0;
            $website_calcs["$dtt"][0]['daily_like_count']    = 0;
            $website_calcs["$dtt"][0]['daily_comment_count'] = 0;
            $website_calcs["$dtt"][0]['daily_reach']         = 0;

            $website_calcs["$dtt"][1]                        = array();
            $website_calcs["$dtt"][1]['timestamp']           = $dtt;
            $website_calcs["$dtt"][1]['is_repin']            = 1;
            $website_calcs["$dtt"][1]['daily_pin_count']     = 0;
            $website_calcs["$dtt"][1]['daily_pinner_count']  = 0;
            $website_calcs["$dtt"][1]['daily_repin_count']   = 0;
            $website_calcs["$dtt"][1]['daily_like_count']    = 0;
            $website_calcs["$dtt"][1]['daily_comment_count'] = 0;
            $website_calcs["$dtt"][1]['daily_reach']         = 0;
        }

    }

    /*
     * Get Daily counts
     */
    CLI::write(Log::debug("Getting Daily Domain Counts for domain ($domain)", $log_context));

    $acc = $DBH->query("select FROM_UNIXTIME(a.created_at,'%Y-%m-%d') as mentioned_day
                                        , a.is_repin
                                        , count(a.pin_id) as pins
                                        , count(distinct a.user_id) as pinners
                                        , sum(a.repin_count) as repins
                                        , sum(a.like_count) as likes
                                        , sum(a.comment_count) as comments
                                        , sum(b.follower_count) as reach
                                from
                                        data_pins_new a use index (domain_created_at_idx)
                                        join data_profiles_new b on a.user_id = b.user_id
                                WHERE a.domain = '$domain'
                                group by mentioned_day, is_repin
                                order by mentioned_day desc")->fetchAll();

    foreach($acc as $a){
        $query_date = strtotime($a['mentioned_day']);
        $is_repin   = $a['is_repin'];

        if(!isset($website_calcs["$query_date"])){
            $website_calcs["$query_date"] = array();
        }

        if(!isset($website_calcs["$query_date"][$is_repin])) {
            $website_calcs["$query_date"][$is_repin] = array();
        }

        $website_calcs["$query_date"][$is_repin]['timestamp']           = $query_date;
        $website_calcs["$query_date"][$is_repin]['is_repin']            = $is_repin;
        $website_calcs["$query_date"][$is_repin]['daily_pin_count']     = $a['pins'];
        $website_calcs["$query_date"][$is_repin]['daily_pinner_count']  = $a['pinners'];
        $website_calcs["$query_date"][$is_repin]['daily_repin_count']   = $a['repins'];
        $website_calcs["$query_date"][$is_repin]['daily_like_count']    = $a['likes'];
        $website_calcs["$query_date"][$is_repin]['daily_comment_count'] = $a['comments'];
        $website_calcs["$query_date"][$is_repin]['daily_reach']         = $a['reach'];

    }

    krsort($website_calcs);

    /*
     * Write data to the cache_domain_daily_counts (update existing values if they exist)
     */
    $acc = "";
    foreach ($website_calcs as $select) {
        foreach($select as $sel){
            if ($acc == "") {
                $acc .= "INSERT INTO
                    cache_domain_daily_counts
                     (domain
                        , date
                        , is_repin
                        , pin_count
                        , pinner_count
                        , repin_count
                        , like_count
                        , comment_count
                        , reach)

                            VALUES
                            ('$domain'
                            , '" . $sel['timestamp'] . "'
                            , '" . $sel['is_repin'] . "'
                            , '" . $sel['daily_pin_count'] . "'
                            , '" . $sel['daily_pinner_count'] . "'
                            , '" . $sel['daily_repin_count'] . "'
                            , '" . $sel['daily_like_count'] . "'
                            , '" . $sel['daily_comment_count'] . "'
                            , '" . $sel['daily_reach'] . "'
                            )";
            } else {
                $acc .= ",
                            ('$domain'
                            , '" . $sel['timestamp'] . "'
                            , '" . $sel['is_repin'] . "'
                            , '" . $sel['daily_pin_count'] . "'
                            , '" . $sel['daily_pinner_count'] . "'
                            , '" . $sel['daily_repin_count'] . "'
                            , '" . $sel['daily_like_count'] . "'
                            , '" . $sel['daily_comment_count'] . "'
                            , '" . $sel['daily_reach'] . "'
                            )";
            }
        }
    }

    if ($acc != "") {
        $acc .= "
                ON DUPLICATE KEY UPDATE
                pin_count = VALUES(pin_count),
                pinner_count = VALUES(pinner_count),
                repin_count = VALUES(repin_count),
                like_count = VALUES(like_count),
                comment_count = VALUES(comment_count),
                reach = VALUES(reach)";

        $DBH->query($acc);
    }

    CLI::write(Log::debug("Inserted Daily Domain Counts for domain ($domain)", $log_context));

}

function calculateDomainHistory($domain)
{

    $DBH = DatabaseInstance::DBO();
    $DBH->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $domain                = mysql_real_escape_string($domain['domain']);
    $unique_domain_pinners = 0;
    $start                 = microtime(true);
    $timestamp             = time();
    $date                  = getFlatDate(time());
    $yesterday_flat_date   = strtotime("-1 day", $date);


    $log_context = array();
    $log_context['domain'] = $domain['domain'];
    /*
     * First we check the latest calculation record date to ensure we're not missing any
     * days of history
     */
    $latest_domain_calc_date = 0;
    $acc                     = $DBH->query(
                                   "select max(date) from calcs_domain_history
                                   where domain = '$domain'")->fetchAll();

    foreach($acc as $a){
        $latest_domain_calc_date = $a['max(date)'];
    }

    /**
     * Check to see if the latest date is earlier than yesterday, and if it is, then fill in
     * the missing records (depending on how many are missing, some of these may actually take
     * a while).
     *
     * If there are no date records at all for this domain, it is a new one and we skip this
     * part
     *
     * This should not run at all during normal operation
     */
    if ($latest_domain_calc_date < $yesterday_flat_date && $latest_domain_calc_date != 0) {


        CLI::write(Log::debug("$domain :: Found missing historical records.  Starting Calculations to fill them", $log_context));
        /*
         * Get the next date to calculate the historical record for (+1 day from the latest one
         * in the table already)
         */
        $calc_date = strtotime("+1 day", $latest_domain_calc_date);

        /*
         * While we're still filling in missing historical records, we run a special query
         * that limits the calculation by date of the pin
         */
        $historical_calcs_count = 0;
        while ($calc_date < $date) {

            CLI::write(Log::debug("Calculate missing historical unique pinner record for $domain", $log_context));
            /*
             * Pull the unique domain pinners for the date range prior to missing date
             */
            $acc = $DBH->query("select count(distinct user_id) as unique_domain_pinners
            from data_pins_new where domain = '$domain'
            and created_at < $calc_date")->fetchAll();

            foreach($acc as $a){
                $unique_domain_pinners = $a['unique_domain_pinners'];
            }

            CLI::write(Log::debug("Insert missing historical unique pinner records for $domain", $log_context));
            /*
             * Insert the historical record
             */
            $acc = "INSERT into calcs_domain_history
                    (domain, date, unique_domain_pinners, timestamp)
                    VALUES ('$domain', '$date', '$unique_domain_pinners', '$timestamp')
                    ON DUPLICATE KEY UPDATE
                    unique_domain_pinners = VALUES(unique_domain_pinners),
                    timestamp = VALUES(timestamp)";
            $DBH->query($acc);

            print "--- filled historical calc record #$historical_calcs_count for " .
                date('Y-m-d',$calc_date) . PHP_EOL;

            /*
             * increment to the next day
             */
            $calc_date = strtotime("+1 day", $calc_date);
            $historical_calcs_count++;
        }
    }

    CLI::write(Log::debug("Finished filling missing historical records for $domain", $log_context));


    CLI::write(Log::debug("Calculate current unique domain pinners for $domain", $log_context));
    /*
     * Run Unique Domain Pinners Calculation
     */
    $acc = $DBH->query(
               "select count(distinct user_id) as unique_domain_pinners
               from data_pins_new where domain = '$domain'")->fetchAll();

    foreach($acc as $a){
        $unique_domain_pinners = $a['unique_domain_pinners'];
    }


    CLI::write(Log::debug("Insert current unique domain pinners for $domain", $log_context));
    /*
     * Insert today's historical record
     */
    $acc = "INSERT into calcs_domain_history
            (domain, date, unique_domain_pinners, timestamp)
            VALUES ('$domain', '$date', '$unique_domain_pinners', '$timestamp')
            ON DUPLICATE KEY UPDATE
            unique_domain_pinners = VALUES(unique_domain_pinners),
            timestamp = VALUES(timestamp)";
    $DBH->query($acc);

    $end = microtime(true);

    $log_context['run_time'] = ($end - $start);
    CLI::write(Log::debug("Finished Domain Unique Pinners for $domain :: " . ($end - $start) . " seconds.", $log_context));

}

function getDomainsForCalcs($track_type, $before_date, $limit)
{
    $DBH = DatabaseInstance::DBO();
    $DBH->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $domains       = array();
    $domains_fixed = array();
    $acc           = $DBH->query(
                         "select domain from status_domains
                         where last_calced < '$before_date' AND track_type = '$track_type'
                         order by last_calced asc limit $limit")->fetchAll();

    foreach($acc as $a){
        $domain = $a['domain'];

        var_dump($domain);

        $domain_item           = array();
        $domain_item['domain'] = $domain;
        array_push($domains, $domain_item);

        array_push($domains_fixed, "\"$domain\"");
    }

    if (count($domains_fixed) != 0) {
        $time = time();
        $acc  = "update status_domains set timestamp = '$time', last_calced = '$time' where domain IN (" . implode(",", $domains_fixed) . ")";
        $DBH->query($acc);
    }

    return $domains;
}


function removeCurrentDomainPinData($domain, $period)
{
    $DBH = DatabaseInstance::DBO();
    $acc = "delete from cache_domain_pins where domain = '$domain' and period = '$period'";
    $DBH->query($acc);
}

function removeCurrentDomainInfluencerData($domain, $period)
{
    $DBH = DatabaseInstance::DBO();
    $acc = "delete from cache_domain_influencers where domain = '$domain' and period = '$period'";
    $DBH->query($acc);
}


?>
