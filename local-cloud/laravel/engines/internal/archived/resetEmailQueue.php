<?php

/**
 * This script takes all the user email preferences and generates the email queue from it
 * Interfaces mostly with user_email_queue
 */

/*
 * Config
 */

use Pinleague\CLI;

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';
Config::set('app.debug', true);

try {

    CLI::h1('Reseting email queue');

    $DBH = DatabaseInstance::DBO();

    $a = $DBH->query('
        select * from user_email_preferences
        '
    )->fetchAll();

    foreach($a as $pref) {

        if ($pref->username == 'pinleague') { continue; }
        if ($pref->username == 'mademovement') { continue; }
        if ($pref->username == 'pinfluencers') { continue; }
        if ($pref->username == 'tailwindapp') { continue; }
        if ($pref->username == 'mssocialmedia') { continue; }
        if ($pref->username == 'rbips') { continue; }
        if ($pref->username == 'albachiara30') { continue; }

        $profile = Profile::findInDB($pref->username);
        $user_id = $profile->user_id;
        $username = $profile->username;

        $DBH->query("
            update user_email_preferences set `user_id` = '$user_id'
             where username = '$username'
        ");
    }

    $results = $DBH->query("
                select distinct cust_id from user_email_queue
            ")->fetchAll();

    foreach($results as $customer) {
        CLI::write($customer->cust_id);
       $user = User::find($customer->cust_id);
        $user->removeQueuedAutomatedEmails()->seedAutomatedEmails();
    }

    CLI::write('Engine completed');

    CLI::end();
    exit;

}

catch (EngineException $e) {
    CLI::alert($e->getMessage());
    exit;
}

catch (Exception $e) {
    CLI::alert($e->getFile() . ' line:' . $e->getLine());
    CLI::alert($e->getMessage());
    exit;
}

