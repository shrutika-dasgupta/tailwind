<?php class DataTrafficDailyCount extends PDODatabaseModel
{
public
    $table = 'data_traffic_daily_counts',
    $columns =
        [  'traffic_id',
            'date',
            'device',
            'network',
            'country',
            'region',
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
            'added_at',
            'timestamp',
        ],
    $primary_keys = ['traffic_id','date','device','network','country','region',];

   public
        $traffic_id,
        $date,
        $device,
        $network,
        $country,
        $region,
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
        $added_at,
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

class DataTrafficDailyCountException extends DBModelException {}
