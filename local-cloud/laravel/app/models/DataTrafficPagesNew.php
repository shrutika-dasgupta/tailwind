<?php

/**
 * Class DataTrafficPagesNew
 */
class DataTrafficPagesNew extends PDODatabaseModel

{
public
    $table = 'data_traffic_pages_new',
    $columns =
        [ 'traffic_id',
           'date',
           'page',
           'device',
           'network',
           'full_referrer',
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
    $primary_keys = ['traffic_id','date','page'];

   public
        $traffic_id,
        $date,
        $page,
        $device,
        $network,
        $full_referrer,
        $users,
        $new_user,
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
     * @return \DataTrafficPagesNew
     */
    public function __construct()
    {
        $this->timestamp   = time();

        parent::__construct();
    }
}

class DataTrafficPagesNewException extends DBModelException {}