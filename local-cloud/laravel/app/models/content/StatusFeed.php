<?php

namespace Content;

/*
 * Models for Status Feed table
 *
 * @author Yesh
 * @author Daniel
 */
class StatusFeed extends \PDODatabaseModel
{
    public $table = 'status_feeds';

    public $columns = array(
        'id',
        'url',
        'subscribers_count',
        'velocity',
        'engagement',
        'curated',
        'last_pulled',
        'added_at',
        'updated_at',
    );

    public $primary_keys = array('id');

    public $id;
    public $url;
    public $subscribers_count;
    public $velocity;
    public $engagement;
    public $curated;
    public $last_pulled;
    public $added_at;
    public $updated_at;

    /**
     * Initializes the class.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->added_at   = time();
        $this->updated_at = time();
    }
}
