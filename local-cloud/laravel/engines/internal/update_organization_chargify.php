<?php
/**
 * Updates the trial starts / ends from chargify data
 *
 * @author  Will
 */

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\CLI;

try {

    $now = date('g:ia');
    CLI::h1('Updating chargify data for all chargify organizations (' . $now . ')');

    $DBH = DatabaseInstance::DBO();
    CLI::write('Connected to database');

    $STH = $DBH->query(
               'select * from user_organizations where chargify_id IS NOT NULL AND chargify_id > 1'
    );

    CLI::write('Got the orgs. Updating via chargify');

    foreach ($STH->fetchAll() as $organizationData) {
        /** @var  $organization Organization */
        $organization = Organization::createFromDBData($organizationData);
        try {

            $subscription = $organization->subscription('force update');

            $organization->subscription_state = $subscription->state;
            if (!empty($subscription->trial_started_at)) {
                $organization->parseSubscription($subscription);
            }
            $organization->insertUpdateDB();
            CLI::write('Updated ' . $organization->org_id);


        }
        catch (Exception $e) {
            CLI::write($e->getMessage() . PHP_EOL);
        }

    }
}
catch (Exception $e) {
    CLI::alert($e->getMessage());

}