<?php

use Pinleague\Pinterest;

/**
 * Map repins boards pins model
 *
 * @author Yesh
 *
 */
class mapRepinBoardPin extends PDODatabaseModel
{
    public $table = 'map_repins_boards_pins';

    public $board_id
    , $parent_pin
    , $origin_pin
    , $flag
    , $added_at;

    public $columns = array('board_id'
    , 'parent_pin'
    , 'origin_pin'
    , 'flag'
    , 'added_at');


    public function __construct(){
        parent::__construct();
        $this->added_at = time();
    }

}

class mapRepinBoardPinException extends DBModelException {}
