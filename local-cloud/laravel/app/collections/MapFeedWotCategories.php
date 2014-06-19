<?php

/*
 * Collection of Map Feed WOT category
 *
 * @author Yesh
 * @author Daniel
 */
class MapFeedWotCategories extends \DBCollection
{
    const MODEL = 'MapFeedWotCategory';
    const TABLE = 'map_feed_wot_categories';

    public $table = 'map_feed_wot_categories';

    public $columns = array(
        'feed_id',
        'curated',
        'category_identifier',
        'reliability_score',
        'added_at'
    );
    public $primary_keys = ['feed_id', 'category_identifier'];
}
