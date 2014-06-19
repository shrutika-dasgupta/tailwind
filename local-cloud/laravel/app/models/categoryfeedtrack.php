<?php

use Pinleague\Pinterest;

/**
 * Category Feed Track Model
 *
 * @author Yesh
 */
class CategoryFeedTrack extends PDODatabaseModel
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
            'new_recent_pins_count',
            'new_is_repin_count',
            'timestamp'
        ),
        $primary_keys = array();
    public
        $category_name,
        $total_pins_count,
        $new_pins_count,
        $new_recent_pins_count,
        $new_is_repin_count,
        $timestamp;

    public function __construct($category_name)
    {
        parent::__construct();
        $this->category_name= $category_name;
        $this->timestamp = time();
    }

    /** Insert data to database
     *
     * @author Yesh
     *         Alex
     */

    public function insertDB($data){
        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->prepare("INSERT INTO track_category_feeds
            (category_name
            , total_pins_count
            , new_pins_count
            , new_recent_pins_count
            , new_is_repin_count
            , timestamp)
            VALUES
            (:category_name
            , :total_pins_count
            , :new_pins_count
            , :new_recent_pins_count
            , :new_is_repin_count
            , :timestamp)");
        $STH->execute(array(":category_name"         => $data->category_name,
                            ":total_pins_count"      => $data->total_pins_count,
                            ":new_pins_count"        => $data->new_pins_count,
                            ":new_recent_pins_count" => $data->new_recent_pins_count,
                            ":new_is_repin_count"    => $data->new_is_repin_count,
                            ":timestamp"             => $data->timestamp));
    }

    /**
     * This function tries to grab the latest total pin count
     * from the table
     *
     * @return array
     */
    public function update($new_pins_count, $pins_from_api_count, $new_recent_pins_count, $new_is_repin_count, $category_name)
    {
        $DBH = DatabaseInstance::DBO();
        $category_feed_freq = new CategoryFeedTrack($category_name);
        $STH = $DBH->prepare("SELECT total_pins_count
                                   FROM track_category_feeds
                                   WHERE category_name = :category_name
                                   AND timestamp > :flat_time
                                   ORDER BY timestamp DESC
                                   LIMIT 1");
        $STH->execute(array(
                           ":flat_time" => flat_date('day'),
                           ":category_name"   => $category_name));

        $count_from_db = $STH->fetchAll();

        if (!isset($count_from_db[0]->total_pins_count)){
            $total_pins_count_db= 0;
        } else {
            $total_pins_count_db = $count_from_db[0]->total_pins_count;
        }

        $total_pins_count = $total_pins_count_db + $pins_from_api_count;

        $category_feed_freq->total_pins_count      = $total_pins_count;
        $category_feed_freq->new_pins_count        = $new_pins_count;
        $category_feed_freq->new_recent_pins_count = $new_recent_pins_count;
        $category_feed_freq->new_is_repin_count    = $new_is_repin_count;

        $category_feed_freq->insertDB($category_feed_freq);
    }
}

class CategoryFeedTrackException extends DBModelException {}
