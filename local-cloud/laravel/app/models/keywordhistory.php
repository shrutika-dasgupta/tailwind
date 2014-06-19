<?php

use Pinleague\Pinterest;

/**
 * Keyword History Model for table calcs_keyword
 *
 * @author Yesh
 *
 */
class KeywordHistory extends PDODatabaseModel
{
    public $table = 'track_keywords_data';

    public $keyword
    , $total_pins_count
    , $new_pins_count
    , $timestamp;

    public $columns = array('keyword', 'total_pins_count',
                            'new_pins_count', 'timestamp');


    public function __construct($keyword)
    {
        $this->keyword = $keyword;
    }

    /**
     * This function tries to grab the latest total pin count
     * from the table
     *
     * @return array
     */
    public function totalCountInDB()
    {
        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->prepare("SELECT total_pins_count
                                   FROM track_keywords_data
                                   WHERE keyword = :keyword
                                   AND timestamp > :flat_time
                                   ORDER BY timestamp DESC
                                   LIMIT 1");
        $STH->execute(array(
                           ":flat_time" => flat_date('day'),
                           ":keyword"   => $this->keyword));

        $count = $STH->fetchAll();

        if (!isset($count[0]->total_pins_count)) {
            $total_count = 0;
        } else {
            $total_count = $count[0]->total_pins_count;
        }

        return $total_count;
    }

    /**
     *  This function computes the new pins from the
     *  API return
     *
     */
    public function newPinsFromAPI($pin_ids, $keyword)
    {
        $pin_ids_implode = implode(",", $pin_ids);

        $DBH = DatabaseInstance::DBO();

        $STH            = $DBH->prepare("SELECT COUNT(*) count
                              FROM map_pins_keywords
                              WHERE pin_id IN ($pin_ids_implode) AND keyword = :keyword");
        $STH->execute(array(":keyword" => $keyword));

        $count_from_api = $STH->fetchAll();
        $total_new_pins = count($pin_ids) - $count_from_api[0]->count;

        return $total_new_pins;
    }

    /**
     *  This function processes the data from
     *  the API to put it in a Model
     */
    public function update($response_data, $pin_ids, $keyword)
    {
        $total_pins_count = $this->totalCountInDB() + count($response_data->data);
        $new_pins_count   = $this->newPinsFromAPI($pin_ids, $keyword);

        $this->total_pins_count = $total_pins_count;
        $this->new_pins_count   = $new_pins_count;
        $this->timestamp        = time();

        return $this;
    }
}

class KeywordHistoryException extends DBModelException {}
