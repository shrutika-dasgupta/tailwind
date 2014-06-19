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

Log::setLog(__FILE__);

$number_of_boards = 50;

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        sleep (10);
        throw new EngineException('Engine is running');
    } else {
        $engine->start();
        CLI::write(Log::info('Engine started'));

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
            sleep (300);
            CLI::alert(Log::notice("Too many calls to the API | Sleep 300"));
            exit;
        }

        //Makes an array of unique board_slugs for
        //newly inserted pins with no information
        CLI::write(Log::debug('Grabbing board slugs to pull'));
        $STH = $DBH->query("SELECT DISTINCT board_slug
                            FROM map_traffic_pins_boards
                            WHERE calced_flag = 0
                            LIMIT $number_of_boards");
        $unique_boards = $STH->fetchAll();

        CLI::write('Got boards');

        CLI::write(Log::info('Getting pin data from API'));

        // Pulling all pins from API for all the unique boards
        try{
            $complete_resp = Boards::fetchPinsBoardSlugs($unique_boards);
        } catch (\Pinleague\Pinterest\NoBatchCallsException $e){
            CLI::alert(Log::notice('No more batched calls to make | Sleep 30'));
            sleep(30);
            $engine->complete();
            CLI::stop();
        }

        $pins_from_resp_data = $complete_resp['pins_arr'];
        echo "Count of pins from API: " . count($pins_from_resp_data) . "\n";

        $dead_boards = $complete_resp['dead_boards'];
        echo "Dead Boards: " . var_dump($dead_boards) . "\n";

        $rerun_boards = $complete_resp['rerun_boards'];
        echo "Rerun Boards: " . var_dump($rerun_boards) . "\n";

        CLI::write(Log::debug(
                      'Handling boards that were not found. Setting pins to "orphan"' .
                      ' and calced_flag in map_traffic_pins_boards = 2 (not found)'
        ));
        foreach($dead_boards as $board_slug){
            $STH = $DBH->prepare("
                                SELECT pin_id
                                FROM map_traffic_pins_boards
                                WHERE board_slug = :board_slug");
            $STH->execute(array(":board_slug" => $board_slug->board_slug));
            $dead_pins = $STH->fetchAll();
            echo "Dead Pins: " . var_dump($dead_pins);

            foreach($dead_pins as $pins){
                $STH = $DBH->prepare("
                                       UPDATE data_pins_new
                                       SET track_type = 'orphan'
                                       WHERE pin_id = :pin_key");
                $STH->execute(array(":pin_key" => $pins->pin_id));
            }

            $STH = $DBH->prepare("
                                 UPDATE map_traffic_pins_boards
                                      SET calced_flag = 2
                                      WHERE board_slug = :board_slug");
            $STH->execute(array(":board_slug" => $board_slug->board_slug));
        }

        CLI::write(Log::debug('Getting PINS from Data'));
        foreach($pins_from_resp_data as $data){
            if (isset($data->id)){
            array_push($pins_from_resp, $data->id);
            }
        }


        CLI::write(Log::debug(
                      'Handling boards where we found the pins we were looking for.' .
                      ' Setting calced_flag in map_traffic_pins_boards = 1 (completed)'
        ));
        foreach($unique_boards as $board){

            //Pulling all the pins for a given board
            $STH = $DBH->prepare("
                                             SELECT pin_id
                                             FROM map_traffic_pins_boards
                                             WHERE board_slug = :board_slug");
            $STH->execute(array(":board_slug" => $board->board_slug));
            array_push($pins_unique_board, $STH->fetchAll());

            $STH = $DBH->prepare('UPDATE map_traffic_pins_boards
                                      SET calced_flag = 1
                                      WHERE board_slug = :board_slug');
            $STH->execute(array(":board_slug" => $board->board_slug));
        }


        foreach($pins_unique_board as $pins){
            foreach($pins as $pin)
                array_push($pins_local, $pin->pin_id);
        }

        echo count($pins_local) . "\n";
        $common_pins = array_intersect($pins_from_resp, $pins_local);

        $lost_pins = array_diff($pins_local,$common_pins);


        CLI::write('The common pins are:');
        var_dump($common_pins);

        CLI::write('The lost pins are:');
        var_dump($lost_pins);

        $pins_to_save = new Pins();

        foreach($common_pins as $pins_keys=>$pin_value){
            if (!isset($pins_from_resp_data[$pins_keys]->parent_pin->id)){
                $pins_from_resp_data[$pins_keys]->parent_pin->id = NULL;
            }
            if (!isset($pins_from_resp_data[$pins_keys]->via_pinner->id)){
                $pins_from_resp_data[$pins_keys]->via_pinner->id = NULL;
            }
            if (!isset($pins_from_resp_data[$pins_keys]->origin_pin->id)){
                $pins_from_resp_data[$pins_keys]->origin_pin->id = NULL;
            }
            if (!isset($pins_from_resp_data[$pins_keys]->origin_pinner->id)){
                $pins_from_resp_data[$pins_keys]->origin_pinner->id = NULL;
            }

            $pin = new Pin();
            $pin_model = $pin->loadAPIData($pins_from_resp_data[$pins_keys]);

            $pins_to_save->add($pin_model);

            if (!(isset($pins_from_resp_data[$pins_keys]->pinner->id))){
                array_push($orphan_pins, $pins_keys);
                foreach($orphan_pins as $okeys=>$ovalues){
                    $STH = $DBH->prepare("
                                       UPDATE data_pins_new
                                       SET track_type = 'orphan'
                                       WHERE pin_id = :pin_key");
                    $STH->execute(array(":pin_key" => $ovalues));
                }
            }
        }

        CLI::write(Log::debug(
                      "Handling pins that were too deep in the board to find." .
                      " Setting calced_flag in map_traffic_pins_boards = 2 (not found)."
        ));
        if (count($lost_pins) > 0){
            foreach($lost_pins as $lpin){
            $STH = $DBH->prepare("
                             UPDATE map_traffic_pins_boards
                                  SET calced_flag = 2
                                  WHERE pin_id = :pin_id");
            $STH->execute(array(":pin_id" => $lpin));
            }
        }

        CLI::write(Log::debug("Setting any boards we had trouble pulling to pull again."));
        foreach($rerun_boards as $rerun){
            $STH = $DBH->prepare("
                                 UPDATE map_traffic_pins_boards
                                      SET calced_flag = 0
                                      WHERE board_slug = :board_slug");
            $STH->execute(array(":board_slug" => $rerun->board_slug));
        }

        CLI::write(Log::debug("Saving pins we've found."));
        try{
            $pins_to_save->insertUpdateDB();
        } catch (CollectionException $e){
            CLI::alert(Log::error('No pins to save | Sleep 2'));
            sleep(2);
        }

        CLI::write(Log::info('Finished saving pins into data_pins_new table'));


        /*
         * Now we want to check the data_traffic_pins table to make sure we are also
         * updating the user_id field there from the pin data we just found
         */
        $stringify_pin_ids = $pins_to_save->stringifyField("pin_id");
        if(!empty($stringify_pin_ids)){

            /*
            * Lets iterate through the pins we just saved to grab only the pin_ids and
            * user_ids for easy reference later
            */
            $pins_with_user_ids = array();
            foreach($pins_to_save as $pin){
                $pins_to_save["$pin->pin_id"] = array();
                $pins_with_user_ids["$pin->pin_id"]['pin_id'] = $pin->pin_id;
                $pins_with_user_ids["$pin->pin_id"]['user_id'] = $pin->user_id;
            }

            CLI::write(Log::debug(
                          "Getting records from data_traffic_pins to check for user_ids: $stringify_pin_ids"
            ));

            $traffic_pins = $DBH->query(
                       "select pin_id, user_id
                        from data_traffic_pins
                        where pin_id in ($stringify_pin_ids)
                        group by pin_id"
            )->fetchAll();

            /*
             * See if any of the pins we found do not have a user_id specified in data_traffic_pins
             */
            CLI::write(Log::debug(
                          'Finding pins in data_traffic_pins without user_ids specified'
            ));
            $traffic_pins_to_update = array();
            foreach($traffic_pins as $pin){

                if(empty($pin->user_id)){
                    array_push($traffic_pins_to_update, $pins_with_user_ids[$pin->pin_id]);

                }
            }

            /*
             * For pins that did not have a user_id specified, we'll update those records here.
             */
            if(count($traffic_pins_to_update) != 0){

                CLI::write(Log::info(
                              'Updating data_traffic_pins with user_ids'
                ));

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
        CLI::write(Log::info('Complete'));

        CLI::write(Log::runtime(). 'total runtime');
        CLI::write(Log::memory().' peak memory usage');

    }

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

