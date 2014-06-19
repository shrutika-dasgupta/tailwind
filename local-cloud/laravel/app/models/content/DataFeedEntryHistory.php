<?php

namespace Content;

/*
 * Data feed entry history model.
 *
 * @author Daniel
 */
class DataFeedEntryHistory extends \PDODatabaseModel
{
    public $table = 'data_feed_entry_history';

    public $columns = array(
        'feed_entry_id',
        'date',
        'social_score',
        'facebook_score',
        'googleplus_score',
        'pinterest_score',
        'twitter_score',
    );

    public $primary_keys = array('feed_entry_id');

    public $feed_entry_id;
    public $date;
    public $social_score;
    public $facebook_score;
    public $googleplus_score;
    public $pinterest_score;
    public $twitter_score;

    /**
     * Initializes the class.
     * 
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->date = flat_date();
    }
}
