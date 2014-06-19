<?php

/**
 * Status profiles model.
 *
 * @author Yesh
 */
class StatusProfiles extends DBCollection
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

    public $primary_keys = ['user_id'];

     /**
     * Get records from the status_profiles based on any timestamp column
     * (last_pulled, last_calced, last_pulled_boards, etc.) that you want to add on to
     * the api_call_queue
     *
     * @author Alex
     * @author Yesh
     *
     * @param  string $column_name Column name for grabbing data
     * @param  string $track_type Track type to be filtered on
     * @param  int    $limit
     *
     * @return array
     */
    public static function push($column_name, $track_type, $limit)
    {
        $DBH       = DatabaseInstance::DBO();
        $flat_time = flat_date('day');
        $time      = time();

        $new_users = $DBH->prepare("
            SELECT user_id, track_type
            FROM status_profiles
            WHERE $column_name < :flat_time
            AND track_type = :track_type
            ORDER BY $column_name ASC
            LIMIT $limit"
        );
        $new_users->execute(array(':flat_time'  => $flat_time,
                                   ':track_type' => $track_type));
        $users = $new_users->fetchAll();

        foreach ($users as $user) {
            $STH = $DBH->query("UPDATE status_profiles
                            SET $column_name = $time
                            WHERE user_id = $user->user_id");
        }

        return $users;
    }

}

