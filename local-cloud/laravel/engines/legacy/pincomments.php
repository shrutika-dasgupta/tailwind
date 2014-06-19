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

    if ($engine->running()) {
        throw new EngineException('Engine is running');
    } else {

        $conn = DatabaseInstance::mysql_connect();

        $pinterest = Pinterest::getInstance();


        $engine->start();

        $start = microtime(true);

        CLI::write(Log::info('Engine started'));

        CLI::write(Log::debug('Grab calls off Queue'));
        $calls = popPinCommentsAPICalls(120, $conn);

        if (count($calls) == 0) {
            CLI::write(Log::notice('No pins to pull comments.'));
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

            CLI::write(Log::debug('Pulling comments for pin_id: ' . $call['object_id']));

            $api_return['data'] = $pinterest->getPinComments($call['object_id'], $parameters);

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
                    if (anyNewComments($pin_id, $api_data, $conn)) {
                        queuePinCommentsAPICall($pin_id, $bookmark, $track_type, $conn);

                        CLI::write(Log::debug('Found bookmark; adding new call to the queue.'));
                    }
                }

                foreach($api_data as $comment_data) {
                    $comments = processAPIPinCommentData($pin_id, $comment_data);
                    array_push($activity, $comments);

                    if (array_key_exists("commenter", $comment_data)) {
                        $user = processAPIUserData("track", $comment_data['commenter']);
                        array_push($users, $user);
                    }
                }
            } else {
                if (getAPIErrorCode($api_return['data']) == 50) {
                    removeAPICall($api_return['call'], $conn);
                    CLI::write(Log::warning('Pin not found; removing from the Queue.'));
                }
            }

            CLI::write(Log::debug('Saving comments to data_pins_comments table.'));
            savePinCommentActivity($activity, $conn);

            CLI::write(Log::debug('Adding new commenters to data_profiles_new table.'));
            saveUsers($users, $conn);
        }

        $engine->complete();

        CLI::write(Log::runtime(). 'total runtime');
        CLI::write(Log::memory().' peak memory usage');;

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

function anyNewComments($pin_id, $data, $conn) {

    $comment_ids = array();
    foreach($data as $d) {
        $comment_id = $d['id'];
        if ($comment_id != "") {
            array_push($comment_ids, "\"$comment_id\"");
        }
    }

    $found_comment_ids = array();
    if (count($comment_ids) > 0) {
        $acc = "select comment_id from data_pins_comments where pin_id = '$pin_id' AND comment_id IN (" . implode(",", $comment_ids) . ")";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $comment_id = $a['comment_id'];

            $found_comment_ids["$comment_id"] = true;
        }
    }

    foreach($data as $d) {
        $comment_id = $d['id'];
        if ($comment_id != "") {
            if (!array_key_exists($comment_id, $found_comment_ids)) {
                return true;
            }
        }
    }

    return false;
}

function queuePinCommentsAPICall($pin_id, $bookmark, $track_type, $conn) {
    queueAPICall("Pin Engagement Comments", $pin_id, "", $bookmark, $track_type, $conn);
}

function popPinCommentsAPICalls($limit, $conn) {
    $calls = popAPICalls("Pin Engagement Comments", "pin_engagement", $limit, false, $conn);


    if (count($calls) < $limit) {
        $calls = array_merge($calls, popAPICalls("Pin Engagement Comments", "pin_engagement_keyword", $limit - count($calls), false, $conn));
    }
    return $calls;
}

function processAPIPinCommentData($pin_id, $comment_data) {
    $data = array();
    $data['pin_id'] = $pin_id;
    $data['comment_id'] = $comment_data['id'];
    $data['commenter_user_id'] = getCommenterUserId($comment_data);
    $data['comment_text'] = $comment_data['text'];
    $data['created_at'] = parsePinterestCreationDateToTimestamp($comment_data['created_at']);

    return $data;
}

function getCommenterUserId($comment_data) {
    if (array_key_exists("commenter", $comment_data)) {
        if (is_array($comment_data["commenter"])) {
            if (array_key_exists("id", $comment_data["commenter"])) {
                return $comment_data["commenter"]["id"];
            }
        }
    }

    return "";
}

/*

$comment_id = mysql_real_escape_string($comment['comment_id']);
$pin_id = mysql_real_escape_string($comment['pin_id']);
$commenter_user_id = mysql_real_escape_string($comment['commenter_user_id']);
$comment_text = mysql_real_escape_string($comment['comment_text']);
$created_at = mysql_real_escape_string($likes['created_at']);

 { [0]=> array(10) {

        ["text"]=> string(58) "What a great change from the usual! I love mini anything!"
        ["created_at"]=> string(31) "Thu, 12 Apr 2012 17:46:40 +0000"
        ["commenter"]=> array(9) {
            ["username"]=> string(9) "pearlb996"
            ["first_name"]=> string(5) "Pearl"
            ["last_name"]=> string(7) "Berdass"
            ["image_medium_url"]=> string(69) "http://media-cache-ec0.pinimg.com/avatars/pearlb996_1337116280_75.jpg"
            ["full_name"]=> string(13) "Pearl Berdass"
            ["image_small_url"]=> string(69) "http://media-cache-ec3.pinimg.com/avatars/pearlb996_1337116280_30.jpg"
            ["type"]=> string(4) "user"
            ["id"]=> string(18) "253820266407265509"
            ["image_large_url"]=> string(70) "http://media-cache-ec2.pinimg.com/avatars/pearlb996_1337116280_140.jpg"
        }
        ["type"]=> string(7) "comment"
        ["id"]=> string(15) "422487342991715" }
*/

?>
