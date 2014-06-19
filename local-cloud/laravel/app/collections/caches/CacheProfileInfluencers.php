<?php

namespace Caches;

use Pinleague\Pinterest;

/**
 * Collection of CacheProfileInfluencer model
 *
 * Models Top Followers by different metrics based on a given user_id.
 *
 * @author Alex
 */
class CacheProfileInfluencers extends Caches
{
    public $table = 'cache_profile_influencers';

    /*
     * DB Attributes
     */
    public $columns =
        [
            'user_id',
            'influencer_user_id',
            'influencer_username',
            'first_name',
            'last_name',
            'follower_count',
            'following_count',
            'boards_followed',
            'value',
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
        $primary_keys = ['user_id', 'influencer_user_id'];

    public
        $user_id,
        $influencer_user_id,
        $influencer_username,
        $first_name,
        $last_name,
        $follower_count,
        $following_count,
        $boards_followed,
        $value,
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
}