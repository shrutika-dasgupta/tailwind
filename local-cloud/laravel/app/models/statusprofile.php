<?php

/**
 * Status profile
 *
 */
class StatusProfile extends PDODatabaseModel
{
    public $columns = array(
        'user_id',
        'username',
        'last_calced',
        'last_pulled',
        'last_pulled_boards',
        'followers_found',
        'last_updated_followers_found',
        'calculate_influencers_footprint',
        'track_type',
        'timestamp'
    ), $table = 'status_profiles';
    public
        $user_id,
        $username,
        $last_calced,
        $last_pulled,
        $last_pulled_boards,
        $followers_found,
        $last_updated_followers_found,
        $calculate_influencers_footprint,
        $track_type,
        $timestamp;

    /**
     * @author  Will
     * @bug     If its an update the model will be returned with old data, my B
     */
    public static function create(Profile $profile, $track_type)
    {
        $status = new StatusProfile();


        $columns_list = implode(',',$status->columns);
        $bind_list = rtrim(':'.implode(',:',$status->columns),':');

        $STH = $status->DBH->prepare("
              insert into status_profiles
               (
               $columns_list
               ) VALUES (
               $bind_list
               )
             ON DUPLICATE KEY UPDATE
             last_pulled = VALUES(last_pulled),
			 track_type = IF(
			                  VALUES(track_type)='user','user',
			              IF (track_type='orphan',VALUES(track_type),
			                  track_type)
			                  ),
			 timestamp = VALUES(timestamp)
            ");

        $status->user_id                      = $profile->user_id;
        $status->username                     = $profile->username;
        $status->last_calced                  = 0;
        $status->last_pulled                  = 0;
        $status->last_pulled_boards           = 0;
        $status->followers_found              = 0;
        $status->last_updated_followers_found = 0;
        $status->calculate_influencers_footprint = 0;
        $status->track_type                   = $track_type;
        $status->timestamp                    = time();

        $params = array();

        foreach ($status->columns as $column) {
            $key          = ':' . $column;
            $params[$key] = $status->$column;
        }

        $STH->execute($params);

        return $status;
    }


    /**
     * @param     $flag Name of the column
     * @param int $limit
     *
     * @return array
     *
     * @author Yesh
     */
    public static function fetchUserIds($flag, $limit = 150) {

        $DBH = DatabaseInstance::DBO();

        $STH = $DBH->query("SELECT user_id
                              FROM status_profiles
                              WHERE $flag = 0 AND
                              track_type = 'user'
                              LIMIT $limit");

        $results = $STH->fetchAll();

        if (empty($results)) {

            $STH = $DBH->query("SELECT user_id
                              FROM status_profiles
                              WHERE $flag = 0 AND
                              track_type = 'competitor'
                              LIMIT $limit");

            $results = $STH->fetchAll();
        }

        return $results;
    }
    /**
     * @author  Will
     *
     * @param $user_id
     * @return bool|\StatusProfile
     */
    public static function find($user_id)
    {
        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->prepare("
            select * from status_profiles
            where user_id = :user_id
            limit 1
        ");

        $STH->execute(
            array(
                 ':user_id' => $user_id
            )
        );

        if ($STH->rowCount() == 0) {
            return false;
        }

        $status_profile_record = new self();
        $status_profile_record->loadDBData($STH->fetch());

        return $status_profile_record;
    }

    /**
     * @author   Alex
     *
     * @return array counts
     */
    public static function getActiveCount()
    {
        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->query(
                         "SELECT track_type, count(*) as count
                          FROM status_profiles
                          WHERE track_type in ('user', 'competitor', 'free')
                          GROUP BY track_type"
        );

        return $STH->fetchAll();
    }

    /**
     * @author   Alex
     *
     * @return array counts
     */
    public static function getLastPulledTodayCount()
    {
        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->prepare(
                   "SELECT track_type, count(*) as count
                    FROM status_profiles
                    WHERE last_pulled >= :flat_date
                    AND track_type in ('user', 'competitor', 'free')
                    GROUP BY track_type"
        );

        $STH->execute(
                    array(
                         ':flat_date' => flat_date('day')
                    ));

        $counts = $STH->fetchAll();

        return $counts;
    }

    /**
     * @author   Alex
     *
     * @return array counts
     */
    public static function getLastPulledBoardsTodayCount()
    {
        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->prepare(
                   "SELECT track_type, count(*) as count
                    FROM status_profiles
                    WHERE last_pulled_boards >= :flat_date
                    AND track_type in ('user', 'competitor', 'free')
                    GROUP BY track_type"
        );

        $STH->execute(
            array(
                 ':flat_date' => flat_date('day')
            ));

        $counts = $STH->fetchAll();

        return $counts;
    }

    /**
     * @author   Alex
     *
     * @param array $user_ids
     *
     * @return array counts
     *
     * Returns the actual number of followers we've found so far, as recorded in the
     * status_profiles table.  We do this so that we're not constantly running
     * "select count(followers) from data_followers where user_id = $user_id" on
     * every single run of our followers pull script.
     *
     * If this count has not been updated recently, we'll pull the count
     * from the data_followers table and update it in the status_profiles_table.
     */
    public static function getFollowersFoundCount($user_ids)
    {
        $DBH = DatabaseInstance::DBO();
        $user_follower_counts = array();

        $user_ids_implode = implode(",", $user_ids);

        $STH = $DBH->prepare(
                   "SELECT user_id, followers_found, last_updated_followers_found
                    FROM status_profiles
                    WHERE user_id IN ($user_ids_implode)"
        );

        $STH->execute();

        $profiles = $STH->fetchAll();

        foreach($profiles as $profile){
            $followers_found = $profile->followers_found;
            $user_follower_counts[$profile->user_id] = $followers_found;

            if($profile->last_updated_followers_found < strtotime("-20 minutes")) {
                $followers_found = Follower::getFollowersFoundCount($profile->user_id);
                self::updateFollowersFound($profile->user_id, $followers_found);
                $user_follower_counts[$profile->user_id] = $followers_found;
            }
        }

        return $user_follower_counts;
    }

    /**
     * @author   Alex
     *
     * @param $user_id
     * @param $followers_found
     *
     * Updates the followers_found and last_updated_followers_found fields
     */
    public static function updateFollowersFound($user_id, $followers_found)
    {
        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->prepare(
                   "UPDATE status_profiles
                    SET followers_found = :followers_found,
                    last_updated_followers_found = :last_updated_followers_found
                    WHERE user_id = :user_id"
        );

        $STH->execute(
            array(
                 ':followers_found'              => $followers_found,
                 ':last_updated_followers_found' => time(),
                 ':user_id'                      => $user_id
            )
        );
    }


}

class StatusProfileException extends DBModelException {}
