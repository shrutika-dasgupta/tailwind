<?php

use Pinleague\Pinterest;
use Pinleague\PinterestException;

/**
 * Pin description Model
 *
 * @author  Yesh
 * @author  Will
 *
 */
class PinDescription extends PDODatabaseModel
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    public
        $pin_id,
        $description,
        $created_at;
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

    /*
    |--------------------------------------------------------------------------
    | Instance Methods
    |--------------------------------------------------------------------------
    */

    /**
     * @author   Yesh
     *
     * @param Pin $pin
     *
     * @return $this
     */
    public function loadPinData(Pin $pin)
    {
        $this->pin_id      = $pin->pin_id;
        $this->description = $pin->description;
        $this->created_at  = $pin->created_at;

        return $this;
    }
}