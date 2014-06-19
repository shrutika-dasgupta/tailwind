<?php

/**
 * Imports data for entries that match a set of topics and curated feeds.
 * 
 * @author Daniel
 */

use Content\DataFeedEntries;
use Content\StatusTopic;
use Content\StatusTopics;
use Pinleague\CLI;

ini_set('memory_limit', '1500M');

chdir(__DIR__);
include '../../../bootstrap/bootstrap.php';

$feeds_per_topic    = 50;
$entries_page_limit = 50;
$entries_per_page   = 2000;

$client = new \AlgoliaSearch\Client(
    Config::get('algolia.app_id'),
    Config::get('algolia.write_api_key')
);

$index = $client->initIndex('feed_entries');

$topics = StatusTopic::find(array(
    'type'    => 'pinterest',
    'curated' => 1,
));

$topics = StatusTopics::createFromDBData($topics);

$objects_total = 0;

foreach ($topics->sortBy('topic') as $i => $topic) {
    CLI::h1("Pulling Data For Topic \"{$topic->topic}\"");

    // Retrieve a bunch of entries.
    for ($page = 1; $page <= $entries_page_limit; $page++) { 
        $entries = DataFeedEntries::findByTopic(
            $topic->topic,
            $page,
            $entries_per_page,
            array('feed_num' => $feeds_per_topic)
        );

        // No more results - continue to the next topic.
        if ($entries->isEmpty()) {
            break;
        }

        $objects = array();
        foreach ($entries as $entry) {
            // Index the entry if it contains all required search index data.
            if ($data = $entry->getSearchIndexData()) {
                $objects[] = $data;
            }
        }

        $objects_count  = count($objects);
        $objects_total += $objects_count;

        CLI::write('Saving ' . number_format($objects_count) . ' Objects to Algolia');

        try {
            // Save objects to the Algolia Search Index.
            $index->saveObjects($objects);
        } catch (Exception $e) {
            dar($e);
            break;
        }
    }
}

CLI::seconds();

CLI::end('Saved ' . number_format($objects_total) . ' Objects to Algolia');