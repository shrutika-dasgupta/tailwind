<?php
//EXPLANATION
//finds which calcs to pull from
//      status_boards -> last_calced

//inserts data into the tables
//      calcs_board_history
//      calcs_board_categories

ini_set('max_execution_time', '5000');
include('../legacy/includes/functions.php');
include('../../bootstrap/bootstrap.php');

use Pinleague\CLI;
use Pinleague\Pinterest;
use Pinleague\PinterestException;

Log::setLog(__FILE__, 'CLI');

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        Log::notice('Engine running - sleeping for 30. (If script is on shell script, this should not happen.)');
        sleep(30);
        throw new EngineException('Engine is running');
    }

    $engine->start();
    Log::info('Engine started');

    $start        = microtime(true);
    $current_hour = date('G', time());
    $current_track_type = 'user';

    $DBH = DatabaseInstance::DBO();

    Log::debug('Grabbing User boards to run calcs');
    if($current_hour > 3){
        $boards = getBoardsForCalcs("user", getFlatDate(time()));
        if (count($boards) == 0) {

            $current_track_type = 'competitor';
            Log::debug('No more User boards.  Grabbing Competitor boards.');
            $boards = getBoardsForCalcs("competitor", getFlatDate(time()));
            if (count($boards) == 0) {

                $current_track_type = 'free';
                Log::debug('No more Competitor boards.  Grabbing Free boards.');
                $boards = getBoardsForCalcs("free", getFlatDate(time()));
                if (count($boards) == 0) {

                    $current_track_type = 'pinmail';
                    $boards = getBoardsForCalcs("pinmail", getFlatDate(time()));
                }
            }
        }
    } else {
        $boards = getBoardsForCalcs("user", 0);
        if (count($boards) == 0) {

            $current_track_type = 'competitor';
            $boards = getBoardsForCalcs("competitor", 0);
            if (count($boards) == 0) {

                $current_track_type = 'free';
                $boards = getBoardsForCalcs("free", 0);
                if (count($boards) == 0) {

                    $current_track_type = 'pinmail';
                    $boards = getBoardsForCalcs("pinmail", 0);
                }
            }
        }
    }

    if (count($boards) == 0) {
        Log::notice('No boards to run calcs.  Sleeping 120.');
        sleep(120);
        $engine->complete();
        exit;
    }

    Log::debug('Starting Board History Calculations.');
    calculateBoardHistory($boards);

    if ($current_track_type == 'user') {
        Log::debug('Starting Board Influencer Calculations.');
        calculateBoardInfluencers($boards);
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

function calculateBoardHistory($boards) {

    $DBH = DatabaseInstance::DBO();

    $calcs_board_histories = new CalcBoardHistories();

    foreach($boards as $board) {
        $board_id = $board['board_id'];
        $date = getFlatDate(time());

        $calcs_board_history = new CalcBoardHistory();

        $calcs_board_history->board_id  = $board_id;
        $calcs_board_history->date      = $date;
        $calcs_board_history->timestamp = time();

        $board_stats = $DBH->query(
           "SELECT user_id, follower_count AS followers, pin_count AS pins
            FROM data_boards
            WHERE board_id = '$board_id'
            AND user_id != 0
            AND track_type != 'deleted'
            AND track_type != 'footprint'
            ORDER BY last_pulled DESC
            LIMIT 1")->fetchAll();

        foreach($board_stats as $stat){
            $calcs_board_history->user_id   = $stat->user_id;
            $calcs_board_history->followers = $stat->followers;
            $calcs_board_history->pins      = $stat->pins;

            /**
             * Calculate Board Pin Metrics
             */
            $board_calcs = $DBH->query(
                "SELECT
                SUM(repin_count) as repins,
                SUM(like_count) as likes,
                SUM(comment_count) as comments,
                SUM(case when repin_count > 0 then 1 else 0 end) as pins_atleast_one_repin,
                SUM(case when like_count > 0 then 1 else 0 end) as pins_atleast_one_like,
                SUM(case when comment_count > 0 then 1 else 0 end) as pins_atleast_one_comment,
                SUM(case when (repin_count > 0 OR like_count > 0 OR comment_count > 0) then 1 else 0 end) as pins_atleast_one_engage
                from data_pins_new where board_id = '$board_id'")->fetchAll();

            foreach($board_calcs as $calc){
                $calcs_board_history->repins                   = $calc->repins;
                $calcs_board_history->likes                    = $calc->likes;
                $calcs_board_history->comments                 = $calc->comments;
                $calcs_board_history->pins_atleast_one_repin   = $calc->pins_atleast_one_repin;
                $calcs_board_history->pins_atleast_one_like    = $calc->pins_atleast_one_like;
                $calcs_board_history->pins_atleast_one_comment = $calc->pins_atleast_one_comment;
                $calcs_board_history->pins_atleast_one_engage  = $calc->pins_atleast_one_engage;
            }

            /**
             * Calculate Board Follower Metrics
             */
            $board_reach = $DBH->query(
               "SELECT
                SUM(follower_follower_count) as follower_reach
                FROM data_board_followers
                WHERE board_id = '$board_id'")->fetchAll();
            foreach($board_reach as $reach){
                $calcs_board_history->follower_reach = $reach->follower_reach;
            }
        }

        Log::debug("Finished Calcs for board_id: $board_id");

        $calcs_board_histories->add($calcs_board_history);
    }
    Log::debug('Finished Calcs for all boards', $boards);

    /*
     * Save CalcsBoardHistories models
     */
    Log::debug("Saving board history models to the calcs_board_history table");
    try{
        $calcs_board_histories->insertUpdateDB();
    } catch (CollectionException $e){
        Log::notice('No board history models found so save in calcs_board_history table');
    }
}


function calculateBoardInfluencers($boards) {

    $DBH = DatabaseInstance::DBO();

    $follower_user_ids       = array();
    $status_footprints       = new StatusFootprints();

    foreach($boards as $board) {

        $board_id                = $board['board_id'];
        $cache_board_influencers = new \Caches\CacheBoardInfluencers();

        $start = microtime(true);

        $influencers = $DBH->query("SELECT
                q.board_id
                , q.user_id
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
                (SELECT user_id, board_id, follower_user_id
                 FROM data_board_followers
                 WHERE board_id = '$board_id'
                 ORDER BY follower_follower_count DESC limit 50) as q
            JOIN data_profiles_new c
            ON q.follower_user_id = c.user_id")->fetchAll();

        foreach($influencers as $influencer){

            $follower_user_id = $influencer->follower_user_id;
            $follower_user_ids["$follower_user_id"] = $follower_user_id;

            $cache_board_influencer = new \Caches\CacheBoardInfluencer();

            $cache_board_influencer->board_id            = $board_id;
            $cache_board_influencer->user_id             = $influencer->user_id;
            $cache_board_influencer->influencer_user_id  = $influencer->follower_user_id;
            $cache_board_influencer->influencer_username = $influencer->username;
            $cache_board_influencer->first_name          = $influencer->first_name;
            $cache_board_influencer->last_name           = $influencer->last_name;
            $cache_board_influencer->follower_count      = $influencer->follower_count;
            $cache_board_influencer->following_count     = $influencer->following_count;
            $cache_board_influencer->image               = $influencer->image;
            $cache_board_influencer->website             = $influencer->website_url;
            $cache_board_influencer->facebook            = $influencer->facebook_url;
            $cache_board_influencer->twitter             = $influencer->twitter_url;
            $cache_board_influencer->location            = $influencer->location;
            $cache_board_influencer->board_count         = $influencer->board_count;
            $cache_board_influencer->pin_count           = $influencer->pin_count;
            $cache_board_influencer->like_count          = $influencer->like_count;
            $cache_board_influencer->created_at          = $influencer->created_at;

            $cache_board_influencers->add($cache_board_influencer);
        }

        /**
         * Delete previously cached influencers
         */
        removeCurrentBoardInfluencerData($board_id);

        /*
         * Save CacheBoardInfluencers models
         */
        try{
            $cache_board_influencers->insertIgnoreDB();
        } catch (CollectionException $e){
            Log::notice('No followers found so save in cache_board_influencers table');
        }

        $end = microtime(true);
        Log::debug("Board Influencers ($board_id):: " . ($end - $start) . " seconds.");
    }

    /**
     * Add all top followers to the status_footprint table to have category footprints
     * pulled and calculated, if it hasn't already been pulled.
     */
    CLI::write(Log::debug("Inserting follower_user_ids into status_footprint table"));
    foreach ($follower_user_ids as $follower_user_id) {

        $status_footprint = new StatusFootprint();

        $status_footprint->user_id    = $follower_user_id;
        $status_footprint->track_type = "influencer";
        $status_footprint->last_run   = 0;

        $status_footprints->add($status_footprint);
    }

    /*
     * Save status footprint models
     */
    $footprint_count = $status_footprints->count();
    Log::debug("Saving $footprint_count user_ids to the status_footprint table");
    try{
        $status_footprints->insertIgnoreDB();
    } catch (CollectionException $e){
        Log::notice('No user_ids found so save in status_footprint table');
    }
}

/**
 * Removes current cached influencer data for a given board_id
 */
function removeCurrentBoardInfluencerData($board_id) {

//    foreach ($boards as $board) {
//        $board_ids[] = $board['board_id'];
//    }
//    $board_ids_csv = rtrim(implode(',', $board_ids));

    $DBH = DatabaseInstance::DBO();
    $acc = "delete from cache_board_influencers where board_id = $board_id";
    $DBH->query($acc);
}


function getBoardsForCalcs($track_type, $before_date) {

    $DBH = DatabaseInstance::DBO();

    $boards = array();
    $board_ids = array();
    $boards_to_calc = $DBH->query(
               "select board_id from status_boards
               where last_calced <= '$before_date'
               AND track_type = '$track_type'
               order by last_calced asc limit 500")->fetchAll();

    foreach($boards_to_calc as $board_to_calc){
        $board = array();
        $board_id = $board_to_calc->board_id;
        $board['board_id'] = $board_id;

        array_push($boards, $board);
        array_push($board_ids, "\"$board_id\"");
    }

    if (count($board_ids) != 0) {
        $time = time();
        $acc = "update status_boards set last_calced = '$time' where board_id IN (" . implode(",", $board_ids) . ")";
        $DBH->query($acc);
    }

    return $boards;
}
?>
