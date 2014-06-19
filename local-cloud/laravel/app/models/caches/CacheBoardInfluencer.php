<?php

namespace Caches;

/**
 * CacheBoardInfluencer Model
 *
 * @package Caches
 *
 * @author  Alex
 */
class CacheBoardInfluencer extends Cache {

    public $table = 'cache_board_influencers';
    /*
     * DB Attributes
     */
    public $columns =
        [
            'board_id',
            'user_id',
            'influencer_user_id',
            'influencer_username',
            'first_name',
            'last_name',
            'follower_count',
            'following_count',
            'image',
            'website',
            'facebook',
            'twitter',
            'location',
            'board_count',
            'pin_count',
            'like_count',
            'created_at',
            'timestamp'
        ],
        $primary_keys = ['board_id','user_id','influencer_user_id'];

    public
        $board_id,
        $user_id,
        $influencer_user_id,
        $influencer_username,
        $first_name,
        $last_name,
        $follower_count,
        $following_count,
        $image,
        $website,
        $facebook,
        $twitter,
        $location,
        $board_count,
        $pin_count,
        $like_count,
        $created_at,
        $timestamp;

    public function __construct()
    {
        $this->timestamp = time();
        parent::__construct();
    }
}
