<?php

namespace Content;

/*
 * Collection of MapFeedEntryKeyword model.
 *
 * @author Yesh
 */
class MapFeedEntryKeywords extends \DBCollection
{
    const MODEL = 'Content\MapFeedEntryKeyword';
    const TABLE = 'map_feed_entry_keywords';

    public $table = 'map_feed_entry_keywords';

    public $columns = array(
        'entry_id',
        'keyword',
        'added_at',
    );

    public $primary_keys = array('entry_id', 'keyword');
}
