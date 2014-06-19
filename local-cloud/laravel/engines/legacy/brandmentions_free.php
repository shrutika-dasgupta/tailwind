<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
ini_set('memory_limit', '500M');
ini_set('max_execution_time', '5000');
include('classes/pinterest.php');
include('classes/pin.php');
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

        sleep(2);

        $engine->start();

        $start = microtime(true);

        CLI::write(Log::info('Engine started'));

        $calls = popBrandMentionsAPICalls(150, $conn);

        if (count($calls) < 250) {
            $sources = getBrandsToPullPins(150, strtotime("-8 hours"), $conn);

            if (count($sources) == 0) {
                CLI::write(Log::notice('No brands to queue pages'));
            }
            queueBrandMentionsStartAPICalls($sources, $conn);
        }

        if (count($calls) == 0) {
            CLI::write(Log::notice('No brands to pull pins.'));
            $engine->complete();
            exit;
        }

        $api_returns = array();
        foreach($calls as $call) {
            $api_return = array();
            $api_return['domain'] = $call['object_id'];
            $api_return['call'] = $call;
            print $api_return['domain'] . " pulled.".PHP_EOL;
            $api_return['track_type'] = $call['track_type'];
                var_dump($api_return['track_type']);

            $parameters = array();
            if ($call['bookmark']) {
                $parameters['bookmark'] = $call['bookmark'];
            }

            CLI::write(Log::debug('Pulling pins for domain: ' . $call['object_id']));

            $api_return['data'] = $pinterest->getBrandMentions($call['object_id'], $parameters);

            array_push($api_returns, $api_return);
        }

        foreach($api_returns as $api_return) {
            $pins = array();
            if (isValidAPIReturn($api_return['data'], $api_return['call'], $conn)) {
                removeAPICall($api_return['call'], $conn);
                $domain = $api_return['domain'];
                $track_type = $api_return['track_type'];

                $bookmark = getBookmarkFromAPIReturn($api_return['data']);
                if ($bookmark != "") {
                    queueBrandMentionAPICall($domain, $bookmark, $track_type, $conn);
                    CLI::write(Log::debug('Found bookmark; adding new call to the queue.'));
                }

                $api_data = getAPIDataFromCall($api_return['data']);

                if ($api_return['call']['bookmark'] == "") {
                    updatePinsPerDay($domain, $api_data, $conn);
                }

                Log::debug('Load API data to Pin object [legacy]');
                foreach($api_data as $pin_data) {
                    $pin = processAPIPinData("domain", $pin_data);
                    array_push($pins, $pin);
                }
            } else {
                if (getAPIErrorCode($api_return['data']) == 70) {
                    removeAPICall($api_return['call'], $conn);
                    CLI::write(Log::warning('Domain (' . $api_return['domain'] . ') not found. Removing call from the Queue.'));
                }
            }

            CLI::write(Log::debug('Saving pins.'));
            savePins($pins, $conn);
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



function updatePinsPerDay($domain, $api_data, $conn) {
    $pins_per_day = round(getPinsPerDayAverage($api_data),2);

    /*
     * To account for lulls / times when the script is run and the pin velocity might be lower
     * than at other times, we want to err to the side of running too often vs not running
     * it enough
     */
    $acc = "select pins_per_day from status_domains where domain = '$domain'";
    $acc_res = mysql_query($acc,$conn) or die(mysql_error());
    $a = mysql_fetch_array($acc_res);


    /*
     * Update pins per day only if its higher than the previous amount already in there
     */
    if($pins_per_day > $a['pins_per_day']) {

        $domain = mysql_real_escape_string($domain);
        $acc = "update status_domains set pins_per_day = '$pins_per_day' where domain = '$domain'";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());

    }
}

function getPinsPerDayAverage($api_data) {
    if (count($api_data) == 0) {
        return 0;
    }

    $i = count($api_data) - 1;
    $total_pins = count($api_data);

    $oldest_pin = parsePinterestCreationDateToTimestamp($api_data[$i]['created_at']);

    $gap = time() - $oldest_pin;
    $pins_per_day = ($total_pins / $gap) * (60*60*24);

    return $pins_per_day;
}

