<?php

namespace Content;

/*
 * MapFeedEntryKeyword model.
 *
 * @author Yesh
 */
class MapFeedEntryKeyword extends \PDODatabaseModel
{
    public $table = 'map_feed_entry_keywords';

    public $columns = array(
        'entry_id',
        'keyword',
        'added_at',
    );

    public $primary_keys = array('entry_id', 'keyword');

    public $entry_id;
    public $keyword;
    public $added_at;
}
