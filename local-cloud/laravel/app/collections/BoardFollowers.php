<?php

use Pinleague\Pinterest;

/**
 * Collection of Followers
 *
 * @author Yesh
 */
class BoardFollowers extends DBCollection
{
    /**
     * @const Schema Data
     */
    const TABLE = 'data_board_followers';
    const MODEL = 'BoardFollower';

    public
        $table = 'data_board_followers',
        $columns =
        array(
            'user_id',
            'board_id',
            'follower_user_id',
            'follower_pin_count',
            'follower_follower_count',
            'follower_gender',
            'follower_created_at',
            'added_at',
            'updated_at'
        ),
        $primary_keys = array('user_id', 'board_id', 'follower_user_id');


    /**
     * @param array $dont_update_these_columns
     *
     * @return $this
     */
    public function insertUpdateDB($dont_update_these_columns = array())
    {
        array_push($dont_update_these_columns,'added_at');

        return parent::insertUpdateDB($dont_update_these_columns);
    }
}

/**
 * Class BoardFollowersException
 */
class BoardFollowersException extends CollectionException {}
