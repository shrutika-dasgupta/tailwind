<?php

namespace Content;

/**
 * Collection for data feeds.
 * 
 * @author Daniel
 * @author Yesh
 */
class DataFeeds extends \DBCollection
{
    const MODEL = 'Content\DataFeed';
    const TABLE = 'data_feeds';

    public $table = 'data_feeds';

    public $columns = array(
        'feed_id',
        'url',
        'domain',
        'title',
        'description',
        'visual_url',
        'language',
        'velocity',
        'engagement',
        'curated',
        'subscribers_count',
        'fb_likes',
        'twitter_followers',
        'facebook_username',
        'twitter_username',
        'timestamp',
    );

    public $primary_keys = array('feed_id');
}


