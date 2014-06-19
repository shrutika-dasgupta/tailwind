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

    CLI::write('Clearing out all values for billing events');
    $DBH->query(
        "
        update user_organizations
        set
        billing_event_count = 0,
        first_billing_event_at = NULL,
        total_amount_billed = 0,
        last_billing_amount = 0,
        last_billing_event_at = NULL
        "
    );


    CLI::write('Finding all billing events');
    $STH = $DBH->query("
                select * from user_history
                where `type` = 'Refunded'
                OR `type` = 'Subscription billed'
            ");

    foreach ($STH->fetchAll() as $row) {

        /** @var /User */
        try {

            $user = User::find($row->cust_id);

            if (!$user) {
                throw new Exception('no user found');
            }
            /** @var Organization $organization */
            $organization = $user->organization();

            switch ($row->type) {

                case 'Refunded':

                    $parameters = (array)json_decode($row->description);
                    $amount     = $parameters['total_amount_refunded'];

                    CLI::write('Subtract ' . $amount);
                    $organization->total_amount_billed -= $amount;

                    break;

                case 'Subscription billed':

                    $parameters = array();
                    if (!empty($row->description)) {
                        if (substr($row->description, 0, 1) == '{') {
                            $parameters = (array)json_decode($row->description);
                        } else {
                            $amount                            = trim(str_replace('Billed $', '', $row->description));
                            $parameters['total_amount_billed'] = $amount;
                        }
                    }
                    $amount = $parameters['total_amount_billed'];

                    CLI::write('Add $' . $amount . ' from ' . date('d / m / Y g:ia', $row->timestamp));
                    $organization->total_amount_billed += $amount;
                    $organization->billing_event_count++;

                    if ($organization->first_billing_event_at == 0
                        OR $organization->first_billing_event_at > $row->timestamp
                    ) {
                        $organization->first_billing_event_at = $row->timestamp;
                    }

                    if (is_null($organization->last_billing_event_at)
                        OR $organization->last_billing_event_at < $row->timestamp
                    ) {
                        $organization->last_billing_event_at = $row->timestamp;
                        $organization->last_billing_amount   = $amount;
                    }

                    break;
            }

            $organization->insertUpdateDB();

        }
        catch (Exception $e) {
            CLI::alert($e->getMessage());
        }
    }

}
catch (Exception $e) {
    CLI::alert($e->getMessage() . ' | Line ' . $e->getLine());
}
