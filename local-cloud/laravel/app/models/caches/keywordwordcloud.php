<?php namespace Caches;

/**
 * Create a model for the table cache_keywords_wordclouds
 *
 * @author Yesh
 */
class KeywordWordCloud extends Cache
{
    public $keyword
    ,   $date
    ,   $word
    ,   $word_count
    ,   $timestamp;

    public $columns =
        array(
        'keyword',
        'date',
        'word',
        'word_count',
        'timestamp'
    );

    public $table = 'cache_keyword_wordclouds';

    public $primary_keys = array('keyword', 'date', 'word');

    public function __construct()
    {
        parent::__construct();
        $this->timestamp = time();
    }
}

class CacheKeywordWordCloudException extends \DBModelException {}
