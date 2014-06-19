<?php namespace Caches;

/**
 * Class CacheBoardInfluencers
 *
 * @package Caches
 */
class CacheBoardInfluencers extends Caches
{
    public $columns = array(
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
    );

    public $table = 'cache_board_influencers';

    public $primary_keys = array(
        'board_id',
        'user_id',
        'influencer_user_id'
    );

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
}