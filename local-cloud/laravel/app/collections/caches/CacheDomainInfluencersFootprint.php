<?php

namespace Caches;

use Pinleague\Pinterest;
use Pinleague\PinterestException;

/**
 * Collection of CacheDomainInfluencerFootprint model
 *
 * @author Yesh
 */

class CacheDomainInfluencersFootprint extends Caches
{

    public $table = 'cache_domain_influencers_footprint';
    public $columns =
        [
            'domain',
            'category',
            'period',
            'footprint_count'
        ],
        $primary_keys = ['domain','category', 'period'];
}