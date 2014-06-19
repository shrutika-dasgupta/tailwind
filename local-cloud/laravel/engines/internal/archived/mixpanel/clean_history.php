<?php

use Pinleague\CLI;

/**
 * We need to "Seed" the mixpanle project with actions that will be imported because of some
 * shit going on with the way Mixpanel imports things.
 *
 * @author  Will
 */

chdir(__DIR__);
include '../../../bootstrap/bootstrap.php';


set_time_limit(0);
ini_set('memory_limit', '500M');

$now = date('g:ia');
CLI::h1('Starting import (' . $now . ')');

$DBH = DatabaseInstance::DBO();
CLI::write('Connected to database');

$DBH = DatabaseInstance::DBO();

CLI::write('Creating Mixpanel instance');
$mixpanel = Mixpanel::getInstance(Config::get('mixpanel.TOKEN'));

CLI::write('Script is commented out because we shouldnt be doing this');

/*
 * Remove Duplicates from the CSV

CLI::h2('Remove 1-to-1 absolute duplicates');
$duplicates = csv_to_array(ROOT_PATH . 'duplicates.csv');

$keep = array();

foreach ($duplicates as $duplicate) {
    $key = md5($duplicate['cust_id'].$duplicate['type'].$duplicate['description'].$duplicate['timestamp']);

    if (!array_key_exists($key,$keep)) {
        $keep[$key] = $duplicate;
    } else {
        $remove[] = $dup = $duplicate['id'];
        $DBH->query("
            delete from user_history where id IN ($dup)
        ");
    }
}

CLI::write('Kept '.count($keep). ' user_history records');
CLI::write(('Removed '.count($remove)). ' user_history records');

CLI::h2('Remove "signup" duplicates');

$signups = array();

$STH = $DBH->query("
select * from user_history
where type = 'Sign up for an account'
");

CLI::write($STH->rowCount().' signup records found');

foreach($STH->fetchAll() as $row) {
    if (!in_array($row->cust_id,$signups)) {
        $signups[$row->cust_id] = $row->cust_id;
    } else {
        $remove[] = $dup = $row->id;
        $DBH->query("
            delete from user_history where id IN ($dup)
        ");
        // delete it
    }
}

CLI::write(count($signups).' total unique signup records found');

$STH = $DBH->query("
select * from user_history
where type = 'signup'
");

CLI::write($STH->rowCount().' more signup records found');

foreach($STH->fetchAll() as $row) {
    if (!in_array($row->cust_id,$signups)) {
        $signups[$row->cust_id] = $row->cust_id;
    } else {
        $remove[] = $dup = $row->id;
        $DBH->query("
            delete from user_history where id IN ($dup)
        ");
        // delete it
    }
}

CLI::write(count($signups).' total unique signup records found');
 */
