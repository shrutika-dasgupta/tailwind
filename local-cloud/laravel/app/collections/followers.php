<?php

use Pinleague\Pinterest;
use Pinleague\PinterestException;

/**
 * Collection of Followers
 *
 * @author Yesh
 */
class Followers extends DBCollection
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    public
        $table = 'data_followers',
        $columns =
        array(
            'user_id',
            'follower_user_id',
            'follower_pin_count',
            'follower_followers',
            'follower_created_at',
            'follower_facebook',
            'follower_twitter',
            'follower_domain',
            'follower_domain_verified',
            'follower_location',
            'timestamp'
        ),
        $primary_keys = array();


    public function insertUpdateDB($dont_update_these_columns = array())
    {
        array_push($dont_update_these_columns,'timestamp');

        return parent::insertUpdateDB($dont_update_these_columns);
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/
class FollowersException extends CollectionException {}
