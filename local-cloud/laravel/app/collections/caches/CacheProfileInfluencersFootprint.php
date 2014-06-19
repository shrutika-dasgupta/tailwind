<?php

namespace Caches;

use Pinleague\Pinterest;
use Pinleague\PinterestException;

/**
 * Collection of CacheProfileInfluencerFootprint model
 *
 * @author Yesh
 */

class CacheProfileInfluencersFootprint extends Caches
{

    public $table = 'cache_profile_influencers_footprint';
    public $columns =
        [
            'user_id',
            'category',
            'footprint_count'
        ],
        $primary_keys = ['user_id','category'];
}