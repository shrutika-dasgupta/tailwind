<?php
/**
 *  User Boards for user track_type
 *
 * @author Yesh
 *         Alex
 */


ini_set('memory_limit', '500M');

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use Pinleague\PinterestException;
use Pinleague\CLI;

Log::setLog(__FILE__, "CLI");
$client_id       = Config::get("pinterest.new.client_id");

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        Log::notice('Engine running | Sleep 10');
        CLI::stop(Log::error('Engine is already running'),array('sleep'=>15));
        throw new EngineException('Engine is running');
    }

    $engine->start();
    $start = microtime(true);

    Log::info('Engine started');

    $numberOfCallsInBatch = 40;

    // Keep a check out for the API RATE
    //  would exit if the limit crosses 70000

    $api_rate = engine::current_call_rate($client_id);

    if ($api_rate > 45000) {
        sleep(60);
        $engine->complete();
    }

    $TRACK_TYPE = array('user', 'competitor', 'free');

    // $queue_empty is a flag set in order to check if the
    // current track_type has completed. If it has we use
    // $queue_index to move on to the next track_type
    $queue_empty = False;
    $queue_index = 0;

    $DBH = DatabaseInstance::DBO();
    CLI::write('Connected to Database');

    $queued_calls = new QueuedApiCalls();

    while($queue_empty !== True){

        Log::debug('Grab user_ids that need boards updated from the DB for track_type: ' . $TRACK_TYPE[$queue_index]);
        $update_queue = StatusProfiles::push('last_pulled_boards',
                                              $TRACK_TYPE[$queue_index],
                                              $numberOfCallsInBatch);

        Log::debug('Add user_id calls to a QueuedApiCalls collection');
        foreach ($update_queue as $q) {
            $queue = QueuedApiCall::readyDBObject('User Boards', $q);
            $queued_calls->add($queue);
        }

        Log::debug('Saving "user boards" calls to Queue.');
        try {
            $queued_calls->saveModelsToDB();
        }
        catch (CollectionException $e) {
            Log::notice("No more users to save to queue for track_type: " . $TRACK_TYPE[$queue_index]);
        }

        Log::debug('Grabbing "User Boards" calls off the Queue for track_type: ' . $TRACK_TYPE[$queue_index]);
        try {
            $calls = QueuedApiCalls::fetchAndUpdateRunning('User Boards',
                $TRACK_TYPE[$queue_index],
                $numberOfCallsInBatch);

            $queue_empty = True;

        } catch (NoApiCallsOnQueueException $e)
        {
            Log::notice("No more users to grab off queue for track_type: " . $TRACK_TYPE[$queue_index]);
            $queue_index++;

            if($queue_index == count($TRACK_TYPE)){
                Log::notice('Completed all track type. Going idle and sleeping.');
                sleep(30);
                $engine->idle();
                exit;
            }
            Log::info('Trying track type: ' . $TRACK_TYPE[$queue_index]);
        }

        $current_track_running = $TRACK_TYPE[$queue_index];

    }

    Log::info("Starting User Boards pulls for '$current_track_running' track_type");

    $user_ids_called = array();
    foreach ($calls as $call) {
        $user_ids_called[] = $call->object_id;
    }

    Log::debug("User_ids being called", $user_ids_called);

    $stringify_user_ids = implode(', ', $user_ids_called);
    rtrim($stringify_user_ids);


    Log::info('Making batch calls to the API');
    $response_data = $calls->send(true);

    /** @param $bookmark_data QueuedApiCalls
     *                        Adding all the calls that need to be added back on
     *                        the queue
     *
     * @param $save_boards    Boards
     *                        Collection of boards we get back from the API.
     */
    $bookmark_data     = new QueuedApiCalls();
    $save_boards       = new Boards();
    $status_boards     = new StatusBoards();

    // Keeping a counter to track the index of the response
    $count = -1;
    echo count($response_data) . PHP_EOL;

    Log::info("Parsing through API responses");

    foreach ($response_data as $response) {
        $count += 1;

        $object_id = $calls[$count]->object_id;

        /** @var $call QueuedApiCall */
        $call = $calls->getModel($count);

        // Checking to see if the entire request failed. If it did, we
        // reset and try again.
        if (!is_object($response)){
            Log::error('The response is NULL, reset the call ' .
                $object_id);
            ApiError::create(
                    'user_boards',
                        $object_id,
                        'Got NULL response back from curl',
                        'from user_boards.php',
                        QueuedApiCallException::BLANK_RESULT,
                        $call->bookmark
            );

            $call->rerunCall();

        } else {
            switch ($response->code) {

                case 0:
                    Log::debug('Response returned - Code: ' . $response->code .
                        ' user_id: ' . $object_id);

                    if (isset($response->bookmark)) {
                        $bookmark_call = QueuedApiCall::loadBookmarkFromApi($response, 'User Boards', $current_track_running, $object_id);
                        $bookmark_data->add($bookmark_call);
                    }

                    if (!isset($response->data)){

                        //Rerunning call
                        $call->updateTimestamp();
                        $call->rerunCall();

                        Log::warning('The response has code 0, but there is no data for ' .
                            $object_id);
                        ApiError::create(
                                $call->api_call,
                                    $object_id,
                                    'Got NULL response back code 0',
                                    'from user_boards.php',
                                    $response->code,
                                    $call->bookmark
                        );
                        break;
                    } else {

                        //Deleting the calls from the Queue
                        $call->removeFromDB();

                        foreach ($response->data as $data) {
                            // Loading boards from API to the boards collection
                            /** @noinspection PhpUndefinedClassInspection */
                            $save_board       = new Board();
                            $status_board     = new StatusBoard();

                            $save_board->track_type = $current_track_running;
                            $save_board->user_id    = $object_id;
                            $save_board_db = $save_board->loadAPIData($data);
                            $save_boards->add($save_board_db, true);

                            $status_board->board_id      = $data->id;
                            $status_board->owner_user_id = $data->owner->id;
                            $status_board->track_type    = $current_track_running;
                            $status_board->is_owned      = false;
                            $status_board->loadAPIData($data);
                            if ($save_board_db->is_owner) {
                                $status_board->is_owned = true;
                            }
                            $status_boards->add($status_board, true);

                            // All the board stats from the API
                            $board_stat_details_api = array(
                                'user_id'        => $object_id,
                                'owner_user_id'  => $data->owner->id,
                                'pin_count'      => $data->pin_count,
                                'follower_count' => $data->follower_count
                            );

                            // Creating the hash map with board_id as the key and board
                            // stats as the value
                            $board_data_from_api[$data->id] = $board_stat_details_api;
                            $board_ids_from_api[]           = $data->id;
                        }


                        /**
                         * Now, we'll grab all of this user's associated boards so we
                         * can compare against what we got back from the API and see if there's
                         * anything missing (any boards the user may have removed or deleted).
                         */
                        $STH = $DBH->prepare("
                        SELECT *
                        FROM data_boards
                        WHERE user_id = :user_id
                        and track_type != 'deleted'
                        and track_type != 'orphan'");
                        $STH->execute(array(":user_id" => $object_id));

                        $boards_in_db = $STH->fetchAll();

                        $board_ids_from_db = array();
                        // Making an array of all the board ids of
                        // from user in the db
                        foreach($boards_in_db as $board){
                            $board_ids_from_db[] = $board->board_id;
                        }

                        Log::debug('Checking against user\'s existing boards');

                        $dead_boards = array_diff($board_ids_from_db, $board_ids_from_api);

                        if (!empty($dead_boards)){
                            Log::debug('Found deleted boards for user_id: ' . $object_id);
                            foreach($dead_boards as $board){
                                $STH = $DBH->prepare("
                                UPDATE data_boards
                                SET track_type = 'deleted',
                                timestamp = :timestamp,
                                last_pulled = :last_pulled
                                WHERE user_id = :user_id AND board_id = :board_id");
                                $STH->execute(
                                    array(
                                         ":timestamp" => time(),
                                         ":last_pulled" => time(),
                                         ":user_id" => $object_id,
                                         ":board_id" => $board
                                    )
                                );

                                /**
                                 * Now we're going to check and see if any other users have this board as
                                 * still active.  If not, then we can set the track_type to "orphan" in the
                                 * status_boards table as well, since we have no reason to pull data on this
                                 * board's pins or followers any longer.
                                 */
                                $STH = $DBH->prepare(
                                           "SELECT count(*) as count
                                           FROM data_boards
                                           WHERE board_id = :board_id
                                           AND track_type != 'deleted'"
                                );

                                $STH->execute(array(":board_id" => $board));

                                $deleted_count = $STH->fetch();

                                if ($deleted_count->count == 0) {
                                    $STH = $DBH->prepare(
                                       "UPDATE status_boards
                                        SET track_type = 'orphan'
                                        WHERE board_id = :board_id"
                                    );
                                    $STH->execute(array(":board_id" => $board));
                                }

                            }
                            Log::debug('Updated deleted boards for user_id: ' . $object_id . '.  Board_id ' . $board . ' removed.');
                        }


                        break;
                    }

                case 10: //should never happen on this call because you never get bookmarks
                    Log::warning('Bookmark not found: ' . $response->code .
                        ' object_id: ' . $object_id);
                    apierror::create(
                            'User Boards',
                                $calls[$count]->object_id,
                                $response->message,
                                'user_boards-user.' . __line__,
                                $response->code,
                                $call->bookmark
                    );
                    $call->removeFromDB();
                    break;

                case 11:
                    Log::critical('API method not found: ' . $response->code .
                        ' object_id: ' . $object_id);
                    apierror::create(
                            'User Boards',
                                $calls[$count]->object_id,
                                $response->message,
                                'user_boards-user.' . __line__,
                                $response->code,
                                $call->bookmark
                    );
                    $call->removeFromDB();
                    break;

                case 12:
                case 16:

                    Log::warning('Pinterest error on their end (resetting call): Code: ' . $response->code .
                        ' object_id: ' . $object_id);

                    ApiError::create(
                            'User Boards',
                                $calls[$count]->object_id,
                                $response->message,
                                'user_boards-user.' . __LINE__,
                                $response->code,
                                $call->bookmark
                    );

                    $errors_found = ApiError::numberOfEntriesWithinTime($call, $response->code, flat_date('hour'), true);

                    if ($errors_found > 5) {
                        $call->removeFromDB();
                    } else {
                        // Reset the running flag to rerun the call
                        $call->updateTimestamp();
                        $call->rerunCall();
                    }

                    break;

                case 30: //User not found

                    Log::warning('User not found- code: ' . $response->code .
                        '.  user_id: ' . $object_id . '. Checking to see if we should set' .
                        ' track_types to \'not_found\'.');
                    apierror::create(
                            'User Boards',
                                $calls[$count]->object_id,
                                $response->message,
                                'user_boards.' . __line__,
                                $response->code,
                                $call->bookmark
                    );

                    /*
                     * Updating track_types for this user_id so we don't continue to pull it
                     */

                    $errors_found = ApiError::numberOfEntries($call, $response->code);

                    if ($errors_found > 1) {

                        Log::notice(
                           "User has not been found $errors_found times." .
                           " Setting track_types of this user_id to not_found" .
                           " so we don't continue to pull it."
                        );

                        $STH = $DBH->prepare("UPDATE status_profiles
                                            SET track_type = 'not_found'
                                            WHERE user_id = :user_id");

                        $STH->execute(array(':user_id' => $call->object_id));

                        $STH = $DBH->prepare("UPDATE status_profile_pins
                                            SET track_type = 'not_found'
                                            WHERE user_id = :user_id");

                        $STH->execute(array(':user_id' => $call->object_id));

                        $STH = $DBH->prepare("UPDATE status_profile_followers
                                            SET track_type = 'not_found'
                                            WHERE user_id = :user_id");

                        $STH->execute(array(':user_id' => $call->object_id));

                        $call->removeFromDB();
                    } else {
                        Log::warning(
                           "User has not been found $errors_found times." .
                           " We'll keep trying until this error happens again."
                        );

                        $call->updateTimestamp();
                        $call->rerunCall();

                    }

                    break;

                default:
                    // Reset the running flag to rerun the call
                    $call->updateTimestamp();
                    $call->rerunCall();

                    Log::critical('Uncaught Pinterest Error: Code: ' . $response->code .
                        '. Message: ' . $response->message . '. Object_id: ' . $object_id . '');

                    ApiError::create(
                            'User Boards',
                                $calls[$count]->object_id,
                                $response->message,
                                'user_boards-user.' . __LINE__,
                                $response->code,
                                $call->bookmark
                    );
                    break;
            }
        }
    }

    Log::info(
       "Removed completed calls from the Queue.  Now saving bookmarked calls back to the queue"
    );

    try{
        $bookmark_data->insertUpdateDB();
    } catch (CollectionException $e){
        Log::notice('No Bookmarks found to save to the queue');
    }

    $board_follower_calls = new QueuedApiCalls();

    $save_new_and_updated_boards = new Boards();

    /**
     * Check to see if number of followers for the board has increased, so we can
     * intelligently queue up board followers to pull only when needed.
     */
    if ($current_track_running == 'user') {
        if($save_boards->count() > 0){

            Log::info(
               "Checking newly received board metrics against current data in our database"
            );

            $stringify_api_board_ids = $save_boards->stringifyField('board_id');
            $db_board_details = $DBH->query(
                                    "SELECT user_id, board_id, owner_user_id,
                              pin_count, follower_count
                              FROM data_boards
                              WHERE user_id in ($stringify_user_ids)
                              AND track_type != 'deleted'
                              AND track_type != 'footprint'
                              AND track_type != 'not_found'
                              AND track_type != 'orphan'
                              GROUP BY board_id"
            )->fetchAll();

            Log::debug("Checking to see if board follower calls already on the queue");

            /**
             * Find out which board_ids we've pulled are already on the queue
             */
            $boards_on_queue = $DBH->query(
                "SELECT object_id FROM status_api_calls_queue
                WHERE api_call = 'Board Followers'
                AND object_id in ($stringify_api_board_ids)"
            )->fetchAll();

            foreach ($boards_on_queue as $board_id) {
                $board_ids_on_queue[] = $board_id->object_id;
            }

            foreach ($db_board_details as $board) {
                // Board stat details from the database
                $board_stat_details_db = array(
                    'user_id'        => $board->user_id,
                    'owner_user_id'  => $board->owner_user_id,
                    'pin_count'      => $board->pin_count,
                    'follower_count' => $board->follower_count
                );

                // Create an array of the board stats data from db
                $board_data_from_db[$board->board_id] = $board_stat_details_db;

            }

            /**
             * Now, we'll see if there are any new or updated
             * boards coming from the API, compared to what we already have in the DB.
             */
            Log::debug('Checking for changes in board stats counts between API and DB.');
            foreach ($board_data_from_api as $data_key => $data_value) {

                /*
                 * If we didn't find this board's data in the database earlier, we'll set the stats
                 * values to 0.
                 */
                if(!$board_data_from_db[$data_key]){
                    $board_stat_details_db = array(
                        'follower_count' => "0"
                    );

                    $board_data_from_db[$data_key] = $board_stat_details_db;
                }

                /**
                 * If the follower count has increased, we'll queue up a Board Followers call
                 * for this board to get any new followers.
                 */
                if (($data_value['follower_count'] > $board_data_from_db[$data_key]['follower_count'])) {
                    /**
                     * Make sure the board is not already having followers pulled on the queue
                     */
                    if (!in_array($data_key, $board_ids_on_queue)) {
                        $board_follower_call = QueuedApiCall::loadPinFromAPI($data_key, 'Board Followers', $current_track_running);
                        $board_follower_calls->add($board_follower_call);
                    }
                }
            }
        }
    }

    /*
     * Save Boards
     */
    $total_board_count = $save_boards->count();

    Log::info(
       "Saving board models to the data_boards table.
        $total_board_count Boards found and saved."
    );
    try{
        $save_boards->insertUpdateDB();
    } catch (CollectionException $e){
        Log::notice('No boards found to save');
    }


    /*
     * Save new Board Follower calls to add to the queue
     */
    Log::info('Saving Board Follower calls to the queue');
    try{
        $board_follower_calls->insertUpdateDB();
    } catch (CollectionException $e){
        Log::notice('No Board Follower Calls found to save to the queue');
    }

    /*
     * Insert any new Boards into the Status Boards table
     */
    Log::info('Saving new Boards to Status Boards table');
    try{
        $status_boards->insertUpdateDB(
                      ['added_at',
                      'last_pulled_followers',
                      'last_pulled_pins',
                      'last_calced',
                      'track_type',
                      'followers_found',
                      'last_updated_followers_found']
        );
    } catch (CollectionException $e){
        Log::notice('No new Boards to save.');
    }

    $engine->complete();

    Log::runtime() . 'total runtime';
    Log::memory() . ' peak memory usage';

    Log::info('Boards Engine Complete');

}
catch (EngineException $e) {

    CLI::alert($e->getMessage());
    Log::error($e);
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
