<?php

/**
 * Pins comments collection.
 *
 * @author Daniel
 */
class PinsComments extends DBCollection
{
    /*
     * Schema.
     */
    public
        $table = 'data_pins_comments',
        $columns = array(
            'comment_id',
            'pin_id',
            'commenter_user_id',
            'comment_text',
            'created_at',
            'timestamp',
        ),
        $primary_keys = array('comment_id');
}

class PinsCommentsException extends CollectionException {}