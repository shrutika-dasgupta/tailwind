<?php
//EXPLANATION
//finds which calcs to pull from
//		status_profiles -> last_calced

//inserts data into the tables
//		calcs_profile_history
//		cache_profile_influencers
//		cache_profile_pins

ini_set('memory_limit', '300M');
ini_set('max_execution_time', '5000');
include('../legacy/includes/functions.php');
include('../../bootstrap/bootstrap.php');

use Pinleague\CLI;

Log::setLog(__FILE__, "CLI");

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        Log::notice('Engine running - sleeping for 30. (If script is on shell script, this should not happen.)');
        sleep(30);
        throw new EngineException('Engine is running');
    }

    $DBH = DatabaseInstance::DBO();

    $engine->start();

    $start = microtime(true);

    Log::info('Engine started');

    $current_hour = date('G', time());

    $current_track_type = 'competitor';

    /*
     * Run regular calculations only after 4am on any given day
     */
    if($current_hour > 3){

        Log::debug('Grabbing Competitor profiles to run calcs');
        $profiles = getProfilesForCalcs("competitor", getFlatDate(time()));
        if (count($profiles) == 0) {

            $current_track_type = 'free';
            Log::debug('No more Competitor profiles.  Grabbing Free profiles.');
            $profiles = getProfilesForCalcs("free", getFlatDate(time()));
            if (count($profiles) == 0) {

                $current_track_type = 'user';
                Log::debug('No more Free profiles.  Grabbing User profiles.');
                $profiles = getProfilesForCalcs("user", getFlatDate(time()));
                if (count($profiles) == 0) {

                    $current_track_type = 'pinmail';
                    $profiles = getProfilesForCalcs("pinmail", getFlatDate(time()));
                }
            }
        }
        /*
         * if between 12am-4am, we still want to check for new signups and ensure we run calculations for
         * them right away.  In this case, their "last_calced" value would be 0, so we use a different
         * function to periodically look for them in case they happen.
         */
    } else {
        $profiles = getProfilesForCalcs("competitor", 0);
        if (count($profiles) == 0) {

            $current_track_type = 'free';
            $profiles = getProfilesForCalcs("free", 0);
            if (count($profiles) == 0) {

                $current_track_type = 'user';
                $profiles = getProfilesForCalcs("user", 0);
                if (count($profiles) == 0) {

                    $current_track_type = 'pinmail';
                    $profiles = getProfilesForCalcs("pinmail", 0);
                }
            }
        }
    }

    if (count($profiles) == 0) {
        Log::notice('No profiles to run calcs. Sleeping 30.');
        sleep(30);
        $engine->complete();
        exit;
    }

    Log::debug('Starting Profile Calculations.');
    calculateProfileHistory($profiles);

    if ($current_track_type == 'user') {
        calculateProfileFollowerDistribution($profiles);
        calculateMostValuableFollowers($profiles);
    }

    foreach($profiles as $profile) {
        $user_id = $profile['user_id'];

        Log::debug('Start Calculating top profile pins.');
        calculateProfilePins($user_id);

        Log::debug('Start Calculating top profile followers.');
        calculateProfileInfluencers($user_id, $current_track_type);
    }

    $engine->complete();

    Log::runtime(). 'total runtime';
    Log::memory().' peak memory usage';

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

