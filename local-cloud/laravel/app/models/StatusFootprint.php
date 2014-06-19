<?php

/**
 * StatusFootprint model
 *
 * @author Alex
 */
class StatusFootprint extends PDODatabaseModel
{
    public $table = 'status_footprint';

    public
        $user_id,
        $track_type,
        $last_run;

    public $columns = array(
        'user_id',
        'track_type',
        'last_run'
    );
    public $primary_keys = array('user_id');
}

class StatusFootprintException extends DBModelException {}
