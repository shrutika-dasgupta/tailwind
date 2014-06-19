<?php

/**
 * Create a model for the table map_pin_keyword
 *
 * @author Yesh
 */
class MapPinKeyword extends PDODatabaseModel
{
    public $pin_id
    ,   $keyword
    ,   $pinner_id
    ,   $domain
    ,   $repin_count
    ,   $like_count
    ,   $comment_count
    ,   $created_at
    ,   $timestamp;

    public $columns = array(
        'pin_id',
        'keyword',
        'pinner_id',
        'domain',
        'repin_count',
        'like_count',
        'comment_count',
        'created_at',
        'timestamp'
    );

    public $table = 'map_pins_keywords';

    public $primary_keys = array('pin_id','keyword');


    /** Load data from a Pin Model
     * @author Yesh
     *
     * @return MapPinKeyword Model
     */
    public function load(Pin $data, $keyword){
        $this->pin_id = $data->pin_id;
        $this->keyword = $keyword;
        $this->pinner_id = $data->user_id;
        $this->domain = $data->domain;
        $this->repin_count = $data->repin_count;
        $this->like_count = $data->like_count;
        $this->comment_count = $data->comment_count;
        $this->created_at = $data->created_at;
        $this->timestamp = time();

        return $this;
    }
}
