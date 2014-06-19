<?php

namespace Caches;

use Pinleague\Pinterest;
use Pinleague\PinterestException;

/**
 * Collection of CacheProfileFollowerDistribution model
 *
 * Models how many people explicitly follow any given number of the user's boards.
 *
 * @author Alex
 */
class CacheProfileFollowerDistributions extends Caches
{

    public $table = 'cache_profile_follower_distribution';
    public $columns =
        [
            'user_id',
            'boards_followed',
            'followers'
        ],
        $primary_keys = ['user_id','boards_followed'];

    public $user_id,
           $boards_followed,
           $followers;
}