<?php

namespace Content;

/*
 * Collection of Map Feed Dtags
 *
 * @author Yesh
 */
class MapFeedDtags extends \DBCollection
{
    const MODEL = 'Content\MapFeedDtag';
    const TABLE = 'map_feed_dtags';

    public $table = 'map_feed_dtags';
    
    public $columns = array(
        'feed_id',
        'related_dtag',
        'added_at',
    );

    public $primary_keys = array('feed_id', 'related_dtag');
}
