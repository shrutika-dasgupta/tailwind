<?php

/**
 * Map Boards Keywords model.
 *
 * This model represents boards we find by using Pinterest's board search end point.
 *
 * This table also serves as a queue for a script to pull pins from these boards in order to find
 * keyword matches.
 * 
 * @author Alex
 */
class MapBoardsKeyword extends PDODatabaseModel
{
    public $table = 'map_boards_keywords';

    public $columns = array(
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
    );

    public $primary_keys = array('board_id', 'keyword');

    public $keyword;
    public $board_id;
    public $pin_count;
    public $follower_count;
    public $category;
    public $first_found_at;
    public $times_found;
    public $last_pulled_pins;
    public $pin_matches_found;
    public $timestamp;

    /**
     * Initializes the class.
     *
     * @author Alex
     *
     * @return \MapBoardKeyword
     */
    public function __construct()
    {
        $this->timestamp        = time();
        $this->last_pulled_pins = 0;
        $this->first_found_at   = time();

        parent::__construct();
    }

    /** Load data from a Board Model
     *
     * @author   Alex
     *
     * @param Board $data
     * @param       $keyword
     *
     * @internal param $search_position
     *
     * @return MapBoardsKeyword Model
     */
    public function load(Board $data, $keyword){
        $this->keyword = $keyword;
        $this->board_id = $data->board_id;
        $this->pin_count = $data->pin_count;
        $this->follower_count = $data->follower_count;
        $this->category = $data->category;
        $this->last_pulled_pins = 0;
        $this->first_found_at = time();
        $this->times_found = 1;
        $this->timestamp = time();

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Instance Methods
    |--------------------------------------------------------------------------
    */

    /**
     * @author Alex
     *
     * @return $this
     *
     * this is a special database insert function
     *
     *
     */
    public function addToTable()
    {


        $STH = $this->DBH->prepare("
           insert into map_boards_keywords
            (
                keyword,
                board_id,
                pin_count,
                follower_count,
                category,
                last_pulled_pins,
                first_found_at,
                times_found,
                timestamp
            ) VALUES (
                  :keyword,
                  :board_id,
                  :pin_count,
                  :follower_count,
                  :category,
                  :last_pulled_pins,
                  :first_found_at,
                  :times_found,
                  timestamp
            )
            ON DUPLICATE KEY UPDATE
            timestamp = VALUES(timestamp),
            times_found = times_found + 1
        ");


        $params = array(
            ":keyword" => $this->keyword,
            ":board_id" => $this->board_id,
            ":pin_count" => $this->pin_count,
            ":follower_count" => $this->follower_count,
            ":category" => $this->category,
            ":last_pulled_pins" => $this->last_pulled_pins,
            ":first_found_at" => $this->timestamp,
            ":times_found" => $this->times_found,
            ":timestamp" => $this->timestamp
        );


        $STH->execute($params);

        return $this;

    }


}

class MapBoardsKeywordException extends DBModelException {}
