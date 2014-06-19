<?php

use Pinleague\Pinterest;
use Pinleague\PinterestException;

/**
 * Collection of Status repin tree
 *
 * @author Yesh
 */
class statusRepinsTree extends DBCollection
{

    public $table = 'status_repin_tree';


    public $columns = array('pin_id'
    , 'source_pin'
    , 'parent_pin'
    , 'origin_pin'
    , 'has_repin'
    , 'last_pulled_repins'
    , 'last_pulled_boards');

    public function fetchPinToPullBoards($number_of_pins){
        $DBH = DatabaseInstance::DBO();

        $current_day    = flat_date('day');
        $current_time   = time();

        $STH = $DBH->prepare("SELECT *
                            FROM status_repin_tree
                            WHERE has_repin > 0
                            AND last_pulled_boards < :current_day
                            LIMIT $number_of_pins");

        $STH->execute(array(':current_day' => $current_day));
        $pins_to_pull = $STH->fetchAll();

        $pin_ids_pulled = array();
        $pin_as_source  = array();

        foreach($pins_to_pull as $pin){

            if(!empty($pin->source_pin)){
                $pin_ids_pulled[] = $pin->pin_id;
            } else {
                $pin_as_source[] = $pin->pin_id;
            }

        }

        $pin_ids_pulled_implode = implode(",", $pin_ids_pulled);

        if (!empty($pin_ids_pulled)){
            $STH = $DBH->prepare("UPDATE status_repin_tree
                                  SET last_pulled_boards = :current_time
                                  WHERE pin_id in ($pin_ids_pulled_implode)");
            $STH->execute(array(':current_time' => $current_time));
        }

        return array("pins_to_pull"   => $pins_to_pull,
                     "pins_as_source" => $pin_as_source);
    }

}

class statusRepinsTreeException extends DBModelException {}
