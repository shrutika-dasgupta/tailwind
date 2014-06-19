<?php namespace Caches;

/**
 * Class EngagementInfluencer
 *
 * @package Caches
 */
class EngagementInfluencer extends Cache
{
    public $columns = array(
        'user_id',
        'repinner_user_id',
        'date',
        'period',
        'overall_engagement',
        'username',
        'first_name',
        'last_name',
        'follower_count',
        'following_count',
        'image',
        'website_url',
        'facebook_url',
        'twitter_url',
        'location',
        'board_count',
        'pin_count',
        'like_count',
        'timestamp',
    );

    public
        $user_id,
        $repinner_user_id,
        $date,
        $period,
        $overall_engagement,
        $username,
        $first_name,
        $last_name,
        $follower_count,
        $following_count,
        $image,
        $website_url,
        $facebook_url,
        $twitter_url,
        $location,
        $board_count,
        $pin_count,
        $like_count,
        $timestamp;

    public $table = 'cache_engagement_influencers';
}