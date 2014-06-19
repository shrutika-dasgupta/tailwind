<?php /**
 * Collection of DataTrafficDailyCount model
 */
class DataTrafficDailyCounts extends DBCollection
{
    const MODEL = 'DataTrafficDailyCount';
    const TABLE = 'data_traffic_daily_counts';

    public $table = 'data_traffic_daily_counts';

    public $columns =
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
        ];
    public $primary_keys = ['traffic_id','date','device','network','country','region',];
}

class DataTrafficDailyCountsException extends CollectionException {}
