<?php

namespace Content;

/*
 * Map topic feeds collection.
 *
 * @author Daniel
 */
class MapTopicsFeeds extends \DBCollection
{
    const MODEL = 'Content\MapTopicFeed';
    const TABLE = 'map_topics_feeds';

    public $table = 'map_topics_feeds';

    public $columns = array(
        'topic_id',
        'feed_id',
        'score',
        'added_at',
    );

    public $primary_keys = array('topic_id', 'feed_id');
}
