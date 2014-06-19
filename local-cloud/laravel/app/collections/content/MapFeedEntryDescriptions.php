<?php

namespace Content;

use DatabaseInstance;

/*
 * Collection for map feed entry descriptions.
 *
 * @author Daniel
 */
class MapFeedEntryDescriptions extends \DBCollection
{
    const MODEL = 'Content\MapFeedEntryDescription';
    const TABLE = 'map_feed_entry_descriptions';

    public $table = 'map_feed_entry_descriptions';

    public $columns = array(
        'feed_entry_id',
        'description',
        'added_at',
        'updated_at',
    );

    public $primary_keys = array('feed_entry_id');

    /**
     * @param int $limit
     *
     * @return mixed
     */
    public static function fetch($limit = 50)
    {
        $DBH = DatabaseInstance::DBO();

        $query = $DBH->query("SELECT feed_entry_id
                              FROM map_feed_entry_descriptions
                              WHERE updated_at = 0
                              LIMIT $limit");

        $results = $query->fetchAll();

        $feed_entry_ids = implode(', ', array_map(function($result) {
                     return $result->feed_entry_id;
                     }, $results)
                    );

        $STH = $DBH->query("SELECT *
                            FROM map_feed_entry_descriptions
                            WHERE feed_entry_id IN ($feed_entry_ids)");
        $entry_details = $STH->fetchAll();

        return self::createFromDBData($entry_details);
    }
}
