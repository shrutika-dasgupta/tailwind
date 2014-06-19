<?php
//EXPLANATION
//finds which calcs to pull from
//		status_traffic -> last_calced

//inserts data into the tables
//		cache_traffic_influencers
//		cache_traffic_pins

error_reporting(E_ALL);
ini_set('display_errors', 'On');
ini_set('max_execution_time', '5000');
include('../legacy/includes/functions.php');
include('../../bootstrap/bootstrap.php');

use Pinleague\CLI;

Log::setLog(__FILE__);

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        throw new EngineException('Engine is running');
    } else {

        $DBH = DatabaseInstance::DBO();

        $engine->start();

        $start = microtime(true);

        CLI::write(Log::info('Engine started'));


        CLI::write(Log::debug('Grabbing Traffic Profiles to run calcs'));
        $traffic_settings = getTrafficSettingsForCalcs("user", getFlatDate(time()));

        if (count($traffic_settings) == 0) {
            CLI::write(Log::notice('No traffic settings to run calcs.'));
            $engine->complete();
            exit;
        }

        foreach($traffic_settings as $traffic_setting) {

            CLI::write(Log::debug("Starting traffic calculations for traffic_id: " . $traffic_setting['traffic_id']));

            CLI::write(Log::debug("Starting traffic pins calculations."));
            calculateTrafficPins($traffic_setting['traffic_id']);
            CLI::write(Log::info("Finished traffic pins."));

            CLI::write(Log::debug("Starting traffic influencers."));
            calculateTrafficInfluencers($traffic_setting['traffic_id']);
            CLI::write(Log::info("Finished keyword influencers."));

            CLI::write(Log::debug("All calculations for traffic_id: " . $traffic_setting['traffic_id'] . " Finished!"));;
        }

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

function calculateTrafficPins($traffic_id) {

    $DBH = DatabaseInstance::DBO();
    $DBH->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $sorts = array("sum(visits)", "sum(revenue)", "sum(transactions)");
    $periods = array(0,60,30,14,7);

    /*
    * run most valuable pins by time period
    */
    foreach($periods as $period) {

        removeCurrentTrafficPinData($traffic_id, $period);

        foreach($sorts as $sort) {
            $sql = "";

            $acc = $DBH->query("select
                    q1.pin_id as pin_id
                    , q1.visits
                    , q1.revenue
                    , q1.transactions

                    , b.domain as domain
                    , b.user_id as user_id
                    , b.board_id as board_id
                    , b.method as method
                    , b.is_repin as is_repin
                    , b.parent_pin as parent_pin
                    , b.via_pinner as via_pinner
                    , b.origin_pin as origin_pin
                    , b.origin_pinner as origin_pinner
                    , b.image_url as image
                    , b.link as link
                    , b.description as description
                    , b.repin_count as repin_count
                    , b.like_count as like_count
                    , b.comment_count as comment_count
                    , b.created_at as created_at
                    FROM
                        (select
                        pin_id
                        , sum(visits) as visits
                        , sum(revenue) as revenue
                        , sum(transactions) as transactions
                        from data_traffic_pins
                        where traffic_id = '$traffic_id'
                        ".($period==0 ? "" : "and date > ".strtotime("-$period Days",getFlatDate(time())))."
                        GROUP BY pin_id
                        ORDER BY $sort desc limit 500)
                    as q1
                    left join data_pins_new b
                    on q1.pin_id = b.pin_id
                    where b.created_at is not null")->fetchAll();

            $insert_values = array();

            foreach($acc as $a){
                $pin_id = $a['pin_id'];
                $visits = $a['visits'];
                $revenue = $a['revenue'];
                $transactions = $a['transactions'];
                $domain = $a['domain'];
                $user_id = $a['user_id'];
                $board_id = $a['board_id'];
                $method = $a['method'];
                $is_repin = $a['is_repin'];
                $parent_pin = $a['parent_pin'];
                $via_pinner = $a['via_pinner'];
                $origin_pin = $a['origin_pin'];
                $origin_pinner = $a['origin_pinner'];
                $image = $a['image'];
                $link = $a['link'];
                $description = $a['description'];
                $repin_count = $a['repin_count'];
                $like_count = $a['like_count'];
                $comment_count = $a['comment_count'];
                $created_at = $a['created_at'];

                $timestamp = time();

                array_push($insert_values
                    , $traffic_id
                    , $period
                    , $pin_id
                    , $visits
                    , $transactions
                    , $revenue
                    , $domain
                    , $user_id
                    , $board_id
                    , $method
                    , $is_repin
                    , $parent_pin
                    , $via_pinner
                    , $origin_pin
                    , $origin_pinner
                    , $image
                    , $link
                    , $description
                    , $repin_count
                    , $like_count
                    , $comment_count
                    , $created_at
                    , $timestamp);

                if ($sql == "") {
                    $sql .= "INSERT INTO cache_traffic_pins
                    (traffic_id
                    , period
                    , pin_id
                    , visits
                    , transactions
                    , revenue
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
                    , repin_count
                    , like_count
                    , comment_count
                    , created_at
                    , timestamp)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                } else {
                    $sql .= ",
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                }


            }

            if ($sql != "") {
                $sql .= "
                    ON DUPLICATE KEY UPDATE visits = VALUES(visits)
                    , revenue = VALUES(revenue)
                    , transactions = VALUES(transactions)
                    , domain = VALUES(domain)
                    , user_id = VALUES(user_id)
                    , board_id = VALUES(board_id)
                    , method = VALUES(method)
                    , is_repin = VALUES(is_repin)
                    , parent_pin = VALUES(parent_pin)
                    , via_pinner = VALUES(via_pinner)
                    , origin_pin = VALUES(origin_pin)
                    , origin_pinner = VALUES(origin_pinner)
                    , image = VALUES(image)
                    , link = VALUES(link)
                    , description = VALUES(description)
                    , repin_count = VALUES(repin_count)
                    , like_count = VALUES(like_count)
                    , comment_count = VALUES(comment_count)
                    , created_at = VALUES(created_at)
                    , timestamp = VALUES(timestamp)";

                $STH = $DBH->prepare($sql);
                $STH->execute($insert_values);
            }
        }
    }
}

function calculateTrafficInfluencers($traffic_id) {

    $DBH = DatabaseInstance::DBO();
    $DBH->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $sorts = array("visits", "revenue", "transactions");
    $periods = array(0,60,30,14,7);

    /*
    * run top revenue generators by period
    */
    foreach($periods as $period) {

        removeCurrentTrafficInfluencerData($traffic_id, $period);

        foreach($sorts as $sort) {
            $sql = "";

            $acc = $DBH->query(" select q1.user_id as user_id
                    , q1.visits
                    , q1.revenue
                    , q1.transactions

                    , c.first_name as first_name
                    , c.last_name as last_name
                    , c.follower_count as follower_count
                    , c.following_count as following_count
                    , c.image as image
                    , c.website_url as website
                    , c.facebook_url as facebook
                    , c.twitter_url as twitter
                    , c.location as location
                    , c.board_count as board_count
                    , c.pin_count as pin_count
                    , c.like_count as like_count
                    , c.created_at as created_at
                    from
                        (select user_id as user_id
                        , SUM(visits) as visits
                        , SUM(revenue) as revenue
                        , SUM(transactions) as transactions
                        from data_traffic_pins
                        where traffic_id = '$traffic_id'
                        and user_id != 0
                        ".($period==0 ? "" : "and date > ".strtotime("-$period Days",getFlatDate(time())))."
                        GROUP BY user_id
                        ORDER BY $sort desc limit 100)
                    as q1
                    left join data_profiles_new c
                    on q1.user_id = c.user_id")->fetchAll();

            $insert_values = array();

            foreach($acc as $a){
                $user_id = $a['user_id'];
                $visits = $a['visits'];
                $revenue = $a['revenue'];
                $transactions = $a['transactions'];
                $first_name = $a['first_name'];
                $last_name = $a['last_name'];
                $follower_count = $a['follower_count'];
                $following_count = $a['following_count'];
                $image = $a['image'];
                $website = $a['website'];
                $facebook = $a['facebook'];
                $twitter = $a['twitter'];
                $location = $a['location'];
                $board_count = $a['board_count'];
                $pin_count = $a['pin_count'];
                $like_count = $a['like_count'];
                $created_at = $a['created_at'];
                $timestamp = time();


                /*
                 * Check to see if user_id exists in our database, or else we'll skip this record.
                 */
                if(!empty($user_id)){
                    array_push($insert_values
                    , $traffic_id
                    , $period
                    , $user_id
                    , $visits
                    , $revenue
                    , $transactions
                    , $first_name
                    , $last_name
                    , $follower_count
                    , $following_count
                    , $image
                    , $website
                    , $facebook
                    , $twitter
                    , $location
                    , $board_count
                    , $pin_count
                    , $like_count
                    , $created_at
                    , $timestamp);

                    if ($sql == "") {
                        $sql .= "INSERT INTO cache_traffic_influencers
                        (traffic_id
                        , period
                        , influencer_user_id
                        , visits
                        , revenue
                        , transactions
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
                        , timestamp)
                            VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    } else {
                        $sql .= ",
                            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    }
                }
            }

            if ($sql != "") {
                $sql .= "
                    ON DUPLICATE KEY UPDATE visits = VALUES(visits)
                    , revenue = VALUES(revenue)
                    , transactions = VALUES(transactions)
                    , first_name = VALUES(first_name)
                    , last_name = VALUES(last_name)
                    , follower_count = VALUES(follower_count)
                    , following_count = VALUES(following_count)
                    , image = VALUES(image)
                    , website = VALUES(website)
                    , facebook = VALUES(facebook)
                    , twitter = VALUES(twitter)
                    , location = VALUES(location)
                    , board_count = VALUES(board_count)
                    , pin_count = VALUES(pin_count)
                    , like_count = VALUES(like_count)
                    , created_at = VALUES(created_at)";

                $STH = $DBH->prepare($sql);
                $STH->execute($insert_values);
            }
        }
    }
}


function removeCurrentTrafficInfluencerData($traffic_id, $period) {

    $DBH = DatabaseInstance::DBO();
    $acc = "delete from cache_traffic_influencers where traffic_id = '$traffic_id' and period = '$period'";
    $DBH->query($acc);
}

function removeCurrentTrafficPinData($traffic_id, $period) {

    $DBH = DatabaseInstance::DBO();
    $acc = "delete from cache_traffic_pins where traffic_id = '$traffic_id' and period = '$period'";
    $DBH->query($acc);
}

function getTrafficSettingsForCalcs($track_type, $before_date) {

    $DBH = DatabaseInstance::DBO();
    $DBH->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $traffic_settings = array();
    $traffic_ids = array();
    $acc = $DBH->query("select a.traffic_id
            from status_traffic a
            left join user_organizations b
            on a.org_id = b.org_id
            where (b.plan = 3 or b.plan = 4)
            and last_calced < '$before_date'
            order by a.last_calced asc limit 10")->fetchAll();

    foreach($acc as $a){
        $traffic_setting = array();
        $traffic_setting['traffic_id'] = $a['traffic_id'];

        array_push($traffic_settings, $traffic_setting);

        $traffic_id = $a['traffic_id'];
        array_push($traffic_ids, "\"$traffic_id\"");
    }

    if (count($traffic_ids) != 0) {
        $time = time();
        $acc = "update status_traffic set last_calced = '$time' where traffic_id IN (" . implode(",", $traffic_ids) . ")";
        $DBH->query($acc);
    }

    return $traffic_settings;
}

?>
