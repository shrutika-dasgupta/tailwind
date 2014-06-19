<?php
/**
 * Post Scheduler
 *
 * The script checks if there are any posts that need to be
 * posted to the respective networks.
 *
 * @author  Yesh
 * @author  Will
 */

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use
    Pinleague\CLI,
    Pinleague\Pinterest,
    Publisher\Post,
    Publisher\Posts,
    Publisher\TimeSlots;

Log::setLog(__FILE__, 'CLI');

try {

    CLI::h1('Starting Post Scheduler');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        Log::error('Engine is already running');
        CLI::sleep(15);
        CLI::stop();
    }

    $engine->start();
    Log::info('Engine started');

    $DBH = DatabaseInstance::DBO();
    Log::debug('Connected to database');

    $STH = $DBH->prepare("SELECT *
                          FROM publisher_time_slots
                          WHERE send_at < :current_time");

    $STH->execute([":current_time" => time()]);
    Log::debug('Sent query to find available time slots');

    /*** @var $time_slots TimeSlots */
    $time_slots = TimeSlots::createFromDBData($STH->fetchAll());

    if ($time_slots->isEmpty()) {
        Log::notice('No time slots ready');
        $engine->idle();
        CLI::end();
    }

    /**
     * Get the posts for these time slots (manual and auto)
     */
    $posts = $time_slots->getPosts();

    $time_slots->setNextSendTimes();
    Log::info('Updated time slots for the next send time');

    if ($posts->isEmpty()) {
        Log::notice('No posts to publish');
        $engine->idle();
        CLI::end();
    }

    $posts->setPropertyOfAllModels('status',Post::STATUS_PROCESSING);
    $posts->insertUpdateDB();

    $pins          = new Pins;
    $updated_posts = new Posts;

    /** @var $post \Publisher\Post */
    foreach ($posts as $post) {

        try {

            $pinterest    = new Pinterest();
            $user_account = $post->getUserAccount();

            if ($user_account->hasOAuthToken() == false) {
                throw new Exception(
                   'This account does not have a valid OAuth token and cannot ' .
                   'post pins or repins.'
                );
            }

            $pinterest->setAccessToken($user_account->access_token);

            if ($post->isRepin()) {

                $posted = $pinterest->postRepin(
                    $post->parent_pin,
                    $post->board_name,
                    $post->description
                );

                Log::debug('Repinned '.$post->parent_pin.' to Pinterest', $posted);

            } else {

                $posted = $pinterest->putPin(
                                    $post->board_name,
                                    $post->image_url,
                                    $post->description,
                                    $post->link
                );

                Log::debug('Pinned '.$post->image_url.' to Pinterest', $posted);
            }

            $post->pin_id  = $posted->pin_id;
            $post->status = Post::STATUS_SENT;
            $post->sent_at = time();

            $pins->add($posted);
        }
        catch (Exception $e) {
            $post->sent_at = time();
            $post->status = Post::STATUS_FAILED;

            Log::error($e,$post);
        }

        $updated_posts->add($post);
    }

    $updated_posts->insertUpdateDB();
    Log::info('Updated posts after posting');
    Log::debug($updated_posts->failed()->count().' posts failed.');

    $pins->insertUpdateDB();
    Log::info('Saved new pins to DB');

    $engine->complete();
    Log::memory();
    Log::runtime();

    Log::info('Engine completed');

    CLI::end();
}
catch (Exception $e) {

    Log::error($e);
    $engine->fail();

    CLI::stop();
}
