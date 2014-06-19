<?php

use Pinleague\Pinterest;
use Pinleague\CLI;

/**
 * Class Category Feeds Queues
 * Collection of Category Feed Queue
 *
 * @author Yesh
 */
class CategoryFeedsQueues extends DBCollection
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
     */
    public
        $table = 'status_category_feed_queue',

        $pin_id,
        $user_id,
        $board_id,
        $domain,
        $method,
        $is_repin,
        $parent_pin,
        $via_pinner,
        $origin_pin,
        $origin_pinner,
        $image_url,
        $image_square_url,
        $link,
        $description,
        $location,
        $dominant_color,
        $rich_product,
        $repin_count,
        $like_count,
        $comment_count,
        $created_at,
        $category_name,
        $match_type,
        $timestamp;

    /*
     * Table meta data
     */
    public
        $columns =
        array(
            'pin_id',
            'user_id',
            'board_id',
            'domain',
            'method',
            'is_repin',
            'parent_pin',
            'via_pinner',
            'origin_pin',
            'origin_pinner',
            'image_url',
            'image_square_url',
            'link',
            'description',
            'location',
            'dominant_color',
            'rich_product',
            'repin_count',
            'like_count',
            'comment_count',
            'created_at',
            'category_name',
            'match_type',
            'timestamp',

        ),
        $primary_keys = array('pin_id');

    /*
    |--------------------------------------------------------------------------
    | Static Methods
    |--------------------------------------------------------------------------
     */

    /* Fetch the pins from the track categories table
     *
     * @author Yesh
     *
     * @returns DBO StdClass
     */
    public static function fetch($category_name)
    {
        $DBH = DatabaseInstance::DBO();
        $DBH->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET sql_log_bin = 0");

        $STH = $DBH->prepare("SELECT timestamp
                              FROM status_category_feed_queue
                              WHERE category_name = :category_name
                              ORDER BY timestamp DESC
                              LIMIT 250, 1");

        $STH->execute(array(
                ':category_name' => $category_name));
        $least_time = $STH->fetchAll();

        if(empty($least_time)){
            throw new Exception('No more feeds to consume in ' . $category_name);
        }

        $least_timestamp = $least_time[0]->timestamp;


        CLI::write("Pulling pins to consume from " . $category_name);
        $STH = $DBH->prepare("SELECT *
            FROM status_category_feed_queue
            WHERE category_name = :category_name
            AND timestamp < :least_timestamp
            ORDER BY timestamp DESC");

        $STH->execute(array(
            ':category_name' => $category_name,
            ':least_timestamp' => $least_timestamp));
        $category_feed = $STH->fetchAll();

        $category_fetch = array('category_feed' => $category_feed,
                                'least_timestamp' => $least_timestamp);

        return $category_fetch;
    }

    /* Delete the pins from the track categories table
     *
     * @author Yesh
     *
     * @returns DBO StdClass
     */
    public static function deleteFromDB($category_name, $least_timestamp)
    {
        $DBH = DatabaseInstance::DBO();

        $STH = $DBH->prepare("DELETE FROM status_category_feed_queue
            WHERE category_name = :category_name
            AND timestamp < :least_timestamp
            ORDER BY timestamp DESC");

        $STH->execute(array(
            ':category_name' => $category_name,
            ':least_timestamp' => $least_timestamp));
    }


    /*
    |--------------------------------------------------------------------------
    | Instance Methods
    |--------------------------------------------------------------------------
     */
    public function insertUpdateDB($dont_update_these_columns = array())
    {
        array_push($dont_update_these_columns, 'user_id', 'email');

        $append = "ON DUPLICATE KEY UPDATE ";

        foreach ($this->columns as $column) {
            if (!in_array($column, $dont_update_these_columns)) {
                $append .= "$column = VALUES($column),";
            }
        }

        if (!in_array('track_type', $dont_update_these_columns)) {
            $append .=
                "track_type=IF(VALUES(track_type)='user',
                    'user',IF(VALUES(track_type)='competitor', 'competitor', track_type))";
        }

        return $this->saveModelsToDB('INSERT INTO'
                                    , $append
                                    , $dont_log_error = true);
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
 */
class CategoryFeedsQueuesException extends CollectionException {}
