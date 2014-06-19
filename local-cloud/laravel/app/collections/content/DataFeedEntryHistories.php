<?php

namespace Content;

/*
 * Collection for data feed entry history.
 *
 * @author Daniel
 */
class DataFeedEntryHistories extends \DBCollection
{
    const MODEL = 'Content\DataFeedEntryHistory';
    const TABLE = 'data_feed_entry_history';

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
}
