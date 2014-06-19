<?php


/**
 * This script takes cohorts and opts them into various email
 * preferences
 */
$csv_file = '8_cohorts-may2-2014.csv';


chdir(__DIR__);
include '../../../bootstrap/bootstrap.php';

use Pinleague\CLI;

CLI::h1('Starting program');
CLI::date();

CLI::h2('Grabbing cohorts');
$cohorts = csv_to_array(__DIR__ . '/csv/' . $csv_file, ',', false);

foreach ($cohorts as list($cohort, $cust_id, $org_id)) {

    try {

        switch ($cohort) {
            default:
                throw new Exception('Cohort not accounted for: ' . $cohort);
                break;

            /**
             * Control group. Nothing to do.
             */
            case 1:
                CLI::write("Control group: $cust_id - Nothing done.");
                break;

                /**
                 * Daily summaries
                 */
            case
                2:

                CLI::write("Adding $cust_id to daily summary");

                /** @var User $user */
                $user = User::find($cust_id);

                /** @var UserAccount $user_account */
                foreach ($user->organization()->activeUserAccounts() as $user_account) {

                    $stats_preference               = new UserEmailPreference();
                    $stats_preference->cust_id      = $cust_id;
                    $stats_preference->name         = UserEmail::DAILY_SUMMARY;
                    $stats_preference->user_id      = $user_account->user_id;
                    $stats_preference->username     = $user_account->username;
                    $stats_preference->frequency    = UserEmailPreference::DAILY;
                    $stats_preference->hour_to_send = UserEmailPreference::DEFAULT_SEND_TIME;
                    $stats_preference->insertUpdateDB();
                }

                break;

                /**
                 * Weekly summaries
                 */
            case 3:

                CLI::write("Adding $cust_id to weekly summary");

                $user = User::find($cust_id);

                /** @var UserAccount $user_account */
                foreach ($user->organization()->activeUserAccounts() as $user_account) {

                    $stats_preference               = new UserEmailPreference();
                    $stats_preference->cust_id      = $cust_id;
                    $stats_preference->name         = UserEmail::WEEKLY_SUMMARY;
                    $stats_preference->user_id      = $user_account->user_id;
                    $stats_preference->username     = $user_account->username;
                    $stats_preference->frequency    = UserEmailPreference::WEEKLY;
                    $stats_preference->hour_to_send = UserEmailPreference::DEFAULT_SEND_TIME;
                    $stats_preference->insertUpdateDB();
                }

                break;

                /**
                 * Both weekly and daily
                 */
            case 4:
                CLI::write("Adding $cust_id to both weekly and daily");


                $user = User::find($cust_id);

                /** @var UserAccount $user_account */
                foreach ($user->organization()->activeUserAccounts() as $user_account) {

                    $stats_preference               = new UserEmailPreference();
                    $stats_preference->cust_id      = $cust_id;
                    $stats_preference->name         = UserEmail::WEEKLY_SUMMARY;
                    $stats_preference->user_id      = $user_account->user_id;
                    $stats_preference->username     = $user_account->username;
                    $stats_preference->frequency    = UserEmailPreference::WEEKLY;
                    $stats_preference->hour_to_send = UserEmailPreference::DEFAULT_SEND_TIME;
                    $stats_preference->insertUpdateDB();


                    $stats_preference               = new UserEmailPreference();
                    $stats_preference->cust_id      = $cust_id;
                    $stats_preference->name         = UserEmail::DAILY_SUMMARY;
                    $stats_preference->user_id      = $user_account->user_id;
                    $stats_preference->username     = $user_account->username;
                    $stats_preference->frequency    = UserEmailPreference::DAILY;
                    $stats_preference->hour_to_send = UserEmailPreference::DEFAULT_SEND_TIME;
                    $stats_preference->insertUpdateDB();

                }

                break;

            /**
             * these cohorts haven't been used yet
             */
            case 5:
            case 6:
            case 7:
            case 8:
                continue;
                break;

        }

        if($user) {
            $user->removeQueuedAutomatedEmails()
                 ->seedAutomatedEmails();
        }

    }
    catch (Exception $e) {
        CLI::alert($e->getMessage());
    }

}
