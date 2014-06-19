<?php namespace Caches;

/**
 * Class DomainInfluencers
 *
 * @package Caches
 */
class DomainInfluencers extends Caches
{
    public $columns = array(
        'domain',
        'period',
        'influencer_user_id',
        'username',
        'domain_mentions',
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

    public $table = 'cache_domain_influencers';

    public $primary_keys = array(
        'domain',
        'period',
        'influencer_user_id'
    );
}