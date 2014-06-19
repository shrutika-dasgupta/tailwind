<?php

/**
 * This engine grabs related topics for a given topic and
 * also helps save new feeds to the status_feed and data_feed
 * table
 *
 * @author Daniel
 * @author Yesh
 */

ini_set('memory_limit', '2000M');

chdir(__DIR__);
include '../../../bootstrap/bootstrap.php';

use Content\DataFeed,
    Content\DataFeeds,
    Content\MapTopicFeed,
    Content\MapTopicsFeeds,
    Content\MapTopicRelation,
    Content\MapTopicRelations,
    Content\MapFeedDtag,
    Content\MapFeedDtags,
    Content\StatusTopic,
    Content\StatusTopics,
    Content\StatusFeed,
    Pinleague\CLI;

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

    Log::info('Fetching Topics');
    $topics = StatusTopics::fetch($limit = 20);

    if ($topics->isEmpty()) {
        Log::notice('No more topics to pull.');
        $engine->complete();
        CLI::stop();
    }

    $status_topics = $topics->getModels();

    $data_feeds          = new DataFeeds();
    $map_topics_feeds    = new MapTopicsFeeds();
    $map_topic_relations = new MapTopicRelations();
    $feed_dtags          = new MapFeedDtags();

    $topics_pulled = array();

    $api_results = $topics->loadFeeds();
    foreach ($api_results as $key => $result) {

        $status_topic = array_get($status_topics, $key);
        $topics_pulled[] = $status_topic->id;
        $topic_id    = $status_topic->id;

        CLI::h2("Topic ID {$status_topic->id}: Beginning Topic Processing");

        foreach ($result->feeds as $api_feed) {
            /*
             * Saving the feeds across StatusFeed
             * DataFeed and MapTopicFeed
             */
            $status_feed = new StatusFeed();
            $status_feed->loadDBData($api_feed);
            $status_feed->curated = $status_topic->curated;
            $status_feed->insertUpdateDB(["curated", "last_pulled"]);

            if (strlen($status_feed->url) > 512) {
                Log::warning("The url is really long for " . $status_feed->url);
                continue;
            }

            if(empty($status_feed->id)) {

                $inserted_feed = $status_feed::find_one(["url" => $status_feed->url]);
                $status_feed->id = $inserted_feed->id;
            }

            foreach ($api_feed->dtags as $dtag) {
                $feed_dtag               = new MapFeedDtag();
                $feed_dtag->feed_id      = $status_feed->id;
                $feed_dtag->related_dtag = $dtag;
                $feed_dtag->added_at     = time();

                $feed_dtags->add($feed_dtag);
            }

            /*
             * Add data_feed models to collection
             */

            $data_feed = new DataFeed();
            $data_feed->loadDBData($status_feed);
            $data_feed->feed_id     = $status_feed->id;
            $data_feed->description = $api_feed->description;
            $data_feed->title       = $api_feed->title;
            $data_feed->domain      = $api_feed->domain;

            $data_feeds->add($data_feed);

            /*
             * Add map_topic_feed models to collection
             */
            $map_topic_feed           = new MapTopicFeed();
            $map_topic_feed->topic_id = $status_topic->id;
            $map_topic_feed->feed_id  = $status_feed->id;
            $map_topic_feed->score    = $api_feed->score;
            $map_topic_feed->added_at = time();

            $map_topics_feeds->add($map_topic_feed);
        }

        Log::info('Parsing the topics for topic ' . $status_topic->topic);
        foreach ($result->topics as $api_topic) {
            $status_topic        = new StatusTopic();
            $status_topic->topic = $api_topic;
            $status_topic->curated = 0;
            $status_topic->saveToDB();

            $map_topic_relation                = new MapTopicRelation();
            $map_topic_relation->topic_id      = $topic_id;
            $map_topic_relation->related_topic = $status_topic->topic;

            $map_topic_relations->add($map_topic_relation);
        }
    }

    Log::info("Saving {$data_feeds->count()} data feeds.");
    try {
        $data_feeds->insertUpdateDB();
    }
    catch (CollectionException $e) {
        Log::notice('No data feeds to save');
    }

    Log::info("Saving {$map_topics_feeds->count()} topic feed mappings.");
    try {
        $map_topics_feeds->insertUpdateDB();
    }
    catch (CollectionException $e) {
        Log::notice('No map topic pins to save');
    }

    Log::info("Saving {$map_topic_relations->count()} topic relations.");
    try {
        $map_topic_relations->saveModelsToDB();
    }
    catch (CollectionException $e) {
        Log::notice('No topic relations to save');
    }

    Log::info("Saving {$feed_dtags->count()} delicious tags.");
    try {
        $feed_dtags->insertUpdateDB();
    }
    catch (CollectionException $e) {
        Log::notice('No map feed dtag to save');
    }

    /*
     * Update the last_pulled time for topics
     */
    $topics_pulled_implode = '"' . implode('","', $topics_pulled) . '"';

    $DBH = DatabaseInstance::DBO();

    $STH = $DBH->prepare("UPDATE status_topics
                          SET last_pulled = :current_time
                          WHERE id in ($topics_pulled_implode)");
    $STH->execute(array(":current_time" => time()));

    CLI::write('Completed');
    $engine->complete();

    CLI::write(Log::runtime() . 'total runtime');
    CLI::write(Log::memory() . ' peak memory usage');
} catch (Exception $e) {
    Log::error($e);
    
    $engine->fail();
    CLI::stop();
}
