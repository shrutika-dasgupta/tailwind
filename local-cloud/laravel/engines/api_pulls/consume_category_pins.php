<?php

/** The consume script feeds the data from  status_category_feed_queue
 *  table and analyses the data and sorts them by keyword, user_id
 *  and domain
 *
 *  All the timings present in the script are done in order to optimize
 *  the code. They are temporary and will be removed once the bottleneck
 *  has been identified.
 *
 * @author Yesh
 */

ini_set('memory_limit', '5000M');

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use Pinleague\PinterestException;
use Pinleague\CLI;

$category_name = $argv[1];

Log::setLog(false,
            'Tailwind',
            basename(__FILE__, ".php") . "_" . $category_name);

$numberOfCallsInBatch = 40;

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__ . " " . $category_name);

    if ($engine->running()) {
        throw new EngineException('Engine is running');
    } else {
        $engine->start();
        $start = microtime(true);

        CLI::write(Log::info('Engine started'));
        $Pinterest = new Pinterest();

        $DBH = DatabaseInstance::DBO();
        CLI::write('Connected to Database');

        if(is_null($DBH)){
            CLI::alert('Can not connect to the database');
            Log::emergency('DBH object empty. Cannot connect to database | Sleep 10');
            sleep(10);
            exit;
        }

        /*
         * Check to see how many consume scripts are already running now.
         * We do not want more than 10 running at one time because they take up a lot of resources
         * and also crowd each other out.
         *
         * Exception:  since the "everything" feed has so much more data coming through it, we want
         * to make sure that this one actually runs each time to ensure that it does not
         * accumulate too many pins to consume between each run.
         */
        if($category_name != "everything"){
            $STH = $DBH->prepare("SELECT COUNT(*) count
                                  FROM status_engines
                                  WHERE status IN (:status,
                                                   :status_fail)
                                  AND engine LIKE :engines");
            $STH->execute(array(":status" => 'Running',
                                ":status_fail" => 'Failed',
                                ":engines" => '%consume%'));

            $engines_running = $STH->fetchAll();

            if($engines_running[0]->count > 10){
                $engine->complete();
                CLI::alert(Log::notice("Too many consume scripts running. | Sleep 10"));
                sleep(60);
                exit;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Check for matches
        |--------------------------------------------------------------------------
         */
        $queue_start_time = microtime(true);

        // Fetch the categories from the status_category_feed_queue
        // table
        $category_fetch = CategoryFeedsQueues::fetch($category_name);

        $category_feeds = $category_fetch['category_feed'];
        $least_timestamp = $category_fetch['least_timestamp'];

        $queue_stop_time = microtime(true);

        // Time taken for fetching category pins from queue
        $queue_range = $queue_stop_time - $queue_start_time;

        if (empty($category_feeds)){
            CLI::alert('Going to sleep because I couldnt find any more feeds');
            $engine->idle();
            sleep(30);
            Log::notice('No more feeds to consume | Sleep 30');
            exit;
        }
        //The timestamp is noted in order to be able to delete
        // all the category feeds

        Log::debug("Save raw data to disk");

        $category_feeds_array = json_encode((array) $category_feeds);

        if(!empty($category_feeds_array)){
            $storage_path = '/mnt/storage/';

            $dir_main_path     = $storage_path . 'category_feed/';

            if (!file_exists($dir_main_path) and !is_dir($dir_main_path)){
                mkdir($dir_main_path);
            }

            $dir_path     = $storage_path . 'category_feed/' .date("M-j-Y");

            if (!file_exists($dir_path) and !is_dir($dir_path)){
                mkdir($dir_path);

                $name =  date("M-j-Y-G:i:s") . "-" . $category_name . ".txt";
                $file_path = $dir_path . "/" . $name;

                CLI::write('Saving to File' . $file_path);
                $handle = fopen($file_path, 'a+');
                file_put_contents($file_path, $category_feeds_array);
                fclose($handle);

            } else {
                $name =  date("M-j-Y-G:i:s") . "-" . $category_name . ".txt";
                $file_path = $dir_path . "/" . $name;

                CLI::write('Saving to File' . $file_path);
                $handle = fopen($file_path, 'a+');
                file_put_contents($file_path, $category_feeds_array);
                fclose($handle);
            }
        }

        $keyword_match_count = 0;

        Log::debug("Checking for keyword matches");
        CLI::write('Checking for keyword matches');
        $keyword_start_time = microtime(true);
        // Find matching keyword matches by using the method
        // matchAndNotifyKeyword from the model
        // CategoryFeedQueue
        $notifications_keywords_array = CategoryFeedQueue::matchAndNotifyKeyword($category_feeds);
        $notifications_keywords = $notifications_keywords_array['notifications'];
        $keyword_match_count = $notifications_keywords_array['keyword_match_count'];

        $keyword_stop_time = microtime(true);
        $keyword_range = $keyword_stop_time - $keyword_start_time;

        CLI::write('Checking for domain and user related matches');

        $user_id_range_total   = 0;
        $domain_range_total    = 0;
        $total_pins            = 0;
        $user_id_inner_time    = 0;
        $user_hash_inner_time  = 0;
        $via_pinner_inner_time = 0;
        $origin_pinner_inner_time = 0;
        $users_match_count        = 0;
        $domain_match_count       = 0;



        $total_pins = (count($category_feeds));

        $track = new CategoryFeedQueue($category_name);

        $user_id_start_time = microtime(true);

        // Matching User_id, pin_id, via_pinner, origin_pinner
        $notifications_user_array = $track->matchAndNotifyUsers($category_feeds);
        if(!empty($notifications_user_array)){
            $notifications_user[] = $notifications_user_array['notifications'];
        }

        $user_id_inner_time += $notifications_user_array['user_id_timings']['user_id_inner'];
        $via_pinner_inner_time += $notifications_user_array['user_id_timings']['via_pinner_inner'];
        $origin_pinner_inner_time += $notifications_user_array['user_id_timings']['origin_pinner_inner'];
        $user_hash_inner_time += $notifications_user_array['user_id_timings']['user_hash_inner'];
        $users_match_count    += $notifications_user_array['users_match_count'];

        $user_id_stop_time = microtime(true);
        $user_id_range = $user_id_stop_time - $user_id_start_time;
        $user_id_range_total += $user_id_range;


        //Matching Domains
        $domain_start_time = microtime(true);

        $notifications_domain_array = $track->matchAndNotifyDomain($category_feeds);
        $notifications_domain       = $notifications_domain_array['notifications'];
        $domain_match_count         = $notifications_domain_array['domain_match_count'];

        $domain_stop_time = microtime(true);
        $domain_range = $domain_stop_time - $domain_start_time;
        $domain_range_total += $domain_range;

        /*
        |--------------------------------------------------------------------------
        | Saving to Databases
        |--------------------------------------------------------------------------
         */

        Log::debug("Insert user details into status_category_feed_matches");
        CLI::write('Inserting user notifications to notifications table');

        $insert_start_time = microtime(true);

        $insert_start_time_user = microtime(true);

        foreach($notifications_user as $notifications){
            try{
                $notifications->insertUpdateDB();
            } catch (CollectionException $e){
              CLI::alert("No notification to save for user_id");
            }
        }

        $insert_stop_time_user = microtime(true);
        $insert_range_user = $insert_stop_time_user - $insert_start_time_user;

        $insert_start_time_domain = microtime(true);

        Log::debug("Insert domain details into status_category_feed_matches");
        CLI::write('Inserting domain notifications to notifications table');

        try{
            $notifications_domain->insertUpdateDB();
        } catch (CollectionException $e){
          CLI::alert("No notification to save for domain");
        }

        $insert_stop_time_domain = microtime(true);
        $insert_range_domain = $insert_stop_time_domain - $insert_start_time_domain;

        $insert_start_time_keyword = microtime(true);

        Log::debug("Insert keyword details into status_category_feed_matches");
        CLI::write('Inserting keyword notifications to notifications table');

        try{
            $notifications_keywords->insertUpdateDB();
        } catch (CollectionException $e){
          CLI::alert("No notification to save for keywords");
        }

        $insert_stop_time_keyword = microtime(true);
        $insert_range_keyword = $insert_stop_time_keyword - $insert_start_time_keyword;

        $insert_stop_time = microtime(true);
        $insert_range = $insert_stop_time - $insert_start_time;

        Log::debug("Deleting calls from the database");
        CLI::write('Deleting calls from DB');

        CategoryFeedsQueues::deleteFromDB($category_name, $least_timestamp);

        Log::debug("Insert into track_category_consume table");
        CLI::write('Insert into track category consume table');
        $STH = $DBH->prepare("INSERT INTO
                              track_category_consume
                              (timestamp,
                              pin_count,
                              category_name,
                              pull_from_queue_time,
                              keyword_check_time,
                              keyword_match_count,
                              user_check_time,
                              users_match_count,
                              domain_check_time,
                              domain_match_count,
                              insert_matches,
                              insert_range_user,
                              insert_range_domain,
                              insert_range_keyword,
                              user_id_inner_time,
                              via_pinner_inner_time,
                              origin_pinner_inner_time,
                              user_hash_inner_time)
                              VALUES
                              (:timestamp,
                              :pin_count,
                              :category_name,
                              :pull_from_queue_time,
                              :keyword_check_time,
                              :keyword_match_count,
                              :user_check_time,
                              :users_match_count,
                              :domain_check_time,
                              :domain_match_count,
                              :insert_matches,
                              :insert_range_user,
                              :insert_range_domain,
                              :insert_range_keyword,
                              :user_id_inner_time,
                              :via_pinner_inner_time,
                              :origin_pinner_inner_time,
                              :user_hash_inner_time)");

        $STH->execute(array(":timestamp" => time(),
                            ":pin_count" => $total_pins,
                            ":category_name" => $category_name,
                            ":pull_from_queue_time" => $queue_range,
                            ":keyword_check_time" => $keyword_range,
                            ":keyword_match_count" => $keyword_match_count,
                            ":user_check_time"    => $user_id_range_total,
                            ":users_match_count" => $users_match_count,
                            ":domain_check_time"  => $domain_range_total,
                            ":domain_match_count" => $domain_match_count,
                            ":insert_matches"     => $insert_range,
                            ":insert_range_user"  => $insert_range_user,
                            ":insert_range_domain"  => $insert_range_domain,
                            ":insert_range_keyword"  => $insert_range_keyword,
                            ":user_id_inner_time" => $user_id_inner_time,
                            ":via_pinner_inner_time" => $via_pinner_inner_time,
                            ":origin_pinner_inner_time" => $origin_pinner_inner_time,
                            ":user_hash_inner_time" => $user_hash_inner_time));



        $engine->complete();
        Log::info('Engine set to complete');

        CLI::write(Log::runtime(). 'total runtime');
        CLI::write(Log::memory().' peak memory usage');
       }
    } catch (EngineException $e){

        CLI::alert($e->getMessage());
        Log::error($e);
        CLI::stop();
    } catch (PDOException $e){
        CLI::alert($e->getMessage());
        $engine->fail();
        Log::error($e);
        CLI::stop();
    }
    catch (PinterestException $e) {

        CLI::alert($e->getMessage());
        $engine->fail();
        CLI::stop();

    } catch (Exception $e){
        CLI::alert($e->getMessage());
        $engine->fail();
        Log::error($e);
        CLI::stop();
    }
