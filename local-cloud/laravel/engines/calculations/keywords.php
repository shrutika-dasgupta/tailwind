<?php
//EXPLANATION
//finds which calcs to pull from
//		status_keywords -> last_calced

//inserts data into the tables
//		cache_keyword_pins

error_reporting(E_ALL);
ini_set('display_errors', 'On');
ini_set('max_execution_time', '50000');
include('../legacy/includes/functions.php');
include('../../bootstrap/bootstrap.php');

use Pinleague\Pinterest;
use Pinleague\PinterestException;
use Pinleague\CLI;

Log::setLog(__FILE__);

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    CLI::write(Log::info('Checking how many keywords scripts are currently running'));
    exec("ps aux| grep 'keywords.php'", $processes);

    // The reason we are checking for 11 processes here is
    // because we want to make sure there are no more than 5 domains calcs scripts running
    // at any time.
    //
    // So we have to check for:
    // 0. ps aux| grep 'keywords.php' process (1)
    // 1. grep 'keywords.php' process (1)
    // 2. The parent process for each keywords.php script coming from the crontab (5)
    // 3. The actual keywords.php process (5)
    // 4. The parent process and script for this current instance of the script (2)
    // If it finds more than 14 running, then we stop the script and wait until next time.
    CLI::write(Log::debug(count($processes) . " domains.php processes running.", $processes));

    if(count($processes) > 14){
        CLI::write(Log::warning(
                      "Running too many Keyword Calculation processes..Stopping script. " . count($processes) . " keywords.php processes running (should not be greater than 14)."
                          , $processes
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

    $keywords = getKeywordsForCalcs("user", getFlatDate(time()));

    CLI::write(Log::debug('Grabbing User Keywords to run calcs'));
    if (count($keywords) == 0) {
        $keywords = getKeywordsForCalcs("keyword_tracking", getFlatDate(time()));
    }

    if (count($keywords) == 0) {
        CLI::write(Log::notice('No Keywords to run calcs'));
        $engine->complete();
        exit;
    }

    $log_context = array();

    foreach($keywords as $keyword) {

        $log_context['keyword'] = $keyword['keyword'];

        CLI::write(Log::debug("Starting keyword calculations for " . $keyword['keyword'], $log_context));

        CLI::write(Log::debug("Starting keyword pins calculations.", $log_context));
            calculateKeywordPins($keyword['keyword']);
        CLI::write(Log::info("Finished keyword pins.", $log_context));

        CLI::write(Log::debug("Starting keyword influencers.", $log_context));
            calculateKeywordInfluencers($keyword['keyword']);
        CLI::write(Log::info("Finished keyword influencers.", $log_context));

        CLI::write(Log::debug("Starting keyword domains.", $log_context));
            calculateKeywordDomains($keyword['keyword']);
        CLI::write(Log::info("Finished keyword domains.", $log_context));

        CLI::write(Log::debug("Starting daily keyword counts.", $log_context));
            calculateDailyKeywordData($keyword['keyword']);
        CLI::write(Log::info("Finished daily keyword counts.", $log_context));

        CLI::write(Log::debug("All calculations for " . $keyword['keyword'] . " finished!", $log_context));
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


function calculateKeywordDomains($keyword) {

    $DBH = DatabaseInstance::DBO();
    $DBH->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $keyword = mysql_real_escape_string($keyword);

    $sorts = array("count(pin_id)", "sum(repin_count)", "sum(like_count)", "sum(comment_count)");
    $periods = array(0,60,30,14,7);
    foreach($periods as $period) {

        removeCurrentKeywordDomainsData($keyword, $period);

        foreach($sorts as $sort) {

            $start = microtime(true);

            $select_array = array();

            $acc = $DBH->query("SELECT
                        keyword
                        , domain
                        , count(pin_id)
                        , sum(repin_count)
                        , sum(like_count)
                        , sum(comment_count)
                        , unix_timestamp(now())
                    FROM
                        map_pins_keywords
                        ".($period==0 ? "use index (keyword_domain_pin_id_cidx)" : "use index (keyword_created_at_domain_cidx)")."
                    WHERE
                        keyword = '$keyword'
                        ".($period==0 ? "" : "and created_at > ".strtotime("-$period Days",getFlatDate(time())))."
                    GROUP BY domain
                    ORDER BY $sort desc limit 100")->fetchAll();

            foreach($acc as $a){

                $domain = $a['domain'];

                $select_array["$domain"] = array();
                $select_array["$domain"]['domain'] = mysql_real_escape_string($domain);
                $select_array["$domain"]['count'] = mysql_real_escape_string($a['count(pin_id)']);
                $select_array["$domain"]['repin_count'] = $a['sum(repin_count)'];
                $select_array["$domain"]['like_count'] = $a['sum(like_count)'];
                $select_array["$domain"]['comment_count'] = $a['sum(comment_count)'];
                $select_array["$domain"]['timestamp'] = time();

            }


            $end = microtime(true);
            CLI::write(Log::debug("Keyword Domains ($keyword):: $period days, sorted by $sort:: " . ($end - $start) . " seconds."));

            $acc = "";

            foreach($select_array as $sel){

                if($acc == ""){
                    $acc .= "INSERT into cache_keyword_domains
                        (keyword
                        , period
                        , domain
                        , keyword_mentions
                        , repin_count
                        , like_count
                        , comment_count
                        , timestamp
                        )

                    VALUES
                        ('$keyword'
                        , '$period'
                        , '" . $sel['domain'] . "'
                        , '" . $sel['count'] . "'
                        , '" . $sel['repin_count'] . "'
                        , '" . $sel['like_count'] . "'
                        , '" . $sel['comment_count'] . "'
                        , '" . $sel['timestamp'] . "'
                        )";
                } else {
                    $acc .= ",
                        ('$keyword'
                        , '$period'
                        , '" . $sel['domain'] . "'
                        , '" . $sel['count'] . "'
                        , '" . $sel['repin_count'] . "'
                        , '" . $sel['like_count'] . "'
                        , '" . $sel['comment_count'] . "'
                        , '" . $sel['timestamp'] . "'
                        )";
                }
            }

            if ($acc != "") {
                $acc .= "
                ON DUPLICATE KEY UPDATE
                keyword_mentions = VALUES(keyword_mentions),
                repin_count = VALUES(repin_count),
                comment_count = VALUES(comment_count),
                like_count = VALUES(like_count),
                timestamp = VALUES(timestamp)";

                $DBH->query($acc);
            }

        }
    }
}

function calculateKeywordInfluencers($keyword) {

    $DBH = DatabaseInstance::DBO();
    $DBH->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $keyword = mysql_real_escape_string($keyword);

    $periods = array(0,60,30,14,7);

    /*
    * run all-time domain top pinners
    *
    */
    foreach($periods as $period) {

        $start = microtime(true);


        $select_array = array();
        $acc = $DBH->query("SELECT
            q1.keyword
            , q1.pinner_id
            , c.username
            , q1.count as keyword_mentions
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
        from
            (select keyword, pinner_id, count(pin_id) as count
            from map_pins_keywords
            where keyword = '$keyword'
            ".($period==0 ? "" : "and created_at > ".strtotime("-$period Days",getFlatDate(time())))."
            group by pinner_id
            order by count(pin_id) desc
            limit " . ($period == 0 ? "500" : "100") . ")
        as q1
            join data_profiles_new c
            on q1.pinner_id = c.user_id")->fetchAll();

        foreach($acc as $a){

            $user_id = $a['pinner_id'];

            $select_array["$user_id"] = array();
            $select_array["$user_id"]['user_id'] = $user_id;
            $select_array["$user_id"]['username'] = mysql_real_escape_string($a['username']);
            $select_array["$user_id"]['count'] = $a['keyword_mentions'];
            $select_array["$user_id"]['first_name'] = mysql_real_escape_string($a['first_name']);
            $select_array["$user_id"]['last_name'] = mysql_real_escape_string($a['last_name']);
            $select_array["$user_id"]['follower_count'] = mysql_real_escape_string($a['follower_count']);
            $select_array["$user_id"]['following_count'] = mysql_real_escape_string($a['following_count']);
            $select_array["$user_id"]['image'] = mysql_real_escape_string($a['image']);
            $select_array["$user_id"]['website'] = mysql_real_escape_string($a['website_url']);
            $select_array["$user_id"]['facebook'] = mysql_real_escape_string($a['facebook_url']);
            $select_array["$user_id"]['twitter'] = mysql_real_escape_string($a['twitter_url']);
            $select_array["$user_id"]['location'] = mysql_real_escape_string($a['location']);
            $select_array["$user_id"]['board_count'] = mysql_real_escape_string($a['board_count']);
            $select_array["$user_id"]['pin_count'] = mysql_real_escape_string($a['pin_count']);
            $select_array["$user_id"]['like_count'] = mysql_real_escape_string($a['like_count']);
            $select_array["$user_id"]['created_at'] = mysql_real_escape_string($a['created_at']);
            $select_array["$user_id"]['timestamp'] = time();
        }

        $end = microtime(true);
        CLI::write(Log::debug("Keyword Influencers ($keyword):: $period days:: " . ($end - $start) . " seconds."));

        removeCurrentKeywordInfluencerData($keyword, $period);

        $acc = "";

        foreach($select_array as $sel){

            if($acc == ""){
                $acc .= "INSERT into cache_keyword_influencers
                    (
                    keyword
                    , period
                    , influencer_user_id
                    , influencer_username
                    , keyword_mentions
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
                    ('$keyword'
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
                    ('$keyword'
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
            influencer_username = VALUES(influencer_username),
            keyword_mentions = VALUES(keyword_mentions),
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

        CLI::write(Log::debug("Inserting user_ids into status_footprint table"));
        $acc = "";
        foreach ($select_array as $sel) {

            if ($acc == "") {
                $acc .= "INSERT IGNORE into status_footprint
                    (user_id, last_run)
                    VALUES
                    ('" . $sel['user_id'] . "', 0)";
            } else {
                $acc .= ",
                    ('" . $sel['user_id'] . "', 0)";
            }
        }

        if ($acc != "") {
            $DBH->query($acc);
        }
    }
}

function calculateKeywordPins($keyword) {

    $DBH = DatabaseInstance::DBO();
    $DBH->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $keyword = mysql_real_escape_string($keyword);

    $sorts = array("repin_count", "like_count", "comment_count");
    $periods = array(0,60,30,14,7);

    foreach($periods as $period) {

        removeCurrentKeywordPinData($keyword, $period);


        foreach($sorts as $sort) {

            $start = microtime(true);

            $select_array = array();

            $acc = $DBH->query("SELECT
                    q1.keyword
                    , q1.pin_id
                    , a.domain
                    , a.user_id
                    , a.board_id
                    , a.method
                    , a.is_repin
                    , a.parent_pin
                    , a.via_pinner
                    , a.origin_pin
                    , a.origin_pinner
                    , a.image_url
                    , a.link
                    , a.description
                    , a.dominant_color
                    , a.repin_count
                    , a.like_count
                    , a.comment_count
                    , a.created_at
                    , unix_timestamp(now())

                FROM
                    (SELECT keyword, pin_id
                    FROM map_pins_keywords
                    WHERE keyword = '$keyword'
                    ".($period==0 ? "" : "and created_at > ".strtotime("-$period Days",getFlatDate(time())))."
                    ORDER BY $sort DESC
                    LIMIT 100)
                AS q1
                    LEFT JOIN data_pins_new a on a.pin_id = q1.pin_id")->fetchAll();

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

                $select_array["$pin_id"] = array();
                $select_array["$pin_id"]['pin_id'] = $pin_id;
                $select_array["$pin_id"]['domain'] = mysql_real_escape_string($a['domain']);
                $select_array["$pin_id"]['user_id'] = $a['user_id'];
                $select_array["$pin_id"]['board_id'] = $a['board_id'];
                $select_array["$pin_id"]['method'] = $a['method'];
                $select_array["$pin_id"]['is_repin'] = $a['is_repin'];
                $select_array["$pin_id"]['parent_pin'] = $parent_pin;
                $select_array["$pin_id"]['via_pinner'] = $via_pinner;
                $select_array["$pin_id"]['origin_pin'] = $origin_pin;
                $select_array["$pin_id"]['origin_pinner'] = $origin_pinner;
                $select_array["$pin_id"]['image'] = mysql_real_escape_string($a['image_url']);
                $select_array["$pin_id"]['link'] = mysql_real_escape_string($a['link']);
                $select_array["$pin_id"]['description'] = mysql_real_escape_string($a['description']);
                $select_array["$pin_id"]['dominant_color'] = mysql_real_escape_string($a['dominant_color']);
                $select_array["$pin_id"]['repin_count'] = $a['repin_count'];
                $select_array["$pin_id"]['like_count'] = $a['like_count'];
                $select_array["$pin_id"]['comment_count'] = $a['comment_count'];
                $select_array["$pin_id"]['created_at'] = $a['created_at'];
                $select_array["$pin_id"]['timestamp'] = time();
            }

            $end = microtime(true);
            CLI::write(Log::debug("Keyword Pins ($keyword):: $period days, sorted by $sort:: " . ($end - $start) . " seconds."));

            $acc = "";

            foreach($select_array as $sel){

                if($acc == ""){
                    $acc .= "INSERT INTO
                cache_keyword_pins
                 (keyword
                    , period
                    , pin_id
                    , domain
                    , user_id
                    , board_id
                    , method
                    , is_repin
                    , parent_pin
                    , via_pinner
                    , origin_pin
                    , origin_pinner
                    , image
                    , link
                    , description
                    , dominant_color
                    , repin_count
                    , like_count
                    , comment_count
                    , created_at
                    , timestamp)

                    VALUES
                        ('$keyword'
                        , '$period'
                        , '" . $sel['pin_id'] . "'
                        , '" . $sel['domain'] . "'
                        , '" . $sel['user_id'] . "'
                        , '" . $sel['board_id'] . "'
                        , '" . $sel['method'] . "'
                        , '" . $sel['is_repin'] . "'
                        , '" . $sel['parent_pin'] . "'
                        , '" . $sel['via_pinner'] . "'
                        , '" . $sel['origin_pin'] . "'
                        , '" . $sel['origin_pinner'] . "'
                        , '" . $sel['image'] . "'
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
                        ('$keyword'
                        , '$period'
                        , '" . $sel['pin_id'] . "'
                        , '" . $sel['domain'] . "'
                        , '" . $sel['user_id'] . "'
                        , '" . $sel['board_id'] . "'
                        , '" . $sel['method'] . "'
                        , '" . $sel['is_repin'] . "'
                        , '" . $sel['parent_pin'] . "'
                        , '" . $sel['via_pinner'] . "'
                        , '" . $sel['origin_pin'] . "'
                        , '" . $sel['origin_pinner'] . "'
                        , '" . $sel['image'] . "'
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
                board_id = VALUES(board_id),
                domain = VALUES(domain),
                method = VALUES(method),
                is_repin = VALUES(is_repin),
                parent_pin = VALUES(parent_pin),
                via_pinner = VALUES(via_pinner),
                origin_pin = VALUES(origin_pin),
                origin_pinner = VALUES(origin_pinner),
                image = VALUES(image),
                link = VALUES(link),
                description = VALUES(description),
                dominant_color = VALUES(dominant_color),
                repin_count = VALUES(repin_count),
                like_count = VALUES(like_count),
                comment_count = VALUES(comment_count),
                created_at = VALUES(created_at),
                timestamp = VALUES(timestamp)";

                $DBH->query($acc);
            }
        }
    }
}


function calculateDailyKeywordData($keyword) {

    $DBH = DatabaseInstance::DBO();
    $DBH->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $keyword = mysql_real_escape_string($keyword);

    $keyword_calcs = array();
    $current_date  = getFlatDate(time());

    $start = microtime(true);

    /*
     * get the oldest pin date and fill in any days we dont have pins/pinners for with 0s
     */
    $acc = $DBH->query(
               "select min(created_at) from map_pins_keywords
               where keyword='$keyword'")->fetchAll();

    foreach($acc as $a){
        $oldest_date_with_pins = $a['min(created_at)'];
    }

    $end = microtime(true);
    CLI::write(Log::debug("Keyword First Pin Date ($keyword):: " . ($end - $start) . " seconds."));

    if(!empty($oldest_date_with_pins)){
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
        if (!isset($keyword_calcs["$dtt"])) {
            $keyword_calcs["$dtt"]                        = array();
            $keyword_calcs["$dtt"]['timestamp']           = $dtt;
            $keyword_calcs["$dtt"]['daily_pin_count']     = 0;
            $keyword_calcs["$dtt"]['daily_pinner_count']  = 0;
            $keyword_calcs["$dtt"]['daily_repin_count']   = 0;
            $keyword_calcs["$dtt"]['daily_like_count']    = 0;
            $keyword_calcs["$dtt"]['daily_comment_count'] = 0;
            $keyword_calcs["$dtt"]['daily_reach']         = 0;
        }
    }


    $start = microtime(true);

    /*
     * Get Daily counts
     */
    $acc = $DBH->query("select FROM_UNIXTIME(c.created_at,'%Y-%m-%d') as mentioned_day
					, count(c.pin_id) as pins
					, count(distinct c.pinner_id) as pinners
					, sum(c.repin_count) as repins
					, sum(c.like_count) as likes
					, sum(c.comment_count) as comments
					, sum(b.follower_count) as reach
				from
					map_pins_keywords c use index (keyword_created_at_pinner_id_cidx)
					left join data_profiles_new b on c.pinner_id=b.user_id
				WHERE c.keyword = '$keyword'
				group by mentioned_day
				order by mentioned_day desc;")->fetchAll();

    foreach($acc as $a){
        $query_date      = strtotime($a['mentioned_day']);

        if (!isset($keyword_calcs["$query_date"])) {
            $keyword_calcs["$query_date"]                        = array();
            $keyword_calcs["$query_date"]['timestamp']           = $query_date;
            $keyword_calcs["$query_date"]['daily_pin_count']     = $a['pins'];
            $keyword_calcs["$query_date"]['daily_pinner_count']  = $a['pinners'];
            $keyword_calcs["$query_date"]['daily_repin_count']   = $a['repins'];
            $keyword_calcs["$query_date"]['daily_like_count']    = $a['likes'];
            $keyword_calcs["$query_date"]['daily_comment_count'] = $a['comments'];
            $keyword_calcs["$query_date"]['daily_reach']         = $a['reach'];
        } else {
            $keyword_calcs["$query_date"]['daily_pin_count']     = $a['pins'];
            $keyword_calcs["$query_date"]['daily_pinner_count']  = $a['pinners'];
            $keyword_calcs["$query_date"]['daily_repin_count']   = $a['repins'];
            $keyword_calcs["$query_date"]['daily_like_count']    = $a['likes'];
            $keyword_calcs["$query_date"]['daily_comment_count'] = $a['comments'];
            $keyword_calcs["$query_date"]['daily_reach']         = $a['reach'];
        }
    }

    $end = microtime(true);
    CLI::write(Log::debug("Keyword Daily Counts ($keyword):: " . ($end - $start) . " seconds."));

    krsort($keyword_calcs);


    /*
     * Write data to the cache_domain_daily_counts
     */
    $acc = "";
    foreach($keyword_calcs as $sel){

        if($acc == ""){
            $acc .= "INSERT INTO
                cache_keyword_daily_counts
                 (keyword
                    , date
                    , pin_count
                    , pinner_count
                    , repin_count
                    , like_count
                    , comment_count
                    , reach)

                        VALUES
                        ('$keyword'
                        , '" . $sel['timestamp'] . "'
                        , '" . $sel['daily_pin_count'] . "'
                        , '" . $sel['daily_pinner_count'] . "'
                        , '" . $sel['daily_repin_count'] . "'
                        , '" . $sel['daily_like_count'] . "'
                        , '" . $sel['daily_comment_count'] . "'
                        , '" . $sel['daily_reach'] . "'
                        )";
        } else {
            $acc .= ",
                        ('$keyword'
                        , '" . $sel['timestamp'] . "'
                        , '" . $sel['daily_pin_count'] . "'
                        , '" . $sel['daily_pinner_count'] . "'
                        , '" . $sel['daily_repin_count'] . "'
                        , '" . $sel['daily_like_count'] . "'
                        , '" . $sel['daily_comment_count'] . "'
                        , '" . $sel['daily_reach'] . "'
                        )";
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

}

function removeCurrentKeywordPinData($keyword, $period) {

    $DBH = DatabaseInstance::DBO();
    $acc = "delete from cache_keyword_pins where keyword = '$keyword' and period = '$period'";
    $DBH->query($acc);
}

function removeCurrentKeywordInfluencerData($keyword, $period) {

    $DBH = DatabaseInstance::DBO();
    $acc = "delete from cache_keyword_influencers where keyword = '$keyword' and period = '$period'";
    $DBH->query($acc);
}

function removeCurrentKeywordDomainsData($keyword, $period) {

    $DBH = DatabaseInstance::DBO();
    $acc = "delete from cache_keyword_domains where keyword = '$keyword' and period = '$period'";
    $DBH->query($acc);
}

function getKeywordsForCalcs($track_type, $before_date) {

    $DBH = DatabaseInstance::DBO();
    $DBH->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $keywords = array();
    $keywords_formatted = array();


    $acc = $DBH->query(
               "select keyword from status_keywords
               where track_type = '$track_type'
               and (last_calced < '$before_date' OR last_calced is null)
               order by last_calced asc limit 10")->fetchAll();

    foreach($acc as $a){
        $keyword = array();
        $keyword['keyword'] = $a['keyword'];

        array_push($keywords, $keyword);

        $keyword = $a['keyword'];
        array_push($keywords_formatted, "\"$keyword\"");
        var_dump($keywords_formatted);
    }

    $new_keyword_refresh = array();
    $new_keyword_refresh[0]['last_calc'] = strtotime("-15 minutes");
    $new_keyword_refresh[0]['threshold'] = strtotime("-1 hours");
    $new_keyword_refresh[0]['name']      = "last hour";

    $new_keyword_refresh[1]['last_calc'] = strtotime("-1 hours");
    $new_keyword_refresh[1]['threshold'] = strtotime("-3 hours");
    $new_keyword_refresh[1]['name']      = "last 3 hours";

    $new_keyword_refresh[2]['last_calc'] = strtotime("-2 hours");
    $new_keyword_refresh[2]['threshold'] = strtotime("-6 hours");
    $new_keyword_refresh[2]['name']      = "last 6 hours";

    $new_keyword_refresh[3]['last_calc'] = strtotime("-4 hours");
    $new_keyword_refresh[3]['threshold'] = strtotime("-24 hours");
    $new_keyword_refresh[3]['name']      = "last 24 hours";

    foreach($new_keyword_refresh as $nk){
        $new_keyword_last_calc = $nk['last_calc'];
        $new_keyword_threshold = $nk['threshold'];
        $new_keyword_name      = $nk['name'];

        $acc = $DBH->query(
                   "select keyword from status_keywords
                   where track_type = '$track_type' and last_calced < '$new_keyword_last_calc'
                   AND added_at > '$new_keyword_threshold'
                   order by last_calced asc limit 10")->fetchAll();

        foreach($acc as $a){
            $keyword = array();
            $keyword['keyword'] = $a['keyword'];

            if(!in_array($keyword,$keywords)){
                array_push($keywords, $keyword);
            }
            $keyword = $a['keyword'];

            if(!in_array("\"$keyword\"",$keywords_formatted)){
                array_push($keywords_formatted, "\"$keyword\"");
                CLI::write(Log::debug("Found a recently added keyword ($new_keyword_name) to update: $keyword"));
            }
        }
    }

    if (count($keywords_formatted) != 0) {
        $time = time();
        $acc = "update status_keywords set last_calced = '$time'
        where keyword IN (" . implode(",", $keywords_formatted) . ")";
        $DBH->query($acc);
    }

    CLI::write(Log::debug("Found " . count($keywords_formatted) . " keywords to run calcs for: " . implode(",", $keywords_formatted)));

    return $keywords;
}
