<?php

use Pinleague\Pinterest;

/**
 * Follower Model
 *
 * @author Yesh
 */
class Follower extends PDODatabaseModel
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */

    public
        $table = 'data_followers',
        $columns =
        array(
            'user_id',
            'follower_user_id',
            'follower_pin_count',
            'follower_followers',
            'follower_created_at',
            'follower_facebook',
            'follower_twitter',
            'follower_domain',
            'follower_domain_verified',
            'follower_location',
            'timestamp'
        ),
        $primary_keys = array();
    public
        $user_id,
        $follower_user_id,
        $follower_pin_count,
        $follower_followers,
        $follower_created_at,
        $follower_facebook,
        $follower_twitter,
        $follower_domain,
        $follower_domain_verified,
        $follower_location,
        $timestamp;

    protected $_profile = false;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    /**
     * @author Yesh
     */
    public function __construct()
    {
        $this->timestamp = time();
        parent::__construct();
    }

    /*
    |--------------------------------------------------------------------------
    | Static methods
    |--------------------------------------------------------------------------
    */

    /**
     * @author                           Yesh
     *
     * @param string $column_name        The name of the column we want the count for
     * @param Array  $user_ids           An array of all the user_ids we want to get the count for.
     *
     * @internal                         param Array $follower_count_db : A hash map of the user_ids and their follower_count
     *                                   from the DB
     *
     * @return array
     */
    public static function columnCount($column_name, $user_ids)
    {
        $DBH               = DatabaseInstance::DBO();
        $follower_count_db = array();

        foreach ($user_ids as $user_id) {
            $STH = $DBH->prepare("SELECT COUNT($column_name) as count
                              FROM data_followers
                              WHERE user_id = :user_id");
            $STH->execute(array(":user_id" => $user_id));
            $count = $STH->fetchAll();

            $follower_count_db[$user_id] = $count[0]->count;
        }

        return $follower_count_db;
    }

    /**
     * @author  Yesh
     *
     * @param $follower
     */
    public static function followersExistDB($follower)
    {

    }

    public static function getFollowersCount($user_ids)
    {
        $DBH          = DatabaseInstance::DBO();
        $actual_count = array();

        foreach ($user_ids as $user_id) {

            $STH = $DBH->prepare("SELECT follower_count
                    FROM data_profiles_new
                    WHERE user_id = :user_id");
            $STH->execute(array(":user_id" => $user_id));

            $follower_count         = $STH->fetchAll();
            $actual_count[$user_id] = $follower_count[0]->follower_count;
        }

        return $actual_count;
    }

    /**
     * Get actual number of followers we've found for a user so far
     */
    public static function getFollowersFoundCount($user_id)
    {
        $DBH = DatabaseInstance::DBO();

        $STH = $DBH->prepare("SELECT count(follower_user_id) as count
                FROM data_followers
                WHERE user_id = :user_id");
        $STH->execute(array(":user_id" => $user_id));

        $followers_found_count = $STH->fetch()->count;

        return $followers_found_count;
    }

    /**
     * Returns the profile of the following account
     *
     * @author  Will
     *
     * @param bool $force_update
     *
     * @return bool|Profile
     */
    public function profile($force_update = false)
    {
        if ($this->_profile && !$force_update) {
            return $this->_profile;
        }

        return $this->_profile = Profile::findInDB($this->follower_user_id);
    }

    /**
     * @author  Will
     *
     * @param $width
     * @param $height
     *
     * @return string
     */
    public function getImageUrl($width = false,$height = false) {
        try {
            return $this->profile()->getImageUrl();
        }
        catch (Exception $e) {
            Log::warning($e);
            return 'http://passets-ak.pinterest.com/images/user/default_140.png';
        }
    }

    /**
     * @author  Yesh
     *
     * @param Profile $data
     * @param         $object_id
     *
     * @return $this
     */
    public function updateViaProfile(Profile $data, $object_id)
    {
        $this->user_id                  = $object_id;
        $this->follower_user_id         = $data->user_id;
        $this->follower_pin_count       = $data->pin_count;
        $this->follower_followers       = $data->follower_count;
        $this->follower_created_at      = $data->created_at;
        $this->follower_facebook        = $data->facebook_url;
        $this->follower_twitter         = $data->twitter_url;
        $this->follower_domain          = $data->website_url;
        $this->follower_domain_verified = $data->domain_verified;
        $this->follower_location        = $data->location;
        $this->timestamp                = $data->timestamp;

        return $this;
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/
class FollowerException extends DBModelException {}
