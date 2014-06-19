<?php

use Pinleague\Pinterest;

/**
 * Status repin tree model
 *
 * @author Yesh
 *
 */
class statusRepinTree extends PDODatabaseModel
{
    public $table = 'status_repin_tree';

    public $pin_id
    ,$source_pin
    , $parent_pin
    , $origin_pin
    , $has_repin
    , $last_pulled_repins
    , $last_pulled_boards;

    public $columns = array('pin_id'
                            , 'source_pin'
                            , 'parent_pin'
                            , 'origin_pin'
                            , 'has_repin'
                            , 'last_pulled_repins'
                            , 'last_pulled_boards');


    /** Load API data into repin tree object
     * @param $pin_data
     *
     * @return $this
     */
    public function loadAPIData($pin_data){
        $this->pin_id             = $pin_data->id;
        $this->source_pin         = $pin_data->id;
        $this->parent_pin         = $pin_data->parent_pin->id;
        $this->origin_pin         = $pin_data->origin_pin->id;
        $this->has_repin          = $pin_data->repin_count;
        $this->last_pulled_repins = 0;
        $this->last_pulled_boards = 0;

        return $this;
    }

}

class statusRepinTreeException extends DBModelException {}
