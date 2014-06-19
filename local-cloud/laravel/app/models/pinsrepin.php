<?php

use Pinleague\Pinterest;

/**
 * PinsRepin Model
 *
 * @author  Will
 */
class PinsRepin extends PDODatabaseModel
{
    public
        $pin_id,
        $repinner_user_id,
        $board_id,
        $board_url,
        $board_name,
        $category,
        $board_follower_count,
        $is_collaborative,
        $created_at,
        $timestamp;

    public
        $table = 'data_pins_repins',
        $columns =
        array(
            'pin_id',
            'repinner_user_id',
            'board_id',
            'board_url',
            'board_name',
            'category',
            'board_follower_count',
            'is_collaborative',
            'created_at',
            'timestamp'
        ),
        $primary_keys = array('pin_id','repinner_user_id');


    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct($pin_id)
    {
        parent::__construct();
        $this->pin_id = $pin_id;
        $this->timestamp = time();
    }

    /*
    |--------------------------------------------------------------------------
    | Instance Methods
    |--------------------------------------------------------------------------
    */
    /**
     * @author  Will
     *
     * @param $data
     * @return $this
     */
    public function loadAPIData($data)
    {
        if (is_object($data)) {
            $data = (array)$data;
        }

        /*
         * The owner field isn't always set, so we have to make sure it's there
         * and all that nonsense
         */
        $this->repinner_user_id = '';
        if (array_key_exists("owner", $data)) {
            if(is_object($data['owner'])) {
                $data['owner'] = (array) $data['owner'];
            }

            if (is_array($data["owner"])) {
                if (array_key_exists("id", $data["owner"])) {
                    $this->repinner_user_id = $data["owner"]["id"];
                }
            }
        }

        $this->board_id             = $data['id'];
        $this->board_url            = $data['url'];
        $this->board_name           = $data['name'];
        $this->category             = $data['category'];
        $this->board_follower_count = $data['follower_count'];
        $this->is_collaborative     = $data['is_collaborative'];
        $this->created_at           = Pinterest::creationDateToTimestamp($data['created_at']);

        return $this;
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/
class PinsRepinException extends DBModelException {}
