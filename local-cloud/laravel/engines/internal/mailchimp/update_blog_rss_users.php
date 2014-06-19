<?php

/**
 * Updates member info for users subscribed to the MailChimp Blog RSS mailing list. This is intended
 * to run once to initially fill new merge tags for all subscribers. Future merge tag updates will
 * handled within the app.
 *
 * @author Janell
 */

chdir(__DIR__);
include '../../../bootstrap/bootstrap.php';

use Pinleague\CLI,
    Pinleague\MailchimpWrapper;

Log::setLog(__FILE__, 'CLI', 'mailchimp_update_blog_rss_users');

try {
    CLI::h1('Starting MailChimp Update Blog RSS Users');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        throw new EngineException('Engine is already running');
    }

    $engine->start();
    Log::info('Engine started');

    Log::info('Creating MailChimp instance');
    $mailchimp = MailchimpWrapper::instance();

    $list_id = Config::get('mailchimp.BLOG_RSS_LIST_ID');

    $DBH = DatabaseInstance::DBO();
    Log::debug('Connected to database');

    $page       = 1;
    $limit      = 500;
    $more_users = true;

    while ($more_users) {
        $offset = $limit * ($page - 1);

        $STH = $DBH->prepare("
            SELECT *
            FROM users
            LIMIT $offset, $limit
        ");

        $STH->execute();
        Log::debug('Sent query to find users', array(
            'page'  => $page,
            'limit' => $limit,
        ));

        $data = $STH->fetchAll();
        foreach ($data as $user_data) {
            $user = User::createFromDBData($user_data);

            try {
                Log::debug('Updating list member', $user);
                $mailchimp->updateListMember($list_id, $user);
            } catch (Exception $e) {
                Log::error($e);
            }
        }

        if (count($data) < $limit) {
            $more_users = false;
        }

        $page++;
    }

    $engine->complete();
    Log::memory();
    Log::runtime();

    Log::info('Engine completed');

    CLI::end();

} catch (EngineException $e) {
    Log::error($e);
    CLI::stop();

} catch (Exception $e) {
    Log::error($e);
    echo $e->getTraceAsString();
    $engine->fail();

    CLI::stop();
}