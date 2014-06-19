<?php

use Pinleague\Pinterest;

/**
 * MapPinPromoters are a collection of connections of pins and who is promoting them
 *
 * @author  Will
 */
class MapPinPromoters extends DBCollection
{
    /**
     * Meta data about this collection's model
     *
     * @var $model_name   string
     * @var $table        string
     * @var $columns      array
     * @var $primary_keys array
     */
    public
        $model_name = 'MapPinPromoter',
        $table = 'map_pins_promoters',
        $columns =
        array(
            'pin_id',
            'promoter_id',
            'feed',
            'feed_attribute',
            'found_at'
        ),
        $primary_keys = array('pin_id', 'promoter_id');
}
