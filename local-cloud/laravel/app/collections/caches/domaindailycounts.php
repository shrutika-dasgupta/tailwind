<?php namespace Caches;

use DatabaseInstance;

/**
 * Class DomainDailyCounts
 *
 * @package Caches
 */
class DomainDailyCounts extends Caches
{

    /**
     * @const Schema Data
     */
    const TABLE = 'cache_domain_daily_counts';
    const MODEL = 'Caches\DomainDailyCount';

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

    public $table = 'cache_domain_daily_counts';
    public $primary_keys = array('domain','date','is_repin');

    /**
     * Creates a sum of the column during a specific
     *
     * @param $domain
     * @param $column
     * @param $since
     * @param $until
     */
    public static function sumDuring(
        $domain,
        $column,
        $since,
        $until
    )
    {
        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->prepare("
                 select sum($column) as sum
                 from cache_domain_daily_counts
                 where
                 `date` >= :since
                 AND `date` < :until
                 AND `domain` = :domain
                ");

        $STH->execute(
            array(':since'  => $since,
                  ':until'  => $until,
                  ':domain' => $domain
            ));

        $result = $STH->fetch();

        return $result->sum;
    }
}
