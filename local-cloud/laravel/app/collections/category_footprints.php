<?php

/**
 * CategoryFootprints Collection
 *
 * @author  Yesh
 */

use Pinleague\Pinterest;
use Pinleague\CLI;

class CategoryFootprints extends DBCollection
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */

    public
        $user_id,
        $activity_indv_hash,
        $influence_indv_hash,
        $board_indv_count_hash,
        $activity_collab_hash,
        $influence_collab_hash,
        $board_collab_count_hash,
        $recency_hash,
        $footprint_hash,
        $timestamp;

    public $columns = array(
        'user_id',
        'activity_indv_hash',
        'influence_indv_hash',
        'board_indv_count_hash',
        'activity_collab_hash',
        'influence_collab_hash',
        'board_collab_count_hash',
        'recency_hash',
        'footprint_hash',
        'timestamp'
    ),
        $primary_keys = array('user_id');

    public $table = 'map_profiles_category_footprint';


    /**
     * @param $limit
     *
     * @return array List of all the user_ids to
     *               run
     *
     * @author Yesh
     */
    public function fetchUserIds($limit){


        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->prepare("SELECT user_id
                              FROM status_footprint
                              WHERE last_run = 0
                              AND track_type = :track_type
                              limit $limit");
        $STH->execute([":track_type" => "influencer"]);

        $user_ids = $STH->fetchAll();

        if (empty($user_ids)) {

            $STH = $DBH->prepare("SELECT user_id
                                  FROM status_footprint
                                  WHERE last_run = 0
                                  AND track_type = :track_type
                                  limit $limit");
            $STH->execute([":track_type" => "pinner"]);

            $user_ids = $STH->fetchAll();

        }

        return $user_ids;

    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/
class CategoryFootprintsException extends CollectionException {}
