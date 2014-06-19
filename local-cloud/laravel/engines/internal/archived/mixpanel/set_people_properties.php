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

        CLI::h2("Updating $user->user_id");

        $mixpanel->people->set(
                         $user->cust_id,
                             $user->getMixpanelPeopleParameters()
        );
        CLI::write('Successfully sent!');


    }

    CLI::end();

}
catch (Exception $e) {
    CLI::alert($e->getMessage() . ' | Line ' . $e->getLine());
    CLI::write($e->getFile());
}
