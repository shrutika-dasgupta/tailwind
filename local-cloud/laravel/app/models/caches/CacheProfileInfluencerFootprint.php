<?php

namespace Caches;

/**
 * CacheProfileInfluencerFootprint Model
 *
 * @package Caches
 *
 * @author  Yesh
 */
class CacheProfileInfluencerFootprint extends Cache {

    public $table = 'cache_profile_influencers_footprint';

    public $columns =
        [
            'user_id',
            'category',
            'footprint_count'
        ],
    $primary_keys = ['user_id','category'];

    public
        $user_id,
        $category,
        $footprint_count;
}
