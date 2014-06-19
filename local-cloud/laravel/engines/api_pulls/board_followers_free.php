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

$numberOfCallsInBatch = 40;

const TRACK_TYPE = 'free';
$client_id       = Config::get("pinterest.new.client_id");
Log::setLog(__FILE__, 'CLI');

try {
    CLI::h1('Starting ' . __FILE__);
    $engine = new Engine(__FILE__);

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

    if ($api_rate > 60000) {

        Log::warning('Too many api calls | Sleep 300');

        CLI::sleep(300);
        $engine->idle();

        CLI::stop();
    }

    $DBH = DatabaseInstance::DBO();
    CLI::write('Connected to database');

    $queuing_calls = new QueuedApiCalls();

    Log::debug('Looking through all the boards in the status_boards table');

    $status_boards = QueuedApiCalls::pushBoards('last_pulled_followers',
        TRACK_TYPE,
        $numberOfCallsInBatch);

    Log::debug('Find boards to add to the Queue');
    foreach ($status_boards as $status_board) {
        $queue = QueuedApiCall::addFromStatusBoards('Board Followers', $status_board);
        $queuing_calls->add($queue);
    }

    Log::debug('Saving new boards to pull followers for to the Queue table', $queuing_calls);
    try {
        $queuing_calls->saveModelsToDB();
    }
    catch (CollectionException $e) {
        Log::notice('No boards to save to queue');
    }

    Log::debug('Grabbing Board Followers off the Queue');

    try {
        $calls = QueuedApiCalls::fetchAndUpdateRunning('Board Followers',
            TRACK_TYPE,
            $numberOfCallsInBatch);
    }
    catch (NoApiCallsOnQueueException $e) {
        Log::notice('No more boards to grab off the queue.  Going idle and sleeping.');
        CLI::sleep(60);
        $engine->idle();
        exit;
    }

    $board_ids_from_call = array();
    foreach ($calls as $call) {
        $board_ids_from_call[] = $call->object_id;
    }

    $count_from_db = BoardFollower::columnCountByBoard('follower_user_id', $board_ids_from_call);
    $actual_data   = BoardFollower::getFollowerCountAndOwnerId($board_ids_from_call);

    Log::info('Making batch calls to the API', $board_ids_from_call);
    $response_data = $calls->send(true);


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
                $profiles->add($profile);

                $board_follower->addViaProfile($profile, $owner_id, $object_id);
                $board_followers->add($board_follower);
                $follower_ids_from_api[] = $data->id;
            }

            // Getting all the followers from the database from the array of
            // response follower_ids
            $follower_ids_implode = implode(",", $follower_ids_from_api);
            if (!empty($follower_ids_from_api)) {

                if (isset($response->bookmark)) {

                    $STH                = $DBH->query("SELECT board_id, follower_user_id
                                    FROM data_board_followers
                                    WHERE board_id = $object_id AND follower_user_id IN ($follower_ids_implode)");
                    $follower_ids_in_db = $STH->fetchAll();

                    // Calculating the percentage of users present in the database based on the
                    // total number from the api.
                    $percentage        = ($count_from_db[$object_id] / $actual_data[$object_id]['follower_count'] * 100);
                    $within_percentage = ($percentage > 97) ? true : false;

                    // Checking to see if the followers we got back from the response already exist in the database
                    // If they do, we set flag to false
                    $any_new_followers = (count($follower_ids_in_db) == count($follower_ids_from_api)) ? false : true;

                    Log::debug("Bookmark found. " .
                        "Percentage of user's followers we have in the DB: $percentage." .
                        "New followers found during this run: " . ($any_new_followers ? "Yes" : "No"));


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

            $method_call_rate = engine::current_call_rate("getBoardFollowers");

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
                                        flat_date('day', time())
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

    $board_followers->insertUpdateDB();
    Log::info('Saved board followers');

    $profiles->insertUpdateDB();
    Log::info('Saved profiles');

    if ($bookmark_data->isEmpty()) {
        Log::notice('No bookmarked calls found on this run.');
    } else {
        $bookmark_data->insertUpdateDB();
        Log::info('Found bookmarks - adding new bookmarked calls to the queue');
    }

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
