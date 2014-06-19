<?php

/**
 * Class DataTrafficPinsNew
 */
class DataTrafficPinsNew extends PDODatabaseModel
{

    /**
     * The category is NULL when no category is selected and when there is
     * an empty string it shows that the category needs to be updated
     *
     */

public
    $table = 'data_traffic_pins_new',
    $columns =
        [  'traffic_id',
            'pin_id',
            'user_id',
            'board_id',
            'category',
            'hour',
            'device',
            'users',
            'new_users',
            'sessions',
            'bounces',
            'time_on_site',
            'pageviews',
            'pageviews_per_session',
            'unique_pageviews',
            'transactions',
            'revenue',
            'timestamp',
        ],
    $primary_keys = ['traffic_id','pin_id','date', 'hour','device',];

   public
        $traffic_id,
        $pin_id,
        $user_id,
        $board_id,
        $category,
        $hour,
        $device,
        $users,
        $new_users,
        $sessions,
        $bounces,
        $time_on_site,
        $pageviews,
        $pageviews_per_session,
        $unique_pageviews,
        $transactions,
        $revenue,
        $timestamp;

    /**
     * Class initializer.
     *
     * @return \StatusTraffic
     */
    public function __construct()
    {
        $this->timestamp   = time();

        parent::__construct();
    }
}

class DataTrafficPinsNewException extends DBModelException {}
