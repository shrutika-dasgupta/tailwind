<?php
/**
 * Engine to calculate profile history of a given user
 * used from the command line
 *
 * @author  Will
 *
 * @example
 *          php calculateProfileHistory.php 123823452344
 */

ini_set('memory_limit', '300M');

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

Log::setLog(__FILE__);

$profile_id = $argv[1];

try {

    Log::info("Finding profile",array('profile_id'=>$profile_id));
    $profile = Profile::findInDB($profile_id);

    Log::debug('Found profile',$profile);
    /*
     * Uses the Pinterest API to grab boards
     * stores them in the database
     */
    $boards = $profile->getAPIBoards();
    $boards->setPropertyOfAllModels('track_type', 'leads');
    $boards->insertUpdateDB();

    Log::info('Saved boards',
              array(
                   'board_ids' => $boards->stringifyField('board_id')
              )
    );

    /*
     * Uses the boards from the API (above), finds which one the username owns,
     * finds the pins of those boards via the API
     * and then saves those to the DB
     */
    $pins = $profile->getAPIPinsFromOwnedBoards();
    $pins->setPropertyOfAllModels('track_type', 'free');
    $pins->insertUpdateDB();

    Log::info('Saved pins from boards');

    /*
     * Calculate Profile history
     */
    $calc = $profile->calculateProfileHistory();
    Log::debug('Calculated profile history',$calc);

    /*
     * Save to DB
     */
    $calc->insertUpdateDB();
    Log::info('Saved profile calc to database',$calc);


    Log::runtime();
    Log::memory();

}
catch (Exception $e) {
    Log::error($e);
}


