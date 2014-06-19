<?php
/**
 *  User Pins for user track_type
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

        if ($api_rate > 75000) {
            sleep(60);
            $engine->complete();
        }

        $TRACK_TYPE = array('free', 'user', 'competitor');

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

            CLI::write('Getting all the users from DB for ' . $TRACK_TYPE[$queue_index]);
            $update_queue = QueuedApiCalls::push('status_profile_pins',
                $TRACK_TYPE[$queue_index],
                $numberOfCallsInBatch);

            CLI::write('Adding users to Queue');
            foreach ($update_queue as $q) {
                $queue = QueuedApiCall::readyDBObject('User Pins', $q);
                $queued_calls->add($queue);
            }

            CLI::write('Saving user to Queue table');
            try {
                $queued_calls->saveModelsToDB();
            }
            catch (CollectionException $e) {
                Log::notice("No more users to save to queue for " . $TRACK_TYPE[$queue_index]);
                echo "No more users to save to queue for " . $TRACK_TYPE[$queue_index] . PHP_EOL;
            }

            CLI::write('Grabbing users off the Queue for ' . $TRACK_TYPE[$queue_index]);
            try {
                $calls = QueuedApiCalls::fetchAndUpdateRunning('User Pins',
                    $TRACK_TYPE[$queue_index],
                    $numberOfCallsInBatch);

                $queue_empty = True;

            } catch (NoApiCallsOnQueueException $e)
            {
                Log::notice("No more users to grab off queue for " . $TRACK_TYPE[$queue_index]);
                CLI::alert('No more users to grab off the queue for ' . $TRACK_TYPE[$queue_index]);
                $queue_index ++;

                if($queue_index == count($TRACK_TYPE)){
                    CLI::alert('Completed all track type. Going idle and sleeping.');
                    sleep(30);
                    $engine->idle();
                    exit;
                }
                CLI::write('Trying track type: ' . $TRACK_TYPE[$queue_index]);
            }

            $current_track_running = $TRACK_TYPE[$queue_index];

        }

        Log::info("Starting User Pins pulls for '$current_track_running' track_type");


        CLI::write(Log::info('Making batch calls to the API'));
        $response_data = $calls->send();

        /** @param $bookmark_data QueuedApiCalls
         *                        Adding all the calls that need to be added back on
         *                        the queue
         *
         * @param $save_pins      Pins
         *                        Collection of pins we get back from the API.
         */

        $bookmark_data     = new QueuedApiCalls();
        $save_pins         = new Pins();
        $save_descriptions = new PinDescriptions();
        $save_attributions = new PinAttributions();
        $pin_histories     = new PinsHistories();

        CLI::write('Queue up bookmarked responses');

        // Keeping a counter to track the index of the response
        $count = -1;
        echo count($response_data) . PHP_EOL;

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
                        'userpins_user',
                            $object_id,
                            'Got NULL response back from curl',
                            'from userpins_user.php',
                            QueuedApiCallException::BLANK_RESULT,
                            $call->bookmark
                );

                $call->rerunCall();

            } else {
                switch ($response->code) {

                    case 0:
                        CLI::write(Log::debug('Response returned - Code: ' . $response->code .
                            ' user_id: ' . $object_id));

                        //Deleting the calls from the Queue
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
                                        'Got NULL response back code 0',
                                        'from userpins_user.php',
                                        $response->code,
                                        $call->bookmark
                            );
                            break;
                        } else {
                            foreach ($response->data as $data) {
                                // Loading pins to the pins collection
                                /** @noinspection PhpUndefinedClassInspection */
                                $save_pin         = new Pin($current_track_running);
                                $save_description = new PinDescription();

                                $save_pin->track_type = $current_track_running;

                                $save_pin_db = $save_pin->loadAPIData($data);
                                $save_description->loadPinData($save_pin_db);
                                if (!empty($data->attribution)) {
                                    $save_attribution = new PinAttribution();
                                    $save_attribution->loadAPIData($data->attribution, $object_id);
                                    $save_attribution->pin_id = $data->id;
                                    $save_attributions->add($save_attribution, true);
                                }
                                $save_pins->add($save_pin_db, true);
                                $save_descriptions->add($save_description, true);

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

                    case 10:
                        CLI::write(Log::warning('Bookmark not found: ' . $response->code .
                            ' object_id: ' . $object_id));
                        apierror::create(
                                'user pins',
                                    $calls[$count]->object_id,
                                    $response->message,
                                    'userpins-user.' . __line__,
                                    $response->code,
                                    $call->bookmark
                        );
                        $call->removeFromDB();
                        break;

                    case 11:
                        CLI::write(Log::critical('API method not found: ' . $response->code .
                            ' object_id: ' . $object_id));
                        apierror::create(
                                'user pins',
                                    $calls[$count]->object_id,
                                    $response->message,
                                    'userpins-user.' . __line__,
                                    $response->code,
                                    $call->bookmark
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
                                'User Pins',
                                    $calls[$count]->object_id,
                                    $response->message,
                                    'userpins-user.' . __LINE__,
                                    $response->code,
                                    $call->bookmark
                        );

                        $call->updateTimestamp();

                        break;

                    case 30: //User not found

                        CLI::write(Log::warning('User not found- code: ' . $response->code .
                            '.  user_id: ' . $object_id . '. Checking to see if we should set' .
                            ' track_types to \'not_found\'.'));
                        apierror::create(
                                'user pins',
                                    $calls[$count]->object_id,
                                    $response->message,
                                    'userpins-user.' . __line__,
                                    $response->code,
                                    $call->bookmark
                        );

                        /*
                         * Updating track_types for this user_id so we don't continue to pull it
                         */

                        $errors_found = ApiError::numberOfEntries($call, $response->code);

                        if ($errors_found > 4) {

                            CLI::write(Log::notice(
                                          "User has not been found $errors_found times." .
                                          " Setting track_types of this user_id to not_found" .
                                          " so we don't continue to pull it."
                            ));

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
                            CLI::write(Log::warning(
                                          "User has not been found $errors_found times." .
                                          " We'll keep trying until this error happens 5 times."
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
                                    'userpins-user.' . __LINE__,
                                    $response->code,
                                    $call->bookmark
                        );
                        break;
                }
            }
        }

        CLI::write('Removed completed calls from the Queue');

        CLI::write(Log::info('Saving bookmarked calls to the queue'));
        try{
            $bookmark_data->insertUpdateDB();
        } catch (CollectionException $e){
            CLI::alert(Log::notice('No Bookmarks found to save to the queue'));
        }

        $engagement_calls = new QueuedApiCalls();

        $save_new_and_updated_pins = new Pins();
        $save_new_pin_descriptions = new PinDescriptions();
        $save_new_pin_attributions = new PinAttributions();

        CLI::write(Log::info('The API hash is completed'));

        // We are grabbing all the pin_ids from the API response

        if($save_pins->count() > 0){
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

                    /*
                     * Add to a new collection of pins that we're actually going to save
                     *
                     * We do this because many of these pins will already be in the database when we
                     * pull them (because we're constantly pulling more often than we need to in
                     * order to make sure we don't miss anything new).
                     *
                     * So, we do this extra pass in order to NOT bloat our bulk inserts at the end
                     * of this script with data we already have that hasn't changed.
                     *
                     * We will also add existing pins with changes in engagement counts to the
                     * "$save_new_and_updated_pins" collection a little further below.
                     *
                     */
                    $save_new_and_updated_pins->add($save_pins->getModel($data_key), true);
                    $save_new_pin_descriptions->add($save_descriptions->getModel($data_key), true);
                    if ($save_attributions->getModel($data_key) != false) {
                        $save_new_pin_attributions->add($save_attributions->getModel($data_key), true);
                    }
                }

                if (($data_value['like_count'] != $pin_data_from_db[$data_key]['like_count'])) {
                    if($current_track_running != "free"){
                        $call_like = QueuedApiCall::loadPinFromAPI($data_key, 'Pin Engagement Likes', 'pin_engagement');
                        $engagement_calls->add($call_like);
                    }
                    $create_history = true;

                    /*
                     * If we're not already saving this pin, add it to the collection to we can update
                     * its engagement counts.
                     */
                    if ($save_new_and_updated_pins->getModel($data_key) == false) {
                        $save_new_and_updated_pins->add($save_pins->getModel($data_key), true);
                    }
                }
                if (($data_value['repin_count'] != $pin_data_from_db[$data_key]['repin_count'])) {
                    if($current_track_running != "free"){
                        $call_repins = QueuedApiCall::loadPinFromAPI($data_key, 'Pin Engagement Repins', 'pin_engagement');
                        $engagement_calls->add($call_repins);
                    }
                    $create_history = true;

                    /*
                     * If we're not already saving this pin, add it to the collection to we can update
                     * its engagement counts.
                     */
                    if ($save_new_and_updated_pins->getModel($data_key) == false) {
                        $save_new_and_updated_pins->add($save_pins->getModel($data_key), true);
                    }
                }
                if (($data_value['comment_count'] != $pin_data_from_db[$data_key]['comment_count'])) {
                    $call_comment = QueuedApiCall::loadPinFromAPI($data_key, 'Pin Engagement Comments', 'pin_engagement');
                    $engagement_calls->add($call_comment);
                    $create_history = true;

                    /*
                     * If we're not already saving this pin, add it to the collection to we can update
                     * its engagement counts.
                     */
                    if ($save_new_and_updated_pins->getModel($data_key) == false) {
                        $save_new_and_updated_pins->add($save_pins->getModel($data_key), true);
                    }
                }


                /*
                 * Create pin histories collection to insert into the DB
                 */
                if ($create_history) {

                    $pin_history = new PinHistory();

                    $pin_history->pin_id        = $data_key;
                    $pin_history->user_id       = $data_value['user_id'];
                    $pin_history->date          = flat_date('day');
                    $pin_history->repin_count   = $data_value['repin_count'];
                    $pin_history->like_count    = $data_value['like_count'];
                    $pin_history->comment_count = $data_value['comment_count'];
                    $pin_history->timestamp     = time();

                    $pin_histories->add($pin_history);
                }

            }
        }


        // All pins left over that haven't been run are reset
        // if they have a flag set as running.
        // This is a temporary fix.
