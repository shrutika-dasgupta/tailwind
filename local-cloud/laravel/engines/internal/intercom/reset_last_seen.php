<?php
/**
 * In the move to adding intercom events, we were setting the last_seen in
 * Intercom when the user wasn't actually in app.
 *
 * To fix this, we're going to go through all the events in user history (dating
 * back to last Wednesday (May 21) and find the latest non
 */
chdir(__DIR__);
include '../../../bootstrap/bootstrap.php';

use
    Pinleague\CLI,
    Pinleague\IntercomWrapper;

Log::setLog(__FILE__, 'CLI');

/**
 * The list of events that shouldn't update last seen
 * @var array
 */
$mistaken_events = array(
    UserHistory::EMAIL_SEND,
    UserHistory::PROFILE_HISTORY_EMAIL_SENT,
    UserHistory::EMAIL_BOUNCE,
    UserHistory::MAILCHIMP_PROFILE_UPDATE,
    UserHistory::SUBSCRIPTION_STATE_CHANGE,
    UserHistory::BILLED,
    UserHistory::BILLING_FAILED,
    UserHistory::CARD_SOON_TO_EXPIRE,
    UserHistory::TRIAL_END,
    UserHistory::TRIAL_CONVERTED,
);

$time_of_push = 1400691600;

try {
    CLI::h1('Starting Intercom fixer upper');

    $DBH = DatabaseInstance::DBO();
    Log::debug('Connected to database');

    CLI::write("The push for Segment.io was Thursday around 1pm (1400691600 epoch time)");
    CLI::write("Finding those with history actions that shouldn't have been updated");


    $STH = $DBH->prepare("
        SELECT DISTINCT cust_id FROM user_history WHERE timestamp > :time_of_push
        AND `type` IN('". implode($mistaken_events,"','")."')
        ");

    $STH->execute([":time_of_push" => $time_of_push]);

    foreach ($STH->fetchAll() as $row) {

        CLI::h2("Updating last_seen for $row->cust_id");

        $UH = $DBH->prepare("
            SELECT max(`timestamp`) as last_seen
            from user_history
            where cust_id = :cust_id
            AND `type` NOT IN('". implode($mistaken_events,"','")."')
        ");

        $UH->execute(['cust_id'=>$row->cust_id]);

        $history = $UH->fetch();

        CLI::write("Real last seen for $row->cust_id is $history->last_seen");

        $user = User::find($row->cust_id);

        $intercom = IntercomWrapper::instance();
        $intercom->updateLastSeen($user,$history->last_seen);

        CLI::write('Updated the last seen for '.$row->cust_id.' in intercom');

    }

    Log::memory();
    Log::runtime();

    Log::info('Last seen times have been updated to our best guess');

    CLI::end();
}
catch (Exception $e) {

    Log::error($e);
    $engine->fail();

    CLI::stop();
}
