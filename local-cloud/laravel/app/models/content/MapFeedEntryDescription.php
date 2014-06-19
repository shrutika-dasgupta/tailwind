<?php

namespace Content;

/*
 * Map feed entry description model.
 *
 * @author Daniel
 */
class MapFeedEntryDescription extends \PDODatabaseModel
{
    public $table = 'map_feed_entry_descriptions';

    public $columns = array(
        'feed_entry_id',
        'description',
        'added_at',
        'updated_at',
    );

    public $primary_keys = array('feed_entry_id');

    public $feed_entry_id;
    public $description;
    public $added_at;
    public $updated_at;

    /**
     * Initializes the class.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->added_at   = time();
        $this->updated_at = time();
    }
}