function getBrandsToPullPins($limit, $before_date, $conn) {
    $sources = getDomainsToPullPinsWithTrack($limit, "free", $before_date, $conn);

    if (count($sources) < $limit) {
        $sources = array_merge($sources, getDomainsToPullPinsWithTrack($limit - count($sources), "competitor", $before_date, $conn));
    }

    if (count($sources) < $limit) {
        $sources = array_merge($sources, getDomainsToPullPinsWithTrack($limit - count($sources), "keyword_tracking", $before_date, $conn));
    }

    if (count($sources) < $limit) {
        $sources = array_merge($sources, getDomainsToPullPinsWithTrack($limit - count($sources), "user", $before_date, $conn));
    }

    if (count($sources) < $limit) {
        $sources = array_merge($sources, getDomainsToPullPinsWithTrack($limit - count($sources), "pinmail", $before_date, $conn));
    }

    if (count($sources) < $limit) {
        $sources = array_merge($sources, getDomainsToPullPinsWithTrack($limit - count($sources), "track", $before_date, $conn));
    }


    return $sources;
}

function getDomainsToPullPinsWithTrack($limit, $track_type, $before_date, $conn) {
    $sources = array();
    $sources_fixed = array();
    $acc = "select domain, track_type, pins_per_day, last_pulled from status_domains where track_type = '$track_type' order by last_pulled asc";
    $acc_res = mysql_query($acc,$conn) or die(mysql_error());
    while ($a = mysql_fetch_array($acc_res)) {

        $last_pulled = $a['last_pulled'];
        $pins_per_day = $a['pins_per_day'];

        if ($pins_per_day < 100) {
            if ($last_pulled > strtotime("-1 day")) {
                continue;
            }
        } else if ($pins_per_day < 300) {
            if ($last_pulled > strtotime("-12 hours")) {
                continue;
            }
        } else if ($pins_per_day < 1000) {
            if ($last_pulled > strtotime("-8 hours")) {
                continue;
            }
        } else if ($pins_per_day < 1500) {
            if ($last_pulled > strtotime("-6 hours")) {
                continue;
            }
        } else if ($pins_per_day < 3000) {
            if ($last_pulled > strtotime("-3 hours")) {
                continue;
            }
        } else if ($pins_per_day < 8000) {
            if ($last_pulled > strtotime("-1 hours")) {
                continue;
            }
        } else if ($pins_per_day < 15000) {
            if ($last_pulled > strtotime("-30 minutes")) {
                continue;
            }
        } else if ($pins_per_day < 20000) {
            if ($last_pulled > strtotime("-20 minutes")) {
                continue;
            }
        } else {
            if ($last_pulled > strtotime("-15 minutes")) {
                continue;
            }
        }


        $source = array();
        $source['domain'] = $a['domain'];
        $source['track_type'] = $track_type;

        array_push($sources, $source);

        $domain = $a['domain'];
        array_push($sources_fixed, "\"$domain\"");

        if (count($sources) > $limit) {
            break;
        }
    }

    if (count($sources_fixed) != 0) {
        $time = time();
        $acc = "update status_domains set last_pulled = '$time' where domain IN (" . implode(",", $sources_fixed) . ")";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
    }

    return $sources;
}

function popBrandMentionsAPICalls($limit, $conn) {
    $calls = popAPICalls("Brand Mentions", "free", $limit, false, $conn);

    if (count($calls) < $limit) {
        $calls = array_merge($calls, popAPICalls("Brand Mentions", "competitor", $limit - count($calls), false, $conn));
    }

    if (count($calls) < $limit) {
        $calls = array_merge($calls, popAPICalls("Brand Mentions", "user", $limit - count($calls), false, $conn));
    }

    if (count($calls) < $limit) {
        $calls = array_merge($calls, popAPICalls("Brand Mentions", "keyword_tracking", $limit - count($calls), false, $conn));
    }

    if (count($calls) < $limit) {
        $calls = array_merge($calls, popAPICalls("Brand Mentions", "pinmail", $limit - count($calls), false, $conn));
    }

    if (count($calls) < $limit) {
        $calls = array_merge($calls, popAPICalls("Brand Mentions", "track", $limit - count($calls), false, $conn));
    }

    return $calls;
}

function queueBrandMentionAPICall($domain, $bookmark, $track_type, $conn) {
    queueAPICall("Brand Mentions", $domain, "", $bookmark, $track_type, $conn);
}

function queueBrandMentionsStartAPICalls($sources, $conn) {
    foreach($sources as $source) {
        $domain = $source['domain'];
        $track_type = $source['track_type'];
        queueBrandMentionAPICall($domain, "", $track_type, $conn);
    }
}
?>
