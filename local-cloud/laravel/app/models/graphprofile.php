<?php

use Pinleague\Pinterest;

/**
 * GraphProfiles  Model
 *
 * @author  Yesh
 */
class GraphProfile extends PDODatabaseModel
{
    /*
     * DB Attributes
     */
    public
        $source,
        $user_id,
        $flag,
        $followers,
        $following_count,
        $created_at,
        $timestamp;

    public $columns = array(
          'source'
        , 'user_id'
        , 'flag'
        , 'followers'
        , 'following_count'
        , 'created_at'
        , 'timestamp');

    public $table = 'status_pull_profiles_queue';

    public function __construct($source)
    {
        parent::__construct();
        $this->source = $source;
        $this->flag = 0;
        $this->timestamp = time();
    }

    public function update($data)
    {
        /*
         * The data comes as an std object from the multi curl
         * and as an array from the Pinterest class
         * so we cast it as an array to make it simpler
         */
        if (is_object($data)) {
            $data = (array)$data;
        }

        /*
         * Again, with the array typecasting from ^^^
         */

        if (array_key_exists('id', $data)) {
            $this->user_id         = $data['id'];
            $this->followers       = $data['follower_count'];
            $this->following_count = $data['following_count'];
            $this->created_at      = Pinterest::creationDateToTimestamp($data['created_at']);
        } else {
            throw new ProfileException('There was a successful response, but no data to load');
        }

        return $this;
    }
}

class GraphProfileException extends DBModelException {}
