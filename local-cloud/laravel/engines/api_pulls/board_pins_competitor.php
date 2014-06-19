<?php
/**
 *  User Pins for user track_type
 *  This script get
 *
 * @author Yesh
 */


ini_set('memory_limit', '500M');

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use Pinleague\PinterestException;
use Pinleague\CLI;

Log::setLog(__FILE__);

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        sleep(10);
        Log::notice('Engine running | Sleep 10');
        throw new EngineException('Engine is running');
    } else {
        $engine->start();
        $start = microtime(true);

        CLI::write(Log::info('Engine started'));

        $numberOfCallsInBatch = 40;

        // Keep a check out for the API RATE
        //  would exit if the limit crosses 70000

        $api_rate = engine::current_call_rate();

        if ($api_rate > 70000) {
            sleep(60);
            $engine->complete();
        }

        $TRACK_TYPE = array('competitor', 'user', 'free');

        /** All variables used later in the code
         *
         * @param $pin_data_from_api All the pin data from the API
         * @param $pin_data_from_db  All the pin data from the database
         * @param $pin_ids_api       All the pin ids from the api
         * @param $db_pin_details    All the pin details based on $pin_ids_api
         *
         */
        $pin_data_from_api = array();
        $pin_data_from_db  = array();
        $pin_ids_api       = array();
        $db_pin_details    = array();


        // $queue_empty is a flag set in order to check if the
        // current track_type has completed. If it has we use
        // $queue_index to move on to the next track_type
        $queue_empty = False;
        $queue_index = 0;

        $DBH = DatabaseInstance::DBO();
        CLI::write('Connected to Database');

        $queued_calls = new QueuedApiCalls();

        while($queue_empty !== True){

            CLI::write('Getting boards from DB to queue up for track_type = ' . $TRACK_TYPE[$queue_index]);
            $status_boards = QueuedApiCalls::pushBoards('last_pulled_pins',
                $TRACK_TYPE[$queue_index],
                $numberOfCallsInBatch);

            CLI::write('Adding boards to Queue');
            foreach ($status_boards as $status_board) {
                $queue = QueuedApiCall::addFromStatusBoards('Board Pins', $status_board);
                $queued_calls->add($queue);
            }

            CLI::write('Saving boards to the API Queue table.');
            try {
                $queued_calls->saveModelsToDB();
            }
            catch (CollectionException $e) {
                CLI::write(Log::notice("No more boards to save to queue for track_type = " . $TRACK_TYPE[$queue_index]));
            }

            CLI::write('Grabbing users off the Queue for track_type = ' . $TRACK_TYPE[$queue_index]);
            try {
                $calls = QueuedApiCalls::fetch('Board Pins',
                    $TRACK_TYPE[$queue_index],
                    $numberOfCallsInBatch);

                $queue_empty = True;

            } catch (NoApiCallsOnQueueException $e)
            {
                CLI::alert(Log::notice("No more boards to grab off queue for " . $TRACK_TYPE[$queue_index]));
                $queue_index ++;

                if($queue_index == count($TRACK_TYPE)){
                    CLI::alert('Completed all track types. Going idle and sleeping.');
                    sleep(30);
                    $engine->idle();
                    exit;
                }
                CLI::write('Trying track type: ' . $TRACK_TYPE[$queue_index]);
            }

            $current_track_running = $TRACK_TYPE[$queue_index];

        }

        Log::info("Running API pull for $current_track_running track_type");

        CLI::write(Log::info('Setting Flag to running in queue'));

        $board_ids_stringify = $calls->stringifyField('object_id');

        $time = time();
        $SET_FLAG_Q = $DBH->query("UPDATE status_api_calls_queue
                                            SET running = 1,
                                            timestamp = $time
                                            WHERE api_call = 'Board Pins' AND
                                            object_id in ($board_ids_stringify)");

        CLI::write(Log::info('Making batch calls to the API'));
        $response_data = $calls->send();

        /** @param $bookmark_data QueuedApiCalls
         *                        Adding all the calls that need to be added back on
         *                        the queue
         *
         * @param $save_pins      Pins Saving the data to pins table
         */

        CLI::write(Log::debug('debug'));
        $bookmark_data     = new QueuedApiCalls();
        $save_pins         = new Pins();
        $save_descriptions = new PinDescriptions();
        $save_attributions = new PinAttributions();

        CLI::write('Queue up bookmarked responses');

        // Keeping a counter to track the index of the response
        $count = -1;
        echo count($response_data) . PHP_EOL;

        foreach ($response_data as $response) {
            $count += 1;

            $object_id = $calls[$count]->object_id;

            $call = $calls->getModel($count);

            // Checking to see if the entire request failed. If it did, we
            // reset and try again.
            if (!is_object($response)){
                Log::error('No response was received, resetting the call ' .
                             $object_id);
                ApiError::create(
                    $call->api_call,
                    $object_id,
                    'No response from curl',
                    'board_pins_' . $current_track_running . '. Line: ' . __LINE__,
                    QueuedApiCallException::BLANK_RESULT
                );

                $call->rerunCall();

            } else {
                switch ($response->code) {

                    case 0:
                        CLI::write(Log::debug('Response returned - Code: ' . $response->code .
                            ' user_id: ' . $object_id));

                        //Deleting the calls from the Queue
                        /** @var $call QueuedApiCall */
                        $call->removeFromDB();

                        if (isset($response->bookmark)) {
                            $bookmark_call = QueuedApiCall::loadBookmarkFromApi($response, 'User Pins', $current_track_running, $object_id);
                            $bookmark_data->add($bookmark_call);
                        }

                        if (!isset($response->data)){
                            Log::warning('The response has code 0, but there is no data for ' .
                                                $object_id);
                            ApiError::create(
                                $call->api_call,
                                $object_id,
                                'Successful response, with no data ',
                                'board_pins_' . $current_track_running . '. Line: ' . __LINE__,
                                $response->code
                            );
                            break;
                        } else {
                            foreach ($response->data as $data) {
                                // Loading pins to the pins collection
                                $save_pin         = new Pin($current_track_running);
                                $save_description = new PinDescription();

                                $save_pin->track_type = $current_track_running;

                                $save_pin_db = $save_pin->loadAPIData($data);
                                $save_description->loadPinData($save_pin_db);
                                if (!empty($data->attribution)) {
                                    $save_attribution = new PinAttribution();
                                    $save_attribution->loadAPIData($data->attribution, $object_id);
                                    $save_attribution->pin_id = $data->id;
                                    $save_attributions->add($save_attribution);
                                }
                                $save_pins->add($save_pin_db);
                                $save_descriptions->add($save_description);

                                // All the engagement details from the API

                                $pin_engagement_details_api = array(
                                    'user_id'       => $data->pinner->id,
                                    'like_count'    => $data->like_count,
                                    'repin_count'   => $data->repin_count,
                                    'comment_count' => $data->comment_count);

                                // Creating the hash map with pin_id as the key and pin
                                // engagement as the value
                                $pin_data_from_api[$data->id] = $pin_engagement_details_api;
                                $pin_ids_api[]                = $data->id;
                            }
                            break;
                        }

                    case 8:
                        $call->rerunCall();

                        $method_call_rate = engine::current_call_rate("getBoardsPins");

                        ApiError::create(
                                'Board Pins',
                                    $call->object_id,
                                    $response->message,
                                    'Calls this hour: ' . $method_call_rate . '. board_pins_' . TRACK_TYPE . '. Line: ' . __LINE__,
                                    $response->code,
                                    $response->bookmark
                        );

                        $errors_found = ApiError::numberOfRateLimitExceptions(
                                                $call,
                                                    $response->code,
                                                    flat_date('hour', time())
                        );

                        if ($errors_found > 100) {
                            Log::error("API limit exceeded. $method_call_rate getBoardsPins calls this hour");
                            CLI::sleep(5);
                        } else {
                            Log::error("API limit exceeded. $method_call_rate getBoardsPins calls this hour");
                            CLI::sleep(1);
                        }

                        break;

                    case 10:
                        /** @var $call QueuedApiCall */
                        CLI::write(Log::warning('Bookmark not found: ' . $response->code .
                            ' object_id: ' . $object_id));
                        apierror::create(
                                'board pins',
                                    $calls[$count]->object_id,
                                    $response->message,
                                    'board_pins_' . $current_track_running . '. Line: ' . __LINE__,
                                    $response->code,
                                    $response->bookmark
                        );
                        $call->removeFromDB();
                        break;

                    case 11:
                        /** @var $call QueuedApiCall */
                        CLI::write(Log::critical('API method not found: ' . $response->code .
                            ' object_id: ' . $object_id));
                        apierror::create(
                                'board pins',
                                    $calls[$count]->object_id,
                                    $response->message,
                                    'board_pins_' . $current_track_running . '. Line: ' . __LINE__,
                                    $response->code,
                                    $response->bookmark
                        );
                        $call->removeFromDB();
                        break;

                    case 12:
                    case 16:
                        // Reset the running flag to rerun the call
                        $call->rerunCall();
                        CLI::write(Log::warning('Pinterest error on their end (resetting call): Code: ' . $response->code .
                            ' object_id: ' . $object_id));

                        ApiError::create(
                                'Board Pins',
                                    $calls[$count]->object_id,
                                    $response->message,
                                    'board_pins_' . $current_track_running . '. Line: ' . __LINE__,
                                    $response->code,
                                    $response->bookmark
                        );

                        $call->updateTimestamp();

                        break;

                    case 40: //User not found

                        /** @var $call QueuedApiCall */
                        CLI::write(Log::warning('Board not found- code: ' . $response->code .
                            '.  board_id: ' . $object_id . '. Checking to see if we should set' .
                            ' track_types to \'not_found\'.'));
                        apierror::create(
                                'board pins',
                                    $calls[$count]->object_id,
                                    $response->message,
                                    'board_pins_' . $current_track_running . '. Line: ' . __LINE__,
                                    $response->code,
                                    $response->bookmark
                        );

                        /*
                         * Updating track_types for this user_id so we don't continue to pull it
                         */

                        $errors_found = ApiError::numberOfEntries($call, $response->code);

                        if ($errors_found > 1) {

                            CLI::write(Log::notice(
                                          "Board has not been found $errors_found times." .
                                          " Setting track_types of this board_id to not_found/deleted" .
                                          " so we don't continue to pull it."
                            ));

                            $STH = $DBH->prepare("UPDATE status_boards
                                        SET track_type = 'not_found'
                                        WHERE board_id = :board_id");

                            $STH->execute(array(':board_id' => $call->object_id));

                            $STH = $DBH->prepare("UPDATE data_boards
                                        SET track_type = 'deleted'
                                        WHERE board_id = :board_id");

                            $STH->execute(array(':board_id' => $call->object_id));

                            $call->removeFromDB();

                        } else {
                            CLI::write(Log::warning(
                                          "Board has not been found $errors_found times." .
                                          " We'll keep trying until this error happens again."
                            ));

                            $call->rerunCall();

                        }

                        break;

                    default:
                        // Reset the running flag to rerun the call
                        $call->rerunCall();

                        CLI::write(Log::critical('Uncaught Pinterest Error: Code: ' . $response->code .
                            '. Message: ' . $response->message . '. Object_id: ' . $object_id . ''));

                        ApiError::create(
                                'User Pins',
                                    $calls[$count]->object_id,
                                    $response->message,
                                    'board_pins_' . $current_track_running . '. Line: ' . __LINE__,
                                    $response->code,
                                    $response->bookmark
                        );
                        break;
                }
            }

        }

        CLI::write('Removing completed calls from the Queue');

        CLI::write(Log::info('The API hash is completed'));

        // We are grabbing all the pin_ids from the API response

        $stringify_api_pin_ids = $save_pins->stringifyField('pin_id');

        $db_pin_details = $DBH->query(
                              "SELECT pin_id, repin_count, like_count, comment_count
                              FROM data_pins_new
                              WHERE pin_id in ($stringify_api_pin_ids)"
        )->fetchAll();

        CLI::write(Log::info('Creating the DB hash'));
        //Creating the hash from the database

        foreach ($db_pin_details as $pin) {
            // Pin engagement details from the database
            $pin_engagement_details_db = array('like_count'    => $pin->like_count,
                                               'repin_count'   => $pin->repin_count,
                                               'comment_count' => $pin->comment_count);

            // Create the hash map of the pin engagement data from db
            $pin_data_from_db[$pin->pin_id] = $pin_engagement_details_db;

        }

        // The comparison between the API hash and the DB hash.
        // The history is also calculated and all the data is updated in the
        // data_pins_history
        CLI::write(Log::debug('Grabbing pin history record counts for the pins we just pulled'));
        $pin_history_counts = $DBH->query(
                                  "SELECT pin_id, count(*) as history_count
                              FROM data_pins_history
                              where pin_id in ($stringify_api_pin_ids)
                              GROUP BY pin_id"
        )->fetchAll();

        $pin_history_array = array();
        foreach($pin_history_counts as $pin_history){
            $pin_history_array[$pin_history->pin_id] = $pin_history->history_count;
        }

        CLI::write(Log::debug('Checking for changes in pin engagement counts.'));
        foreach ($pin_data_from_api as $data_key => $data_value) {

            $create_history = false;

            $history_count = $pin_history_array[$data_key];

            if (!$history_count) {
                $create_history = true;
            }

            /*
             * If we didn't find this pin's data in the database earlier, we'll set the engagement
             * values to 0.
             *
             * This is so that when we compare the engagement values with those returned from the
             * api, any pins with engagement > 0 will have "pin engagement" api calls queued for
             * any engagement type (repin chain, likers, comments) that's > 0.
             */
            if(!$pin_data_from_db[$data_key]){
                $pin_engagement_details_db = array('like_count'    => "0",
                                                   'repin_count'   => "0",
                                                   'comment_count' => "0");

                $pin_data_from_db[$data_key] = $pin_engagement_details_db;
            }

            if (($data_value['like_count'] != $pin_data_from_db[$data_key]['like_count'])) {
                if($current_track_running != "free"){
                    $call_like = QueuedApiCall::loadPinFromAPI($data_key, 'Pin Engagement Likes', 'pin_engagement');
                    $bookmark_data->add($call_like);
                }
                $create_history = true;
            }

            /*
             * Repin Engagement not supported anymore.. for now.
             */
            if (($data_value['repin_count'] != $pin_data_from_db[$data_key]['repin_count'])) {
                if($current_track_running != "free"){
                    $call_repins = QueuedApiCall::loadPinFromAPI($data_key, 'Pin Engagement Repins', 'pin_engagement');
                    $bookmark_data->add($call_repins);
                }
                $create_history = true;
            }

            if (($data_value['comment_count'] != $pin_data_from_db[$data_key]['comment_count'])) {
                $call_comment = QueuedApiCall::loadPinFromAPI($data_key, 'Pin Engagement Comments', 'pin_engagement');
                $bookmark_data->add($call_comment);
                $create_history = true;
            }


            if ($create_history) {

                $flatDate = flat_date('day');
                $time     = time();

                $STH = $DBH->prepare("insert ignore into data_pins_history
                        set pin_id = :pin_id ,
                        user_id = :user_id,
                        date = :flatDate,
                        repin_count = :repin_count,
                        like_count = :like_count,
                        comment_count = :comment_count,
                        timestamp = :timestamp");

                $STH->execute(array(
                                   ":pin_id"          => $data_key
                                   , ":user_id"       => $data_value['user_id']
                                   , ":flatDate"      => $flatDate
                                   , ":repin_count"   => $data_value['repin_count']
                                   , ":like_count"    => $data_value['like_count']
                                   , ":comment_count" => $data_value['comment_count']
                                   , ":timestamp"     => $time
                              ));

            }

        }


        // All pins left over that haven't been run are reset
        // if they have a flag set as running.
        // This is a temporary fix.
//        $time_run = strtotime('-60 minutes', time());

//        $STH = $DBH->prepare("UPDATE status_api_calls_queue
//                            SET running = 0
//                            WHERE timestamp < :time_run
//                            AND api_call = :api_call
//                            AND running = 1");
//
//        $STH->execute(array(':time_run' => $time_run,
//                            ':api_call' => 'User Pins'));

        CLI::write(Log::info('Saving new "Board Pins" calls with bookmarks, and Pin Engagement calls to the queue'));
        try{
            $bookmark_data->insertUpdateDB();
        } catch (CollectionException $e){
            CLI::alert(Log::notice('No Bookmarked calls or Pin Engagement calls to add to the queue'));
        }

        CLI::write('Saving Pins to the data_pins_new table');
        try{
            $save_pins->insertUpdateDB();
        } catch (CollectionException $e){
            CLI::alert(Log::notice('No pins found'));
        }

        CLI::write('Saving pin descriptions to the map_pins_descriptions table');
        try{
            $save_descriptions->saveModelsToDB();
        } catch (CollectionException $e){
            CLI::alert(Log::notice('No pins found so save in descriptions'));
        }


        CLI::write('Saving pin attributions to the map_pins_attributions table');
        try{
            $save_attributions->insertUpdateDB();
        } catch (CollectionException $e){
            CLI::alert(Log::notice('No pins found so save in attributions'));
        }

        $engine->complete();

        CLI::write(Log::runtime(). 'total runtime');
        CLI::write(Log::memory().' peak memory usage');

        CLI::h1(Log::info('Complete'));

    }
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
