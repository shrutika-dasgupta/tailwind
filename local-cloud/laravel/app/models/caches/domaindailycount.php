<?php

namespace Caches;

/**
 * Class DomainDailyCounts
 *
 * @package Caches
 */
class DomainDailyCount extends Cache
{
    public $columns = array(
        'domain',
        'date',
        'is_repin',
        'pin_count',
        'pinner_count',
        'repin_count',
        'like_count',
        'comment_count',
        'reach'
    );

    public
        $domain,
        $date,
        $is_repin,
        $pin_count,
        $pinner_count,
        $repin_count,
        $like_count,
        $comment_count,
        $reach;

    public $table = 'cache_domain_daily_counts';
    public $primary_keys = array('domain','date','is_repin');
}