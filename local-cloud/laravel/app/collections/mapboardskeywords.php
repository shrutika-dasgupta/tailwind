<?php

/**
 * Collection of StatusKeywordBoardSearches
 *
 * @author Alex
 */
class MapBoardsKeywords extends DBCollection
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    public
        $table = 'map_boards_keywords',
        $columns =
        array(
            'board_id',
            'keyword',
            'pin_count',
            'follower_count',
            'category',
            'first_found_at',
            'times_found',
            'last_pulled_pins',
            'pin_matches_found',
            'timestamp'
        ),
        $primary_keys = array('board_id', 'keyword');


    /**
     * @author   Alex
     *
     * @param $number_of_boards
     *
     * @return $this
     *
     * grabs boards to pull pins from and look for keyword matches
     *
     */
    public function fetchBoardsQueue($number_of_boards){

        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->query(
                   "SELECT * FROM map_boards_keywords
                    WHERE last_pulled_pins = 0
                    ORDER BY timestamp asc
                    LIMIT $number_of_boards"
        );
        $board_data = $STH->fetchAll();
        return $board_data;
    }

    /**
     * @author Alex
     *
     * @param array $dont_update_these_columns
     * @param bool  $dont_log_error
     *
     * @return $this
     */
    public function insertUpdateDB($dont_update_these_columns = array(),$dont_log_error = false)
    {
        array_push($dont_update_these_columns,'last_pulled_pins','first_found_at');

        $append = "ON DUPLICATE KEY UPDATE ";

        foreach ($this->columns as $column) {
            if(!in_array($column,$dont_update_these_columns)) {
                $append .= "$column = VALUES($column),";
            }
        }

        $append .= "times_found = times_found + 1";

        return $this->saveModelsToDB('INSERT INTO',$append);
    }

}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/

class MapBoardsKeywordsException extends CollectionException {}
