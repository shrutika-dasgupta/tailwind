<?php

namespace Content;

/*
 * Collection of Map Feed Categories
 *
 * @author Yesh
 * @author Daniel
 */
class MapFeedEntryCategories extends \DBCollection
{
    const MODEL = 'Content\MapFeedEntryCategory';
    const TABLE = 'map_feed_entry_categories';

    public $table = 'map_feed_entry_categories';

    public $columns = array(
        'feed_entry_id',
        'category',
        'added_at',
    );

    public $primary_keys = array('feed_entry_id', 'category');
}
