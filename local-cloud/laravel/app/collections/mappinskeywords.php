<?php

use Pinleague\Pinterest;
use Pinleague\PinterestException;

/**
 * Class Map Pins Keywords
 * Collection of Map Pin Keyword model
 *
 * @author Yesh
 */
class MapPinsKeywords extends DBCollection
{
    public $table = 'map_pins_keywords';

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

    public $primary_keys = array('pin_id','keyword');

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