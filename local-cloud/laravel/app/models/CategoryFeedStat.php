<?php

use Pinleague\Pinterest;

/**
 * Category Feed Track Model
 *
 * @author Yesh
 */
class CategoryFeedStat extends PDODatabaseModel
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
     */

    public
        $table = 'track_category_feed_stats',
        $columns =
        array(
            'category',
            'hour',
            'recency',
            'domain',
            'pin_count',
            'repin_count',
            'like_count',
            'comment_count'
        ),
        $primary_keys = array('category','hour','recency','domain');
    public
        $category,
        $hour,
        $recency,
        $domain,
        $pin_count,
        $repin_count,
        $like_count,
        $comment_count;

    public function __construct($category)
    {
        parent::__construct();
        $this->category= $category;
        $this->hour = flat_date('hour',time());
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
 */
class CategoryFeedStatException extends DBModelException {}
