<?php

use Pinleague\Pinterest;

/**
 * Collection of PinAttributions
 *
 * @author  Yesh
 * @author  Will
 */
class PinAttributions extends DBCollection
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */

    /*
    * Table meta data
    */
    public
        $table = 'map_pins_attribution',
        $columns =
        array(
            'pin_id',
            'provider_name',
            'author_name',
            'author_url',
            'title',
            'url',
            'timestamp'
        ),
        $primary_keys = array('pin_id');

    /*
    |--------------------------------------------------------------------------
    | Instance Methods
    |--------------------------------------------------------------------------
    */
    /**
     * @author  Will
     * @author  Yesh
     *
     * @param array $ignore_these_columns
     *
     * @return $this
     */
    public function insertUpdateDB($ignore_these_columns = array())
    {
        /*
         * Don't update the timestamp (not sure why, Yesh probably knows)
         */
        array_push($ignore_these_columns,'timestamp');

        return parent::insertUpdateDB($ignore_these_columns);
    }
}
