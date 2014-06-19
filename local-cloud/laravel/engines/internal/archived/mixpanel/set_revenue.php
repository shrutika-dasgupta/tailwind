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
                select * from user_history
                where `type` = 'Refunded'
                OR `type` = 'Subscription billed'
            ");

    $total = 0;

    foreach ($STH->fetchAll() as $row) {

        $user_history = new UserHistory($row->cust_id);

        switch ($row->type) {

            case 'Refunded':

                $parameters = (array) json_decode($row->description);

                CLI::write('Refund $'.$parameters['total_amount_refunded']);
                $mixpanel->people->trackCharge(
                    $row->cust_id,
                        $parameters['total_amount_refunded'],
                        $row->timestamp
                );


                $total = $total + $parameters['total_amount_refunded'];

                break;

            case 'Subscription billed':

                $parameters = array();
                if (!empty($row->description)) {
                    if (substr($row->description, 0, 1) == '{') {
                        $parameters = (array) json_decode($row->description);
                    }
                    else {
                        $amount                            = trim(str_replace('Billed $', '', $row->description));
                        $parameters['total_amount_billed'] = $amount;
                    }
                }

                CLI::write('Add $'.$parameters['total_amount_billed'].' from '.date('d / m / Y g:ia',$row->timestamp));

                $mixpanel->people->trackCharge(
                                 $row->cust_id,
                                 $parameters['total_amount_billed'],
                                 $row->timestamp
                );

                $total = $total + $parameters['total_amount_billed'];

                break;
        }
    }

    CLI::end('$'.number_format($total));

}
catch (Exception $e) {
    CLI::alert($e->getMessage() . ' | Line ' . $e->getLine());
}
