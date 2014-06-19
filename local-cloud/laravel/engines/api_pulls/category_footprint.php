<?php
/**
 * Category Footprint
 * The script creates a hash (uuid/footprint) for each user. The actual
 * logic for the generation of the hash is explained in detail in the
 * categoryfootprint Collection.
 *
 *
 * Through out this script there is a usage of the word hash and hashmap.
 * Wrt the script a hash is a unique identifier, a hashmap is a assosiative
 * array, dictionary etc
 *
 * @author Yesh
 */

ini_set('memory_limit', '500M');

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use Pinleague\PinterestException;
use Pinleague\CLI;

$numberOfCallsInBatch = 150;

Log::setLog(__FILE__);

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        throw new EngineException('Engine is running');
    } else {
        $start = microtime(true);

        CLI::write(Log::info('Engine started'));

        // Keep a check out for the API RATE
        //  would exit if the limit crosses 70000

        $api_rate = engine::current_call_rate();

        if ($api_rate > 70000) {
            $engine->complete();
            sleep(300);
            CLI::h2(Log::warning('Too many api calls | Sleep 300'));
        }

        $DBH = DatabaseInstance::DBO();
        CLI::write('Connected to Database');

        $Pinterest              = Pinterest::getInstance();
        $Pinterest->batch_calls = true;

        // Fetch user_ids from status_footprint table with flag 0
        $user_ids_from_db = new CategoryFootprints();
        $user_ids_from_db = $user_ids_from_db->fetchUserIds($numberOfCallsInBatch);

        if(empty($user_ids_from_db)){
            CLI::alert(Log::notice('No more users to pull - going idle.'));
            $engine->idle();
            exit;
        }

        CLI::write(Log::info('Making the batch call'));

        // This array is created in order to make the SQL
        // query to set the flag for completed profiles
        $user_ids_flag = array();

        foreach($user_ids_from_db as $user_id) {
            array_push($user_ids_flag, $user_id->user_id);
            $Pinterest->getProfileBoards($user_id->user_id);
        }

        //Making the batch call

        $user_details = $Pinterest->sendBatchRequests();

        /**
         * Initialization of arrays to be used for setting or resetting
         * the flag
         *
         * @param array $users_to_rerun User_ids that need to rerun in the
         *                              next run
         *
         * @param array $users_to_remove User_ids that need to removed when
         *                               they error out
         *
         * @param array $user_ids_active User_ids that are active and have
         *                               boards
         */
        $users_to_rerun  = array();
        $users_to_remove = array();
        $user_ids_active = array();

        CLI::write(Log::info('Making the category hashmap'));

        /**
         * Creating a hashmap between username and all the boards of
         * that user
         * Example: {$user_boards : [ {$user_name_1 : $category_data},
         *                          {$user_name_2 : $category_data},
         *                          ...]
         *         }
         */

        $counter = 0;
        foreach($user_details as $details){
            if(!is_object($details)){
                Log::error('The response is NULL ' .
                            $user_ids_from_db[$counter]->user_id);
            } else {
                switch ($details->code){
                    case 0:
                        if (!empty($details->data)) {
                            $user_boards[$user_ids_from_db[$counter]->user_id] = $details->data;
                            $user_categories[$user_ids_from_db[$counter]->user_id] = array();
                            break;
                        } else {
                            Log::warning('The response has code 0, but there is no data for ' .
                                             $user_ids_from_db[$counter]->user_id);
                            break;
                        }
                    case 10:
                        CLI::write(Log::warning('Bookmark not found: ' . $details->code .
                            ' for: ' . $user_ids_from_db[$counter]->user_id));
                        apierror::create(
                                    'category-footprint',
                                    $user_ids_from_db[$counter]->user_id,
                                    $details->message,
                                    'category-footprint- ' . __line__,
                                    $details->code,
                                    $details->bookmark
                        );

                        array_push($users_to_remove,
                                   $user_ids_from_db[$counter]->user_id);

                        break;

                    case 11:
                        CLI::write(Log::error('API method not found: ' . $details->code .
                            ' for: ' . $user_ids_from_db[$counter]->user_id));
                        apierror::create(
                                    'category-footprint',
                                    $user_ids_from_db[$counter]->user_id,
                                    $details->message,
                                    'category-footprint- ' . __line__,
                                    $details->code,
                                    $details->bookmark
                        );

                        array_push($users_to_remove,
                                   $user_ids_from_db[$counter]->user_id);

                        break;

                    case 12:
                    case 16:
                        CLI::write(
                            Log::warning('Pinterest error on their end (resetting call): Code: '
                            . $details->code .
                            ' for: ' . $user_ids_from_db[$counter]->user_id));
                        apierror::create(
                                    'category-footprint',
                                    $user_ids_from_db[$counter]->user_id,
                                    $details->message,
                                    'category-footprint- ' . __line__,
                                    $details->code,
                                    $details->bookmark
                        );

                        array_push($users_to_rerun,
                                   $user_ids_from_db[$counter]->user_id);

                        break;

                    case 30:
                        /*
                         * Checking the number of times we've tried to pull this user's boards and received
                         * a 'user not found' error
                         */

                        CLI::write(Log::warning('User NOT FOUND ' . $details->code .
                            ' for: ' . $user_ids_from_db[$counter]->user_id));
                        apierror::create(
                                    'category-footprint',
                                    $user_ids_from_db[$counter]->user_id,
                                    $details->message,
                                    'category-footprint- ' . __line__,
                                    $details->code,
                                    $details->bookmark
                        );


                        $STH = $DBH->prepare('
                            select api_call from status_api_errors
                            where api_call = :api_call
                            AND object_id = :object_id
                            AND code = :code
                        ');

                        $STH->execute(
                            array(
                                 ':api_call'  => 'category-footprint',
                                 ':object_id' => $user_ids_from_db[$counter]->user_id,
                                 ':code'      => $details->code
                            )
                        );

                        array_push($users_to_remove,
                                   $user_ids_from_db[$counter]->user_id);

                        break;
                }
            }
            $counter ++;
        }

        CLI::write(Log::debug('Making the boards per category hashmap'));

        /**
         * The logic here is to create a user_categories hashmap. Which for
         * each user aggregates the boards with same categories
         * that user
         * Example: {$user_categories : [ {'geek' : [$board_data_1, $board_data_2]},
         *                                {'food_drinks' : [$board_data]},
         *                          ...]
         *         }
         */

        $board_collection = new Boards();

        foreach($user_boards as $user_id => $boards){
            if ($boards != NULL){

                // These are the user_ids who do have boards
                array_push($user_ids_active, $user_id);
                foreach($boards as $board){

                    $board_details[$board->id] = ["category" => $board->category];
                    $board_ids[] = $board->id;

                    $board_data = new Board();
                    $board_data->user_id = $user_id;
                    $board_data   = $board_data->loadAPIData($board);
                    $board_data->track_type = 'footprint';
                    $board_collection->add($board_data);

                    if(!empty($board->category)){
                        if(isset($user_categories[$user_id][$board->category])){
                            array_push($user_categories[$user_id][$board->category],
                                       $board);
                        } else {
                            $user_categories[$user_id][$board->category] = array();
                            array_push($user_categories[$user_id][$board->category],
                                       $board);
                        }
                    }
                }
            }
        }

        if (count($board_ids) > 0) {
            $board_ids_implode = implode(",", $board_ids);

            $STH = $DBH->query("SELECT pin_id, board_id
                         FROM data_traffic_pins_new
                         WHERE board_id in ($board_ids_implode)
                         AND category=''");

            $pins_to_update = $STH->fetchAll();

            foreach($pins_to_update as $pin) {

                $STH = $DBH->prepare("UPDATE data_traffic_pins_new
                                      SET category = :category
                                      WHERE pin_id = :pin_id");

                $STH->execute([":category" => $board_details[$pin->board_id]["category"],
                               ":pin_id" => $pin->pin_id]);
            }
        }

        $user_footprint = array();

        CLI::write(Log::info('Calculate data for creating hashes'));


        /**
         * We iterate the user_categories array and record the following
         * parameters
         *
         * @param int $total_board_count_indv
         * @param int $total_pin_count_indv
         * @param int $total_followers_count_indv
         * @param int $total_board_count_collab
         * @param int $total_pin_count_collab
         * @param int $total_followers_count_collab
         *
         * We also calculate the recency of each board
         *
         */
        foreach($user_categories as $user_id => $user_category){

            foreach($user_category as $category_name => $boards){

                $total_board_count_indv       = 0;
                $total_pin_count_indv         = 0;
                $total_followers_count_indv   = 0;

                $total_board_count_collab     = 0;
                $total_pin_count_collab       = 0;
                $total_followers_count_collab = 0;

                foreach($boards as $board){

                    if ($board->is_collaborative === false) {
                        $total_board_count_indv     += 1;
                        $total_pin_count_indv       += $board->pin_count;
                        $total_followers_count_indv += $board->follower_count;

                    } elseif ($board->is_collaborative === true and
                              $user_id == $board->owner->id) {

                        $total_board_count_collab     += 1;
                        $total_pin_count_collab       += $board->pin_count;
                        $total_followers_count_collab += $board->follower_count;
                    }
                }



                if (!($total_board_count_indv === 0)){
                    // Calculate Activity for individual boards
                    $user_footprint[$user_id][$category_name]['activity'] =
                                $total_pin_count_indv;

                    // Calculate Influence for individual boards
                    $user_footprint[$user_id][$category_name]['influence'] =
                        round(($total_followers_count_indv
                                / $total_board_count_indv));

                    // Calculate board_count for individual boards
                    $user_footprint[$user_id][$category_name]['board_count'] =
                                $total_board_count_indv;
                }

                if (!($total_board_count_collab === 0)){

                    // Calculate Activity for individual boards
                    $user_footprint[$user_id][$category_name]['activity_collab'] =
                            $total_pin_count_collab;

                    // Calculate Influence for individual boards
                    $user_footprint[$user_id][$category_name]['influence_collab'] =
                        round(($total_followers_count_collab
                                / $total_board_count_collab));

                    // Calculate board_count for individual boards
                    $user_footprint[$user_id][$category_name]['board_count_collab'] =
                        $total_board_count_collab;
                }

                // Calculate recency
                $user_footprint[$user_id][$category_name]['recency'] =
                            strtotime($board->created_at);
            }

        }

        CLI::write(Log::info('Creating Category footprint hash [uuid]'));
        $user_hashs = new CategoryFootprints();

        foreach ($user_footprint as $user_id=> $user){

            $user_hash = new CategoryFootprint();

            $user_hash                 = $user_hash->createHash($user);
            $user_hash->user_id        = $user_id;
            $user_hash->footprint_hash =
                $user_hash->userFootprint($user_hash);

            $user_hashs->add($user_hash);
        }

        CLI::write(Log::info('Insert hash to DB'));
        try{
            $user_hashs->insertUpdateDB();
        } catch (CollectionException $e){
            CLI::write(Log::info('No more user_hashes to save'));
        }

        CLI::write(Log::info('Insert boards to DB'));
        try{
            $board_collection->insertUpdateDB($ignore_these_columns = array('track_type'));
        } catch (CollectionException $e){
            CLI::write(Log::info('No more user_hashes to save'));
        }

        CLI::write(Log::info('Inserted boards'));
        $user_ids_flag_implode = implode(",", $user_ids_flag);

        CLI::write(Log::info('Set last run for user_ids that are active'));
        $DBH->query("UPDATE status_footprint
                    SET last_run = UNIX_TIMESTAMP(now())
                    WHERE user_id in ($user_ids_flag_implode)");

        CLI::write(Log::info('Set last_run for deleted user_ids'));

        $user_ids_deleted = (array_diff($user_ids_flag,
                                        $user_ids_active));
        foreach ($users_to_remove as $user_id) {
            array_push($user_ids_deleted,
                       $user_id);
        }
        $user_ids_deleted_implode = implode(",", array_unique($user_ids_deleted));


        if(!empty($user_ids_deleted_implode)){
            $DBH->query("UPDATE status_footprint
                    SET last_run = 2
                    WHERE user_id in ($user_ids_deleted_implode)");
        }

        CLI::write(Log::info('Set last_run back to 0 to rerun'));

        $users_to_rerun_implode = implode(",", $users_to_rerun);

        if(!empty($users_to_rerun_implode)){
            $DBH->query("UPDATE status_footprint
                    SET last_run = 0
                    WHERE user_id in ($users_to_rerun_implode)");
        }

        Log::info('Engine completed | Sleep 20');

        sleep(20);

        $engine->complete();

        CLI::write(Log::runtime(). 'total runtime');
        CLI::write(Log::memory().' peak memory usage');
    }
}
catch (EngineException $e) {

    CLI::alert($e->getMessage());
    CLI::stop();

}
catch (PinterestException $e) {

    CLI::alert($e->getMessage());
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
