<?php
/**
 * Keywords Search
 *
 * @author Yesh
 */

ini_set('memory_limit', '200M');

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use Pinleague\PinterestException;
use Pinleague\CLI;
const TRACK_TYPE = 'social_compass';
$numberOfCallsInBatch = 40;

Log::setLog(__FILE__);

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        throw new EngineException('Engine is running');
    } else {
        $engine->start();
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
        CLI::write(Log::debug('Connected to Database'));

        $queued_calls = new QueuedApiCalls();


        CLI::write(Log::debug('Getting all the keyword from the status_keywords table'));
        $update_queue = QueuedApiCalls::pushKeyword('status_keywords',
                                                    TRACK_TYPE,
                                                    $numberOfCallsInBatch);

        CLI::write(Log::debug('Finding Keywords that need to be pulled and adding them to the Queue'));
        foreach ($update_queue as $q) {
            $queue = QueuedApiCall::readyDBObjectKeyword('Keyword Search', $q);
            $queued_calls->add($queue);
        }

        CLI::write(Log::debug('Saving keywords to be pulled to Queue table', $queued_calls));
        try {
            $queued_calls->saveModelsToDB();
        }
        catch (CollectionException $e) {
            echo "No more keywords to save to queue" . PHP_EOL;
            Log::notice('No keywords left to be added to the queue at this time');
        }

        CLI::write(Log::info('Grabbing Keyword Pulls off the Queue'));
        try{
            $calls = QueuedApiCalls::fetch('Keyword Search',
                                           TRACK_TYPE,
                                           $numberOfCallsInBatch);
        } catch (NoApiCallsOnQueueException $e)
        {
            CLI::alert(Log::notice('No more keyword pulls left to grab off the queue'));
            $engine->complete();
            exit;
        }
        CLI::write(Log::info('Making batch calls to the API'));
        $response_data = $calls->send();

        CLI::write(Log::debug('Prepare to parse through API responses.'));

        $bookmark_data          = new QueuedApiCalls();
        $save_pins              = new Pins();
        $save_descriptions      = new PinDescriptions();
        $save_attributions      = new PinAttributions();
        $keywords               = new KeywordsHistory();
        $save_map_pins_keywords = new MapPinsKeywords();
        $profiles               = new Profiles();

        $all_params_created = false;

        // Keeping a counter to track the index of the response
        $count = -1;

        foreach ($response_data as $response) {

            $count += 1;
            $response_message = $response->message;
            $response_code    = $response->code;
            $object_id        = $calls[$count]->object_id;

            if (!is_object($response)){
                Log::warning('The response value is NULL for ' .
                             $object_id);
                ApiError::create(
                    'Keyword_sc',
                    $object_id,
                    'Got NULL response back from curl',
                    'from keyword_sc.php',
                    QueuedApiCallException::BLANK_RESULT
                );
            } else {
                $keyword          = new KeywordHistory($object_id);

                // Build the keyword regex match pattern.
                $pattern = \StatusKeyword::regexMatchPattern($object_id);

                if (!isset($response_data)){
                    Log::warning('The response has code 0, but there is no data for ' .
                                 $object_id);
                    ApiError::create(
                        'Keyword_sc',
                        $object_id,
                        'Got NULL response back code 0',
                        'from keyword_sc.php',
                        $response_code
                    );
                    break;
                } else {
                    if (($response->code) === 0) {

                        CLI::write(Log::debug("Parsing through returned follower data for keyword: $keyword_lower_case"));

                        if (isset($response->bookmark)) {
                            $bookmark_call = QueuedApiCall::loadBookmarkFromApi($response, 'Keyword Search', TRACK_TYPE, $object_id);
                            $bookmark_data->add($bookmark_call);

                            CLI::write(Log::debug('Found bookmark to add back to the queue.'));
                        }


                        $pin_ids_api = array();

                        CLI::write(Log::debug('Obtaining keyword matches by parsing pins'));
                        foreach ($response->data as $data) {
                            // Loading pins to the pins collection
                            $save_pin             = new Pin(TRACK_TYPE);
                            $save_description     = new PinDescription();
                            $save_map_pin_keyword = new MapPinKeyword();
                            $profile = new Profile();

                            if(isset($data->promoter)){
                                if (is_object($data)){
                                    $data = (array) $data;
                                }
                                $promoter_category->pin_id = $data['id'];
                                $promoter_category->promoter_id = $data['promoter'];
                                $promoter_category->feed = MapPinPromoter::FEED_CATEGORY;
                                $promoter_category->feed_attribute = $category_name;
                                $promoter_category->timestamp = time();
                                $promoter_categories->add($promoter_category);
                            }

                            $save_pin_db = $save_pin->loadApiData($data);
                            $save_description->loadPinData($save_pin_db);

                            // Add whitespace so that keywords at the beginning and end of the string will be matched.
                            $description = ' ' . $data->description . ' ';

                            if (preg_match($pattern, $description)) {
                                $save_map_pin_keyword->load($save_pin_db, $object_id);
                                CLI::write(Log::debug('Found keyword match!'));
                                var_dump($description);
                            } else {
                                CLI::write(Log::debug('Didn\'t find keyword match.'));
                                var_dump($description);
                            }

                            $profile->user_id = $data->pinner->id;
                            $profile->username = "";
                            $profile->last_pulled = 0;
                            $profile->track_type = 'keyword';
                            $profile->timestamp = time();

                            if (!empty($data->attribution)) {
                                $save_attribution = new PinAttribution();
                                $save_attribution->loadApiData($data->attribution);
                                $save_attribution->pin_id = $data->id;
                                $save_attributions->add($save_attribution);
                            }

                            $save_pins->add($save_pin_db);
                            $save_descriptions->add($save_description);

                            if (!empty($save_map_pin_keyword)){
                                $save_map_pins_keywords->add($save_map_pin_keyword);
                            }
                            $profiles->add($profile);

                            $pin_ids_api[] = $data->id;
                        }

                        if (!empty($pin_ids_api)) {
                            $keyword->update($response, $pin_ids_api, $object_id);
                            $keywords->add($keyword);
                        }

                        //Delete calls from the Queue
                        QueuedApiCalls::DeleteModelsFromDB($calls[$count]);

                    } elseif (($response->code) === 30) {

                        CLI::write(Log::error('Somehow received a "User not found" on a keyword call.'));

                        ApiError::create(
                                'Keyword Search',
                                $object_id,
                                $response->message,
                                'Keywords-sc. Line ' . __LINE__,
                                $response->code,
                                $response->bookmark
                        );

                        QueuedApiCalls::DeleteModelsFromDB($calls[$count]);

                    } elseif (($response->code) === 10) {

                        CLI::write(Log::warning('Bookmark not found'));

                        ApiError::create(
                                'Keyword Search',
                                $object_id,
                                $response->message,
                                'Keywords-sc. Line ' . __LINE__,
                                $response->code,
                                $response->bookmark
                        );

                        QueuedApiCalls::DeleteModelsFromDB($calls[$count]);

                    } elseif (($response->code) === 11) {

                        CLI::write(Log::critical(
                                      "API Method not found. Something is off about this keyword query!"
                                   ));

                        ApiError::create(
                                'Keyword Search',
                                $object_id,
                                $response->message,
                                'Keywords-sc. Line ' . __LINE__,
                                $response->code,
                                $response->bookmark
                        );

                        QueuedApiCalls::DeleteModelsFromDB($calls[$count]);

                    } elseif (($response->code) === 12) {

                        CLI::write(Log::warning('Something went wrong on our end. Sorry about that.'));

                        ApiError::create(
                                'Keyword Search',
                                $object_id,
                                $response->message,
                                'Keywords-sc. Line ' . __LINE__,
                                $response->code,
                                $response->bookmark
                        );

                    } elseif (($response->code) === 16) {

                        CLI::write(Log::warning("Request didn't complete in time."));

                        ApiError::create(
                                'Keyword Search',
                                $object_id,
                                $response->message,
                                'Keywords-sc. Line ' . __LINE__,
                                $response->code,
                                $response->bookmark
                        );

                    } else {

                        CLI::write(Log::critical("Other Uncaught Api Error: Code: " . $response->code . ". Message: " . $response->message . ""));

                        ApiError::create(
                                'Keyword Search',
                                $object_id,
                                $response->message,
                                'Keywords-sc. Line ' . __LINE__,
                                $response->code,
                                $response->bookmark
                        );

                        QueuedApiCalls::DeleteModelsFromDB($calls[$count]);

                    }
                }
            }
        }

        CLI::write(Log::info('Saving models to the queue'));
        try{
            $bookmark_data->insertUpdateDB();
        }
        catch (CollectionException $e) {
            echo "No more bookmarks to save" . PHP_EOL;
            Log::notice('No more bookmarks to save');
        }

        CLI::write(Log::info('Saving models to the pin table'));
        try{
            $save_pins->insertUpdateDB();
        }
        catch (CollectionException $e) {
            echo "No more pins to save" . PHP_EOL;
            Log::notice('No more pins to save');
        }

        CLI::write(Log::info('Saving models to the profiles table'));
        try{
            $profiles->saveModelsToDB();
        }
        catch (CollectionException $e) {
            echo "No more profiles to save" . PHP_EOL;
            Log::notice('No more profiles to save');
        }

        CLI::write(Log::info('Saving models to the keywords history table'));
        try{
            $keywords->insertUpdateDB();
        }
        catch (CollectionException $e) {
            echo "No more keywords to save" . PHP_EOL;
            Log::notice('No more keywords to save');
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

        CLI::write(Log::info('Saving models to the attributions table'));
        try {
            $save_attributions->insertUpdateDB();
        }
        catch (CollectionException $e) {
            echo "No more attributions to save" . PHP_EOL;
            Log::notice('No more attributions to save');
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
    $engine->fail();
    Log::error($e);
    CLI::stop();

} catch (Exception $e) {

    CLI::alert($e->getMessage());
    $engine->fail();
    Log::error($e);
    CLI::stop();
}
