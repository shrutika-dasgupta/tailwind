<?php
/** This script is used for pulling pins based on the category name
 *  Eg: php pull_category_pins.php geek
 *
 * @author : Yesh
 */

ini_set('memory_limit', '5000M');

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use PinLeague\Pinterest\PinterestException;
use Pinleague\CLI;


$category_name = $argv[1];
Log::setLog(false,
            'Tailwind',
            basename(__FILE__, ".php") . "_" . $category_name);

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__ . " " . $category_name);

    // Check to see if the process is already running
    // if it is, complete and exit

    exec("ps aux| grep 'pull_category_pins.php $category_name'", $processes);

    /**
     * The reason we are checking for 3 processes here is
     * because it runs the following processes
     * 1. ps aux| grep 'pull_category_pins.php $category_name'
     * 2. grep pull_category_pins.php $category_name
     * 3. pull_category_pins.php $category_name
     * If it finds more than 3 running. we know for sure it is a rouge script
     *
     * If you want to run it within vagrant increase the processes number to 5.
     */

    if(count($processes) > 5){
        CLI::write(Log::warning("Running way too many processes for $category_name" , $processes));
        CLI::stop();
    }

    if ($engine->running()) {

        CLI::write(Log::notice("Engine already running - exiting"));
        throw new EngineException('Engine is running');

    } else {
        $engine->start();
        $start = microtime(true);

        CLI::write(Log::info('Engine started'));
        $Pinterest = new Pinterest();

        // Keep a check out for the API RATE
        //  would exit if the limit crosses 70000

        $api_rate = engine::current_call_rate();
        CLI::write(Log::info('Current calls: '.$api_rate));

//        if ($api_rate > 75000) {
//            $engine->complete();
//            sleep(30);
//        }

        $DBH = DatabaseInstance::DBO();
        CLI::write(Log::debug('Connected to Database'));

        if(is_null($DBH)){
            CLI::alert('Can not connect to the database');
            Log::error('DBH object empty. Cannot connect to database');
            sleep(10);
            exit;
        }
        // Check if the consume script has been running
        // in the last 1 hr. If not exit the current script
        // This is done so as to avoid a avalanche of pins in
        // the pull categories

        $file_name = "api_pulls-consume_category_pins.php". " ". $category_name;

        $STH = $DBH->prepare("SELECT timestamp
                            FROM status_engines
                            WHERE engine = :file_name");
        $STH->execute(array(":file_name" => $file_name));

        $consume_runtime = $STH->fetchAll();

        $consume_run_limit = strtotime('+1 hours',
                                $consume_runtime[0]->timestamp);

        $current_time = time();

        if ($consume_run_limit < $current_time){
            $engine->complete();
            CLI::write(Log::error('Consume script not running | Sleep 15'));
            sleep(15);
            exit;
        }

        $bookmark_count   = 0;
        $total_pins       = array();
        $bookmark_present = true;
        $response         = array();

        // Making the initial request and if the call fails
        // because of pinterest error the call fails and the
        // script is rerun after 30 seconds

        try{
            $response = $Pinterest->getCategoryFeed($category_name);
        } catch (PinterestException $e){

            ApiError::create(
                'Category Pull',
                $category_name,
                $e->getMessage(),
                'from pull_category pins script',
                $e->getCode()
            );

            pp($e->getCode());

            if(in_array($e->getCode(), array(12, 16))) {
                CLI::alert(Log::warning('Pinterest error '.$e->getCode().': '.$e->getMessage().' | Sleep 30'));
            } else {
                CLI::alert(Log::error('Pinterest error '.$e->getCode().': '.$e->getMessage().' | Sleep 30'));
            }

            CLI::sleep(30);
            $engine->fail();
            CLI::stop();
        }

        /**
         * Sanity check to see if we got back a sane response
         */
        if(is_null($response) || empty($response['data'])){
            CLI::alert(Log::warning('Getting back NULL or EMPTY responses for ' . $category_name . ' |Sleep 30 and exit'));
            sleep(30);
            $engine->fail();
            exit;
        }

        CLI::write(Log::info('Fetched category feed '.$category_name));

        array_merge($total_pins, $response);

        CLI::write(Log::debug('Checking if the response data exists in DB'));

        /**
         * Check to see whether category pins we've pulled are all new, or if they overlap with
         * pins we already have in the database (status_category_feed_queue table)
         */
        $more_requests_to_make = CategoryFeedQueue::doesDataExist(
                                        $response['data'],
                                        $category_name);
        $categories_feeds_queue  = new CategoryFeedsQueues();
        $pins                  = new Pins();
        $promoter_categories   = new MapPinPromoters();
        $profiles              = new Profiles();

        /**
         * Create a sql statement to save user_ids to the data_profiles_new
         * table so that their data can be pulled.
         */

        $sql           = "";
        $insert_values = array();

        /** The logic behind the below while loop is that we check if
         * the is overlap of pin data from the database with pins from the
         * API using the doesDataExist method
         *
         */

        CLI::write(Log::debug('Making more requests for new data'));

        while ($more_requests_to_make === true) {

            foreach ($response['data'] as $data) {
                $pin                 = new Pin();
                $category_feed_queue = new CategoryFeedQueue($category_name);
                $promoter_category   = new MapPinPromoter();

                $profile = new Profile();
                $profile->user_id = $data['pinner']['id'];
                $profiles->add($profile);

                // Checking for promoter pins in the category feed
                try{
                  if(isset($data['promoter'])){
                      $promoter_category->pin_id         = $data['id'];
                      $promoter_category->promoter_id    = $data['promoter'];
                      $promoter_category->feed           = MapPinPromoter::FEED_CATEGORY;
                      $promoter_category->feed_attribute = $category_name;
                      $promoter_category->timestamp      = time();
                      $promoter_categories->add($promoter_category);
                  }
                } catch(CollectionException $e){
                  echo $e . PHP_EOL;
                }


                $pin_data                 = $pin->loadAPIData($data);
                $category_feed_queue_data = $category_feed_queue->loadAPIData($pin_data);

                $categories_feeds_queue->add($category_feed_queue_data);
            }
            /**
             * We'll go about 4 bookmarks down the category feed rabbit-hole at most.
             * So, the "if" loop below checks how deep we are in the feed and if we do
             * have the next bookmark. We also check if there is a overlap of pins
             * from the DB and API. If there are, we exit the loop
             *
             */
            if(($bookmark_count < 4) and isset($response['bookmark'])){

                try{
                    $response = $Pinterest->getCategoryFeed($category_name,
                                        array('bookmark' => $response['bookmark']));
                } catch (PinterestException $e){
                      ApiError::create(
                          'Category Pull',
                          $category_name,
                          $e->getMessage(),
                          'from pull_category pins script',
                          $e->getCode(),
                          $response['bookmark']
                      );

                      /**
                       * Handle Pinterest Error Response
                       */
                      if (!in_array($e->getcode(), array(12, 16))){
                          CLI::alert(Log::error('Pinterest Error: ' . $e->getCode() .
                                                ' Message: ' . $e->getMessage() .  ' |Sleep 30'));
                      } else {
                          CLI::alert(Log::warning('Pinterest Server Error: ' . $e->getCode() .
                              ' Message: ' . $e->getMessage() .  ' |Sleep 30'));
                      }

                      sleep(30);
                      $engine->fail();
                      exit;
                }

                $bookmark_count ++;

                $more_requests_to_make = CategoryFeedQueue::doesDataExist(
                                            $response['data'],
                                            $category_name
                                        );

                if ($bookmark_count == 4){
                    $more_requests_to_make = false;
                }
            } else {
               $more_requests_to_make = false;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Saving to DB
        |--------------------------------------------------------------------------
         */

        CLI::write(Log::debug("Adding Category pins to track categories table"));
        try{
            $categories_feeds_queue->saveModelsToDB();
            CLI::write(Log::info('Saved cateogry pins to track categories table'));
        } catch (CollectionException $e) {
            CLI::alert(Log::notice("No category pins to save"));
        }

        CLI::write(Log::debug("Adding pins to promoter table"));
        try{
            $promoter_categories->insertUpdateDB();
            CLI::write(Log::info('Saved cateogry pins to promoter table'));
        } catch (CollectionException $e){
            CLI::alert(Log::notice("No Promoters to save"));
        }

        /**
         * Inserting user_ids to data_profiles_new,
         * so that they can be pulled
         */
        $profiles->sortByPrimaryKeys();

        foreach($profiles as $profile){
            array_push($insert_values
                , $profile->user_id
                , "track"
                , 0
                , time());

            if($sql == ""){
                $sql = "INSERT IGNORE into data_profiles_new
                                    (user_id, track_type, last_pulled, timestamp)
                                    VALUES
                                    (?, ?, ?, ?)";
            } else {
                $sql .= ",
                                    (?, ?, ?, ?)";
            }
        }

        CLI::write(Log::debug('Inserting user_ids to data_profiles_new'));

        if($sql != ""){
            $STH = $DBH->prepare($sql);
            $STH->execute($insert_values);
        }

        CLI::write(Log::debug('Script completed. Setting engine to complete.'));
        $engine->complete();
        CLI::write(Log::info('Engine completed'));

        CLI::write(Log::runtime(). 'total runtime');
        CLI::write(Log::memory().' peak memory usage');
    }
}
catch (EngineException $e) {
    CLI::alert(Log::error($e));
    CLI::stop();
}
catch (PinterestException $e) {
    CLI::alert(Log::error($e));
    $engine->fail();
    CLI::stop();
}
catch (PDOException $e) {
    CLI::write(Log::error($e));
    $engine->fail();
    CLI::stop();
}
catch (Exception $e) {
    CLI::alert(Log::error($e));
    $engine->fail();
    CLI::stop();
}
