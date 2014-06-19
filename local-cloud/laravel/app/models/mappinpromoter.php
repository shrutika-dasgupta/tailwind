<?php

/**
 * MapPinPromoter links a given Pin with who is promoting it
 * Only promoted pins are in the mapping table
 *
 * @author  Will
 */
class MapPinPromoter extends PDODatabaseModel
{
    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */

    /**
     * Feed contstants are the places where we find promoted pins.
     * To add a feed, create a constant prepended with FEED
     */
    const FEED_CATEGORY = 'category';
    const FEED_KEYWORD  = 'keyword';

    /*
    |--------------------------------------------------------------------------
    | Properties
    |--------------------------------------------------------------------------
    */
    /**
     * The source where promoted pin was found. Should be one of the constants in this model.
     *
     * @var $feed string
     */
    public $feed;

    /**
     * The identifier associated with where the pin was found.
     *
     * @var $feed_attribute string - keyword, category name
     */
    public $feed_attribute;

    /**
     * The time we found the pin
     *
     * @var $found_at int - epoch time
     */
    public $found_at;

    /**
     * The Pinterest pin id that has been identified as being promoted.
     *
     * @var $pin_id int
     */
    public $pin_id;

    /**
     * The Pinterest user_id of the user promoting the pin
     *
     * @var $promoter_id
     */
    public $promoter_id;

    /**
     * Meta data about this model
     *
     * @var $table
     * @var $columns
     * @var $primary_keys
     */
    public
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
