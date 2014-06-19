<?php

/*
 * This script is used to go through all the rss feeds and parse all the data
 * by hitting the google/feedly feeds API.
 *
 * @author Daniel
 * @author Yesh
 */

ini_set('memory_limit', '2000M');

chdir(__DIR__);
include '../../../bootstrap/bootstrap.php';

use Content\MapFeedEntryDescription,
    Content\MapFeedEntryCategory,
    Content\MapFeedEntryImage,
    Content\StatusFeed,
    Content\StatusFeeds,
    Content\DataFeed,
    Content\DataFeeds,
    Content\MapFeedEntryDescriptions,
    Content\MapFeedEntryCategories,
    Content\MapFeedEntryImages,
    Content\TrackFeedVolume,
    Content\TrackFeedsVolumes,
    FastImage\Transports\CurlAdapter,
    Pinleague\CLI,
    Pinleague\SocialWorth;


Log::setLog(__FILE__, 'CLI');

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        Log::warning('Engine Already Running');
        CLI::sleep(10);
        CLI::stop();
    }

    $engine->start();
    Log::info('Engine Started');

    Log::info('Fetching Feeds');
    $feeds = StatusFeeds::fetch($limit = 30);

    if (!$feeds || $feeds->isEmpty()) {
        CLI::alert('No more feeds to pull.');
        $engine->complete();
        CLI::stop();
    }

    $DBH = DatabaseInstance::DBO();

    $map_feed_descriptions = new MapFeedEntryDescriptions();
    $map_feed_categories   = new MapFeedEntryCategories();
    $map_feed_images       = new MapFeedEntryImages();
    $track_volumes         = new TrackFeedsVolumes();

    $status_feeds    = $feeds->getModels();
    $completed_feeds = array();

    try {
        $api_feeds_entries = $feeds->loadFeedsEntries();

    } catch (Guzzle\Http\Exception\MultiTransferException $e) {

        Log::error($e);

        $all_requests  = $e->sendBatchRequests;

        /**
         * Original Author: Yesh
         * TODO: Will and I have discussed on a more efficient way of
         * solving the problem of feeds with errors. The following link
         * shows the proper optimization that we arrived at.
         * Link: http://i.imgur.com/YHjXYJ6.jpg
         */
        $fail_request = $e->getFailedRequests()[0];

        $curl_error_code = $e->getExceptionForFailedRequest($fail_request)->getErrorNo();

        switch($curl_error_code) {

           /**
            * The error_code 7 for curl means it couldn't connect to
            * the host
            */
           case 7:
               ApiError::create(
                       'RSS Feeds',
                       $e->getExceptionForFailedRequest($request)->getCurlInfo()["url"],
                       $e->getExceptionForFailedRequest($request)->getError(),
                       '',
                       $curl_error_code,
                       ''
               );
               Log::error("The curl request couldn't complete | Sleep 20");
               sleep(20);
               $engine->fail();
               CLI::stop();
               break;

           default:
               $counter = 0;
               foreach ($all_requests as $request) {

                   if ($fail_request->getUrl() == $request->getUrl()) {
                       $STH = $DBH->prepare("UPDATE status_feeds
                                             SET curated = :curated,
                                             last_pulled = :current_time
                                             WHERE id = :id");

                       $STH->execute([":curated" => -1,
                                      ":current_time" => time() ,
                                      ":id" => $status_feeds[$counter]->id]);
                       Log::warning("Buggy feed ID: {$status_feeds[$counter]->id}. Resetting the queue");
                       $engine->complete();
                       CLI::stop();
                    } else {
                        $counter ++;
                    }
                }
               break;
       }
    }

    if (empty($api_feeds_entries)) {
        Log::warning("No entries found for any feed this run");
    }

    /*
     * Initialize variables that keep track of time
     */
    $process_entries_start = microtime(true);

    $status_feeds = $feeds->getModels();
    foreach ($api_feeds_entries as $key => $feed_entries) {

        $status_feed = array_get($status_feeds, $key);

        Log::debug("Feed ID {$status_feed->id}: Beginning Feed Entry Processing");
        Log::debug("Feed ID {$status_feed->url}: Beginning Feed Entry Processing");

        if ($feed_entries->count() == 0) {
             DB::table('status_feeds')
                    ->where('id', $status_feed->id)
                    ->update(["curated" => -2]);

            Log::notice("No Entries to Process for Feed ID {$status_feed->id}");
            continue;
        }

        $new_entries_count = $feed_entries->numberOfNewEntries();

        if ($new_entries_count > 0) {

            foreach ($feed_entries->getModels() as $model_key => $entry) {
                // Skip entries missing a URL.
                if (empty($entry->url)) {
                    $feed_entries->removeModel($model_key);
                    continue;
                }

                // Skip entries with really long URLs.
                if (strlen($entry->url) > 255) {
                    Log::warning('The url is really long for ' . $entry->url);

                    $feed_entries->removeModel($model_key);
                    continue;
                }

                // Set entry values from parent feed.
                $entry->feed_id = $status_feed->id;
                $entry->curated = $status_feed->curated;

                /*
                 * Save each entry separately so that its auto-incrementing primary key
                 * can be used for associating the relations below.
                 */
                $entry->insertUpdateDB(["domain", "title", "description",
                                       "social_score", "facebook_score", "twitter_score",
                                       "googleplus_score", "pinterest_score",
                                       "published_at", "curated",
                                       "added_at"]);

                // Save the entry's full text description if one exists.
                if (!empty($entry->meta->content)) {
                    $entry_description                = new MapFeedEntryDescription();
                    $entry_description->feed_entry_id = $entry->id;
                    $entry_description->description   = $entry->meta->content;

                    $map_feed_descriptions->add($entry_description);
                }

                // Save the entry's primary image if one exists.
                if (!empty($entry->meta->image)) {
                    $primary_image = $entry->meta->image;

                    $entry_image                = new MapFeedEntryImage();
                    $entry_image->feed_entry_id = $entry->id;
                    $entry_image->url           = $primary_image->url;
                    $entry_image->width         = $primary_image->width;
                    $entry_image->height        = $primary_image->height;
                    $entry_image->primary       = 1;

                    $map_feed_images->add($entry_image);
                }

                // Save the entry's categories if any exist.
                if (!empty($entry->meta->categories)) {
                    foreach ($entry->meta->categories as $category) {
                        $entry_category                = new MapFeedEntryCategory();
                        $entry_category->feed_entry_id = $entry->id;
                        $entry_category->category      = $category;

                        $map_feed_categories->add($entry_category);
                    }
                }
            }

            $completed_feeds[] = $status_feed->id;

            /*
             * Track information for figuring out the volume of
             * the feeds
             */
            $track_volume                    = new TrackFeedVolume();
            $track_volume->feed_id           = $status_feed->id;
            $track_volume->new_entries_count = $new_entries_count;

            /*
             * Calculate number of hours it has been since the feed had new
             * entries
             */
            $track_volume->hoursSinceLastRun();

            if (!is_null($track_volume->hours_since_last_run)) {
                $track_volume->average_entries_per_hour =
                    ($track_volume->new_entries_count
                        / $track_volume->hours_since_last_run);
            }

            $track_volume->timestamp = time();

            $track_volumes->add($track_volume);

        } else {
            CLI::alert(Log::notice($status_feed->url . " has no new entries"));
            $completed_feeds[] = $status_feed->id;
        }

    }

    $process_entries_stop = microtime(true);
    $process_entries_range = $process_entries_stop - $process_entries_start;
    Log::info("The processing of entries took {$process_entries_range}");

    Log::info("Saving {$map_feed_categories->count()} entry categories.");
    try {
        $map_feed_categories->saveModelsToDB();
    }
    catch (CollectionException $e) {
        CLI::alert(Log::notice('No entry categories to save.'));
    }

    Log::info("Saving {$map_feed_descriptions->count()} entry descriptions.");
    try {
        $map_feed_descriptions->saveModelsToDB();
    }
    catch (CollectionException $e) {
        CLI::alert(Log::notice('No entry descriptions to save.'));
    }

    Log::info("Saving {$map_feed_images->count()} entry images.");
    try {
        $map_feed_images->saveModelsToDB();
    }
    catch (CollectionException $e) {
        CLI::alert(Log::notice('No entry images to save.'));
    }

    Log::debug("Saving {$track_volumes->count()} feed volumes.");
    try {
        $track_volumes->saveModelsToDB();
    }
    catch (CollectionException $e) {
        CLI::alert(Log::notice('No feed volumes to save.'));
    }

    if (empty($completed_feeds)) {
        Log::warning('No more feeds to run');
        $engine->complete();
    }

    /*
     * Update the last_pulled field in status_feeds table with current_time
     */
    $completed_feeds_implode = '"' . implode('","', $completed_feeds) . '"';

    $STH = $DBH->prepare("UPDATE status_feeds
                              SET last_pulled = :current_time
                              WHERE id in ($completed_feeds_implode)");
    $STH->execute(array(":current_time" => time()));

    Log::info('Completed');
    $engine->complete();

    Log::runtime();
    Log::memory();

}
catch (Exception $e) {
    Log::error($e);
    $engine->fail();

    CLI::stop();
}
