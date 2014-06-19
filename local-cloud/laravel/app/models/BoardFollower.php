<?php

use Pinleague\Pinterest;

/**
 * Follower Model
 *
 * @author Yesh
 */
class BoardFollower extends PDODatabaseModel
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */

    public
        $table = 'data_board_followers',
        $columns =
        array(
            'user_id',
            'board_id',
            'follower_user_id',
            'follower_pin_count',
            'follower_follower_count',
            'follower_gender',
            'follower_created_at',
            'added_at',
            'updated_at'
        ),
        $primary_keys = array('user_id','board_id','follower_user_id');
    public
        $user_id,
        $board_id,
        $follower_user_id,
        $follower_pin_count,
        $follower_follower_count,
        $follower_gender,
        $follower_created_at,
        $added_at,
        $updated_at;

    protected $_profile = false;

    /**
     * @author Yesh
     */
    public function __construct()
    {
        $this->added_at = time();
        $this->updated_at = time();
    }



    /**
     * @author  Alex
     *
     * @param Profile $data
     * @param         $user_id
     * @param         $board_id
     *
     * @return $this
     */
    public function addViaProfile(Profile $data, $user_id, $board_id)
    {
        $this->user_id                  = $user_id;
        $this->board_id                 = $board_id;
        $this->follower_user_id         = $data->user_id;
        $this->follower_pin_count       = $data->pin_count;
        $this->follower_follower_count  = $data->follower_count;
        $this->follower_gender          = $data->p_gender;
        $this->follower_created_at      = $data->created_at;

        return $this;
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



    /*
    |--------------------------------------------------------------------------
    | Static methods
    |--------------------------------------------------------------------------
    */

    /**
     * @author                           Alex
     *
     * @param string $column_name        The name of the column we want the count for
     * @param Array  $user_ids           An array of all the user_ids we want to get the count for.
     *
     * @internal                         param Array $follower_count_db : A hash map of the user_ids and their follower_count
     *                                   from the DB
     *
     * @return array
     */
    public static function columnCountByUser($column_name, $user_ids)
    {
        $DBH               = DatabaseInstance::DBO();
        $follower_count_db = array();

        foreach ($user_ids as $user_id) {
            $STH = $DBH->prepare("SELECT COUNT($column_name) as count
                              FROM data_board_followers
                              WHERE user_id = :user_id");
            $STH->execute(array(":user_id" => $user_id));
            $count = $STH->fetchAll();

            $follower_count_db[$user_id] = $count[0]->count;
        }

        return $follower_count_db;
    }

    /**
     * @author                           Alex
     *
     * @param string $column_name The name of the column we want the count for
     * @param        $board_ids
     *
     * @internal     param Array $follower_count_db : A hash map of the user_ids and their follower_count
     *               from the DB
     *
     * @return array
     */
    public static function columnCountByBoard($column_name, $board_ids)
    {
        $DBH               = DatabaseInstance::DBO();
        $follower_count_db = array();

        foreach ($board_ids as $board_id) {
            $STH = $DBH->prepare("SELECT COUNT($column_name) as count
                              FROM data_board_followers
                              WHERE board_id = :board_id");
            $STH->execute(array(":board_id" => $board_id));
            $count = $STH->fetchAll();

            $follower_count_db[$board_id] = $count[0]->count;
        }

        return $follower_count_db;
    }

    /**
     * @author Alex
     *
     * @param  $board_ids
     *
     * @return array
     *
     * Gets a board's follower count number from the data_boards table
     *
     */
    public static function getFollowersCount($board_ids)
    {
        $DBH          = DatabaseInstance::DBO();
        $actual_count = array();

        foreach ($board_ids as $board_id) {

            $STH = $DBH->prepare("SELECT follower_count
                    FROM data_boards
                    WHERE board_id = :board_id");
            $STH->execute(array(":board_id" => $board_id));

            $follower_count          = $STH->fetchAll();
            $actual_count[$board_id] = $follower_count[0]->follower_count;
        }

        return $actual_count;
    }

    /**
     * @author Alex
     *
     * @param  $board_ids
     *
     * @return array
     *
     * Gets a board's follower count number from the data_boards table
     *
     */
    public static function getFollowerCountAndOwnerId($board_ids)
    {
        $DBH          = DatabaseInstance::DBO();
        $actual_data  = array();

        foreach ($board_ids as $board_id) {

            $STH = $DBH->prepare("SELECT user_id, owner_user_id, follower_count
                    FROM data_boards
                    WHERE board_id = :board_id
                    order by last_pulled desc");
            $STH->execute(array(":board_id" => $board_id));

            $board_meta                                 = $STH->fetchAll();
            $actual_data["$board_id"]                   = array();
            $actual_data["$board_id"]['follower_count'] = $board_meta[0]->follower_count;
            if (!empty($board_meta[0]->owner_user_id)) {
                $actual_data["$board_id"]['owner_user_id']  = $board_meta[0]->owner_user_id;
            } else if ($board_meta[1] && !empty($board_meta[1]->owner_user_id)) {
                $actual_data["$board_id"]['owner_user_id']  = $board_meta[1]->owner_user_id;
            } else {
                $actual_data["$board_id"]['owner_user_id']  = $board_meta[0]->user_id;
            }
        }

        return $actual_data;
    }

    /**
     * Get actual number of followers we've found for a board so far
     */
    public static function getFollowersFoundCount($board_id)
    {
        $DBH = DatabaseInstance::DBO();

        $STH = $DBH->prepare("SELECT count(follower_user_id) as count
                FROM data_board_followers use index (board_id_follower_user_id_idx)
                WHERE board_id = :board_id");
        $STH->execute(array(":board_id" => $board_id));

        $followers_found_count = $STH->fetch()->count;

        return $followers_found_count;
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/
class BoardFollowerException extends DBModelException {}
