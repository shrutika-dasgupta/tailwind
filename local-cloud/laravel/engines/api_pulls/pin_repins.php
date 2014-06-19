<?php
use Pinleague\Pinterest\PinterestException,
    Pinleague\CLI;

/**
 * This script fetches the boards that pins were repinned to
 * and stores the data in data_pins_repins
 *
 * @author  Will
 */

$numberOfCallsInBatch = 40;

/*
 * Since we're running this from the CLI, we're changing into this directory so
 * the relative path works as we'd expect here. Otherwise things get hairy
 */
chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

/*
 * Increase the memory limit since some calculations happen here that require a bit
 */
ini_set('memory_limit', '200M');

/*
 * We want to record logs to individual files, so we change the log settings here
 * Otherwise they would go to the general daily log
 */
Log::setLog(__FILE__);

$start              = microtime(true);

$running_times = array(
    'pull_time' => 0,
    'process_time' => 0,
    'existing_check_time' => 0,
    'repin_save_time' => 0,
    'profile_save_time' => 0
);

try {

    CLI::h1('Starting batch');

    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        CLI::sleep(15);
        CLI::stop(Log::error('Engine is already running'),array('sleep'=>15));
    }

    $engine->start();
    CLI::write(Log::info('Engine started'));

    $api_rate = engine::current_call_rate();

    if ($api_rate > 70000) {
        $engine->complete();
        sleep(60);
        CLI::h2(Log::warning('Too many api calls | Sleep 60 seconds'));
    }

    CLI::h2(Log::debug('Fetching calls off the queue'));

        $calls = QueuedApiCalls::fetch(
                               'Pin Engagement Repins', //api_call
                               'pin_engagement', //track_type
                               $numberOfCallsInBatch
        );

        CLI::write(Log::debug('Found ' . $calls->count() . ' calls'));

        CLI::h2('Attempting to make calls with Pinterest');
        $curl_responses = $calls->send();

        CLI::write(Log::info('Calls successfully made. Parsing results.'));

        /*
         * Collection of pins repins object so we can store the response
         */
        $pinsRepins = new PinsRepins();

        $running_times['pull_time'] = microtime(true) - $start;

        $count = -1;
        foreach ($curl_responses as $key => $response) {
            $count++;

            try {

                $start_process = microtime(true);

                /*
                 * The count matches the key in the calls collection
                 * We'll need the call handy, so we grab it
                 */
                /** @var $call QueuedApiCall */
                $call = $calls->getModel($count);
                CLI::h2(Log::debug('Handling response for pin ' . $call->object_id));

                /*
                 * Do some basic sanity checking here to make sure we get an object
                 * back and not null or anything
                 */
                if (!is_object($response)) {
                    throw new QueuedApiCallException(
                        'Object not returned',
                        QueuedApiCallException::BLANK_RESULT
                    );
                }

                /*
                 * If Pinterest returned something other than that we're awesome, we
                 * need to handle that accordingly
                 */
                if ($response->code != 0) {
                    throw new PinterestException(
                        $response->message,
                        $response->code
                    );
                }

                /*
                 * We want to get all this data into the collection
                 * so we can easily save it to the DB
                 *
                 * We store a temporary "current" copy for when we need to check if
                 * we already have these particular pins
                 *
                 */
                CLI::h3(Log::debug('Adding repin data to PinsRepins collection'));
                $current_pins_repins = new PinsRepins();

                foreach ($response->data as $repinData) {
                    $pinsRepin = new PinsRepin($call->object_id);
                    $pinsRepin->loadAPIData($repinData);

                    $pinsRepins->add($pinsRepin);
                    $current_pins_repins->add($pinsRepin);
                    CLI::write('Added ' . $pinsRepin->board_name);

                }

                /*
                * Remove the call from the queue
                * Would prefer to do these in bulk, but
                * since the primary key is 4 columns
                * that proves to be more challenging
                */
                $call->removeFromDB();
                CLI::write(Log::debug('Removed call from the database'));

                $running_times['process_time'] += microtime(true) - $start_process;

                /*
                 * If there is a bookmark, we need to queue it up to be run
                 */
                if (property_exists($response, 'bookmark')) {

                    CLI::write(Log::debug('Bookmark found'));

                    $check_start = microtime(true);
                    /*
                     * We'd only like to add the bookmark if we haven't already seen
                     * all of these repins
                     */
                    if ($current_pins_repins->allDoNotExistInDB()) {

                        /** @var  $new_call QueuedApiCall */
                        $new_call           = $calls->getModel($count);
                        $new_call->bookmark = $response->bookmark;

                        /*
                         * We're putting this in a try-catch in case the write
                         * fails.
                         */
                        try {
                            $addedCall = QueuedApiCall::add($new_call);
                            CLI::write(Log::debug('Added new bookmarked call to queue: '
                                       . $new_call->bookmark
                                       ));
                        }
                        catch (Exception $e) {
                            CLI::alert(Log::error($e));
                        }
                    }

                    $running_times['exiting_check_time'] += microtime(true) - $check_start;
                }

            }
            catch (PinterestException $e) {

                switch ($e->getCode()) {
                    case 10: //bookmark not found
                    case 12:
                    case 16: //Request timeout

                        ApiError::create(
                                $call->api_call,
                                $call->object_id,
                                $e->getMessage(),
                                'from PinRepins script',
                                $e->getCode(),
                                $call->bookmark
                        );

                        CLI::write(Log::notice('Logged error'));

                        if (ApiError::numberOfEntries($call, $e->getCode()) < 4) {
                            $call->bookmark = false;
                            QueuedApiCall::add($call);
                            CLI::write(Log::notice("Added call to the end of the queue"));
                        }

                        $call->removeFromDB();
                        CLI::write(Log::notice('Removed call from queue'));

                        break;

                    case 50: //pin not found
                        $call->removeFromDB();
                        CLI::write(Log::notice('Removed call from queue'));
                        break;

                    default:

                        /*
                         * But we should probably still log this error
                         * otherwise
                         */
                        ApiError::create(
                                $call->api_call,
                                $call->object_id,
                                $e->getMessage(),
                                'from PinRepins script',
                                $e->getCode(),
                                $call->bookmark
                        );

                        CLI::write(Log::error("Pinterest error " . $e->getCode() . ": " . $e->getMessage() . ".  Logged error in our DB."));

                        break;
                }
            }
            catch (QueuedApiCallException $e) {

                CLI::alert(Log::error($e->getMessage()));

                /*
                 * if there was just a blank / weird response
                 * we want to log the error
                 */
                ApiError::create(
                        $call->api_call,
                        $call->object_id,
                        $e->getMessage(),
                        'from PinRepins script',
                        $e->getCode(),
                        $call->bookmark
                );
                CLI::write(Log::notice('Logged error in our DB'));

                /*
                 * Remove the call and add it back so it goes to the end of the queue
                 */
                try {

                    $call->removeFromDB();
                    CLI::write(Log::notice('Removed call from queue'));

                    /*
                     * Only add it if this error has appeared less than 4 times
                     * aka 0,1,2,3
                     */
                    if (ApiError::numberOfEntries($call, $e->getCode()) < 4) {
                        $call->bookmark = false;
                        QueuedApiCall::add($call);
                        CLI::write(Log::notice("Added call back onto queue "));
                    }
                }
                catch (Exception $e) {
                    CLI::alert(Log::error($e->getMessage()));
                }
            }

        }

        $start_saving_repins = microtime(true);
        CLI::h2('Finishing up');
        $pinsRepins->saveModelsToDB();
        CLI::write(Log::info('Saved all PinRepin models to database'));
        $running_times['repin_save_time'] = microtime(true) - $start_saving_repins;

        $start_saving_profiles = microtime(true);
        //$pinsRepins->saveProfileUserIds();

        $pinsRepins->sortBy('repinner_user_id', SORT_ASC);

        $sql           = "";
        $insert_values = array();

        foreach($pinsRepins as $pin_repin){

            array_push($insert_values
                , $pin_repin->repinner_user_id
                , "repin_user"
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

            $DBH = DatabaseInstance::DBO();
            $STH = $DBH->prepare($sql);
            $STH->execute($insert_values);

            CLI::write(Log::info('Saved repinner user_ids to  data_profiles_new'));
            $running_times['profile_save_time'] = microtime(true) - $start_saving_profiles;
            CLI::write($running_times['profile_save_time']);
        }

}


catch (NoApiCallsOnQueueException $e) {
    CLI::write(Log::notice($e), array('sleep' => 15));
    CLI::sleep(15);
}

catch (EngineException $e) {
    CLI::sleep(15);
    CLI::stop(Log::error($e), array('sleep' => 15));
}

catch (Exception $e) {
    /*
     * For general exceptions we want to set the script as failed
     * Sometimes shit will happen and the engine won't be available so we wrap this in it's
     * own try catch block so that we can record that error too
     */
    try {
        $engine->fail();
    }
    catch (Exception $e) {
        CLI::alert(Log::error($e));
    }

    CLI::sleep(15);
    CLI::stop(Log::error($e));
}

CLI::write(Log::debug("Running times", $running_times));

$engine->complete();
CLI::write(Log::info('Engine completed'));

$speed = $engine->computeSpeed($numberOfCallsInBatch);
CLI::write(Log::debug("Made $speed calls/second"));

CLI::write(Log::runtime(). ' total runtime');
CLI::write(Log::memory().' peak memory usage');

CLI::end('OH YEAH YOU BIG BOTTLE OF JUICE. TANK GO BOOM BABY');
