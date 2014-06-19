<?php

namespace Content;

/**
 * Map Topic Relation model
 *
 * @author Yesh
 */
class MapTopicRelation extends \PDODatabaseModel
{
    public $table = 'map_topic_relations';

    public $columns = array(
        'topic_id',
        'related_topic',
    );

    public $primary_keys = array('topic_id', 'related_topic');

    public $topic_id;
    public $related_topic;
}
