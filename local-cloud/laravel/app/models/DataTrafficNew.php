<?php class DataTrafficNew extends PDODatabaseModel
{
public
    $table = 'data_traffic_new',
    $columns =
        [
            'traffic_id',
            'hour',
            'device',
            'network',
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
    $primary_keys = ['traffic_id',
                         'hour',
                         'device',
                         'network'];

   public
        $traffic_id,
        $hour,
        $device,
        $network,
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

class DataTrafficNewException extends DBModelException {}
