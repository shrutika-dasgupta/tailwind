<?php

/**
 *
 * This script pulls pins that were pinned from the domain
 *
 * @author  John
 * @author  Will
 *
 */

/*
 * Config
 */
$numberOfProfilesToRun = 18;

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use Pinleague\PinterestException;
use Pinleague\CLI;

Log::setLog(__FILE__);

try {

    CLI::h1('Starting program');

    $engine = new Engine('PullSitePins');

    if ($engine->running()) {

        throw new EngineException('Engine already running.');

    } else {

        /*
         * Get a list of the "Brand Mention" calls queued up on the status_api_calls_queue
         * If there aren't 150, find some more
         * If there aren't any calls queued up, we're done
         *
         */


    }


}
catch (EngineException $e) {

    CLI::alert($e->getMessage());
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
