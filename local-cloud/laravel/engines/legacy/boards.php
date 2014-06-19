<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
ini_set('memory_limit', '5000M');
ini_set('max_execution_time', '5000');
include('classes/pinterest.php');
include('classes/pin.php');
include('classes/board.php');
// include('includes/connection.php');
include('includes/functions.php');
include ("classes/crawl.php");

include('../../bootstrap/bootstrap.php');

use Pinleague\CLI;

Log::setLog(__FILE__);

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    $time_20_mins_back = strtotime("1200 seconds ago");

    $engine_name = $engine->name;

    if($engine->engineTimestamp($engine_name) < $time_20_mins_back){
        $engine->complete();
        CLI::alert(Log::warning('Been more than 20 mins. Resetting time.'));
        exit;
    }

    if ($engine->running()) {
        throw new EngineException('Engine is running');
    } else {
        $engine->start();

        $start = microtime(true);

        CLI::write(Log::info('Engine started'));

        $conn = DatabaseInstance::mysql_connect();

        $DBH = DatabaseInstance::DBO();

        $pinterest = Pinterest::getInstance();

        CLI::write(Log::debug('Grab calls off Queue'));
        $calls = popUserBoardsAPICalls(80, $conn);

        if (count($calls) < 80) {
            CLI::write(Log::debug('Check for users that need boards pulled'));
            $users = getUsersToPullBoards(230, getFlatDate(time()), $conn);

            if (count($users) == 0) {
                CLI::write(Log::notice('No users left that need boards queued'));
            }

            CLI::write(Log::debug('Add users that need boards pulled to the Queue'));
            queueUserBoardsStartAPICalls($users, $conn);
        }

        if (count($calls) == 0) {
            CLI::write(Log::notice('No users on the Queue to pull boards.'));
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

            CLI::write(Log::debug('Pulling boards for user_id: ' . $call['object_id']));

            $api_return['data'] = $pinterest->getProfileBoards($call['object_id'], $parameters);

            // Making an array of all the board ids of
            // a user from the api
            foreach($api_return['data']['data'] as $data){
                $board_ids_from_api[] = $data['id'];
            }

            $STH = $DBH->prepare("
                                SELECT *
                                FROM data_boards
                                WHERE user_id = :user_id");
            $STH->execute(array(":user_id" => $call['object_id']));

            $boards_in_db = $STH->fetchAll();

            $board_ids_from_db = array();

            // Making an array of all the board ids of
            // a user from the db
            foreach($boards_in_db as $board){
                $board_ids_from_db[] = $board->board_id;
            }

            CLI::write(Log::debug('Checking against user\'s existing boards'));

            $dead_boards = array_diff($board_ids_from_db, $board_ids_from_api);

            if (!empty($dead_boards)){
                CLI::write(Log::debug('Found deleted boards for user_id: ' . $call['object_id']));
                foreach($dead_boards as $board){
                    $STH = $DBH->prepare("
                                        UPDATE data_boards
                                        SET track_type = 'deleted',
                                        timestamp = :timestamp
                                        WHERE user_id = :user_id AND board_id = :board_id");
                    $STH->execute(
                        array(
                             ":timestamp" => time(),
                             ":user_id" => $call['object_id'],
                             ":board_id" => $board
                        )
                    );


                    /**
                     * Now we're going to check and see if any other users have this board as
                     * still active.  If not, then we can set the track_type to "orphan" in the
                     * status_boards table as well, since we have no reason to pull data on this
                     * board's pins or followers any longer.
                     */
                    $STH = $DBH->prepare(
                        "SELECT count(*) as count
                        FROM data_boards
                        WHERE board_id = :board_id
                        AND track_type != 'deleted'"
                    );

                    $STH->execute(array(":board_id" => $board));

                    $count = $STH->fetch();

                    pp($count);

                    if ($count->count == 0) {
                        $STH = $DBH->prepare("
                                        UPDATE status_boards
                                        SET track_type = 'orphan'
                                        WHERE board_id = :board_id");
                        $STH->execute(array(":board_id" => $board));
                    }

                }
                CLI::write(Log::debug('Updated deleted boards for user_id: ' . $call['object_id']));


            }

            array_push($api_returns, $api_return);
        }

        foreach($api_returns as $api_return) {
            $boards = array();
            if (isValidAPIReturn($api_return['data'], $api_return['call'], $conn)) {
                removeAPICall($api_return['call'], $conn);
                $user_id = $api_return['user_id'];
                $track_type = $api_return['track_type'];

                $bookmark = getBookmarkFromAPIReturn($api_return['data']);
                if ($bookmark != "") {
                    queueUserBoardsAPICall($user_id, $bookmark, $track_type, $conn);

                    CLI::write(Log::debug('Found bookmark; adding new call to the queue.'));
                }

                $api_data = getAPIDataFromCall($api_return['data']);

                foreach($api_data as $board_data) {
                    $board = processAPIBoardData($user_id, $track_type, $board_data);
                    array_push($boards, $board);
                }
            } else {
                if (getAPIErrorCode($api_return['data']) == 30) {
                    removeAPICall($api_return['call'], $conn);

                    CLI::write(Log::warning('User not found; removing from the Queue.'));
                } else {
                    removeAPICall($api_return['call'], $conn);
                    CLI::write(Log::warning("Other error: " . getAPIErrorCode($api_return['data']) . ".  Call removed."));
                }
            }

            CLI::write(Log::debug('Saving boards to data_boards table.'));
            saveBoards($boards, $conn);

            CLI::write(Log::debug('Adding new boards to the status_boards table for calcs.'));
            queueBoardsForCalcs($boards, $conn);
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


function queueBoardsForCalcs($boards, $conn) {
    $inserts = "";

    foreach($boards as $board) {
        $board_id = mysql_real_escape_string($board->id);
        $track_type = mysql_real_escape_string($board->track_type);
        $owner_user_id = mysql_real_escape_string($board->owner_user_id);

        $time = time();
        if (($track_type == 'user') || ($track_type == 'competitor') || ($track_type == 'free') || ($track_type == 'pinmail')) {
            if ($inserts == "") {
                $inserts = "INSERT IGNORE INTO status_boards (board_id, owner_user_id, last_calced, track_type, timestamp) VALUES (\"$board_id\", \"$owner_user_id\", '0', \"$track_type\", \"$time\")";
            } else {
                $inserts .= ",
					(\"$board_id\", \"$owner_user_id\", '0', \"$track_type\", \"$time\")";
            }
        }
    }

    if ($inserts != "") {
        $acc_res = mysql_query($inserts,$conn) or die('Line '.__LINE__.': '.mysql_error());
    }
}

function getUsersToPullBoards($limit, $before_date, $conn) {
    $users = getUsersToPullBoardsWithTrack($limit, "user", $before_date, $conn);

    if (count($users) < $limit) {
        $users = array_merge($users, getUsersToPullBoardsWithTrack($limit - count($users), "competitor", $before_date, $conn));
    }

    if (count($users) < $limit) {
        $users = array_merge($users, getUsersToPullBoardsWithTrack($limit - count($users), "free", $before_date, $conn));
    }

    if (count($users) < $limit) {
        $users = array_merge($users, getUsersToPullBoardsWithTrack($limit - count($users), "pinmail", $before_date, $conn));
    }

    if (count($users) < $limit) {
        $users = array_merge($users, getUsersToPullBoardsWithTrack($limit - count($users), "track", $before_date, $conn));
    }

    return $users;
}

function getUsersToPullBoardsWithTrack($limit, $track_type, $before_date, $conn) {
    $users = array();
    $users_fixed = array();
    $acc = "select user_id, track_type from status_profiles where last_pulled_boards < '$before_date' AND track_type = '$track_type' order by last_pulled_boards asc limit $limit";
    $acc_res = mysql_query($acc,$conn) or die('Line '.__LINE__.': '.mysql_error());
    while ($a = mysql_fetch_array($acc_res)) {
        $user = array();
        $user['user_id'] = $a['user_id'];
        $user['track_type'] = $track_type;

        array_push($users, $user);

        $user_id = $a['user_id'];
        array_push($users_fixed, "\"$user_id\"");
    }

    if (count($users_fixed) != 0) {
        $time = time();
        $acc = "update status_profiles set last_pulled_boards = '$time' where user_id IN (" . implode(",", $users_fixed) . ")";
        $acc_res = mysql_query($acc,$conn) or die('Line '.__LINE__.': '.mysql_error());
    }

    return $users;
}

function popUserBoardsAPICalls($limit, $conn) {
    $calls = popAPICalls("User Boards", "user", $limit, false, $conn);

    if (count($calls) < $limit) {
        $calls = array_merge($calls, popAPICalls("User Boards", "competitor", $limit - count($calls), false, $conn));
    }

    if (count($calls) < $limit) {
        $calls = array_merge($calls, popAPICalls("User Boards", "free", $limit - count($calls), false, $conn));
    }

    if (count($calls) < $limit) {
        $calls = array_merge($calls, popAPICalls("User Boards", "pinmail", $limit - count($calls), false, $conn));
    }

    if (count($calls) < $limit) {
        $calls = array_merge($calls, popAPICalls("User Boards", "track", $limit - count($calls), false, $conn));
    }

    return $calls;
}

function queueUserBoardsAPICall($user_id, $bookmark, $track_type, $conn) {
    queueAPICall("User Boards", $user_id, "", $bookmark, $track_type, $conn);
}

function queueUserBoardsStartAPICalls($users, $conn) {
    foreach($users as $user) {
        $user_id = $user['user_id'];
        $track_type = $user['track_type'];
        queueUserBoardsAPICall($user_id, "", $track_type, $conn);
    }
}
?>
