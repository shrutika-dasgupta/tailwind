<?php

/**
 * User Followers for user track_type
 *
 * @author Yesh
 *         Alex
 */


ini_set('memory_limit', '300M');
chdir(__DIR__);

include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use Pinleague\PinterestException;
use Pinleague\CLI;

$numberOfCallsInBatch = 50;
const TRACK_TYPE = 'user';

Log::setLog(__FILE__);

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        Log::notice('Engine running | Sleep 10');
        sleep(10);
        throw new EngineException('Engine is running');
    }

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
    CLI::write('Connected to database');

    $user_ids_from_call = array();

    $queuing_calls = new QueuedApiCalls();

    CLI::write(Log::debug('Looking through all the users in status_profile_followers table'));
    $update_queue = QueuedApiCalls::push('status_profile_followers',
        TRACK_TYPE,
        $numberOfCallsInBatch);

    CLI::write(Log::debug('Find users to add to the Queue'));
    foreach ($update_queue as $q) {
        $queue = QueuedApiCall::readyDBObject('User Followers', $q);
        $queuing_calls->add($queue);
    }

    CLI::write(Log::debug('Saving new users to pull followers for to the Queue table', $queuing_calls));
    try {
        $queuing_calls->saveModelsToDB();
    }
    catch (CollectionException $e) {
        echo "No followers to save to queue" . PHP_EOL;
        Log::notice('No followers to save to queue');
    }

    CLI::write(Log::debug('Grabbing User Followers off the Queue'));

    try{
        $calls = QueuedApiCalls::fetchAndUpdateRunning('User Followers',
            TRACK_TYPE,
            $numberOfCallsInBatch);
    } catch (NoApiCallsOnQueueException $e)
    {
        CLI::alert(Log::notice('No more users to grab off the queue.  Going idle and sleeping.'));
        sleep(60);
        $engine->idle();
        exit;
    }

    foreach ($calls as $call) {
        $user_ids_from_call[] = $call->object_id;
    }


    $count_from_db = StatusProfile::getFollowersFoundCount($user_ids_from_call);
    $actual_count  = Follower::getFollowersCount($user_ids_from_call);


    CLI::write(Log::info('Making batch calls to the API', $user_ids_from_call));
    $response_data = $calls->send();

    $bookmark_data = new QueuedApiCalls();
    CLI::write(Log::debug('Prepare to parse API responses.'));

    $followers = new Followers();
    $profiles = new Profiles();

    // Keeping a counter to track the index of the response
    $count = -1;

    foreach ($response_data as $response) {
        $count += 1;
        if (($response->code) === 0) {

            $response_message = $response->message;
            $response_code    = $response->code;
            $object_id        = $calls[$count]->object_id;

            $follower_ids_from_api = array();

            CLI::write(Log::debug('Parsing through returned follower data.'));

            foreach ($response->data as $data) {
                $follower = new Follower();
                $profile  = new Profile();

                $profile->loadAPIData($data);
                $profile->timestamp = time();
                $profiles->add($profile);

                $follower->updateViaProfile($profile, $object_id);
                $followers->add($follower);
                $follower_ids_from_api[] = $data->id;
            }

            // Getting all the followers from the database from the array of
            // response follower_ids
            $follower_ids_implode = implode(",", $follower_ids_from_api);
            if (!empty($follower_ids_from_api)) {

                if (isset($response->bookmark)) {

                    $STH                = $DBH->query("SELECT user_id, follower_user_id
                                FROM data_followers
                                WHERE user_id = $object_id AND follower_user_id IN ($follower_ids_implode)");
                    $follower_ids_in_db = $STH->fetchAll();

                    // Calculating the percentage of users present in the database based on the
                    // total number from the api.
                    $percentage        = ($count_from_db[$object_id] / $actual_count[$object_id] * 100);
                    $within_percentage = ($percentage > 97) ? true : false;

                    // Checking to see if the followers we got back from the response already exist in the database
                    // If they do, we set flag to false
                    $any_new_followers = (count($follower_ids_in_db) == count($follower_ids_from_api)) ? false : true;

                    CLI::write(Log::debug("Bookmark found. " .
                        "Percentage of user's followers we have in the DB: $percentage." .
                        "New followers found during this run: " . ($any_new_followers ? "Yes" : "No")));


                    if ((!$within_percentage) || $any_new_followers) {
                        $bookmark_call = QueuedApiCall::loadBookmarkFromApi($response, 'User Followers', TRACK_TYPE, $object_id);

                        CLI::write(Log::debug("Adding bookmarked call to the queue"));

                        $bookmark_data->add($bookmark_call);
                    }
                }

                /** @var  $call QueuedApiCall
                 *
                 * Remove call from the DB
                 */
                $call = $calls->getModel($count);
                $call->removeFromDB();

            } else {
                CLI::write(Log::warning('No Data returned, even with successful response.'));

                $call = $calls->getModel($count);

                ApiError::create('User Followers',
                    $object_id,
                    $response->message,
                    'follower-free. No data returned even with a successful response',
                    $response->code,
                    $call->bookmark
                );

                /*
                 * Check to see if we've gotten this error more than 3 times in the last day
                 * If yes, remove it.
                 * If no, then we update the timestamp and add it back to try again
                 */
                $errors_found = ApiError::numberOfEntriesWithinTime(
                                        $call,
                                            $response->code,
                                            flat_date('day', time())
                );

                CLI::write(Log::debug($errors_found . " of the same error found."));

                if($errors_found > 2){
                    $call->removeFromDB();
                } else {
                    $call->rerunCall();
                    $call->updateTimestamp();
                }

            }
        } elseif (($response->code) === 30) {

            CLI::write(Log::warning('User not found'));

            /** @var  $call QueuedApiCall */
            $call = $calls->getModel($count);
            $call->removeFromDB();

            ApiError::create(
                    'User Followers',
                        $object_id,
                        $response->message,
                        'followers-free.' . __LINE__,
                        $response->code,
                        $call->bookmark
            );

        } elseif (($response->code) === 10) {

            CLI::write(Log::warning('Bookmark not found'));

            /** @var  $call QueuedApiCall */
            $call = $calls->getModel($count);
            $call->removeFromDB();

            ApiError::create('User Followers',
                $object_id,
                $response->message,
                'followers-free. Line ' . __LINE__,
                $response->code,
                $call->bookmark
            );

        } elseif (($response->code) === 11) {

            CLI::write(Log::critical('API method not found!'));

            /** @var  $call QueuedApiCall */
            $call = $calls->getModel($count);
            $call->removeFromDB();

            ApiError::create('User Followers',
                $object_id,
                $response->message,
                'followers-free. Line ' . __LINE__,
                $response->code,
                $call->bookmark
            );

        } elseif (($response->code) === 12) {

            CLI::write(Log::warning("Something went wrong on our end. Sorry about that."));

            ApiError::create('User Followers',
                $object_id,
                $response->message,
                'followers-free. Line ' . __LINE__,
                $response->code,
                $call->bookmark
            );

            /** @var  $call QueuedApiCall */
            $call = $calls->getModel($count);
            $call->rerunCall();
            $call->updateTimestamp();

        } elseif (($response->code) === 16) {

            CLI::write(Log::warning("Request didn't complete in time."));

            ApiError::create('User Followers',
                $object_id,
                $response->message,
                'followers-free. Line ' . __LINE__,
                $response->code,
                $call->bookmark
            );

            /** @var  $call QueuedApiCall */
            $call = $calls->getModel($count);
            $call->rerunCall();
            $call->updateTimestamp();

        } else {

            CLI::write(Log::critical('Other Uncaught Api Error: ' . $response->code . '. Message: ' . $response->message . ''));

            /** @var  $call QueuedApiCall */
            $call = $calls->getModel($count);
            $call->removeFromDB();

            ApiError::create('User Followers',
                $object_id,
                $response->message,
                'followers-free. Line ' . __LINE__,
                $response->code,
                $call->bookmark
            );
        }
    }

    CLI::write(Log::info('saving followers to db'));
    $followers->insertupdatedb();

    CLI::write(Log::info('saving profiles to db'));
    $profiles->insertupdatedb();

    try {
        CLI::write(Log::info('Found bookmarks - adding new bookmarked calls to the queue'));
        $bookmark_data->insertupdatedb();
    } catch (CollectionException $e) {
        CLI::write(Log::notice('No bookmarked calls found on this run.'));
    }

    $engine->complete();
    CLI::write(Log::runtime(). 'total runtime');
    CLI::write(Log::memory().' peak memory usage');

    CLI::h1(Log::info('Complete'));
}
catch (EngineException $e) {

    CLI::alert(Log::error($e));
    CLI::stop();

}
catch (PinterestException $e) {

    CLI::alert(Log::error($e));
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
