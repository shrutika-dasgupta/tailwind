<?php /**
 * Collection of DataTrafficNew model
 */
class DataTrafficNews extends DBCollection
{
    const MODEL = 'DataTrafficNew';
    const TABLE = 'data_traffic_new';

    public $table = 'data_traffic_new';

    public $columns =
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
    ];

    public $primary_keys =
    ['traffic_id',
     'hour',
     'device',
     'network'];
}

class DataTrafficNewsException extends CollectionException {}
