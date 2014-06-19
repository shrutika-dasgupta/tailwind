<?php

use DatabaseInstance;

/*
 * Models for Map feed WOT category table
 *
 * @author Yesh
 */
class MapFeedWotCategory extends \PDODatabaseModel
{
    public $table = 'map_feed_wot_categories';

    public $columns = array(
        'feed_id',
        'curated',
        'category_identifier',
        'reliability_score',
        'added_at'
    );
    public $primary_keys = ['feed_id', 'category_identifier'];

    public $feed_id;
    public $curated;
    public $category_identifier;
    public $reliability_score;
    public $added_at;

}
