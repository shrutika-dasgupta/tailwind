<?php
/**
 * Keywords Search inside Board Pins found from board search
 *
 * @author Alex
 */

ini_set('memory_limit', '200M');

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use Pinleague\PinterestException;
use Pinleague\CLI;
const TRACK_TYPE = 'user';
$number_of_boards = 40;

Log::setLog(__FILE__);

try {

    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        sleep(20);
        throw new EngineException('Engine is running');
    } else {
        $engine->start();

        $api_rate = engine::current_call_rate();

        if ($api_rate > 70000) {
            CLI::h2(Log::warning('Too many api calls | Sleep 60 seconds'));
            sleep(60);
            $engine->complete();
            exit;
        }

        CLI::write(Log::info('Engine started'));

        $DBH = DatabaseInstance::DBO();
        CLI::write(Log::debug('Connected to Database'));

        $boards_queue = new MapBoardsKeywords();

        /*
         * Get boards from the map_boards_keywords table to pull and search for keyword matches
         * We're looking for boards with a last_pulled_pins value of 0.
         */
        $boards_to_search = $boards_queue->fetchBoardsQueue($number_of_boards);

        /*
         * If no boards returned, we'll take a break before checking again.
         */
        if (empty($boards_to_search)){
            CLI::alert(Log::notice('No more boards to track | Sleep 20'));
            sleep(20);
            $engine->complete();
            exit;
        }

        $boards_from_queue    = array();
        $all_board_ids_queued = array();
        $board_matches        = array();

        /*
         * Create arrays of
         * 1. all of the boards we're searching, so we can store the board/keyword relationship
         * and know which keyword to look for in each board's pins
         *
         * 2. an array of just all board ids, so we can easily cross-reference each of the
         * board_ids we're pulling with data returned
         *
         * 3. set a count for the number of keyword matches for each board we're pulling to 0, so
         * we can add the actual counts to it later as we find matches
         */
        foreach ($boards_to_search as $map_board_keyword){

            $boards_from_queue[$map_board_keyword->board_id] = $map_board_keyword;

            array_push($all_board_ids_queued, $map_board_keyword->board_id);

            $board_matches[$map_board_keyword->board_id] = 0;
        }

        /*
         * Create various arrays and collections we'll need later.
         */
        $wave_number       = 0;
        $bookmarks_array   = array();
        $pull_next_wave    = true;
        $boards_found      = array();
        $board_ids_found   = array();
        $matched_pin_ids   = array();
        $matched_board_ids = array();
        $dead_boards       = array();

        $boards                   = new Boards();
        $pins_from_boards         = new Pins();
        $save_pins                = new Pins();
        $save_map_pins_keywords   = new MapPinsKeywords();
        $save_pinners             = new Profiles();
        $save_descriptions        = new PinDescriptions();
        $save_attributions        = new PinAttributions();


        /*
         * Start pulling each board's pins
         */
        CLI::write(Log::debug('Running waves'));
        while ($wave_number < 1 and $pull_next_wave == true) {

            $wave_number += 1;

            CLI::write(Log::info('Sending wave '. $wave_number . ' with pins:'));

            try {
                $complete_resp =  $boards->getMissingPinInfo(
                                              $boards_to_search,
                                                  $bookmarks = $bookmarks_array);
            } catch (\Pinleague\NoBatchCallsException $e) {
                CLI::alert(Log::notice('No more batched calls to make | Sleep 30'));
                sleep(30);
                $engine->complete();
                CLI::stop();
            }

            /*
             * The response from the getMissingPinInfo() method returns 4 array:
             * We set 3 of them to variables here..
             *
             * 1. the response of all pins from each board,
             * 2. each api response which returned a bookmark
             * 3. any boards that came back with no data, or with errors like "board not found"
             */
            $pins_from_resp_data = $complete_resp['pins_arr'];
            $bookmarks_array     = $complete_resp['bookmarks'];
            $dead_boards_resp    = $complete_resp['dead_boards'];

            /*
             * For boards that had errors, we'll add them to a new "dead_boards" array
             * that we can cross-check later with all of the boards we originally fetched from the
             * queue to pull data.  We'll be able to pick out the records that were returned as
             * "dead" and update them appropriately so we don't pull them again in the future.
             */
            foreach($dead_boards_resp as $dead_board){
                pp("dead_board: " . $dead_board);
                $dead_boards[$dead_board->board_id] = $dead_board->board_id;
            }


            CLI::write('Calculating API hashes');

            /*
             * Parsing through all of the pins we've received from the API
             */
            foreach ($pins_from_resp_data as $pin){

                $pins_from_boards[$pin->id] = $pin;
                $pin_id                     = $pin->id;
                $board_id                   = $pins_from_boards[$pin_id]->board->id;

                $boards_found[$pin_id]      = $board_id;
                $board_ids_found[$board_id] = $board_id;
                $keyword                    = $boards_from_queue[$board_id]->keyword;

                // Build the keyword regex match pattern.
                $pattern = \StatusKeyword::regexMatchPattern($keyword);

                // Add whitespace so that keywords at the beginning and end of the string will be matched.
                $description = ' ' . $pin->description . ' ';


                /**
                 * We want to save the pin data of matched pins to the data_pins_new
                 * table and other tables, so we're adding to the
                 * collections here when we find a match.
                 */
                if (preg_match($pattern, $description)) {
                    $save_pin             = new Pin("keyword");
                    $save_description     = new PinDescription();
                    $save_map_pin_keyword = new MapPinKeyword();
                    $profile              = new Profile();

                    $save_pin = $save_pin->loadApiData($pin);
                    $save_pin->track_type = "keyword";
                    $save_pins->add($save_pin);

                    $save_description->loadPinData($save_pin);
                    $save_descriptions->add($save_description);

                    $save_map_pin_keyword->load($save_pin, $keyword);
                    $save_map_pins_keywords->add($save_map_pin_keyword);

                    $profile->user_id = $pin->pinner->id;
                    $profile->username = "";
                    $profile->last_pulled = 0;
                    $profile->track_type = 'keyword';
                    $profile->timestamp = time();
                    $save_pinners->add($profile);

                    if (!empty($pin->attribution)) {
                        $save_attribution = new PinAttribution();
                        $save_attribution->loadApiData($pin->attribution);
                        $save_attribution->pin_id = $pin->id;
                        $save_attributions->add($save_attribution);
                    }

                    $matched_pin_ids[] = $pin->id;
                    $matched_board_ids[] = $pins_from_boards[$pin_id]->board->id;

                    $board_matches[$board_id]++;
                }

            }
            CLI::write(Log::notice('Found ' . $save_pins->count() . ' keyword matches!'));

            /*
             * If none of the responses came back with bookmarks, we have no more calls to make
             * and can set the "pull_next_wave" variable to false, so that we can exit this loop.
             */
            if(count($bookmarks_array) == 0){
                $pull_next_wave = false;
            }

        }


        /*
         * Here we update all of the boards we've pulled in the map_boards_keywords table.
         *
         * We'll set the last_pulled_pins value for each record we found to the current timestamp
         * and include the number of matches we've found in that given board
         *
         * For any boards that came back as "dead", we'll set the last_pulled_pins value to
         * 2147483647, which is the maximum timestamp, indicating that it's a dead board and
         * making sure that we don't end up pulling it again in the future.
         */

        CLI::write(Log::info('Updating map_boards_keywords records as pulled.'));

        foreach($all_board_ids_queued as $board_id){

            $time              = time();
            $pin_matches_found = $board_matches[$board_id];
            $keyword           = $boards_from_queue[$board_id]->keyword;

            /*
             * Check to see which boards we found results for.
             * If we've found results, we want to set the last_pulled_pins timestamp to the current
             * time and update the number of results found inside that board.
             */
            if(in_array($board_id, $board_ids_found)){
                pp("pin_matches_found: " . $pin_matches_found . ". Keyword: " . $keyword . ". Board_id: " . $board_id);

                $STH = $DBH->prepare("UPDATE map_boards_keywords
                    SET last_pulled_pins = $time
                    , pin_matches_found = pin_matches_found + $pin_matches_found
                    WHERE board_id = $board_id
                    AND keyword = :keyword");

                $STH->execute(array(":keyword" => $keyword));

            } else if (in_array($board_id, $dead_boards)) {

                pp("dead_board: " . $dead_board);

                $STH = $DBH->prepare("UPDATE map_boards_keywords
                    SET last_pulled_pins = 2147483647
                    WHERE board_id = $board_id
                    AND keyword = :keyword");
                $STH->execute(array(":keyword" => $keyword));
            }
        }


        /*
         * Now we save all of the collections to the database:
         *
         * Pins w/ keyword matches get saved to:
         *  data_pins_new
         *  map_pins_keywords
         *  map_pins_descriptions
         *  map_pins_attribution
         *
         * The pinners of matched pins get saved to the data_profiles_new table, so we can pull
         * data on these pinners in case we don't already have data for them.
         *
         */
        if($save_pins->count() != 0){

            CLI::write(Log::info('Saving models to the pin table'));
            try{
                $save_pins->insertUpdateDB();
            }
            catch (CollectionException $e) {
                echo "No more pins to save" . PHP_EOL;
                Log::notice('No more pins to save');
            }



            CLI::write(Log::info('Saving models to the map pins keywords table'));
            try{
                $save_map_pins_keywords->insertUpdateDB();
            }
            catch (CollectionException $e) {
                echo "No more map-pins-keywords to save" . PHP_EOL;
                Log::notice('No more map-pins-keywords to save');
            }



            CLI::write(Log::info('Saving models to the description table'));
            try {
                $save_descriptions->saveModelsToDB();
            }
            catch (CollectionException $e) {
                echo "No more descriptions to save" . PHP_EOL;
                Log::notice('No more descriptions to save');
            }



            CLI::write(Log::info('Saving models to the profiles table'));
            try{
                $save_pinners->saveModelsToDB();
            }
            catch (CollectionException $e) {
                echo "No more profiles to save" . PHP_EOL;
                Log::notice('No more profiles to save');
            }



            CLI::write(Log::info('Saving models to the attributions table'));
            try {
                $save_attributions->insertUpdateDB();
            }
            catch (CollectionException $e) {
                echo "No more attributions to save" . PHP_EOL;
                Log::notice('No more attributions to save');
            }
        }


        $engine->complete();
        CLI::write(Log::info('Complete'));

        CLI::write(Log::runtime(). 'total runtime');
        CLI::write(Log::memory().' peak memory usage');
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
    $engine->fail();
    Log::error($e);
    CLI::stop();

} catch (Exception $e) {

    CLI::alert($e->getMessage());
    $engine->fail();
    Log::error($e);
    CLI::stop();
}
