<?php

namespace Content;

/*
 * Map feed image model.
 *
 * @author Yesh
 * @author Daniel
 */
class MapFeedEntryImage extends \PDODatabaseModel
{
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

    public $feed_entry_id;
    public $url;
    public $width;
    public $height;
    public $primary;
    public $added_at;
    public $updated_at;

    /**
     * Initializes the class.
     */
    public function __construct()
    {
        parent::__construct();

        $this->added_at   = time();
        $this->updated_at = time();
    }
}