//        $time_run = strtotime('-1 hour', time());
//        $count_running = $DBH->query("
//            SELECT count(*) as count from status_api_calls_queue
//            WHERE running = 1
//            AND timestamp < $time_run;
//        ")->fetch();
//
//        if($count_running->count > 0){
//
//            Log::info('Reset calls on queue for userpins-free');
//
//            $STH = $DBH->prepare("UPDATE status_api_calls_queue
//                        SET running = 0
//                        WHERE running = 1
//                        AND api_call = :api_call
//                        AND timestamp < :time_run");
//            $STH->execute(array(':time_run' => $time_run,
//                                ':api_call' => 'User Pins'));
//        }


        /*
         * Save pins
         */
        $total_pin_count = $save_pins->count();
        $saved_pin_count = $save_new_and_updated_pins->count();
        CLI::write(
           Log::info(
              "Saving pin models to the pin table.
            $total_pin_count Pins found.  $saved_pin_count Pins saved."
           )
        );
        try{
            $save_new_and_updated_pins->insertUpdateDB();
        } catch (CollectionException $e){
            CLI::alert(Log::notice('No pins found to save'));
        }


        /*
         * Save pin histories
         */
        CLI::write(Log::debug('Saving pin histories'));
        try{
            $pin_histories->insertIgnoreDB();
        } catch (CollectionException $e){
            CLI::alert(Log::notice('No pin histories to save.'));
        }


        /*
         * Save pin descriptions
         */
        $description_count = $save_new_pin_descriptions->count();
        CLI::write(Log::debug("Saving $description_count pin descriptions to the map_pin_descriptions table"));
        try{
            $save_new_pin_descriptions->saveModelsToDB();
        } catch (CollectionException $e){
            CLI::alert(Log::notice('No pins found so save in descriptions'));
        }


        /*
         * Save pin attributions
         */
        $attribution_count = $save_new_pin_attributions->count();
        CLI::write(Log::debug("Saving $attribution_count pin attributions to the map_pins_attributions table"));
        try{
            $save_new_pin_attributions->insertUpdateDB();
        } catch (CollectionException $e){
            CLI::alert(Log::notice('No pins found so save in attributions'));
        }


        /*
         * Save new engagement calls to add to the queue
         */
        CLI::write(Log::info('Saving engagement calls to the queue'));
        try{
            $engagement_calls->insertUpdateDB();
        } catch (CollectionException $e){
            CLI::alert(Log::notice('No Engagement Calls found to save to the queue'));
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
