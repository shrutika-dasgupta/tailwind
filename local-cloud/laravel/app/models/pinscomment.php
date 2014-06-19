<?php

/**
 * Pin comment model.
 *
 * @author Daniel
 */
class PinsComment extends PDODatabaseModel
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

    /**
     * Properties.
     */
    public
        $comment_id,
        $pin_id,
        $commenter_user_id,
        $comment_text,
        $created_at,
        $timestamp;

    /*
    |--------------------------------------------------------------------------
    | Cached Properties
    |--------------------------------------------------------------------------
    */
    protected $_pin;
    protected $_commenter;


    /**
     * Get the pin associated with this comment
     * @author  Will
     */
    public function pin() {
        if (!is_null($this->_pin)) {
            return $this->_pin;
        }

        if ($pin = Pin::find_one($this->pin_id)) {
            return $pin = $this->_pin;
        }

        return null;
    }

    /**
     * Get the profile of the person who commented on the pin
     * @author  Will
     */
    public function commenter() {

      /*  if (!is_null($this->_commenter)) {
            return $this->_commenter;
        }
      */

            return  $this->_commenter = Profile::find_one($this->commenter_user_id);

    }
}

class PinsCommentException extends DBModelException {}
