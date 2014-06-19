<?php

use Pinleague\Pinterest;
use Pinleague\CLI;

/**
 * Class Category Feeds Queues
 * Collection of Category Feed Queue
 *
 * @author Yesh
 */
class CategoryFeedstracks extends DBCollection
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
     */
    public
        $table = 'track_category_feeds',
        $columns =
        array(
            'category_name',
            'total_pins_count',
            'new_pins_count',
            'total_new_pins',
            'timestamp'
        ),
        $primary_keys = array();
    public
        $category_name,
        $total_pins_count,
        $new_pins_count,
        $total_new_pins,
        $timestamp;
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
 */
class CategoryFeedsTracksException extends CollectionException {}
