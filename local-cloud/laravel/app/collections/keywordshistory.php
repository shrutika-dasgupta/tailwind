<?php

use Pinleague\Pinterest;
use Pinleague\PinterestException;

/**
 * Collection of Keyword History Models
 *
 * @author Yesh
 */
class KeywordsHistory extends DBCollection
{
    public $table = 'track_keywords_data';

    public $columns = array('keyword', 'total_pins_count',
                            'new_pins_count', 'timestamp');


    public function insertUpdateDB($dont_update_these_columns = array())
    {
        $append = "ON DUPLICATE KEY UPDATE ";

        foreach ($this->columns as $column) {
            if (!in_array($column, $dont_update_these_columns)) {
                $append .= "$column = VALUES($column),";
            }
        }

        if (!in_array('track_type', $dont_update_these_columns)) {
            $append .=
                "track_type=IF(VALUES(track_type)='user',
                'user',IF(VALUES(track_type)='competitor', 'competitor', track_type))";
        }

        return parent::insertUpdateDB($dont_update_these_columns);
    }
}

class KeywordsHistoryException extends CollectionException {}
