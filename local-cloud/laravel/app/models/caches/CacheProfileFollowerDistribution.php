<?php

namespace Caches;

/**
 * CacheProfileFollowerDistrubution Model
 *
 * @author  Alex
 */

use Pinleague\Pinterest;

class CacheProfileFollowerDistribution extends Cache
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
