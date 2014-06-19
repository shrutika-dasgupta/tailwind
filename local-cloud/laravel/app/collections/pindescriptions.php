<?php

use Pinleague\Pinterest;
use Pinleague\PinterestException;

/**
 * Collection of Pins Description
 *
 * @author Yesh
 */
class PinDescriptions extends DBCollection
{
    /*
     * Table meta data
     */
    public
        $table = 'map_pins_descriptions',
        $columns =
        array(
            'pin_id',
            'description',
            'created_at'
        ),
        $primary_keys = array('pin_id');
}
