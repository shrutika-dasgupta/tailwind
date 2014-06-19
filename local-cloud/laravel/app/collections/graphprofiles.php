<?php

use Pinleague\Pinterest;
use Pinleague\PinterestException;

/**
 * Class GraphProfiles
 * Collection of graphprofiles
 *
 * @author Will
 */
class GraphProfiles extends DBCollection
{
    public $table = 'status_pull_profiles_queue';
    public $columns = array('source'
    , 'user_id'
    , 'flag'
    , 'followers'
    , 'following_count'
    , 'created_at'
    , 'timestamp');

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

class GraphProfilesException extends CollectionException {}
