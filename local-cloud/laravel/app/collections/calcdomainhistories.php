<?php

class CalcDomainHistories extends DBCollection
{
    /*
    |--------------------------------------------------------------------------
    | Table Meta Data
    |--------------------------------------------------------------------------
    */
    public $columns = array(
        'domain',
        'date',
        'domain_mentions',
        'repin_count',
        'like_count',
        'comment_count',
        'unique_domain_pinners',
        'domain_reach',
        'domain_impressions',
        'timestamp'
    ), $table = 'calcs_domain_history';
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/

class CalcDomainHistoriesException extends CollectionException {}
