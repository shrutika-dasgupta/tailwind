<?php

/*
 * Processes feed entries.
 *
 * The script excepts args from the CLI
 *
 * argv[1] : Which type of social networks you want to pull. Choices: "fb" or
 *                                                                    "twitter"
 * argv[2] : Limit of the number of feeds you want to fetch
 *
 * argv[3] : The server number you are running it on. Current:
 *           0 = newapp
 *           1 = feeds
 *           2 = calcs
 *
 * @author Daniel
 * @author Yesh
 */

chdir(__DIR__);
include '../../../bootstrap/bootstrap.php';

use Content\DataFeedEntry,
    Content\DataFeedEntryHistory,
    Content\DataFeedEntries,
    Content\DataFeedEntryHistories,
    Pinleague\CLI,
    Pinleague\SocialWorth;

$type   = $argv[1];
$limit  = $argv[2];
$server = $argv[3];

Log::setLog(false, 'CLI', basename(__FILE__, ".php") . "-" . $type . "-" . $server);

$engine = new Engine(__FILE__ . "-" . $type . "-" . $server);

if (is_null($type)
 || is_null($limit)
 || is_null($server)) {
    Log::error("Please set the CLI args. Read comments.");
    $engine->fail();
    CLI::stop();
  }

try {
    CLI::h1('Starting Program');

    if ($engine->running()) {
        Log::warning('Engine Already Running');
        CLI::sleep(10);
        CLI::stop();
    }

    $engine->start();
    Log::info('Feed Entries Engine Started');

    if ($type == "twitter") {
        $social = new SocialWorth(array('pinterest', 'twitter'));
    } else if ($type == "fb"){
        $social = new SocialWorth(array('facebook', 'googleplus'));
    } else {
        Log::error("Please provide a valid type");
        $engine->fail();
        CLI::stop();
    }

    Log::info('Fetching Feed Entries');
    $data = DataFeedEntries::fetch($type, $limit, $server);

    if (empty($data)) {
        CLI::notice('No more feed entries to fetch');
        $engine->complete();
        CLI::stop();
    }

    $entries   = new DataFeedEntries();
    $histories = new DataFeedEntryHistories();

    $entry_urls   = [];
    $entries_data = [];
    $entry_ids    = [];

    Log::info('Processing Feed Entries');
    foreach ($data as $entry) {
        $entry_urls[$entry->id] = $entry->url;

        $entries_data[$entry->id] = $entry;

        $entry_ids[] = $entry->id;
    }

    /*
     * Passing in multiple URLS to value function
     *
     * We get back an array that looks as follows:
     *    $all_socialworth = array(
     *                              "url 1": array (
     *                                          "facebook"  : 0,
     *                                          "twitter"   : 3,
     *                                          "pinterest" : 1
     *                                        ),
     *                              "url 2" : [...]
     *                               ...
     *                          )
     */
    $all_socialworth = $social->value($entry_urls);

    foreach ($all_socialworth as $worth) {
        $entry = $entries_data[$worth["id"]];

        if (empty($worth)) {
            Log::warning("Feed Entry id " . $entry->id . " has empty worth");
            continue;
        }

        if ($type == "twitter") {

            // Skip unchanged scores.
            if ($entry->pinterest_score == array_get($worth, 'pinterest')
                && $entry->twitter_score == array_get($worth, 'twitter')
            ) {
                Log::debug('Tracking history for Feed Entry ID ' . $entry->id);
                $history                = new DataFeedEntryHistory();
                $history->feed_entry_id = $entry->id;
                $history->loadDBData($entry);
                $histories->add($history);
                continue;
            }

            Log::info('Updating Feed Entry ID ' . $entry->id);
            $entry->pinterest_score  = array_get($worth, 'pinterest');
            $entry->twitter_score    = array_get($worth, 'twitter');

        } else {

            // Skip unchanged scores.
            if ($entry->facebook_score == array_get($worth, 'facebook')
                && $entry->googleplus_score == array_get($worth, 'googleplus')
            ) {
                Log::debug('Tracking history for Feed Entry ID ' . $entry->id);
                $history                = new DataFeedEntryHistory();
                $history->feed_entry_id = $entry->id;
                $history->loadDBData($entry);
                $histories->add($history);
                continue;
            }

            Log::info('Updating Feed Entry ID ' . $entry->id);
            $entry->facebook_score   = array_get($worth, 'facebook');
            $entry->googleplus_score = array_get($worth, 'googleplus');

        }

        // Update the social score with the new total.
        $entry->social_score = (int) $entry->facebook_score  + (int) $entry->googleplus_score +
                               (int) $entry->pinterest_score + (int) $entry->twitter_score;

        // Flag updated, curated entries to be reindexed for search.
        if ($entry->curated == 2) {
            $entry->reindex();
        }

        $entries->add($entry);

        Log::debug('Tracking history for Feed Entry ID ' . $entry->id);
        $history                = new DataFeedEntryHistory();
        $history->feed_entry_id = $entry->id;
        $history->loadDBData($entry);
        $histories->add($history);
    }

    if ($entries->isNotEmpty()) {
        Log::debug("Saving {$entries->count()} Updated Entries to the DB");
        $entries->insertUpdateDB();
    }

    if ($histories->isNotEmpty()) {
        Log::debug("Saving {$histories->count()} Entry Histories to the DB");
        $histories->saveModelsToDB();
    }

    /*
     * Update the updated_at time for entries
     */
    Log::info("Reset updated_at time");
    $entries_pulled_implode = '"' . implode('","', $entry_ids) . '"';

    $DBH = DatabaseInstance::DBO();

    $current_time = time();

    if ($type == "twitter") {
        $STH = $DBH->query("UPDATE data_feed_entries
                            SET last_pulled_twitter = $current_time
                            WHERE id IN ($entries_pulled_implode)");
    } else {
        $STH = $DBH->query("UPDATE data_feed_entries
                            SET last_pulled_fb = $current_time
                            WHERE id IN ($entries_pulled_implode)");
    }

    // Update the general updated_at field.
    $STH = $DBH->query("UPDATE data_feed_entries
                        SET updated_at = $current_time
                        WHERE id IN ($entries_pulled_implode)");

    CLI::write('Feed Entries Engine Completed');
    $engine->complete();

    CLI::write(Log::runtime() . 'total runtime');
    CLI::write(Log::memory() . ' peak memory usage');
}
catch (Exception $e) {
    Log::error($e);
    $engine->fail();

    CLI::stop();
}
