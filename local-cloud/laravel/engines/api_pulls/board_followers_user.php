<?php

/**
 * User Followers for user track_type
 *
 * @author Alex
 */
ini_set('memory_limit', '2000M');
chdir(__DIR__);

include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest,
    Pinleague\CLI;

$numberOfCallsInBatch = 20;

$worker = $argv[1];

const TRACK_TYPE = 'user';

/**
 * Workers 1-9 use new Pinterest client_id
 *
 * Workers 10+ user legacy Pinterest client_id
 */
//if ($worker < 10) {
//    $client_id       = Config::get("pinterest.new.client_id");
//    $new_client_id   = true;
//} else {
//    $client_id       = Config::get("pinterest.client_id");
//    $new_client_id   = false;
//}

$client_id       = Config::get("pinterest.new.client_id");
$new_client_id   = true;

Log::setLog(false, 'CLI', basename(__FILE__, ".php") . "-" . $worker);

try {
    CLI::h1('Starting ' . __FILE__);
    $engine = new Engine(__FILE__ . "-" . $worker);

    if ($engine->running()) {
        throw new EngineException('Engine is running');
    }

    $engine->start();
    $start = microtime(true);

    Log::info('Engine started');

    $api_rate = engine::current_call_rate($client_id);

    if ($api_rate > 40000) {
        sleep(10);
    }

    if ($api_rate > 45000) {
        Log::warning('Too many api calls | Sleep 300');
        CLI::sleep(300);
    }

    $DBH = DatabaseInstance::DBO();
    CLI::write('Connected to database');


    /**
     * The following commented out section would use the "last_pulled_followers" column in the
     * status_boards table to queue up "Board Follower" calls.
     *
     * However, we are currently not using this method since "Board Follower" calls are now
     * being queued directly from the User_Boards Engine only when there has actually been a
     * change in the number of followers for a board.
     *
     * We are leaving this code in the script in order to be able to easily switch methodology
     * in case we need to in the future.
     *
     */



    /**
     *
     *      START CALL QUEUING PROCESS
     *
     *

    $queuing_calls = new QueuedApiCalls();

    Log::debug('Looking through all the boards in the status_boards table');

    $status_boards = QueuedApiCalls::pushBoards('last_pulled_followers',
                                                TRACK_TYPE,
                                                $numberOfCallsInBatch);

    Log::debug('Add Board Followers calls to QueuedApiCalls collection');
    foreach ($status_boards as $status_board) {
        $queue = QueuedApiCall::addFromStatusBoards('Board Followers', $status_board);
        $queuing_calls->add($queue);
    }

    $count_queued_calls = $queuing_calls->count();
    Log::debug("Saving $count_queued_calls new boards to pull followers to the Queue table");
    try {
        $queuing_calls->saveModelsToDB();
    }
    catch (CollectionException $e) {
        Log::notice('No boards to save to queue');
    }

    *
    *     END CALL QUEUEING PROCESS
    */

    Log::debug('Grabbing Board Followers off the Queue');

    try {
        $calls = QueuedApiCalls::fetchAndUpdateRunning(
                               'Board Followers',
                                   TRACK_TYPE,
                                   $numberOfCallsInBatch,
                                   $new_client_id
        );
    }
    catch (NoApiCallsOnQueueException $e) {
        Log::notice('No more boards to grab off the queue.  Going idle and sleeping 60.');
        CLI::sleep(60);
        $engine->idle();
        exit;
    }

    $board_ids_from_call = array();
    foreach ($calls as $call) {
        $board_ids_from_call[] = $call->object_id;
    }

    $count_from_db = StatusBoard::getFollowersFoundCount($board_ids_from_call);
    $actual_data   = BoardFollower::getFollowerCountAndOwnerId($board_ids_from_call);

    Log::info('Making batch calls to the API', $board_ids_from_call);

    /**
     * Send calls using the appropriate client_id
     */
    if ($new_client_id){
        $response_data = $calls->send(true);
    } else {
        $response_data = $calls->send();
    }



    $bookmark_data = new QueuedApiCalls();
    Log::debug('Prepare to parse API responses.');

    $board_followers = new BoardFollowers();
    $profiles        = new Profiles();

    // Keeping a counter to track the index of the response
    $count = -1;

    foreach ($response_data as $response) {

        $count += 1;
        if (($response->code) === 0) {

            $response_message = $response->message;
            $response_code    = $response->code;
            $object_id        = $calls[$count]->object_id;
            $owner_id         = $actual_data[$object_id]['owner_user_id'];

            $follower_ids_from_api = array();

            Log::debug('Parsing through returned follower data.');

            foreach ($response->data as $data) {

                $board_follower = new BoardFollower();
                $profile        = new Profile();

                $profile->loadAPIData($data);

                $profile->timestamp = time();
                $profile->track_type = "follower";
                /**
                 * Ensure we're only adding unique profiles to the collection
                 */
                if (!in_array($follower_ids_from_api, $data->id)) {
                    $profiles->add($profile, true);
                }

                $board_follower->addViaProfile($profile, $owner_id, $object_id);
                $board_followers->add($board_follower);
                $follower_ids_from_api[] = $data->id;
            }

            // Getting all the followers from the database from the array of
            // response follower_ids
            $follower_ids_implode = implode(",", $follower_ids_from_api);
            if (!empty($follower_ids_from_api)) {

                if (isset($response->bookmark)) {

                    $STH = $DBH->query(
                               "SELECT board_id, follower_user_id
                                FROM data_board_followers USE INDEX (board_id_follower_user_id_idx)
                                WHERE board_id = $object_id AND follower_user_id IN ($follower_ids_implode)"
                    );
                    $follower_ids_in_db = $STH->fetchAll();

                    // Calculating the percentage of users present in the database based on the
                    // total number from the api.
                    $percentage        = ($count_from_db[$object_id] / $actual_data[$object_id]['follower_count'] * 100);
                    $within_percentage = ($percentage > 97) ? true : false;

                    // Checking to see if the followers we got back from the response already exist in the database
                    // If they do, we set flag to false
                    $any_new_followers = (count($follower_ids_in_db) == count($follower_ids_from_api)) ? false : true;

                    Log::debug("Bookmark found. " .
                                          "Percentage of board's followers ($object_id) we have in the DB: $percentage." .
                                          "New followers found during this run: " . ($any_new_followers ? "Yes" : "No"));


                    if ($percentage > 120) {
                        Log::error(
                           "Something wrong with Board $object_id (percent of followers: " . $percentage . "%).  We have " . $count_from_db[$object_id] .
                           "followers found in the DB, and only " . $actual_data[$object_id]['follower_count'] .
                           " follower reported for this board."
                        );
                    }

                    /**
                     * TODO: for very influential boards (> 500k followers, we might want to
                     * TODO: lower the percentage threshold required
                     */

                    if ((!$within_percentage) || $any_new_followers) {
                        $bookmark_call = QueuedApiCall::loadBookmarkFromApi($response, 'Board Followers', TRACK_TYPE, $object_id);

                        Log::debug("Adding bookmarked call to the queue");

                        $bookmark_data->add($bookmark_call);
                    }
                }

                /** @var  $call QueuedApiCall
                 *
                 * Remove call from the DB
                 */
                $call = $calls->getModel($count);
                $call->removeFromDB();

            } else {
                Log::warning('No Data returned, even with successful response.');

                $call = $calls->getModel($count);

                ApiError::create('Board Followers',
                                 $object_id,
                                 $response->message,
                                 'board_followers_' . TRACK_TYPE . '. No data returned even with a successful response',
                                 $response->code,
                                 $call->bookmark
                );

                /*
                 * Check to see if we've gotten this error more than 3 times in the last day
                 * If yes, remove it.
                 * If no, then we update the timestamp and add it back to try again
                 */
                $errors_found = ApiError::numberOfEntriesWithinTime(
                                        $call,
                                        $response->code,
                                        flat_date('day', time())
                );

                Log::debug($errors_found . " of the same error found.");

                if ($errors_found > 2) {
                    $call->removeFromDB();
                } else {
                    $call->rerunCall();
                    $call->updateTimestamp();
                }

            }
        } elseif (($response->code) === 8) {
            $call = $calls->getModel($count);
            $call->rerunCall();
            $call->updateTimestamp();

            Log::error("Code " . $response->code . ": " . $response->message);

            $method_call_rate = engine::current_call_rate($client_id, "getBoardFollowers");

            ApiError::create(
                    'Board Followers',
                        $call->object_id,
                        $response->message,
                        'Calls this hour: ' . $method_call_rate . '. board_followers_' . TRACK_TYPE . '. Line: ' . __LINE__,
                        $response->code,
                        $call->bookmark
            );

            $errors_found = ApiError::numberOfRateLimitExceptions(
                                    $call,
                                        $response->code,
                                        flat_date('hour', time())
            );

            if ($errors_found > 100) {
                Log::error("API limit exceeded. $method_call_rate getBoardFollowers calls this hour");
                CLI::sleep(5);
            } else {
                Log::error("API limit exceeded. $method_call_rate getBoardFollowers calls this hour");
                CLI::sleep(1);
            }

        } elseif (($response->code) === 40) {

            Log::warning('Board not found');

            /** @var  $call QueuedApiCall */
            $call = $calls->getModel($count);
            $call->removeFromDB();

            ApiError::create(
                    'Board Followers',
                    $call->object_id,
                    $response->message,
                    'board_followers_' . TRACK_TYPE . '. Line: ' . __LINE__,
                    $response->code,
                    $call->bookmark
            );

            /*
             * Updating track_types for this board_id so we don't continue to pull it
             */
            Log::notice(
                          "Board has not been found." .
                          " Setting track_types of this board_id to not_found/deleted" .
                          " so we don't continue to pull it."
                       );

            $STH = $DBH->prepare("UPDATE status_boards
                                    SET track_type = 'not_found'
                                    WHERE board_id = :board_id");

            $STH->execute(array(':board_id' => $call->object_id));

            $STH = $DBH->prepare("UPDATE data_boards
                                    SET track_type = 'deleted'
                                    WHERE board_id = :board_id");

            $STH->execute(array(':board_id' => $call->object_id));


        } elseif (($response->code) === 10) {

            Log::warning('Bookmark not found');

            /** @var  $call QueuedApiCall */
            $call = $calls->getModel($count);
            $call->removeFromDB();

            ApiError::create('Board Followers',
                             $call->object_id,
                             $response->message,
                             'board_followers' . TRACK_TYPE . '. Line ' . __LINE__,
                             $response->code,
                             $call->bookmark
            );

        } elseif (($response->code) === 11) {

            Log::critical('API method not found!');

            /** @var  $call QueuedApiCall */
            $call = $calls->getModel($count);
            $call->removeFromDB();

            ApiError::create('Board Followers',
                             $call->object_id,
                             $response->message,
                             'board_followers' . TRACK_TYPE . '. Line ' . __LINE__,
                             $response->code,
                             $call->bookmark
            );

        } elseif (($response->code) === 12) {

            Log::warning("Something went wrong on our end. Sorry about that.");

            ApiError::create('Board Followers',
                             $call->object_id,
                             $response->message,
                             'board_followers' . TRACK_TYPE . '. Line ' . __LINE__,
                             $response->code,
                             $call->bookmark
            );

            /** @var  $call QueuedApiCall */
            $call = $calls->getModel($count);
            $call->rerunCall();
            $call->updateTimestamp();

        } elseif (($response->code) === 16) {

            $call = $calls->getModel($count);

            Log::warning("Request didn't complete in time.");

            ApiError::create('Board Followers',
                             $call->object_id,
                             $response->message,
                             'board_followers' . TRACK_TYPE . '. Line ' . __LINE__,
                             $response->code,
                             $call->bookmark
            );

            $errors_found = ApiError::numberOfEntriesWithinTime(
                                    $call,
                                        $response->code,
                                        flat_date('hour', time()),
                                        true
            );

            Log::debug($errors_found . " of the same error found.");

            if ($errors_found > 4) {
                $call->removeFromDB();
            } else {
                $call->rerunCall();
                $call->updateTimestamp();
            }

        } else {

            Log::critical('Other Uncaught Api Error: ' . $response->code . '. Message: ' . $response->message . '');

            /** @var  $call QueuedApiCall */
            $call = $calls->getModel($count);
            $call->removeFromDB();

            ApiError::create('Board Followers',
                             $call->object_id,
                             $response->message,
                             'board_followers' . TRACK_TYPE . '. Line ' . __LINE__,
                             $response->code,
                             $call->bookmark
            );
        }
    }

    if ($bookmark_data->isEmpty()) {
        Log::notice('No bookmarked calls found on this run.');
    } else {
        $bookmark_data->insertUpdateDB();
        Log::info('Found bookmarks - adding new bookmarked calls to the queue');
    }

    Log::debug('Saving board followers');
    $follower_count = $board_followers->count();
    $board_followers->insertUpdateDB();
    Log::info("Saved $follower_count board followers");


    /**
     * Check to see if profiles have been updated within the day, and if so, we'll exclude
     * them from this update to make the insert more efficient. 
     */
    $start = microtime(true);
    $follower_ids_csv = rtrim(implode(", ", $follower_ids_from_api));
    $three_days_ago     = strtotime("-1 day", time());
    $STH                = $DBH->query("SELECT user_id, last_pulled
                                    FROM data_profiles_new
                                    WHERE user_id IN ($follower_ids_csv)
                                    AND last_pulled > $three_days_ago");
    $follower_ids_in_db = $STH->fetchAll();
    $followers_to_remove = array();
    foreach ($follower_ids_in_db as $follower) {
        $followers_to_remove[] = $follower->user_id;
    }

    $profiles_count = $profiles->count();
    Log::info("Found $profiles_count profiles");
    $profiles = $profiles->filter(function($model) use ($followers_to_remove) {
        if (in_array($model->user_id, $followers_to_remove)) {
            return false;
        }
        return true;
    });

    $middle = microtime(true);


    Log::debug('Saving follower profiles');
    $profiles_count = $profiles->count();
    try {
        $profiles->saveModelsToDB('REPLACE INTO');
    } catch (CollectionException $e) {
        Log::notice("No more follower profiles to save | Sleep 10");
        CLI::sleep(10);
        $engine->complete();
        CLI::stop();
    }

    Log::info("Saved $profiles_count profiles");
    $end = microtime(true);

    Log::debug("Profile removal time: " . ($middle - $start) . " seconds");
    Log::debug("Profile save time: " . ($end - $middle) . " seconds");

    $engine->complete();
    Log::info('Complete');

    Log::runtime();
    Log::memory();
}
catch (EngineException $e) {

    Log::error($e, array('sleep' => 10));
    CLI::sleep(10);

    CLI::stop();
}
catch (Exception $e) {
    $engine->fail();

    Log::error($e);

    CLI::stop();
}
