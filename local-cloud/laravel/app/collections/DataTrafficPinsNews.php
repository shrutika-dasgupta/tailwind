<?php

/**
 * Collection of DataTrafficPinsNew model
 */
class DataTrafficPinsNews extends DBCollection
{
    const MODEL = 'DataTrafficPinsNew';
    const TABLE = 'data_traffic_pins_new';

    public $table = 'data_traffic_pins_new';

    public $columns =
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
        ];
    public $primary_keys = ['traffic_id','pin_id', 'hour','device',];
}

class DataTrafficPinsNewsException extends CollectionException {}
