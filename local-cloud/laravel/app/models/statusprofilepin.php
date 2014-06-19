<?php

/**
 * Status profile
 *
 */
class StatusProfilePin extends PDODatabaseModel
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    public
        $user_id,
        $last_pulled,
        $track_type,
        $timestamp;

    public $columns = array(
        'user_id', 'last_pulled', 'track_type', 'timestamp'
    );

    /**
     * @author  Will
     */
    public static function create($user_id, $track_type)
    {
        $status = new StatusProfilePin();

        $STH = $status->DBH->prepare("
          insert into status_profile_pins
          (
              user_id,
              last_pulled,
              track_type,
              timestamp
          ) VALUES (
              :user_id,
              :last_pulled,
              :track_type,
              :timestamp
          )
          ON DUPLICATE KEY UPDATE
              last_pulled = VALUES(last_pulled),
              track_type = IF(VALUES(track_type)='user','user',track_type),
              timestamp = VALUES(timestamp)
        ");

        $status->user_id     = $user_id;
        $status->last_pulled = 0;
        $status->track_type  = $track_type;
        $status->timestamp   = time();

        $params = array();

        foreach ($status->columns as $column) {
            $key          = ':' . $column;
            $params[$key] = $status->$column;
        }

        $STH->execute($params);

        return $status;
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
                    FROM status_profile_pins
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
                    FROM status_profile_pins
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
}
