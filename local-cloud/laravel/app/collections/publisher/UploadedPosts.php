<?php namespace Publisher;

use DBCollection;

/**
 * Collection of UploadedPost model
 *
 * @author  Will
 */
class UploadedPosts extends DBCollection
{
    const MODEL = 'Publisher\UploadedPost';
    const TABLE = 'publisher_uploaded_posts';

    public $table = 'publisher_uploaded_posts';

    public $columns = array(
        'id',
        'account_id',
        'type',
        'location',
        'status',
        'added_at',
        'updated_at'
    );

    public $primary_keys = array('id');

}
