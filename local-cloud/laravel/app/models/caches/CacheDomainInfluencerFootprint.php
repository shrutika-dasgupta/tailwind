<?php

namespace Caches;

/**
 * CacheDomainInfluencerFootprint Model
 *
 * @author  Yesh
 */

use Pinleague\Pinterest;

class CacheDomainInfluencerFootprint extends Cache {

    public $table = 'cache_domain_influencers_footprint';

    public $columns =
        [
            'domain',
            'category',
            'period',
            'footprint_count'
        ],
        $primary_keys = ['domain','category', 'period'];

    public
        $domain,
        $category,
        $period,
        $footprint_count;
}
