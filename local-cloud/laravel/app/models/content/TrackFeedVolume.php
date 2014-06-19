<?php

namespace Content;

/*
 * Models for Track Feed Volume table
 *
 * @author Yesh
 * @author Daniel
 */
class TrackFeedVolume extends \PDODatabaseModel
{
    public $table = 'track_feed_volume';

    public $columns = array(
        'feed_id',
        'hours_since_last_run',
        'new_entries_count',
        'average_entries_per_hour',
        'timestamp',
    );

    public $primary_keys = array('feed_id');

    public $feed_id;
    public $hours_since_last_run;
    public $new_entries_count;
    public $average_entries_per_hour;
    public $timestamp;

    /*
     * Set the number of hours it has been since a feed has been
     * tracked
     */
    public function hoursSinceLastRun()
    {
        $STH = $this->DBH->prepare(" SELECT timestamp
                                     FROM track_feed_volume
                                     WHERE feed_id = :feed_id
                                     ORDER BY timestamp DESC
                                     LIMIT 1");
        $STH->execute(array(":feed_id" => $this->feed_id));

        $last_run_time = $STH->fetch();

        if ($last_run_time > 0) {
            $time_diff = time() - $last_run_time->timestamp;
            $this->hours_since_last_run = $time_diff / 3600;
        } else {
            $this->hours_since_last_run = null;
        }
    }
}
