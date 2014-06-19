<?php namespace Caches;

/**
 * CacheDomainsWordClouds is a collection of CacheDomainWordCloud model
 *
 * @author  Alex
 */
class DomainsWordClouds extends Caches
{
    /**
     * Meta data about this collection's model
     *
     * @var $model_name   string
     * @var $table        string
     * @var $columns      array
     * @var $primary_keys array
     */
    public $domain
    ,   $date
    ,   $word
    ,   $word_count
    ,   $timestamp;

    public
        $model_name = 'CacheDomainWordCloud',
        $table = 'cache_domain_wordclouds',
        $columns =
        array(
            'domain',
            'date',
            'word',
            'word_count',
            'timestamp'
        ),
        $primary_keys = array('domain', 'date', 'word');

    /**
     * @author Alex
     *
     * @return $this
     *
     * This method is specifically created for accumulating word_counts on hashtag matches
     * for domain pins throughout the day.
     */
    public function insertAddDB()
    {
        $append = "ON DUPLICATE KEY UPDATE ";
        $append .= "timestamp = VALUES(timestamp), ";
        $append .= "word_count = word_count + VALUES(word_count)";

        return $this->saveModelsToDB('INSERT INTO', $append);
    }

}

class CacheDomainsWordCloudsException extends \DBCollectionException {}

