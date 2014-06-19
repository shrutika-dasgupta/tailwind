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
CLI::h1('Finding duplicates (' . $now . ')');

$DBH = DatabaseInstance::DBO();
CLI::write('Connected to database');

$DBH = DatabaseInstance::DBO();


$STH = $DBH->query(
    'select * from mixpanel_import'
);

$keep = array();
$count = 0;
foreach ($STH->fetchAll() as $row) {

    if (substr($row->description,0,1) == '{') {
        $params = json_decode($row->description);

        if(array_key_exists('imported',$params)) {
            CLI::alert($row->id. ' is an import!');
            $count++;

            $DBH->query("
            delete from mixpanel_import where id IN ($row->id)
        ");

        }
    }
}

CLI::write($count);
