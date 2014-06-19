<?php

class CalcDomainHistory extends PDODatabaseModel
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

    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    public
        $domain,
        $date,
        $domain_mentions,
        $repin_count,
        $like_count,
        $comment_count,
        $unique_domain_pinners,
        $domain_reach,
        $domain_impressions,
        $timestamp;
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/

class CalcDomainHistoryException extends DBmodelException {}
