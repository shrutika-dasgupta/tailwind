<?php

/**
 * Collection of DataTrafficPagesNew model
 */
class DataTrafficPagesNews extends DBCollection
{
    const MODEL = 'DataTrafficPagesNew';
    const TABLE = 'data_traffic_pages_new';

    public $table = 'data_traffic_pages_new';

    public $columns =
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
        ];

    public $primary_keys = ['traffic_id','date','page'];
}

class DataTrafficPagesNewsException extends CollectionException {}
