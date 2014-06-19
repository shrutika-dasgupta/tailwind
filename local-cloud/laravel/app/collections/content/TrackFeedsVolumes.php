<?php

namespace Content;

/*
 * Collection of Track feed Volume
 *
 * @author Yesh
 * @author Daniel
 */
class TrackFeedsVolumes extends \DBCollection
{
    const MODEL = 'Content\TrackFeedVolume';
    const TABLE = 'track_feed_volume';

    public $table = 'track_feed_volume';

    public $columns = array(
        'feed_id',
        'hours_since_last_run',
        'new_entries_count',
        'average_entries_per_hour',
        'timestamp',
    );

    public $primary_keys = array('feed_id');
}
