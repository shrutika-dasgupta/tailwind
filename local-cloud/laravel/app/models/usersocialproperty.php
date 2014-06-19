<?php

/**
 * Class UserSocialProperty.
 *
 * Property types and names are currently defined by Intercom's social profile data.
 *
 * @author Janell
 */
class UserSocialProperty extends PDODatabaseModel
{
    // Property type constants
    const FACEBOOK         = 'facebook';
    const FACEBOOK_PAGE    = 'facebook_page';
    const TWITTER          = 'twitter';
    const LINKEDIN         = 'linkedin';
    const LINKEDIN_COMPANY = 'linkedin_company';
    const GOOGLE_PLUS      = 'googleplus';
    const GOOGLE_PROFILE   = 'googleprofile';
    const YAHOO            = 'yahoo';
    const MYSPACE          = 'myspace';
    const KLOUT            = 'klout';
    const GRAVATAR         = 'gravatar';
    const FLICKR           = 'flickr';
    const YOUTUBE          = 'youtube';
    const VIMEO            = 'vimeo';
    const TUMBLR           = 'tumblr';
    const BLOGGER          = 'blogger';
    const WORDPRESS        = 'wordpress';
    const ABOUTME          = 'aboutme';
    const YELP             = 'yelp';

    // Property name constants
    const URL            = 'url';
    const ID             = 'id';
    const USERNAME       = 'username';
    const FOLLOWER_COUNT = 'follower_count';
    const KLOUT_SCORE    = 'klout_score';

    public static $available_properties = array(
        UserSocialProperty::FACEBOOK => array(
            UserSocialProperty::URL,
            UserSocialProperty::USERNAME,
            UserSocialProperty::ID,
        ),
        UserSocialProperty::FACEBOOK_PAGE => array(
            UserSocialProperty::URL,
            UserSocialProperty::USERNAME,
            UserSocialProperty::ID,
        ),
        UserSocialProperty::TWITTER => array(
            UserSocialProperty::URL,
            UserSocialProperty::USERNAME,
            UserSocialProperty::ID,
            UserSocialProperty::FOLLOWER_COUNT,
        ),
        UserSocialProperty::LINKEDIN => array(
            UserSocialProperty::URL,
            UserSocialProperty::USERNAME,
        ),
        UserSocialProperty::LINKEDIN_COMPANY => array(
            UserSocialProperty::URL,
            UserSocialProperty::USERNAME,
        ),
        UserSocialProperty::GOOGLE_PLUS => array(
            UserSocialProperty::URL,
            UserSocialProperty::USERNAME,
            UserSocialProperty::ID,
        ),
        UserSocialProperty::GOOGLE_PROFILE => array(
            UserSocialProperty::URL,
            UserSocialProperty::USERNAME,
            UserSocialProperty::ID,
        ),
        UserSocialProperty::YAHOO => array(
            UserSocialProperty::URL,
            UserSocialProperty::ID,
        ),
        UserSocialProperty::MYSPACE => array(
            UserSocialProperty::URL,
            UserSocialProperty::USERNAME,
            UserSocialProperty::ID,
        ),
        UserSocialProperty::KLOUT => array(
            UserSocialProperty::URL,
            UserSocialProperty::USERNAME,
            UserSocialProperty::ID,
            UserSocialProperty::KLOUT_SCORE,
        ),
        UserSocialProperty::GRAVATAR => array(
            UserSocialProperty::URL,
            UserSocialProperty::USERNAME,
            UserSocialProperty::ID,
        ),
        UserSocialProperty::FLICKR => array(
            UserSocialProperty::URL,
            UserSocialProperty::USERNAME,
            UserSocialProperty::ID,
        ),
        UserSocialProperty::YOUTUBE => array(
            UserSocialProperty::URL,
            UserSocialProperty::USERNAME,
            UserSocialProperty::ID,
        ),
        UserSocialProperty::VIMEO => array(
            UserSocialProperty::URL,
            UserSocialProperty::USERNAME,
            UserSocialProperty::ID,
        ),
        UserSocialProperty::TUMBLR => array(
            UserSocialProperty::URL,
            UserSocialProperty::USERNAME,
        ),
        UserSocialProperty::BLOGGER => array(
            UserSocialProperty::URL,
            UserSocialProperty::ID,
        ),
        UserSocialProperty::WORDPRESS => array(
            UserSocialProperty::URL,
            UserSocialProperty::USERNAME,
        ),
        UserSocialProperty::ABOUTME => array(
            UserSocialProperty::URL,
            UserSocialProperty::USERNAME,
        ),
        UserSocialProperty::YELP => array(
            UserSocialProperty::URL,
            UserSocialProperty::USERNAME,
        ),
    );

    public $table = 'user_social_properties';

    public $columns = array(
        'cust_id',
        'type',
        'name',
        'value',
        'created_at',
        'updated_at',
    );

    public $primary_keys = array('cust_id', 'type', 'name');

    public $cust_id;
    public $type;
    public $name;
    public $value;
    public $created_at;
    public $updated_at;

    /**
     * Initializes the class.
     *
     * @author Janell
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->created_at = time();
        $this->updated_at = time();
    }

}

class UserSocialPropertyNotFoundException extends DBModelException {}