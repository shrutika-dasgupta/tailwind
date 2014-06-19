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
ini_set('memory_limit', '600M');

$now = date('g:ia');
CLI::h1('Finding duplicates (' . $now . ')');

$DBH = DatabaseInstance::DBO();
CLI::write('Connected to database');

$DBH = DatabaseInstance::DBO();


$STH = $DBH->query(
    'select * from mixpanel_import'
);

$keep  = array();
$signups = array();
$count = 0;

/**
 * This will remove absolute duplicates where EVERYTHING but the id
 * are the same
 */
$signup_types = array(
    'Sign up for a pro account',
    'Sign up for an account',
    'Sign up for free account',
    'signup'
);

$change_plan_types = array(
    'change_plan','Downgraded plan','Upgraded plan'
);

foreach ($STH->fetchAll() as $row) {

    if(in_array($row->type,$signup_types)) {
        $key = md5($row->cust_id. 'signup');
        $signups[$key] = $row;
    }else {
        $key = md5($row->cust_id . $row->type .  $row->timestamp);
    }

    if (!array_key_exists($key, $keep)) {
        $keep[$key] = $row;
    } else {
        $count++;

        $DBH->query("
            delete from mixpanel_import where id IN ($row->id)
        ");

        CLI::alert($row->id . ' is a duplicate! --' . $row->type);
    }
}
CLI::write(count($signups). ' unique signups');
CLI::stop($count . 'total duplicates removed');