function calculateMostValuableFollowers($profiles) {

    $DBH = DatabaseInstance::DBO();
    $DBH->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

    $follower_user_ids         = array();
    $status_footprints         = new StatusFootprints();

    foreach($profiles as $profile) {
        $user_id    = $profile['user_id'];

        $cache_profile_influencers = new \Caches\CacheProfileInfluencers();

        $start = microtime(true);
        removeCurrentProfileInfluencerData($user_id);

        $valuable_followers = $DBH->query(
                                  "SELECT
            q.user_id
			, q.follower_user_id
			, q.boards_followed
			, q.value
			, c.username
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
          FROM
                (SELECT user_id,
                follower_user_id,
                follower_follower_count,
                count(*) AS boards_followed,
                (count(*) * follower_follower_count) AS value
                FROM data_board_followers
                WHERE user_id = '$user_id'
                GROUP BY follower_user_id
                ORDER BY value DESC
                LIMIT 500) AS q
          JOIN data_profiles_new c
          ON q.follower_user_id = c.user_id")->fetchAll();

        foreach ($valuable_followers as $follower) {

            $follower_user_id = $follower->follower_user_id;
            $follower_user_ids["$follower_user_id"] = $follower_user_id;

            $influencer                      = new \Caches\CacheProfileInfluencer();
            $influencer->user_id             = $user_id;
            $influencer->influencer_user_id  = $follower->follower_user_id;
            $influencer->boards_followed     = $follower->boards_followed;
            $influencer->value               = $follower->value;
            $influencer->influencer_username = $follower->username;
            $influencer->first_name          = $follower->first_name;
            $influencer->last_name           = $follower->last_name;
            $influencer->follower_count      = $follower->follower_count;
            $influencer->following_count     = $follower->following_count;
            $influencer->image               = $follower->image;
            $influencer->website             = $follower->website_url;
            $influencer->facebook            = $follower->facebook_url;
            $influencer->twitter             = $follower->twitter_url;
            $influencer->location            = $follower->location;
            $influencer->board_count         = $follower->board_count;
            $influencer->pin_count           = $follower->pin_count;
            $influencer->like_count          = $follower->like_count;
            $influencer->created_at          = $follower->created_at;
            $influencer->timestamp           = time();
            $cache_profile_influencers->add($influencer);
        }

        try{
            $cache_profile_influencers->insertUpdateDB();
        } catch (CollectionException $e){
            Log::notice('No valuable followers to save to the cache_profile_influencers table');
        }

        $end = microtime(true);
        Log::debug("Calculated Most Valuable Followers ($user_id):: " . ($end - $start) . " seconds.");
    }

    /**
     * Add all top followers to the status_footprint table to have category footprints
     * pulled and calculated, if it hasn't already been pulled.
     */
    Log::debug("Inserting " . count($follower_user_ids) . "follower_user_ids into status_footprint table");
    foreach ($follower_user_ids as $follower_user_id) {

        $status_footprint = new StatusFootprint();

        $status_footprint->user_id    = $follower_user_id;
        $status_footprint->track_type = "influencer";
        $status_footprint->last_run   = 0;

        $status_footprints->add($status_footprint);
    }
}

function calculateProfileFollowerDistribution($profiles) {

    $DBH = DatabaseInstance::DBO();
    $DBH->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

    $cache_profile_follower_distributions = new \Caches\CacheProfileFollowerDistributions();

    foreach($profiles as $profile) {
        $user_id    = $profile['user_id'];
        removeCurrentProfileFollowerDistributionData($user_id);

        $follower_distributions = $DBH->query(
                                      "SELECT a.count as boards_followed,
                              count(*) followers FROM
                                (SELECT follower_user_id, count(*) as count
                                 FROM data_board_followers
                                 WHERE user_id = '$user_id'
                                 GROUP BY follower_user_id
                                 ORDER BY count desc) AS a
                              GROUP BY a.count;")->fetchAll();

        foreach($follower_distributions as $distribution){
            $cache_profile_follower_distribution                  = new \Caches\CacheProfileFollowerDistribution();
            $cache_profile_follower_distribution->user_id         = $user_id;
            $cache_profile_follower_distribution->boards_followed = $distribution->boards_followed;
            $cache_profile_follower_distribution->followers       = $distribution->followers;
            $cache_profile_follower_distributions->add($cache_profile_follower_distribution);
        }
    }

    /*
     * Save CalcsProfileFollowerDistribution models
     */
    Log::debug("Saving follower distribution models to the cache_profile_follower_distribution table");
    try{
        $cache_profile_follower_distributions->insertUpdateDB();
    } catch (CollectionException $e){
        Log::notice('No follower distribution models found so save in cache_profile_follower_distribution table');
    }
}




function calculateProfileInfluencers($user_id, $current_track_type) {

    $DBH = DatabaseInstance::DBO();
    $DBH->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $start = microtime(true);

    $select_array = array();
    $acc = $DBH->query("SELECT
			q.user_id
			, q.follower_user_id
			, c.username
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
		FROM
		    (SELECT user_id, follower_user_id
		    FROM data_followers where user_id = '$user_id'
		    ORDER BY follower_followers desc limit 500) as q
        JOIN data_profiles_new c on q.follower_user_id = c.user_id
		where c.last_pulled != 0")->fetchAll();

    foreach($acc as $a){

        $this_user_id = $a['follower_user_id'];

        $select_array["$this_user_id"] = array();
        $select_array["$this_user_id"]['user_id'] = $user_id;
        $select_array["$this_user_id"]['follower_user_id'] = $a['follower_user_id'];
        $select_array["$this_user_id"]['username'] = $a['username'];
        $select_array["$this_user_id"]['first_name'] = $a['first_name'];
        $select_array["$this_user_id"]['last_name'] = $a['last_name'];
        $select_array["$this_user_id"]['follower_count'] = $a['follower_count'];
        $select_array["$this_user_id"]['following_count'] = $a['following_count'];
        $select_array["$this_user_id"]['image'] = $a['image'];
        $select_array["$this_user_id"]['website_url'] = $a['website_url'];
        $select_array["$this_user_id"]['facebook_url'] = $a['facebook_url'];
        $select_array["$this_user_id"]['twitter_url'] = $a['twitter_url'];
        $select_array["$this_user_id"]['location'] = $a['location'];
        $select_array["$this_user_id"]['board_count'] = $a['board_count'];
        $select_array["$this_user_id"]['pin_count'] = $a['pin_count'];
        $select_array["$this_user_id"]['like_count'] = $a['like_count'];
        $select_array["$this_user_id"]['created_at'] = $a['created_at'];
        $select_array["$this_user_id"]['timestamp'] = time();
    }

    $end = microtime(true);

    Log::debug("Profile Influencers ($user_id):: " . ($end - $start) . " seconds.");

    /**
     * If we are not running this for a 'user' track_type, we will not have cleared the previously
     * cached data, because we will not have run the Most Valuable Followers calculation.
     *
     * Therefore, we will want to clear the previously cached data here, so that we can refresh it.
     */
    if ($current_track_type != 'user') {
        removeCurrentProfileInfluencerData($user_id);
    }

    $acc = "";

    $insert_values = array();

    foreach($select_array as $sel){

        array_push($insert_values
            , $user_id
            , $sel['follower_user_id']
            , $sel['username']
            , $sel['first_name']
            , $sel['last_name']
            , $sel['follower_count']
            , $sel['following_count']
            , $sel['image']
            , $sel['website_url']
            , $sel['facebook_url']
            , $sel['twitter_url']
            , $sel['location']
            , $sel['board_count']
            , $sel['pin_count']
            , $sel['like_count']
            , $sel['created_at']
            , $sel['timestamp']);

        if($acc == ""){
            $acc .= "INSERT into cache_profile_influencers
                    (
                    user_id
                    , influencer_user_id
                    , influencer_username
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
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        } else {
            $acc .= ",
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        }
    }

    if ($acc != "") {
        $acc .= "
                ON DUPLICATE KEY UPDATE
                influencer_user_id = VALUES(influencer_user_id),
                influencer_username = VALUES(influencer_username),
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

        $STH = $DBH->prepare($acc);
        $STH->execute($insert_values);
    }

    Log::debug("Inserting follower_user_ids into status_footprint table");
    $acc = "";
    foreach ($select_array as $sel) {

        if ($acc == "") {
            $acc .= "INSERT IGNORE into status_footprint
                    (user_id, track_type, last_run)
                    VALUES
                    ('" . $sel['follower_user_id'] . "',  'influencer', 0)";
        } else {
            $acc .= ",
                    ('" . $sel['follower_user_id'] . "', 'influencer', 0)";
        }
    }

    if ($acc != "") {

        $DBH->query($acc);
    }
}

function calculateProfilePins($user_id) {
    $DBH = DatabaseInstance::DBO();
    $DBH->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    removeCurrentProfilePinData($user_id);

    $sorts = array("repin_count", "like_count", "comment_count", "(repin_count + like_count + comment_count)");

    foreach($sorts as $sort) {

        $start = microtime(true);

        $select_array = array();
        $acc = $DBH->query("SELECT
				user_id
				, pin_id
				, board_id
				, domain
				, is_repin
				, parent_pin
				, image_url
				, link
				, description
				, repin_count
				, like_count
				, comment_count
				, created_at
			FROM
				data_pins_new
			WHERE
				user_id = '$user_id' AND last_pulled != '0'
			ORDER BY $sort desc limit 100")->fetchAll();

        foreach($acc as $a){

            $this_pin_id = $a['pin_id'];

            $select_array["$this_pin_id"] = array();
            $select_array["$this_pin_id"]['user_id'] = $user_id;
            $select_array["$this_pin_id"]['pin_id'] = $a['pin_id'];
            $select_array["$this_pin_id"]['board_id'] = $a['board_id'];
            $select_array["$this_pin_id"]['domain'] = $a['domain'];
            $select_array["$this_pin_id"]['is_repin'] = $a['is_repin'];
            $select_array["$this_pin_id"]['parent_pin'] = $a['parent_pin'];
            $select_array["$this_pin_id"]['image_url'] = $a['image_url'];
            $select_array["$this_pin_id"]['link'] = $a['link'];
            $select_array["$this_pin_id"]['description'] = $a['description'];
            $select_array["$this_pin_id"]['repin_count'] = $a['repin_count'];
            $select_array["$this_pin_id"]['like_count'] = $a['like_count'];
            $select_array["$this_pin_id"]['comment_count'] = $a['comment_count'];
            $select_array["$this_pin_id"]['created_at'] = $a['created_at'];
            $select_array["$this_pin_id"]['timestamp'] = time();
        }

        $end = microtime(true);

        Log::debug("Profile Pins ($user_id):: Sorted by $sort ::" . ($end - $start) . " seconds.");

        $acc = "";

        $insert_values = array();

        foreach($select_array as $sel){

            array_push($insert_values
                , $sel['user_id']
                , $sel['pin_id']
                , $sel['board_id']
                , $sel['domain']
                , $sel['is_repin']
                , $sel['parent_pin']
                , $sel['image_url']
                , $sel['link']
                , $sel['description']
                , $sel['repin_count']
                , $sel['like_count']
                , $sel['comment_count']
                , $sel['created_at']
                , $sel['timestamp']);

            if($acc == ""){
                $acc .= "INSERT INTO
			cache_profile_pins
			 (user_id
			    , pin_id
			    , board_id
			    , domain
			    , is_repin
			    , parent_pin
			    , image_url
			    , link
			    , description
			    , repin_count
			    , like_count
			    , comment_count
			    , created_at
			    , timestamp)

                        VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            } else {
                $acc .= ",
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            }
        }

        if ($acc != "") {
            $acc .= "
                ON DUPLICATE KEY UPDATE
                board_id = VALUES(board_id),
                domain = VALUES(domain),
                is_repin = VALUES(is_repin),
                parent_pin = VALUES(parent_pin),
                image_url = VALUES(image_url),
                link = VALUES(link),
                description = VALUES(description),
                repin_count = VALUES(repin_count),
                like_count = VALUES(like_count),
                comment_count = VALUES(comment_count),
                created_at = VALUES(created_at),
                timestamp = VALUES(timestamp)";

            $STH = $DBH->prepare($acc);
            $STH->execute($insert_values);
        }
    }
}

function calculateProfileHistory($profiles) {

    $DBH = DatabaseInstance::DBO();
    $DBH->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

    $calcs_profile_histories = new CalcProfileHistories();

    foreach($profiles as $profile) {
        $user_id    = $profile['user_id'];
        $user_ids[] = $user_id;
        $date       = getFlatDate(time());

        $calcs_profile_history = new CalcProfileHistory();

        $calcs_profile_history->user_id   = $user_id;
        $calcs_profile_history->date      = $date;
        $calcs_profile_history->timestamp = time();
        $calcs_profile_history->estimate  = 0;

        $profile_stats = $DBH->query(
                             "select user_id, follower_count, following_count, board_count, pin_count
                   from data_profiles_new where user_id = '$user_id' limit 1")->fetchAll();

        foreach($profile_stats as $stat){

            $calcs_profile_history->follower_count  = $stat->follower_count;
            $calcs_profile_history->following_count = $stat->following_count;
            $calcs_profile_history->board_count     = $stat->board_count;
            $calcs_profile_history->pin_count       = $stat->pin_count;

            /**
             * TODO: this will need to be either removed, or somehow aggregately calculated
             * TODO: from the data_board_followers table
             */
            $follower_stats = $DBH->query(
                                  "select SUM(follower_followers) as reach
                        from data_followers where user_id = '$user_id'")->fetchAll();
            foreach($follower_stats as $stat){
                $calcs_profile_history->reach = $stat->reach;
            }

            $profile_calcs = $DBH->query(
                                 "SELECT
                SUM(repin_count) as repin_count,
                SUM(like_count) as like_count,
                SUM(comment_count) as comment_count,
                SUM(case when repin_count > 0 then 1 else 0 end) as pins_atleast_one_repin,
                SUM(case when like_count > 0 then 1 else 0 end) as pins_atleast_one_like,
                SUM(case when comment_count > 0 then 1 else 0 end) as pins_atleast_one_comment,
                SUM(case when (repin_count > 0 OR like_count > 0 OR comment_count > 0) then 1 else 0 end) as pins_atleast_one_engage
                FROM data_pins_new
                WHERE user_id = '$user_id'")->fetchAll();

            foreach($profile_calcs as $calc){
                $calcs_profile_history->repin_count              = $calc->repin_count;
                $calcs_profile_history->like_count               = $calc->like_count;
                $calcs_profile_history->comment_count            = $calc->comment_count;
                $calcs_profile_history->pins_atleast_one_repin   = $calc->pins_atleast_one_repin;
                $calcs_profile_history->pins_atleast_one_like    = $calc->pins_atleast_one_like;
                $calcs_profile_history->pins_atleast_one_comment = $calc->pins_atleast_one_comment;
                $calcs_profile_history->pins_atleast_one_engage  = $calc->pins_atleast_one_engage;
            }
        }

        $calcs_profile_histories->add($calcs_profile_history);
        Log::debug("Retrieved all profile history data for user_id: " . $user_id);
    }

    Log::debug('Finished all profile history calculations');

    /*
     * Save CalcsProfileHistories models
     */
    Log::debug("Saving profile history models to the calcs_profile_history table");
    try{
        $calcs_profile_histories->insertUpdateDB();
    } catch (CollectionException $e){
        Log::notice('No profile history models found so save in calcs_board_history table');
    }

    Log::debug("Inserted Profile Histories.");
    Log::debug("Starting check for calculating history estimation...");

    foreach ($user_ids as $user_id) {
        runProfileHistoryCalculations($user_id);
    }

    Log::debug("Finished with history estimations.");
}

function removeCurrentProfilePinData($user_id) {

    $DBH = DatabaseInstance::DBO();
    $acc = "delete from cache_profile_pins where user_id = '$user_id'";
    $DBH->query($acc);
}

function removeCurrentProfileInfluencerData($user_id) {

    $DBH = DatabaseInstance::DBO();
    $acc = "delete from cache_profile_influencers where user_id = '$user_id'";
    $DBH->query($acc);
}

function removeCurrentProfileFollowerDistributionData($user_id) {
    $DBH = DatabaseInstance::DBO();
    $acc = "delete from cache_profile_follower_distribution where user_id = '$user_id'";
    $DBH->query($acc);
}

/**
 * Calculates a user's profile history (that prior to signing up with Tailwind).
 *
 * @param int $user_id Pinterest User ID.
 *
 * @return void
 */
function runProfileHistoryCalculations($user_id)
{
    if (profileHistoryEstimatesExist($user_id)) {
        return;
    }

    $accounts = UserAccount::find(array('user_id' => $user_id));
    if (!$account = array_get($accounts, 0)) {
        return;
    }

    print "Found profile to estimate history: $user_id." . PHP_EOL . "Starting Estimation..." . PHP_EOL;

    $command = 'nohup /usr/bin/php ' . base_path() . '/engines/calculations/profile_history.php -a ' . escapeshellarg($account->account_id) . ' > /dev/null 2>/dev/null echo $!';

    exec($command);

    print "Estimation Complete." . PHP_EOL;
}

/**
 * Determines whether or not profile history estimates exist for a user.
 *
 * @param int $user_id The user ID to check for.
 *
 * @return bool
 */
function profileHistoryEstimatesExist($user_id)
{
    $db = DatabaseInstance::DBO();
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

    $results = $db->query(
                  "SELECT count(1) AS total
         FROM calcs_profile_history
         WHERE user_id = $user_id
            AND estimate = 1"
    )->fetchAll();

    $histories = array_get($results, '0');

    if ($histories && $histories->total >= 1) {
        return true;
    }

    return false;
}

function getProfilesForCalcs($track_type, $before_date) {

    $DBH = DatabaseInstance::DBO();
    $DBH->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $profiles = array();
    $profile_user_ids = array();
    $acc = $DBH->query(
               "select user_id from status_profiles
               where last_calced <= '$before_date' AND track_type = '$track_type'
               order by last_calced asc limit 300")->fetchAll();

    foreach($acc as $a){
        $profile = array();
        $profile['user_id'] = $a['user_id'];

        array_push($profiles, $profile);

        $user_id = $a['user_id'];
        array_push($profile_user_ids, "\"$user_id\"");
    }

    if (count($profile_user_ids) != 0) {
        $time = time();
        $acc = "update status_profiles set timestamp = '$time', last_calced = '$time' where user_id IN (" . implode(",", $profile_user_ids) . ")";
        $DBH->query($acc);
    }

    return $profiles;
}
?>
