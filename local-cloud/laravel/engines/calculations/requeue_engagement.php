<?php

/**
 * Used to requeue the api calls for engagement of a given user
 *
 * @author  Will
 */

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

$profile_id = $argv[1];

try {

    $profile = Profile::findInDB($profile_id);

    /*
     * Uses the Pinterest API to grab boards
     * stores them in the database
     */
    $boards = $profile->getAPIBoards();
    /** @var $boards Boards */
    $boards->setPropertyOfAllModels('track_type', $profile->track_type);
    $boards->insertUpdateDB();

    /*
     * Uses the boards from the API (above), finds which one the username owns,
     * finds the pins of those boards via the API
     * and then saves those to the DB
     */
    $pins = $profile->getAPIPinsFromOwnedBoards();
    /** @var $pins Pins */
    $pins->setPropertyOfAllModels('track_type', $profile->track_type);
    $pins->insertUpdateDB();

    /*
     * Requeue the api calls for repins, comments, and engagement
     */
    $pins->queueApiCalls(QueuedApiCall::CALL_PIN_ENGAGEMENT_REPINS);
    $pins->queueApiCalls(QueuedApiCall::CALL_PIN_ENGAGEMENT_LIKES);
    $pins->queueApiCalls(QueuedApiCall::CALL_PIN_ENGAGEMENT_COMMENTS);


}
catch (Exception $e) {
    Log::error($e);
}


