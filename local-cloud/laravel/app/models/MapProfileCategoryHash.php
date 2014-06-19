<?php
/**
 * MapProfileCategoryHash Model
 *
 * @author  Yesh
 */

use Pinleague\Pinterest;

class MapProfileCategoryHash extends PDODatabaseModel
{
    /*
     * DB Attributes
     */
public
    $user_id,
    $category,
    $footprint_count,
    $activity_count,
    $influence_count,
    $board_count,
    $recency_order;

public $columns = [
    'user_id',
    'category',
    'footprint_count',
    'activity_count',
    'influence_count',
    'board_count',
    'recency_order'
    ],
    $primary_key = ['user_id'];

    public $table = 'map_profiles_activity_footprint';

}

class MapProfileCategoryHashException extends DBModelException {}
