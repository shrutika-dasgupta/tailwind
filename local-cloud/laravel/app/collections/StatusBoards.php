<?php

use Pinleague\Pinterest;
use Pinleague\PinterestException;

/**
 * Collection of Status Boards
 *
 * @author Alex
 */
class StatusBoards extends DBCollection
{
    public $table = 'status_boards';

    public
        $board_id,
        $owner_user_id,
        $last_pulled_pins,
        $last_pulled_followers,
        $followers_found,
        $last_updated_followers_found,
        $last_calced,
        $track_type,
        $is_owned,
        $is_collaborator,
        $collaborator_count,
        $category,
        $layout,
        $created_at,
        $follower_count,
        $pin_count,
        $added_at,
        $updated_at,
        $timestamp;

    public $columns = array(
        'board_id',
        'owner_user_id',
        'last_pulled_pins',
        'last_pulled_followers',
        'followers_found',
        'last_updated_followers_found',
        'last_calced',
        'track_type',
        'is_owned',
        'is_collaborator',
        'collaborator_count',
        'category',
        'layout',
        'created_at',
        'follower_count',
        'pin_count',
        'added_at',
        'updated_at',
        'timestamp'
    );
    public $primary_keys = array('board_id');
}

class statusBoardsException extends DBModelException {}
