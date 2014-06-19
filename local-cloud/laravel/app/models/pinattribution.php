<?php

use Pinleague\Pinterest;

/**
 * Pin Attribution Model
 *
 * @author Yesh
 */
class PinAttribution extends PDODatabaseModel
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    public
        $pin_id,
        $provider_name,
        $author_name,
        $author_url,
        $title,
        $url,
        $timestamp;

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
     * @author  Yesh
     * @author  Will
     *
     * @param $data
     *
     * @return $this
     */
    public function loadAPIData($data) {

        if (is_object($data)){
            $data = (array)$data;
        }

        $this->provider_name = $data['provider_name'];
        $this->author_name   = $data['author_name'];
        $this->author_url    = $data['author_url'];
        $this->title         = $data['title'];
        $this->url           = $data['url'];

        return $this;
    }
}
