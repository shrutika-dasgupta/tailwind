<?php

use Pinleague\CLI;

/**
 * This script takes emails off the email queue after emailing the correct message
 * Interfaces mostly with user_email_queue
 *
 * @author  Will
 */
$emails_to_pop = 1;

/*
 * Since we're running this from the CLI, we're changing into this directory so
 * the relative path works as we'd expect here. Otherwise things get hairy
 */
chdir(__DIR__);
include '../../bootstrap/bootstrap.php';


/*
 * Increase the memory limit since some calculations happen here that require a bit
 */
ini_set('memory_limit', '5000M');

/*
 * We want to record logs to individual files, so we change the log settings here
 * Otherwise they would go to the general daily log
 */
Log::setLog(__FILE__,'DataEngines');

try {

    CLI::h1('Starting batch of emails');

    /*
     * Engines are how we track which backend processes are working and how long they take
     * We also use them as an extra sanity check to make sure we aren't running more than
     * one instance. It's not perfect.
     */
    CLI::write('Starting engine');
    $engine = new Engine(__FILE__);

    /*
     * We don't send emails between 12am and 5am because pulls are still coming through
     * and generally it complicates things. This should be fixed later.
     */
    if (date('G') >= 0 and date('G') <= 5) {
        /*
         * Update the engine status to idle so we know this isn't an error
         */
        $engine->idle();
        CLI::write(Log::info('Idling engine'));

        sleep(60);
        CLI::stop(Log::info('Pausing as we dont send emails between 12am and 5am | Sleep: 60'));
    }

    /*
     * If the engine is already, something is wrong. Why are we trying again? We sleep and wait to
     * see if another process that was running will set it to complete or idle before running again.
     */
    if ($engine->running()) {
        CLI::sleep(15);
        CLI::stop(Log::error('Engine is already running. | Sleep: 15'));
    }

    $engine->start();
    CLI::write(Log::info('Engine started'));

    try {

        $emails = UserEmails::findByTime('now', $emails_to_pop);

        if ($emails->count() == 0) {
            throw new EmailQueueException('No emails to send right now',960);
        }

        CLI::write(Log::info("Found " . $emails->count() . ' emails to send'));

        /*
         * We set the status of the emails to processing so if another instance of this
         * script is running we don't have the same email sent twice
         */
        $emails->setPropertyOfAllModels('status',UserEmail::STATUS_PROCESSING);
        $emails->insertUpdateDB();

        ClI::write(Log::info('Set emails status as `P`'));

        foreach ($emails as $email) {
            /** @var $email UserEmail */
            try {

                $name = $email->customer()->getName();
                CLI::h2(Log::info("Preparing email for $name"),$email);

                /*
                 * We don't want to send any outdated emails
                 * so anything less than 5 days ago just gets set as failed
                 */
                if ($email->send_at < strtotime('5 days ago')) {
                    CLI::alert(Log::warning('Email was supposed to be sent more than 5 days ago. Set as failed'));
                    $email->status = UserEmail::STATUS_FAILED;

                } else {

                    $email->prepareToSend();

                    if (!$email->send()) {

                        CLI::alert(Log::error('Email did not send. Set as failed.'));
                        $email->status = UserEmail::STATUS_FAILED;

                    } else {
                        CLI::yay(Log::info('Successfully sent email'),$email);
                        $email->status = UserEmail::STATUS_SENT;

                        $email->customer()->recordEvent(
                            UserHistory::EMAIL_SEND
                        );
                        CLI::write(Log::info('Saved send event in user history'),$email->customer());

                        if($email->reQueue()) {
                            CLI::write(Log::info('Requeued email for the next interval'),$email);
                        }
                    }
                }
                $email->saveToDB();
                CLI::write(Log::info('Saved email status back to queue'));
            }

            catch (Exception $e) {
                CLI::alert(Log::error($e));

                $email->status = UserEmail::STATUS_FAILED;
                $email->saveToDB();
                CLI::write(Log::notice('Email failed, but was able to save status as failed'),$email);
            }

        }
    }
    catch (UserEmailException $e) {
        CLI::alert(Log::error($e));
    }

    CLI::h2('Finishing up');
    $per_email = $engine->computeSpeed($emails_to_pop);
    CLI::write("Sent $per_email emails/second");

    $engine->complete();
    CLI::write(Log::info('Engine completed'));

    CLI::write(Log::runtime(). 'total runtime');
    CLI::write(Log::memory().' peak memory usage');

    CLI::end();
}

catch (EmailQueueException $e) {
    /*
     * Setting the engine idle allows the script to run again, but does not
     * count as a "engine run time"
     */
    $engine->idle();

    CLI::write(Log::notice($e,array('sleep'=>15)));

    /*
     * We want to sleep for 15 seconds so this doesn't spin out of control
     * If there were no emails, no reason checking a million times
     */
    CLI::alert('We can try again in like, 15 seconds');
    CLI::sleep(15);

    CLI::stop();
}

catch (Exception $e) {
    /*
     * Setting an engine to fail still allows it to run again, but just gives context if we're looking
     * at the engines.
     */
    $engine->fail();

    CLI::alert(Log::error($e));

    CLI::alert(get_class($e) . ' Exception');
    CLI::alert($e->getFile() . ' line:' . $e->getLine());

    CLI::stop();
}

