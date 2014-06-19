<?php namespace Caches;

/**
 * Create a model for the table cache_keywords_wordclouds
 *
 * @author Yesh
 */
class DomainWordCloud extends Cache
{
    public $domain
    ,   $date
    ,   $word
    ,   $word_count
    ,   $timestamp;

    public $columns =
        array(
        'domain',
        'date',
        'word',
        'word_count',
        'timestamp'
    );

    public $table = 'cache_domain_wordclouds';

    public $primary_keys = array('domain', 'date', 'word');

    public function __construct()
    {
        parent::__construct();
        $this->timestamp = time();
    }
}

class CacheDomainWordCloudException extends \DBModelException {}
