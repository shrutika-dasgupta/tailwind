<?php

namespace Content;

/**
 * Class DataFeed
 *
 * @package Content
 *
 * @author Yesh
 * @author Daniel
 */
class DataFeed extends \PDODatabaseModel
{
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

    public $feed_id;
    public $url;
    public $domain;
    public $title;
    public $description;
    public $visual_url;
    public $language;
    public $velocity;
    public $engagement;
    public $curated;
    public $subscribers_count;
    public $fb_likes;
    public $twitter_followers;
    public $facebook_username;
    public $twitter_username;
    public $timestamp;

    /**
     * Initializes the class.
     */
    public function __construct()
    {
        parent::__construct();

        $this->timestamp = time();
    }
}
