<?php
/**
 * Finding the pin information from repinned boards from
 * map_traffic_pins_boards
 *
 * The data for the found pins are sent to the update_repin_tree table
 *
 * @author yesh
 */

ini_set('memory_limit', '500M');

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use Pinleague\CLI;

Log::setLog(__FILE__);

$number_of_boards = 40;

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {

        throw new EngineException('Engine is running');

    } else {
        $engine->start();
        CLI::write(Log::info('Engine started'));

        $DBH = DatabaseInstance::DBO();
        CLI::write('Connected to Database');

        $boards_queue = new mapRepinsBoardsPins();

        CLI::write(Log::debug('Get boards from the queue'));
        $board_data = $boards_queue->fetchBoardsQueue($number_of_boards);

        if (empty($board_data)){
            CLI::alert(Log::notice('No more boards to track | Sleep 20'));
            sleep(20);
            $engine->complete();
            exit;
        }

        /**
         * Creating empty arrays to be used in the script.
         *
         * @param array $board_ids_from_db Array of all the board_ids from the
         *                                 database
         * @param array $origin_pins_db_hash Array of all the origin_pins from
         *                                   the database
         * @param array $parent_pins_db_hash Array of all the origin_pins from
         *                                   the database
         * @param array $origin_pins_api_hash Array of origin_pins from the API
         * @param array $parent_pins_api_hash Array of parent_pins from the API
         *
         * @param array $all_pins_api_hash A hashmap of all the pins returned
         *                                 from the API
         *
         *
         */
        $board_ids_from_db = array();

        $origin_pins_db_hash = array();
        $parent_pins_db_hash = array();

        $origin_pins_api_hash = array();
        $parent_pins_api_hash = array();

        $all_pins_api_hash   = array();

        foreach ($board_data as $data){

            array_push($board_ids_from_db,
                            $data->board_id);

            array_push($parent_pins_db_hash, $data->parent_pin);
            array_push($origin_pins_db_hash, $data->origin_pin);
        }

        // CLI::write("Check DB for pins data");

        CLI::write(Log::debug('Getting the unique ids for parent and origin pins'));

        $parent_pins_db = array_unique($parent_pins_db_hash);
        $origin_pins_db = array_unique($origin_pins_db_hash);

        $wave_number     = 0;
        $bookmarks_array = array();
        $pull_next_wave  = true;
        $boards_found    = array();

        $boards_pins = new Boards();
        $matched_pins = new statusRepinsTree();

        CLI::write(Log::debug('Running waves'));
        while ($wave_number < 4 and $pull_next_wave == true) {

            $wave_number += 1;

            CLI::write('Sending wave '. $wave_number);

            try {

                $complete_resp =  $boards_pins
                                  ->getMissingPinInfo(
                                       $board_data,
                                       $bookmarks = $bookmarks_array);

            } catch (\Pinleague\NoBatchCallsException $e) {

                CLI::alert(Log::notice('No more batched calls to make | Sleep 30'));
                sleep(30);
                $engine->complete();
                CLI::stop();
            }

            /**
             * @param array $pins_from_resp_data All the pins we have
             *                                   received from the
             *                                   response from the API
             * @param array $bookmarks_array A hashmap of all the pin_ids
             *                               and their associated bookmark
             *
             */

            $pins_from_resp_data = $complete_resp['pins_arr'];
            $bookmarks_array     = $complete_resp['bookmarks'];

            CLI::write(Log::debug('Calculating API hashes'));

            foreach ($pins_from_resp_data as $data){

                $all_pins_api_hash[$data->id] = $data;

                if (!empty($data->parent_pin)) {
                    $parent_pins_api_hash[$data->id] =
                                            $data->parent_pin->id;
                }
                if (!empty($data->origin_pin)) {
                    $origin_pins_api_hash[$data->id] =
                                            $data->origin_pin->id;
                }
            }

            /**
             * We check against the arrays we have received from the
             * database as well as the API if there have been any
             * matches from the parent_pin_id and and the origin_pin_id
             *
             * And we merge both the matches the array $all_matches_pins
             */

            CLI::write(Log::debug('Find the origin and parent pin matches'));
            $matched_parent_pins = array_intersect(
                                        $parent_pins_api_hash,
                                        $parent_pins_db_hash
                                    );

            $matched_origin_pins = array_intersect(
                                        $origin_pins_api_hash,
                                        $origin_pins_db_hash
                                    );

            $all_matches_pins = $matched_parent_pins + $matched_origin_pins;

            /**
             * We want to save the pin data of matched pins to the data_pins_new
             * table, so we're making a collection of pins here.
             */

            CLI::write(Log::debug('Save matched pin data to DB'));
            $pins_to_save = new Pins();

            foreach ($all_matches_pins as $pin_id => $value) {

                /**
                 * Add the matched pin into the pin collection
                 */
                $pin = new Pin();
                $pin->loadAPIData($all_pins_api_hash[$pin_id]);
                $pin->track_type = 'repin_tree';

                $pins_to_save->add($pin);

                $boards_found[$pin_id] = $all_pins_api_hash[$pin_id]->board->id;
            }

            $pins_to_save->insertUpdateDB();

            $board_data = array_diff($board_ids_from_db, $boards_found);

            $all_matches_pins = array();

            if (empty($board_data)){
                $pull_next_wave = false;
            }
        }

        CLI::write(Log::info('Load data into status_repin_tree model'));
        foreach($boards_found as $pin_id => $board_id){
            $matched_pin = new statusRepinTree();

            $matched_pin->loadAPIData($all_pins_api_hash[$pin_id]);
            $matched_pins->add($matched_pin);
        }

        $board_ids_db_implode = implode(',',
                                    $board_ids_from_db);

        $DBH->query("UPDATE map_repins_boards_pins
                    SET flag = 1
                    WHERE board_id in ($board_ids_db_implode)");

        CLI::write('Save pin_ids to status_repin_tree table');
        try {
            $matched_pins->insertUpdateDB(array('last_pulled_boards'));
        } catch (CollectionException $e) {
            CLI::alert(Log::notice('No more matched pins to save'));
        }

        $engine->complete();
        CLI::write(Log::info('Complete'));

        CLI::write(Log::runtime(). 'total runtime');
        CLI::write(Log::memory().' peak memory usage');

    }

} catch (EngineException $e) {

    CLI::alert($e->getMessage());
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

