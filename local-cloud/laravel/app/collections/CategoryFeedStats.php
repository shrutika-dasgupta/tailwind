<?php

use Pinleague\Pinterest;
use Pinleague\CLI;

/**
 * Class Category Feeds Queues
 * Collection of Category Feed Queue
 *
 * @author Yesh
 */
class CategoryFeedStats extends DBCollection
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

    /**
     * Insert Update
     * Uses and insert update to save models to database
     *
     * @author  Alex
     *
     * @param array $ignore_these_columns
     * @param bool  $dont_log_error
     *
     * @returns $this
     */
    public function insertUpdateDB($dont_log_error = false)
    {
        $append = "
                ON DUPLICATE KEY UPDATE
                pin_count = pin_count + VALUES(pin_count)
                , repin_count = repin_count + VALUES(repin_count)
                , like_count = like_count + VALUES(like_count)
                , comment_Count = comment_count + VALUES(comment_count)";

        return $this->saveModelsToDB('INSERT INTO', $append, $dont_log_error);
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
 */
class CategoryFeedsStatsException extends CollectionException {}
