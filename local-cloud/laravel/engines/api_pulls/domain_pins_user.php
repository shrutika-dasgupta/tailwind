<?php
/**
 * Domain Pins Api Pull Engine
 *
 * @author Alex
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
        sleep(20);
        Log::notice('Engine running | Sleep 20');
        throw new EngineException('Engine is running');
    }

    $engine->start();
    $start = microtime(true);

    CLI::write(Log::info('Engine started'));

    $numberOfCallsInBatch = 40;

    $api_rate = engine::current_call_rate();

    if ($api_rate > 75000) {
        sleep(10);
    }

    $TRACK_TYPE = array('user', 'competitor', 'free', 'keyword_tracking', 'pinmail');

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

        CLI::write('Getting all the domains from status_domains for ' . $TRACK_TYPE[$queue_index]);
        $update_queue = QueuedApiCalls::pushDomains(
            $TRACK_TYPE[$queue_index],
            $numberOfCallsInBatch
        );

        CLI::write('Adding domains to Queue object/Collection');
        foreach ($update_queue as $q) {
            $queue = QueuedApiCall::readyDBObjectDomain('Domain Pins', $q);
            $queued_calls->add($queue);
        }

        CLI::write('Saving Queue collection of Domains to the status_api_calls_queue table');
        try {
            $queued_calls->saveModelsToDB();
        }
        catch (CollectionException $e) {
            Log::notice("No more domains to save to queue for " . $TRACK_TYPE[$queue_index]);
        }

        CLI::write('Grabbing Domains off the Queue for ' . $TRACK_TYPE[$queue_index]);
        try {
            $calls = QueuedApiCalls::fetchAndUpdateRunning('Domain Pins',
                $TRACK_TYPE[$queue_index],
                $numberOfCallsInBatch);

            $queue_empty = True;

        } catch (NoApiCallsOnQueueException $e)
        {
            Log::notice("No more Domains to grab off the queue for " . $TRACK_TYPE[$queue_index]);
            $queue_index ++;

            if ($queue_index == count($TRACK_TYPE)) {
                CLI::alert('Completed all Domain track types. Sleeping for 15 and going idle.');
                sleep(15);
                $engine->idle();
                exit;
            }
            CLI::write('Trying track type: ' . $TRACK_TYPE[$queue_index]);
        } catch (ApiCallsAccessException $e)
        {
            Log::error("'$call_type' api_calls for " . $TRACK_TYPE[$queue_index]
                . " locked from access after 10 attempts.");
            $queue_index ++;

            if ($queue_index == count($TRACK_TYPE)) {
                CLI::alert('Completed all Domain track types. Sleeping for 15 and going idle.');
                sleep(15);
                $engine->idle();
                exit;
            }
            CLI::write('Trying track type: ' . $TRACK_TYPE[$queue_index]);
        }

        $current_track_running = $TRACK_TYPE[$queue_index];

    }

    Log::info("Starting Domains Pulls for '$current_track_running' track_type");

    $domains_stringify = $calls->stringifyField('object_id');

    /*
     * Get current pins_per_day values for each domain we're about to pull
     */
    $status_domains_to_update = new StatusDomains();
    $status_domains_db        = StatusDomains::fetchFromList($domains_stringify);


    CLI::write(Log::info('Making batch calls (Domain Pins) to the API'));
    $response_data = $calls->send();

    /** @param $bookmark_data QueuedApiCalls
     *                        Adding all the calls that came back with bookmarks and
     *                        need to be added back on the queue
     *
     * @param $save_pins      Pins for saving the data to data_pins_new table
     *
     * @param $pins_per_day_array   Array of pins_per_day calculations we'll need to be updating
     *                              for each domain we're pulling for.
     */
    $bookmark_data          = new QueuedApiCalls();
    $save_pins              = new Pins();
    $save_descriptions      = new PinDescriptions();
    $save_attributions      = new PinAttributions();
    $pin_histories          = new PinsHistories();
    $save_map_pins_keywords = new MapPinsKeywords();
    $profiles               = new Profiles();
    $keywords_from_db       = array();

    /*
     * Get all keywords
     */
    $STH = $DBH->query(
               "SELECT keyword
                FROM status_keywords
                WHERE keyword IS NOT NULL"
    );
    $keywords_from_db = $STH->fetchAll();

    $pins_per_day_array     = array();

    CLI::write('Queue up bookmarked responses');

    // Keeping a counter to track the index of the response
    $count = -1;
    echo count($response_data) . PHP_EOL;

    foreach ($response_data as $response) {
        $count += 1;

        $object_id = $calls[$count]->object_id;

        $call = $calls->getModel($count);

        /*
         * Checking to see if the entire request failed. If it did, we
         * reset and try again.
         *
         */
        if (!is_object($response)){
            Log::error('The response is NULL, reset the call ' .
                         $object_id);
            ApiError::create(
                'domain_pins_user',
                $object_id,
                'Got NULL response back from curl',
                'from domain_pins_user.php',
                QueuedApiCallException::BLANK_RESULT
            );

            //Deleting the calls from the Queue
            /** @var $call QueuedApiCall */
            $call->rerunCall();

        } else {
            switch ($response->code) {

                case 0:
                    CLI::write(Log::debug('Response returned - Code: ' . $response->code .
                        ' domain: ' . $object_id));

                    //Deleting the calls from the Queue
                    /** @var $call QueuedApiCall */
                    $call->removeFromDB();

                    if (isset($response->bookmark)) {
                        $bookmark_call = QueuedApiCall::loadBookmarkFromApi($response, 'Domain Pins', $current_track_running, $object_id);
                        $bookmark_data->add($bookmark_call);
                    }

                    if (!isset($response->data)){
                        Log::warning('The response has code 0, but there is no data for ' .
                                            $object_id);
                        ApiError::create(
                            $call->api_call,
                            $object_id,
                            'Got NULL response back code 0',
                            'from domain_pins_user.php',
                            $response->code,
                            $call->bookmark
                        );
                        break;
                    } else {

                        foreach ($response->data as $data) {
                            // Loading pins to the pins collection
                            /** @noinspection PhpUndefinedClassInspection */
                            $save_pin         = new Pin('domain');
                            $save_description = new PinDescription();

                            $save_pin->track_type = 'domain';

                            $save_pin_db = $save_pin->loadAPIData($data);
                            $save_description->loadPinData($save_pin_db);
                            if (!empty($data->attribution)) {
                                $save_attribution = new PinAttribution();
                                $save_attribution->loadAPIData($data->attribution);
                                $save_attribution->pin_id = $data->id;
                                $save_attributions->add($save_attribution, true);
                            }
                            $save_pins->add($save_pin_db, true);
                            $save_descriptions->add($save_description, true);

                            // All the engagement details from the API
                            $pin_engagement_details_api = array(
                                'user_id'       => $data->pinner->id,
                                'domain'        => $data->domain,
                                'description'   => $data->description,
                                'like_count'    => $data->like_count,
                                'repin_count'   => $data->repin_count,
                                'comment_count' => $data->comment_count
                            );

                            // Creating the hash map with pin_id as the key and pin
                            // engagement as the value
                            $pin_data_from_api[$data->id] = $pin_engagement_details_api;
                            $pin_ids_api[]                = $data->id;

                        }

                        $pin_count = count($response->data);
                        $oldest_date = strtotime($response->data[$pin_count-1]->created_at);
                        $time_gap = time() - $oldest_date;

                        $status_domain_api = new StatusDomain();
                        $status_domain_api->domain = $object_id;
                        $status_domain_api->pins_per_day = ceil(($pin_count/$time_gap) * (86400));

                        /*
                         * Check to see whether the pins_per_day we just calculated is higher than
                         * the current value we have in the DB
                         */
                        $current_ppd = $status_domains_db->getModel($object_id)->pins_per_day;

                        if ($status_domain_api->pins_per_day > $current_ppd) {
                            $status_domains_to_update->add($status_domain_api, true);
                        }
                        break;
                    }

                case 10:
                    /** @var $call QueuedApiCall */
                    CLI::write(Log::warning('Bookmark not found: ' . $response->code .
                        ' object_id: ' . $object_id));
                    apierror::create(
                            'domain pins',
                                $calls[$count]->object_id,
                                $response->message,
                                'domain_pins_user.' . __line__,
                                $response->code,
                                $call->bookmark
                    );
                    $call->removeFromDB();
                    break;

                case 11:
                    /** @var $call QueuedApiCall */
                    CLI::write(Log::critical('API method not found: ' . $response->code .
                        ' object_id: ' . $object_id));
                    apierror::create(
                            'domain pins',
                                $calls[$count]->object_id,
                                $response->message,
                                'domain_pins_user.' . __line__,
                                $response->code,
                                $call->bookmark
                    );
                    $call->removeFromDB();
                    break;

                case 12:
                case 16:
                    /*
                     * Reset the running flag to rerun the call and update the timestamp to put it
                     * at the back of the queue
                     */
                    /** @var $call QueuedApiCall */
                    $call->rerunCall();
                    $call->updateTimestamp();
                    CLI::write(Log::warning('Pinterest error on their end (resetting call): Code: ' . $response->code .
                        ' object_id: ' . $object_id));

                    ApiError::create(
                            'Domain Pins',
                                $calls[$count]->object_id,
                                $response->message,
                                'domain_pins_user.' . __LINE__,
                                $response->code,
                                $call->bookmark
                    );

                    break;

                case 70: //Domain not found

                    /** @var $call QueuedApiCall */
                    $call->removeFromDB();

                    CLI::write(Log::warning('Domain not found- code: ' . $response->code .
                        '.  domain: ' . $object_id));
                    apierror::create(
                            'domain pins',
                                $calls[$count]->object_id,
                                $response->message,
                                'domain_pins_user.' . __line__,
                                $response->code,
                                $call->bookmark
                    );

                    break;

                default:
                    /*
                     * Reset the running flag to rerun the call and update the timestamp to put it
                     * at the back of the queue
                     */
                    /** @var $call QueuedApiCall */
                    $call->rerunCall();
                    $call->updateTimestamp();

                    CLI::write(Log::critical('Uncaught Pinterest Error: Code: ' . $response->code .
                        '. Message: ' . $response->message . '. Object_id: ' . $object_id . ''));

                    ApiError::create(
                            'Domain Pins',
                                $calls[$count]->object_id,
                                $response->message,
                                'domain_pins_user.' . __LINE__,
                                $response->code,
                                $call->bookmark
                    );
                    break;
            }
        }
    }


    CLI::write(Log::info('The API hash is completed'));



