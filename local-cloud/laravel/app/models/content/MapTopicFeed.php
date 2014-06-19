<?php

namespace Content;

/*
 * Map topic feed model.
 *
 * @author Daniel
 */
class MapTopicFeed extends \PDODatabaseModel
{
    public $table = 'map_topics_feed';

    public $columns = array(
        'topic_id',
        'feed_id',
        'score',
        'added_at',
     );

    public $primary_keys = array('topic_id', 'feed_id');

    public $topic_id;
    public $feed_id;
    public $score;
    public $added_at;
}
