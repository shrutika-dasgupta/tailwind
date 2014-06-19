<?php

use Pinleague\Pinterest;
use Pinleague\CLI;

/**
 * Class CategoryFeedsMatches 
 * Collection of Category Feed Match
 *
 * @author Yesh
 */
class CategoryFeedsMatches extends DBCollection
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
     */

    public
        $table = 'status_category_feed_matches',
        $columns =
        array(
            'pin_id',
            'user_id',
            'domain',
            'via_pinner',
            'origin_pinner',
            'description',
            'match_type',
            'category_name',
            'completed_flag',
            'timestamp'
        ),
        $primary_keys = array('pin_id','category_name','match_type','timestamp');

    public
        $pin_id,
        $user_id,
        $domain,
        $via_pinner,
        $origin_pinner,
        $description,
        $match_type,
        $category_name,
        $completed_flag,
        $timestamp;

    /*
    |--------------------------------------------------------------------------
    | Instance Methods
    |--------------------------------------------------------------------------
    */
    public function insertUpdateDB($dont_update_these_columns = array())
    {
        array_push($dont_update_these_columns, 'user_id', 'email');

        $append = "ON DUPLICATE KEY UPDATE ";

        foreach ($this->columns as $column) {
            if (!in_array($column, $dont_update_these_columns)) {
                $append .= "$column = VALUES($column),";
            }
        }

        return parent::insertUpdateDB($dont_update_these_columns);
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
 */
class CategoryFeedsMatchesException extends CollectionException {}
