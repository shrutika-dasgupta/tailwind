<?php

namespace Content;

use DatabaseInstance;

/*
 * Collection of Status Feed
 *
 * @author Yesh
 * @author Daniel
 */
class MapTopicRelations extends \DBCollection
{
    const MODEL = 'Content\MapTopicRelation';
    const TABLE = 'map_topic_relations';

    public $table = 'map_topic_relations';

    public $columns = array(
        'topic_id',
        'related_topic',
    );

    public $primary_keys = array('topic_id', 'related_topic');
}
