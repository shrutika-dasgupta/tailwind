<?php

use Pinleague\Pinterest;
use Pinleague\PinterestException;

/**
 * Collection Map repins boards pins model
 *
 * @author Yesh
 */
class mapRepinsBoardsPins extends DBCollection
{

    public $table = 'map_repins_boards_pins';

    public $columns = array('board_id'
    , 'parent_pin'
    , 'origin_pin'
    , 'flag'
    , 'added_at');

    public function fetchBoardsQueue($number_of_boards){

        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->query("SELECT *
                            FROM map_repins_boards_pins
                            WHERE flag = 0
                            ORDER BY added_at
                            LIMIT $number_of_boards");
        $board_data = $STH->fetchAll();

        return $board_data;
    }
}

class mapRepinsBoardsPinsException extends DBModelException {}
