<?php

namespace Content;

/*
 * Models for map feed categories table
 *
 * @author Yesh
 * @author Daniel
 */
class MapFeedEntryCategory extends \PDODatabaseModel
{
    public $table = 'map_feed_entry_categories';

    public $columns = array(
        'feed_entry_id',
        'category',
        'added_at',
     );

    public $primary_keys = array('feed_entry_id', 'category');

    public $feed_entry_id;
    public $category;
    public $added_at;

    /**
     * Initializes the class.
     * 
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->added_at = time();
    }
}
