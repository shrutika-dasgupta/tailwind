<?php
use Pinleague\PinterestException,
    Pinleague\CLI;

/**
 * This script fetches the boards from search results based on a keyword
 *
 * @author  Alex
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
ini_set('memory_limit', '300M');

/*
 * We want to record logs to individual files, so we change the log settings here
 * Otherwise they would go to the general daily log
 */
Log::setLog(__FILE__);

$start = microtime(true);

try {

    CLI::h1('Starting batch');

    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        CLI::sleep(15);
        CLI::stop(Log::error('Engine is already running'), array('sleep' => 15));
    }

    $engine->start();
    CLI::write(Log::info('Engine started'));


    $DBH = DatabaseInstance::DBO();
    CLI::write('Connected to Database');

    $queued_calls = new QueuedApiCalls();



    /*
     * Grab new keywords to add to the queue to get board search results pulled
     */
    CLI::write(Log::debug('Getting all the keywords from DB that haven\'t had boards pulled yet today'));
    $update_queue = QueuedApiCalls::pushKeywordBoardPulls('status_keywords',
        'user', $numberOfCallsInBatch);

    CLI::write(Log::debug('Adding keywords to Queue collection'));
    foreach ($update_queue as $q) {
        $queue = QueuedApiCall::readyDBObjectKeyword('Search Boards', $q);
        $queued_calls->add($queue);
    }

    CLI::write(Log::debug('Saving keywords to search boards to API Queue table'));
    try {
        $queued_calls->saveModelsToDB();
    }
    catch (CollectionException $e) {
        echo CLI::write(Log::debug("No more keywords to save to queue"));
    }


    /*
     * Look for keyword calls already on the queue that need to have board search results pulled
     * (either ones we just added, or potentially others that were already on the queue from before)
     */
    CLI::write(Log::debug('Grabbing keywords off the Queue to pull board search results for'));
    try {
        $calls = QueuedApiCalls::fetch('Search Boards',
            'user',
            $numberOfCallsInBatch);

    } catch (NoApiCallsOnQueueException $e)
    {
        CLI::alert(Log::notice('No more keywords to grab off the queue to pull board search results. Going idle and sleeping.'));
        sleep(30);
        $engine->idle();
        exit;

    }


    CLI::write(Log::debug('Found ' . $calls->count() . ' keyword board search calls to make'));

    CLI::h2('Attempting to make calls with Pinterest');
    $curl_responses = $calls->send();

    CLI::write(Log::info('Calls successfully made. Parsing results.'));

    /*
     * Collection of Board (and status_keyword_board_search) objects so we can store the response
     */
    $boards = new Boards();
    $map_boards_keywords = new MapBoardsKeywords();
    $board_ids = array();

    foreach ($curl_responses as $key => $response) {

        try {

            /*
             * The key in the response matches the key in the calls collection
             * We'll need the call handy, so we grab it
             */
            /** @var $call QueuedApiCall */
            $call = $calls->getModel($key);
            CLI::h2(Log::debug('Handling response for keyword: ' . $call->object_id));

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
             * If Pinterest returned something other than that, we're in good shape and we
             * need to handle that accordingly
             */
            if ($response->code != 0) {
                throw new PinterestException(
                    $response->message,
                    $response->code
                );
            }

            /*
             * We want to get all this data into the collections
             * so we can easily save them to the DB
             */
            CLI::h3(Log::debug('Adding board data to Boards and MapBoardsKeywords collections'));
            foreach ($response->data as $boardData) {

                //TODO: check that we get what we want here
                $board = new Board();
                $board->loadAPIData($boardData);
                $board->user_id = $board->owner_user_id;
                $board->is_owner = true;
                $board->track_type = "keyword";
                $board_id = $board->board_id;
                $board_ids[] = $board_id;

                $map_boards_keyword = new MapBoardsKeyword();
                $map_boards_keyword->load($board,$call->object_id);


                /*
                 * Check to see if board is already in the database, so we don't insert twice,
                 * since inserting boards into data_boards seems to be a very expensive operation.
                 */
                $db_board_id = $DBH->query(
                                    "SELECT board_id FROM data_boards
                                    WHERE board_id = $board_id"
                )->fetch();

                pp($db_board_id);
                if(empty($db_board_id)){
                    $boards->add($board);
                }

                $map_boards_keywords->add($map_boards_keyword);
            }

            CLI::write(Log::debug('Added ' . $boards->count() . ' boards for keyword:' . $call->object_id));

            /*
            * Remove the call from the queue
            * Would prefer to do these in bulk, but
            * since the primary key is 4 columns
            * that proves to be more challenging
            */
            $call->removeFromDB();
            CLI::write(Log::debug('Removed call from the database'));

            /*
             * If there is a bookmark, we need to queue it up to be run
             */
            if (property_exists($response, 'bookmark')) {

                CLI::write(Log::debug('Bookmark found'));

                /** @var  $new_call QueuedApiCall */
                $new_call           = $calls->getModel($key);
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

        }
        catch (PinterestException $e) {

            switch ($e->getCode()) {
                case 10: //bookmark not found

                    ApiError::create(
                            $call->api_call,
                                $call->object_id,
                                $e->getMessage(),
                                'from keyword_search_boards script',
                                $e->getCode(),
                                $call->bookmark
                    );

                    $call->removeFromDB();
                    CLI::write(Log::notice('Removed call from queue'));

                    CLI::alert(Log::notice($e));

                    break;
                case 16: //Request timeout

                    CLI::alert(Log::error($e));

                    ApiError::create(
                            $call->api_call,
                                $call->object_id,
                                $e->getMessage(),
                                'from keyword_search_boards script',
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

                case 40: //Board not found

                    CLI::alert(Log::error($e));

                    $call->removeFromDB();
                    CLI::write(Log::notice('Board not found. Removed call from queue'));
                    break;

                default:

                    CLI::alert(Log::error($e));

                    /*
                     * But we should probably still log this error
                     * otherwise
                     */
                    ApiError::create(
                            $call->api_call,
                                $call->object_id,
                                $e->getMessage(),
                                'from keyword_search_boards script',
                                $e->getCode(),
                                $call->bookmark
                    );

                    CLI::write(Log::notice('Logged error in our DB'));

                    break;
            }
        }
        catch (QueuedApiCallException $e) {

            CLI::alert(Log::error($e));

            /*
             * if there was just a blank / weird response
             * we want to log the error
             */
            ApiError::create(
                    $call->api_call,
                        $call->object_id,
                        $e->getMessage(),
                        'from keyword_board_search script',
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
                CLI::alert(Log::error($e));
            }
        }

    }

    CLI::write(Log::runtime() . ' total runtime');
    CLI::write(Log::memory() . ' peak memory usage');

    CLI::h2('Finishing up');
    CLI::h2(Log::debug('Inserting Boards'));

    $start = microtime(true);

    try {
        CLI::write(Log::debug("New boards found: " . $boards->count()));
        $boards->saveModelsToDB();
    } catch(CollectionException $e) {
        CLI::write(Log::debug('No new boards to save'));
    }


    $board_insert_time = microtime(true) - $start;
    CLI::write(Log::debug('board insert time: ' . $board_insert_time . ' seconds.'));
    CLI::h2(Log::debug('inserting map_boards_keywords'));
    try {
        $map_boards_keywords->insertUpdateDB();
    } catch(CollectionException $e) {
        CLI::write(Log::info('No map_boards_keywords models to save.'));
    }
    CLI::write(Log::info('Saved all board and status_keyword_board_search models to database'));

}


catch (NoApiCallsOnQueueException $e) {
    CLI::write(Log::notice($e));
}

catch (EngineException $e) {
    CLI::stop(Log::error($e));
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

    CLI::stop(Log::error($e));
}

CLI::write(Log::debug("Running times", $running_times));

$engine->complete();
CLI::write(Log::info('Engine completed'));

$speed = $engine->computeSpeed($numberOfCallsInBatch);
CLI::write(Log::debug("Made $speed calls/second"));

CLI::write(Log::runtime() . ' total runtime');
CLI::write(Log::memory() . ' peak memory usage');

CLI::end('You\'re my 3D Romeo!');
