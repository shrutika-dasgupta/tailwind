<?php

namespace Content;

/*
 * Models for Map Feed Dtags
 *
 * @author Yesh
 */
class MapFeedDtag extends \PDODatabaseModel
{
    public $table = 'map_feed_dtags';

    public $columns = array(
        'feed_id',
        'related_dtag',
        'added_at',
    );

    public $primary_keys = array('feed_id', 'related_dtag');

    public $feed_id;
    public $related_dtag;
    public $added_at;
}
