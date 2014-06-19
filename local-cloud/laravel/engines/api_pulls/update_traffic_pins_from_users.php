<?php
/**
 * Updating pins information using the pin_ids and board_slug
 * from map_traffic_pins_boards
 * @param $pins_from_resp    : All the pin data from the response
 * @param $pins_unique_board : All the boards from the database
 * @param $pins_local        : All the pins from $pins_unique_board
 * @param $orphan_pins       : All pins from deleted boards returned from API
 *
 * @author yesh
 */

ini_set('memory_limit', '500M');

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use Pinleague\CLI;

Log::setLog(__FILE__, 'CLI');

$number_of_users = 40;

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        Log::error('Engine is already running');
        CLI::sleep(15);
        CLI::stop();
    }

    $engine->start();
    Log::info('Engine started');

    $pins_from_resp = array();
    $pins_unique_board = array();
    $pins_local = array();
    $orphan_pins = array();

    $DBH = DatabaseInstance::DBO();
    CLI::write('Connected to Database');

    // Keep a check out for the API RATE
    //  would exit if the limit crosses 70000

    $api_rate = engine::current_call_rate();

    if ($api_rate > 70000){
        $engine->complete();
        CLI::sleep(300);
        Log::notice("Too many calls to the API | Sleep 300");
        CLI::stop();
    }

    //Makes an array of unique board_slugs for
    //newly inserted pins with no information
    Log::debug('Grabbing user_ids to pull');
    $STH = $DBH->query("SELECT distinct user_id
                        FROM map_traffic_pins_users
                        WHERE calced_flag = 0
                        LIMIT $number_of_users");
    $unique_users = $STH->fetchAll();

    $unique_users_csv = "";
    if(count($unique_users) > 0){
        foreach($unique_users as $user){
            $unique_users_csv .= $user->user_id . ",";
        }
    } else {
        CLI::sleep(30);
        $engine->complete();
        Log::notice("No pins left to update");
        CLI::stop();
    }
    $unique_users_csv = rtrim($unique_users_csv,",");

    Log::debug('Got boards.. now grabbing pin_ids from board_ids');

    $STH = $DBH->query("SELECT pin_id, user_id
                        FROM map_traffic_pins_users
                        WHERE user_id in ($unique_users_csv)");
    $unique_pins = $STH->fetchAll();

    $pins_from_db = array();
    foreach($unique_pins as $pin){
        $pins_from_db[$pin->pin_id] = $pin->pin_id;

        $pin_users_pins_from_db[$pin->pin_id . "-" . $pin->user_id] = $pin->pin_id;
        $pin_users_users_from_db[$pin->pin_id . "-" . $pin->user_id] = $pin->user_id;
    }

    Log::debug("Looking for a total of " . count($pin_users_users_from_db) . " Pins.");

    Log::info('Getting pin data from API');

    foreach ($unique_users as $data){
        array_push($user_ids_from_db,
            $data->user_id);
    }

    $wave_number     = 0;
    $bookmarks_array = array();
    $pull_next_wave  = true;
    $users_found    = array();
    $unsaved_pins   = array();

    $users_pins  = new Profiles();
    $pins_to_save = new Pins();

    Log::debug('Running waves');
    while ($wave_number < 5 and $pull_next_wave == true) {

        $wave_number += 1;

        Log::debug('Sending wave '. $wave_number);
        Log::debug("pulling for " . count($unique_users) . " users");
        Log::debug("have " . count($bookmarks) . " bookmarks");
        try {

            $complete_resp = $users_pins
                ->findPinsFromUsers(
                $unique_users,
                    $bookmarks = $bookmarks_array);

        } catch (\Pinleague\NoBatchCallsException $e) {

            Log::notice('No more batched calls to make | Sleep 30');
            sleep(30);
            $engine->complete();
            CLI::stop();
        }


        $pins_from_resp_data = $complete_resp['pins_arr'];
        $bookmarks_array     = $complete_resp['bookmarks'];

        Log::debug('Calculating API hashes');

        $all_pins_api_hash = array();
        $all_api_pin_ids   = array();

        foreach ($pins_from_resp_data as $data){

            $all_pins_api_hash[$data->id] = $data;
            $all_api_pin_ids[$data->id] = $data->id;
        }

        /**
         * Check for pins we're looking for
         */
        Log::debug('Find pin matches in the users we just pulled');
        $matched_pins = array_intersect(
            $all_api_pin_ids,
            $pins_from_db
        );

        Log::debug("Found " . count($matched_pins) . " pin matches!");

        /**
         * We want to save the pin data of matched pins to the data_pins_new
         * table, so we're making a collection of pins here.
         */
        Log::debug('Saving matched pin data to DB');

        foreach ($matched_pins as $pin_id => $value) {

            /**
             * Add the matched pin into the pin collection
             */
            $pin = new Pin();
            $pin->loadAPIData($all_pins_api_hash[$pin_id]);
            $pin->track_type = 'traffic';

            $pins_to_save->add($pin);

            $users_found[$pin_id] = $all_pins_api_hash[$pin_id]->pinner->id;
            $pin_users_found[$pin_id . "-" . $all_pins_api_hash[$pin_id]->pinner->id] = $all_pins_api_hash[$pin_id]->pinner->id;
        }

        $pin_users_users_from_db = array_diff_assoc($pin_users_users_from_db, $pin_users_found);

        Log::debug(count($pin_users_users_from_db) . " pins left to find");

        $unique_users = array();
        foreach($pin_users_users_from_db as $user_id){

            $unique_users[$user_id] = $user_id;
        }

        Log::debug(count($unique_users) . " users left to pull pins for");

        $all_matches_pins = array();

        if (empty($unique_users)){
            $pull_next_wave = false;
        }

    }



    Log::debug("Saving " . $pins_to_save->count() . " matched pins to the pins table");
    try {
        $pins_to_save->insertUpdateDB();
    } catch (CollectionException $e) {
        CLI::alert(Log::notice('No more matched pins to save'));
    }


    Log::debug("Updating calced_flag in map_traffic_pins_users table to 1 for pins we found");
    if($pins_to_save->count() > 0){
        $stringify_pin_ids = $pins_to_save->stringifyField("pin_id");

        $DBH->query("UPDATE map_traffic_pins_users
                    SET calced_flag = 1
                    WHERE pin_id in ($stringify_pin_ids)");
    }


    /**
     * For any pins we didn't end up finding, we'll update them to calced_flag = 2
     */
    $saved_pin_ids = array();
    foreach($pins_to_save as $pin){
        $saved_pin_ids[$pin->pin_id] = $pin->pin_id;
    }

    $not_found_pin_ids = array_diff($pins_from_db, $saved_pin_ids);

    if(count($not_found_pin_ids) > 0){
        $not_found_pin_ids_implode = implode(",", $not_found_pin_ids);
        $DBH->query("UPDATE map_traffic_pins_users
                SET calced_flag = 2
                WHERE pin_id in ($not_found_pin_ids_implode)");
    }


   /**
    * Now we want to check the data_traffic_pins table to make sure we are also
    * updating the user_id field there from the pin data we just found
    */
    $stringify_pin_ids = $pins_to_save->stringifyField("pin_id");
    if(!empty($stringify_pin_ids)){

       /**
        * Lets iterate through the pins we just saved to grab only the pin_ids and
        * user_ids for easy reference later
        */
        $pins_with_user_ids = array();
        foreach($pins_to_save as $pin){
            $pins_to_save["$pin->pin_id"] = array();
            $pins_with_user_ids["$pin->pin_id"]['pin_id'] = $pin->pin_id;
            $pins_with_user_ids["$pin->pin_id"]['user_id'] = $pin->user_id;
        }

        Log::debug("Getting records from data_traffic_pins to check for user_ids: $stringify_pin_ids");

        $traffic_pins = $DBH->query(
                            "SELECT pin_id, user_id
                            FROM data_traffic_pins
                            WHERE pin_id in ($stringify_pin_ids)
                            AND user_id = 0
                            GROUP BY pin_id"
        )->fetchAll();

        /*
         * See if any of the pins we found do not have a user_id specified in data_traffic_pins
         */
        Log::debug('Finding pins in data_traffic_pins without user_ids specified');
        $traffic_pins_to_update = array();
        foreach($traffic_pins as $pin){
            array_push($traffic_pins_to_update, $pins_with_user_ids[$pin->pin_id]);
        }

        /*
         * For pins that did not have a user_id specified, we'll update those records here.
         */
        if(count($traffic_pins_to_update) != 0){

            Log::info('Updating data_traffic_pins with user_ids');

            foreach($traffic_pins_to_update as $pin){
                $STH = $DBH->prepare("
                                 UPDATE data_traffic_pins
                                      SET user_id = :user_id
                                      WHERE pin_id = :pin_id");

                $STH->execute(
                    array(
                         ":user_id" => $pin['user_id'],
                         ":pin_id"  => $pin['pin_id']
                    ));
            }
        }
    }

    $engine->complete();
    Log::info('Complete');

    Log::runtime();
    Log::memory();


} catch (EngineException $e) {

    CLI::alert($e->getMessage());
    Log::error($e);
    CLI::stop();

} catch (PDOException $e) {

    CLI::alert($e->getMessage());
    $engine->fail();
    Log::error($e);
    CLI::stop();

} catch (Exception $e) {

    CLI::alert($e->getMessage());
    $engine->complete();
    Log::error($e);
    CLI::stop();
}

