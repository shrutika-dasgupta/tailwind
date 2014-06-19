<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
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

    if ($engine->running()) {
        throw new EngineException('Engine is running');
    } else {

        $conn = DatabaseInstance::mysql_connect();

        $pinterest = Pinterest::getInstance();


        $engine->start();

        $start = microtime(true);

        CLI::write(Log::info('Engine started'));

        CLI::write(Log::debug('Grab calls off Queue'));
        $calls = popPinLikesAPICalls(40, $conn);

        if (count($calls) == 0) {
            CLI::write(Log::notice('No pins to pull likes'));
            $engine->complete();
            exit;
        }

        $api_returns = array();
        foreach($calls as $call) {
            $api_return = array();
            $api_return['pin_id'] = $call['object_id'];
            $api_return['track_type'] = $call['track_type'];
            $api_return['call'] = $call;

            $parameters = array();
            if ($call['bookmark']) {
                $parameters['bookmark'] = $call['bookmark'];
            }

            CLI::write(Log::debug('Pulling likes for pin_id: ' . $call['object_id']));

            $api_return['data'] = $pinterest->getPinLikes($call['object_id'], $parameters);

            array_push($api_returns, $api_return);
        }

        foreach($api_returns as $api_return) {
            $activity = array();

            $users = array();
            if (isValidAPIReturn($api_return['data'], $api_return['call'], $conn)) {
                removeAPICall($api_return['call'], $conn);
                $pin_id = $api_return['pin_id'];
                $track_type = $api_return['track_type'];

                $api_data = getAPIDataFromCall($api_return['data']);

                $bookmark = getBookmarkFromAPIReturn($api_return['data']);
                if ($bookmark != "") {
                    if (anyNewLikes($pin_id, $api_data, $conn)) {
                        queuePinLikesAPICall($pin_id, $bookmark, $track_type, $conn);

                        CLI::write(Log::debug('Found bookmark; adding new call to the queue.'));
                    }
                }

                foreach($api_data as $likes_data) {
                    $likes = processAPIPinLikeData($pin_id, $likes_data);
                    array_push($activity, $likes);

                    $user = processAPIUserData("track", $likes_data);
                    array_push($users, $user);
                }
            } else {
                if (getAPIErrorCode($api_return['data']) == 50) {
                    removeAPICall($api_return['call'], $conn);
                    CLI::write(Log::warning('Pin not found; removing from the Queue.'));
                }
            }

            CLI::write(Log::debug('Saving likes to data_pins_likes table.'));
            savePinLikeActivity($activity, $conn);

            CLI::write(Log::debug('Adding new likers to data_profiles_new table.'));
            saveUsers($users, $conn);
        }

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


function anyNewLikes($pin_id, $data, $conn) {

    $user_ids = array();
    foreach($data as $d) {
        $user_id = $d['id'];
        if ($user_id != "") {
            array_push($user_ids, "\"$user_id\"");
        }
    }

    $found_user_ids = array();
    if (count($user_ids) > 0) {
        $acc = "select pin_id, liker_user_id from data_pins_likes where pin_id = '$pin_id' AND liker_user_id IN (" . implode(",", $user_ids) . ")";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $liker_user_id = $a['liker_user_id'];
            $found_user_ids["$liker_user_id"] = true;
        }
    }

    foreach($data as $d) {
        $user_id = $d['id'];
        if ($user_id != "") {
            if (!array_key_exists($user_id, $found_user_ids)) {
                return true;
            }
        }
    }

    return false;
}

function queuePinLikesAPICall($pin_id, $bookmark, $track_type, $conn) {
    queueAPICall("Pin Engagement Likes", $pin_id, "", $bookmark, $track_type, $conn);
}

function popPinLikesAPICalls($limit, $conn) {
    $calls = popAPICalls("Pin Engagement Likes", "pin_engagement", $limit, false, $conn);

    return $calls;
}

function processAPIPinLikeData($pin_id, $likes_data) {
    $data = array();
    $data['liker_user_id'] = $likes_data['id'];
    $data['pin_id'] = $pin_id;
    $data['follower_count'] = $likes_data['follower_count'];
    $data['gender'] = $likes_data['gender'];

    return $data;
}

/*
$pin_id = mysql_real_escape_string($likes['pin_id']);
$liker_user_id = mysql_real_escape_string($likes['liker_user_id']);

 [0]=> array(9) {
["username"]=> string(9) "johnabeth"
["first_name"]=> string(5) "Johna"
["last_name"]=> string(8) "Vitolins"
["image_medium_url"]=> string(61) "http://media-cache-ec4.pinimg.com/avatars/johnabeth-80_75.jpg"
["full_name"]=> string(14) "Johna Vitolins"
["image_small_url"]=> string(61) "http://media-cache-ec2.pinimg.com/avatars/johnabeth-80_30.jpg"
["type"]=> string(4) "user"
["id"]=> string(17) "35043840758909035"
["image_large_url"]=> string(62) "http://media-cache-ec3.pinimg.com/avatars/johnabeth-80_140.jpg" }
*/

?>