/*
|--------------------------------------------------------------------------
| We've processed all of the api responses!  Now for some cleanup
| before we process the pin data we've just found
|--------------------------------------------------------------------------
*/

    CLI::write(Log::info('Saving bookmarked calls to the queue'));
    try{
        $bookmark_data->insertUpdateDB();
    } catch (CollectionException $e){
        CLI::alert(Log::notice('No Bookmarks found to save to the queue'));
    }

    $engagement_calls = new QueuedApiCalls();

    /*
     * Check if we've found any status_domain pins_per_day values to update, and update them.
     */

    if ($status_domains_to_update->count() > 0){
        Log::debug('Updating pins_per_day for ' . $status_domains_to_update->count() . ' domains');
        $status_domains_to_update->updatePinsPerDay();
    }

    // We are grabbing all the pin_ids from the API response


    $domain_hashtags     = array();
    $domain_descriptions = array();





/*
|--------------------------------------------------------------------------
| Begin processing pin data
|   * queue engagement for pins with engagement count changes
|   * save pin histories
|   * find hashtags
|   * find keyword matches (currently disabled!!)  need to make this scale first :(
|--------------------------------------------------------------------------
*/


    $save_new_and_updated_pins = new Pins();
    $save_new_pin_descriptions = new PinDescriptions();
    $save_new_pin_attributions = new PinAttributions();

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

        $keyword_check_time = 0;
        $keyword_check_count = 0;

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
             * We'll also check it for keyword matches.
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


                /*
                 * Look for matches of each keyword we're tracking
                 *
                 * Currently DISABLED!!  Need to find a way to make this scale :(
                 */
                if(1==0){
//                $keyword_start = microtime(true);
//                foreach ($keywords_from_db as $keyword) {
//                    if (empty($keyword->keyword)) {
//                        continue;
//                    }
//
//
//
//                    $pattern = \StatusKeyword::regexMatchPattern($keyword->keyword);
//
//                    // Add whitespace so that keywords at the beginning and end of the string will be matched.
//                    $description = ' ' . $pin->description . ' ';
//
//                    if (!preg_match($pattern, $description)) {
//                        continue;
//                    }
//
//                    $save_map_pin_keyword = new MapPinKeyword();
//                    $save_map_pin_keyword->load($save_pins->getModel($data_key), $keyword->keyword);
//                    $save_map_pins_keywords->add($save_map_pin_keyword);
//
//                }
//                $keyword_time = microtime(true) - $keyword_start;
//                $keyword_check_time += $keyword_time;
//                $keyword_check_count++;
                }

                /*
                 * Do a hashtag analysis of this pin's description and save them as keyword matches
                 */
                $domain = $data_value['domain'];
                $description = $data_value['description'];

                $matches = array();
                preg_match_all('/(#[^#\s\W][#\d+\w][^\d\s][A-Za-z]*)/s', $description, $matches);

                $hashtags = array_count_values($matches[0]);
                arsort($hashtags);


                foreach($hashtags as $hashtag => $count) {

                    /*
                     * Save hashtag association into keywords table
                     */
                    $save_map_pin_keyword = new MapPinKeyword();
                    /** @noinspection PhpParamsInspection */
                    $save_map_pin_keyword->load($save_pins->getModel($data_key), $hashtag);
                    $save_map_pins_keywords->add($save_map_pin_keyword);

                    /*
                     * Save up cumulative hashtag counts for this domain, or order to save them all
                     * later.
                     */
                    if (!isset($domain_hashtags[$domain])) {
                        $domain_hashtags[$domain] = array();
                    }

                    if (!isset($domain_hashtags[$domain][$hashtag])) {
                        $domain_hashtags[$domain][$hashtag] = $count;
                    } else {
                        $domain_hashtags[$domain][$hashtag] += $count;
                    }
                }
            }

            /*
             * Repin Chain data is disabled for this script as the api method may not be available
             */
            if (($data_value['repin_count'] != $pin_data_from_db[$data_key]['repin_count'])) {
//                if($current_track_running != "free"){
//                    $call_repins = QueuedApiCall::loadPinFromAPI($data_key, 'Pin Engagement Repins', 'pin_engagement');
//                    $bookmark_data->add($call_repins);
//                }
                $create_history = true;

                /*
                 * If we're not already saving this pin, add it to the collection to we can update
                 * its engagement counts.
                 */
                if ($save_new_and_updated_pins->getModel($data_key) == false) {
                    $save_new_and_updated_pins->add($save_pins->getModel($data_key), true);
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

    CLI::write("Total keyword check time:  " . $keyword_check_time);
    CLI::write("Keyword check per pin: " . ($keyword_check_time/$keyword_check_count));


    /*
     * Iterate through hashtag matches per domain to save counts to cache_domain_wordclouds table
     */
    $wordclouds = new \Caches\DomainsWordClouds();
    foreach ($domain_hashtags as $domain => $tags_array) {
        foreach ($tags_array as $hashtag => $count) {

            $wordcloud             = new \Caches\DomainWordCloud();
            $wordcloud->domain     = $domain;
            $wordcloud->date       = flat_date('day');
            $wordcloud->word       = $hashtag;
            $wordcloud->word_count = $count;

            $wordclouds->add($wordcloud);
        }
    }

    CLI::write(Log::info('Saving hashtag counts to db'));
    try{
        $wordclouds->insertAddDB();
    } catch (CollectionException $e){
        CLI::alert(Log::notice('No hashtags to save'));
    }




    /*
    |--------------------------------------------------------------------------
    | Begin Saving Collection Data to the DB
    |--------------------------------------------------------------------------
    */



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
     * Save pin keyword matches (hashtags, in this case)
     */
    CLI::write(Log::debug('Saving keyword matches to the map_pins_keywords table'));
    try{
        $save_map_pins_keywords->saveModelsToDB();
    } catch (CollectionException $e){
        CLI::alert(Log::notice('No keyword matches found to save'));
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
     * Save profiles
     */
    CLI::write(Log::debug('Saving new profiles to the profiles table'));
    $save_pins->sortBy('user_id', SORT_ASC);

    $sql           = "";
    $insert_values = array();
    foreach($save_pins as $pin){

        array_push($insert_values
            , $pin->user_id
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

    if($sql != ""){

        $STH = $DBH->prepare($sql);
        $STH->execute($insert_values);

        Log::info('Saved new pinner user_ids to data_profiles_new');
    }

    /*
     * Save to status_footprint
     */

    CLI::write(Log::debug('Saving new profiles to the status_footprint table'));
    if ($current_track_running == 'user'
    ||  $current_track_running == 'competitor') {

        $save_pins->sortBy('user_id', SORT_ASC);

        $sql           = "";
        $insert_values = array();
        foreach($save_pins as $pin){

            array_push($insert_values,
                       $pin->user_id,
                       "pinner",
                        0);

            if($sql == ""){
                $sql = "INSERT IGNORE into status_footprint
                    (user_id, track_type, last_run)
                    VALUES
                    (?, ?, ?)";
            } else {
                $sql .= ",
                    (?, ?, ?)";
            }
        }

        if($sql != ""){

            $STH = $DBH->prepare($sql);
            $STH->execute($insert_values);

            Log::info('Saved new pinner user_ids to status_footprint');
        }
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
