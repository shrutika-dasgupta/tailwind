<?php
chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use Pinleague\CLI;

$pin_id_limit = 10;

Log::setLog(__FILE__, 'CLI');

try {
    CLI::h1('Starting Program');
    $engine  = new Engine(__FILE__);
    $started = microtime(true);

    if ($engine->running()) {
        Log::error('Engine is already running');
        CLI::sleep(15);
        CLI::stop();
    }

    $pinterest = new Pinterest();
    $pin_ids   = array();

    $engine->start();
    Log::info('Engine started');

    $DBH = DatabaseInstance::DBO();

    Log::info('Grabbing empty Pin_ids');
    $empty_pins = $DBH->query("
                             SELECT pin_id
                             FROM data_pins_new
                             WHERE last_pulled = 0
                             and pin_id > 10000000000
                             LIMIT $pin_id_limit")
                 ->fetchAll();

    if (empty($empty_pins)){
        CLI::alert(Log::notice('No more pins to pull | Sleep 20'));
        CLI:sleep(20);
        $engine->complete();
        CLI::stop();
    }

    foreach ($empty_pins as $pins) {
        $pin_ids[$pins->pin_id] = $pins->pin_id;
    }

    Log::info('Calling Public method', $empty_pins);

    $resp = $pinterest->getPublicPinInformation($pin_ids);

    $pins_returned          = new Pins();
    $pins_not_found         = new Pins();
    $pinners                = new Profiles();
    $map_traffic_pins_users = new MapTrafficPinsUsers();
    $status_footprints      = new StatusFootprints();

    Log::debug('Checking to see if any pins returned a successful response.');
    if(empty($resp)){

        Log::debug('No response received on any pins. Skipping all of them.');
        foreach($pin_ids as $pin_id){
            $pin = new Pin();
            $pin->pin_id = $pin_id;
            $pin->last_pulled = time();
            $pin->track_type = "skipped";
            $pin->timestamp = time();
            $pins_not_found->add($pin);
        }
    }

    $no_response_pins = array_diff_key($pin_ids, $resp);

    foreach($no_response_pins as $pin_id){
        $pin = new Pin();
        $pin->pin_id = $pin_id;
        $pin->last_pulled = time();
        $pin->track_type = "skipped";
        $pin->timestamp = time();
        $pins_not_found->add($pin);
    }

    Log::debug('Parsing successful responses and processing the data.');

    foreach ($resp as $key => $value) {

        if (!empty($value)) {

            /**
             * Check to make sure the pin still exists.
             */
            if(array_key_exists('error', $value)) {

                /**
                 * Save a pin as not_found, so we don't pull it again
                 */
                $pin = new Pin();
                $pin->pin_id = $value['id'];
                $pin->last_pulled = time();
                $pin->timestamp = time();
                if($value['error'] == "Not found"){
                    $pin->track_type = "not_found";
                } else {
                    $pin->track_type = $value['error'];
                }

                $pins_not_found->add($pin);

            } else {

                /**
                 * Build Pin model and add to collection of pins with successful response
                 */
                $pin = new Pin();
                $pin->pin_id = $value['id'];
                $pin->user_id = $value['pinner']['id'];
                $pin->board_id = $value['board']['id'];
                $pin->description = $value['description'];
                $pin->link = $value['link'];
                $pin->domain = str_replace("www.", "", parse_url($value['link'], PHP_URL_HOST));
                $pin->last_pulled = time();
                $pin->track_type = "traffic";
                $pin->timestamp = time();
                foreach($value['images'] as $image){
                    $pin->image_url = $image['url'];
                }
                if(!empty($value['dominant_color'])){
                    $pin->dominant_color = $value['dominant_color'];
                }

                $pins_returned->add($pin);

                /**
                 * Save board details ro use later
                 */
                $board_details[$value['board']['id']] = ['board_id' => $value['board']['id'],
                                                       'user_id'  => $value['pinner']['id']];

                /**
                 * Build Pinner Profile Model and add to collection
                 */
                $pinner = new Profile();
                $pinner->user_id = $value['pinner']['id'];
                $pinner->username = str_replace("http://www.pinterest.com/", "", $value['pinner']['profile_url']);
                $pinner->username = str_replace("/", "", $pinner->username);
                $pinner->image  = $value['pinner']['image_small_url'];
                $pinner->track_type = "traffic";
                $pinner->last_pulled = time();
                $pinner->timestamp = time();

                $pinners->add($pinner);


                /**
                 * Add pins to map_traffic_pins_boards collection so we can find them on
                 * their board and fill in the remaining details
                 * like created_at and engagement counts
                 */
                $map_traffic_pins_user = new MapTrafficPinsUser();
                $map_traffic_pins_user->pin_id = $value['id'];
                $map_traffic_pins_user->user_id = $value['pinner']['id'];
                $map_traffic_pins_user->timestamp = time();
                $map_traffic_pins_user->calced_flag = 0;

                $map_traffic_pins_users->add($map_traffic_pins_user);

            }
        }
    }

    Log::info("Found data for " . $pins_returned->count() . " pins.");
    try{
        /**
         * Just for posterity, we'll ignore updates for any fields we don't get back from the
         * public API pins endpoint (or set manually above), so we make sure we're not overwriting
         * data we might already have on this pin with NULL values. (in case the pin was found
         * through other sources, e.g. domain pins)
         *
         * In other words, only update values of fields we've explicitly set in this script.
         */
        $pins_returned->insertUpdateDB(
                      ['method',
                      'is_repin',
                      'parent_pin',
                      'via_pinner',
                      'origin_pin',
                      'origin_pinner',
                      'image_square_url',
                      'location',
                      'rich_product',
                      'repin_count',
                      'like_count',
                      'comment_count',
                      'created_at']
        );
    } catch (CollectionException $e){
        Log::notice('No pins found to save');
    }

    Log::debug("Received 'Not Found' Error for " . $pins_not_found->count() . " pins.");
    try{
        /**
         * Just for posterity, we'll ignore updates for all fields we're not explicitly
         * setting above, so we make sure we're not overwriting
         * data we might already have on this pin with NULL values. (in case the pin was found
         * through other sources, e.g. domain pins)
         *
         * In other words, only update values of fields we've explicitly set in this script.
         */
        $pins_not_found->insertUpdateDB(
                       ['user_id',
                       'board_id',
                       'domain',
                       'method',
                       'is_repin',
                       'parent_pin',
                       'via_pinner',
                       'origin_pin',
                       'origin_pinner',
                       'image_url',
                       'image_square_url',
                       'link',
                       'description',
                       'location',
                       'dominant_color',
                       'rich_product',
                       'repin_count',
                       'like_count',
                       'comment_count',
                       'created_at']
        );
    } catch (CollectionException $e){
        Log::info('No pins with errors found to save');
    }

    Log::info("Saving Pinners profiles");
    try{
        $pinners->insertIgnoreDB();
    } catch (CollectionException $e){
        Log::notice('No pinners found to save');
    }

    Log::info("Saving users to map_traffic_pins_users table");
    try{
        $map_traffic_pins_users->insertUpdateDB(array('calced_flag'));
    } catch (CollectionException $e){
        Log::notice('No pins/users found to save to the map_traffic_pins_boards table');
    }

    /**
     * Now we want to check the data_traffic_pins table to make sure we are also
     * updating the user_id field there from the pin data we just found
     */
    $stringify_pin_ids = $pins_returned->stringifyField("pin_id");
    if(!empty($stringify_pin_ids)){

        /**
         * Lets iterate through the pins we just saved to grab only the pin_ids and
         * user_ids for easy reference later
         */
        $pins_with_user_ids = array();
        foreach($pins_returned as $pin){
            $pins_with_user_ids["$pin->pin_id"]['pin_id'] = $pin->pin_id;
            $pins_with_user_ids["$pin->pin_id"]['user_id'] = $pin->user_id;
        }

        /*
         * See if any of the pins we found do not have a user_id specified in data_traffic_pins
         */
        Log::debug('Finding pins in data_traffic_pins without user_ids specified');
        $traffic_pins = $DBH->query(
                            "SELECT pin_id, user_id
                            FROM data_traffic_pins
                            WHERE pin_id in ($stringify_pin_ids)
                            AND user_id = 0
                            GROUP BY pin_id"
        )->fetchAll();

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

    /**
     * Create an array of unique board_ids of the pins we've successfully pulled
     */
    foreach ($board_details as $key => $detail) {
        $all_board_ids[] = $key;
    }

    Log::debug('Updating board data in traffic_pins table where we can.');
    /**
     * If we got any successful responses with pin data, we should have an array of
     * board_ids to look up.
     */
    if (count($all_board_ids) > 0) {

        /**
         * Get board categories for any boards we've already got saved in the DB
         */
        $all_board_ids_implode = implode(",", $all_board_ids);
        $STH = $DBH->query("SELECT board_id, category
                            FROM data_boards
                            WHERE board_id in ($all_board_ids_implode)");

        $boards_from_db = $STH->fetchAll();

        /**
         * Store a set of unique board_ids for boards we already have data on, and
         * store their categories so we can insert them into the traffic_pins table
         */
        foreach($boards_from_db as $board) {
            $board_ids_from_db[] = $board->board_id;
            $board_categories[$board->board_id] = $board->category;
        }

        /**
         * Go through each pin and fill in the board_id and category fields in the
         * traffic_pins table to complete the data-append.
         */
        foreach ($pins_returned as $pin) {

            /**
             * Check to see whether we've got data on the boards that each pin was pinned to.
             *
             * If we do, then we'll update the data_traffic_pins_new table with the details of
             * this board to complete the picture.
             */
            if (in_array($pin->board_id, $board_ids_from_db)) {
                $STH = $DBH->prepare("UPDATE data_traffic_pins_new
                                     SET category = :category,
                                     board_id = :board_id
                                     WHERE pin_id = :pin_id");
                $STH->execute(
                    [
                    ":category" => $board_categories[$pin->board_id],
                    ":board_id" => $pin->board_id,
                    ":pin_id"   => $pin->pin_id
                    ]
                );
            }
        }

        /**
         * Get a list of boards that we didn't already have data for, and queue up their
         * corresponding pinners' user_ids to the status_footprint table, where each will
         * have their boards pulled.
         */
        $board_ids_to_pull = array_diff($all_board_ids,
            $board_ids_from_db);

        if (!empty($board_ids_to_pull)) {
            foreach ($board_ids_to_pull as $board) {
                $status_footprint = new StatusFootprint();

                $status_footprint->user_id    = $board_details[$board]['user_id'];
                $status_footprint->track_type = 'pinner';
                $status_footprint->last_run   = 0;

                $status_footprints->add($status_footprint);
            }
        }
    }

    Log::info("Saving user ids to status_footprint to pull boards");
    try {
        $status_footprints->insertIgnoreDB();
    } catch (CollectionException $e){
        Log::notice('No user_ids to save to status_footprint');
    }


    $engine->complete();
    CLI::yay(Log::info('Engine completed'));

    Log::runtime();
    Log::memory();
}
catch (EngineException $e) {

    CLI::alert($e->getMessage());
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
