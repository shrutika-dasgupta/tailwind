<?php
use Pinleague\CLI;

/**
 * Takes history in our user history table and imports it to Mixpanel
 *
 * @author  Will
 */

chdir(__DIR__);
set_time_limit(0);
ini_set('memory_limit', '500M');
include '../../../bootstrap/bootstrap.php';


try {

    $now = date('g:ia');
    CLI::h1('Starting import (' . $now . ')');

    $DBH = DatabaseInstance::DBO();
    CLI::write('Connected to database');

    $DBH = DatabaseInstance::DBO();

    CLI::write('Creating Mixpanel instance');
    $mixpanel = Mixpanel::getInstance(Config::get('mixpanel.TOKEN'));

    CLI::write('Script is commented out because we shouldnt be doing this');
    /*

    CLI::write('Finding possible types');
    $STH = $DBH->query("
                select * from users
                join (user_organizations,user_plans)
                on (users.org_id = user_organizations.org_id AND user_plans.id = user_organizations.plan)

            ");


    foreach ($STH->fetchAll() as $row) {

        $user = new User();
        $user->loadDBData($row);
        $user->preLoad('organization',$row);
        $user->organization()->preLoad('plan',$row);

        CLI::write('Reseting revenue for '.$user->email);

        $mixpanel->people->clearCharges($user->cust_id);

    }

    */

}
catch (Exception $e) {
    CLI::alert($e->getMessage() . ' | Line ' . $e->getLine());
    CLI::write($e->getFile());
}
