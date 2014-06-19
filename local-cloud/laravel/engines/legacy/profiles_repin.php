<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
ini_set('memory_limit', '5000M');
ini_set('max_execution_time', '5000');
include('classes/pinterest.php');
include('classes/pin.php');
include('classes/user.php');
// include('includes/connection.php');
include('includes/functions.php');
include ("classes/crawl.php");
include('../../bootstrap/bootstrap.php');

use Pinleague\CLI;

Log::setLog(__FILE__);

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);
    $engine_name = $engine->name;

    $time_20_mins_back = strtotime("1200 seconds ago");

    if($engine->engineTimestamp($engine_name) < $time_20_mins_back){
        $engine->complete();
        CLI::alert(Log::warning('Been more than 20 mins. Resetting time.'));
        exit;
    }

    if ($engine->running()) {
        throw new EngineException('Engine is running');
    } else {

        $conn = DatabaseInstance::mysql_connect();

        $pinterest = Pinterest::getInstance();


        $engine->start();

        $start = microtime(true);

        CLI::write(Log::info('Engine started'));

        CLI::write(Log::debug('Grab calls off Queue'));
        $calls = popUserAPICalls(200, $conn);

        if (count($calls) < 500) {
            CLI::write(Log::debug('Check for users that need their data pulled'));
            $users = getUsersToPull(100, flat_date('day'), $conn);

            if (count($users) == 0) {
                CLI::write(Log::notice('No users to queue pages.'));
            }
            CLI::write(Log::debug('Add users that need data pulled to the Queue'));
            queueUserStartAPICalls($users, $conn);
        }

        if (count($calls) == 0) {
            CLI::write(Log::notice('No users to pull.'));
            $engine->complete();
            exit;
        }

        $api_returns = array();
        foreach($calls as $call) {
            $api_return = array();
            $api_return['user_id'] = $call['object_id'];
            $api_return['track_type'] = $call['track_type'];
            $api_return['call'] = $call;

            $parameters = array();
            if ($call['bookmark']) {
                $parameters['bookmark'] = $call['bookmark'];
            }

            CLI::write(Log::debug('Pulling data for user_id: ' . $call['object_id']));

            $api_return['data'] = $pinterest->getProfileInformation($call['object_id'], $parameters);

            array_push($api_returns, $api_return);
        }

        $users = array();
        foreach($api_returns as $api_return) {
            if (isValidAPIReturn($api_return['data'], $api_return['call'], $conn)) {
                removeAPICall($api_return['call'], $conn);
                $user_id = $api_return['user_id'];
                $track_type = $api_return['track_type'];

                $api_data = getAPIDataFromCall($api_return['data']);

                $user = processAPIUserData($track_type, $api_data);
                array_push($users, $user);

            } else {
                if (getAPIErrorCode($api_return['data']) == 30) {
                    removeAPICall($api_return['call'], $conn);
                    CLI::write(Log::warning('User not found; removing from the Queue.'));
                }
            }
        }

        CLI::write(Log::debug('Saving user data to data_profiles_new table.'));
        saveUsers($users, $conn);

        $engine->complete();

        CLI::write(Log::runtime(). 'total runtime');
        CLI::write(Log::memory().' peak memory usage');

        CLI::h1(Log::info('Complete'));
    }
}
catch (EngineException $e) {

    CLI::alert($e->getMessage());
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

function getUsersToPull($limit, $before_date, $conn) {
    $users = getUsersToPullTrackingOnly($limit, "repin_user", $before_date, $conn);

    if (count($users) < $limit) {
        $users = array_merge($users, getUsersToPullTrackingOnly($limit - count($users), "keyword", $before_date, $conn));
    }

    return $users;
}

function getUsersToPullTrackingOnly($limit, $track_type, $before_date, $conn) {
    $users = array();
    $user_ids = array();
    if ($track_type == "") {
        $acc = "select user_id, track_type from data_profiles_new where last_pulled <= '0' order by last_pulled asc limit $limit";
    } else {
        $acc = "select user_id, track_type from data_profiles_new where last_pulled <= '0' AND track_type = '$track_type' order by last_pulled asc limit $limit";
    }
    $acc_res = mysql_query($acc,$conn) or die(mysql_error());
    while ($a = mysql_fetch_array($acc_res)) {
        $user = array();
        $user['user_id'] = $a['user_id'];
        $user['track_type'] = $a['track_type'];

        array_push($users, $user);

        $user_id = $a['user_id'];
        array_push($user_ids, "\"$user_id\"");
    }

    if (count($user_ids) != 0) {
        $time = time();
        $acc = "update data_profiles_new set last_pulled = '$time' where user_id IN (" . implode(",", $user_ids) . ")";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
    }

    return $users;
}

function getUsersToPullPinsWithTrack($limit, $track_type, $before_date, $conn) {
    $users = array();
    $user_ids = array();
    $acc = "select user_id from status_profiles where last_pulled < '$before_date' AND track_type = '$track_type' order by last_pulled asc limit $limit";
    $acc_res = mysql_query($acc,$conn) or die(mysql_error());
    while ($a = mysql_fetch_array($acc_res)) {
        $user = array();
        $user['user_id'] = $a['user_id'];
        $user['track_type'] = $track_type;

        array_push($users, $user);

        $user_id = $a['user_id'];
        array_push($user_ids, "\"$user_id\"");
    }

    if (count($user_ids) != 0) {
        $time = time();
        $acc = "update status_profiles set last_pulled = '$time' where user_id IN (" . implode(",", $user_ids) . ")";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
    }

    return $users;
}

function popUserAPICalls($limit, $conn) {
    $calls = popAPICalls("User", "user", $limit, false, $conn);

    if (count($calls) < $limit) {
        $calls = array_merge($calls, popAPICalls("User", "repin_user", $limit - count($calls), false, $conn));
    }

    if (count($calls) < $limit) {
        $calls = array_merge($calls, popAPICalls("User", "keyword", $limit - count($calls), false, $conn));
    }

    if (count($calls) < $limit) {
        $calls = array_merge($calls, popAPICalls("User", "", $limit - count($calls), false, $conn));
    }

    return $calls;
}

function queueUserAPICall($user_id, $bookmark, $track_type, $conn) {
    queueAPICall("User", $user_id, "", $bookmark, $track_type, $conn);
}

function queueUserStartAPICalls($users, $conn) {
    foreach($users as $user) {
        $user_id = $user['user_id'];
        $track_type = $user['track_type'];
        queueUserAPICall($user_id, "", $track_type, $conn);
    }
}
?>
