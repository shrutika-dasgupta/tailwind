<?php namespace Caches;

/**
 * CacheKeywordsWordClouds is a collection of CacheKeywordWordCloud model
 *
 * @author  Yesh
 */
class KeywordsWordClouds extends Caches
{
    /**
     * Meta data about this collection's model
     *
     * @var $model_name   string
     * @var $table        string
     * @var $columns      array
     * @var $primary_keys array
     */
    public $keyword
    ,   $date
    ,   $word
    ,   $word_count
    ,   $timestamp;

    public
        $model_name = 'CacheKeywordWordCloud',
        $table = 'cache_keyword_wordclouds',
        $columns =
        array(
            'keyword',
            'date',
            'word',
            'word_count',
            'timestamp'
        ),
        $primary_keys = array('keyword', 'date', 'word');

}

class CacheKeywordsWordCloudsException extends \DBCollectionException {}
