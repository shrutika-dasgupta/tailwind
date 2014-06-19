<?php

namespace Content;

use DatabaseInstance;

/*
 * Map feed images collection.
 *
 * @author Yesh
 * @author Daniel
 */
class MapFeedEntryImages extends \DBCollection
{
    const MODEL = 'Content\MapFeedEntryImage';
    const TABLE = 'map_feed_entry_images';

    public $table = 'map_feed_entry_images';

    public $columns = array(
        'feed_entry_id',
        'url',
        'width',
        'height',
        'primary',
        'added_at',
        'updated_at',
    );

    public $primary_keys = array('feed_entry_id', 'url');


    /**
     * @param int $limit
     *
     * @return mixed
     */
    public static function fetch($limit = 50)
    {
        $DBH = DatabaseInstance::DBO();

        $query = $DBH->query("SELECT *
                              FROM map_feed_entry_images
                              WHERE updated_at = 0
                              AND width = 0
                              OR height = 0
                              LIMIT $limit");

        $results = $query->fetchAll();

        return self::createFromDBData($results);
    }
}
