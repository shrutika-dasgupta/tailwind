<?php

/**
 * Class QueuedApiCall
 *
 * @author  Will
 */
class QueuedApiCall extends PDODatabaseModel
{
    const CALL_BRAND_MENTIONS          = 'Brand Mentions';
    const CALL_KEYWORD_SEARCH          = 'Keyword Search';
    const CALL_PIN_ENGAGEMENT_LIKES    = 'Pin Engagement Likes';
    const CALL_PIN_ENGAGEMENT_COMMENTS = 'Pin Engagement Comments';
    const CALL_PIN_ENGAGEMENT_REPINS   = 'Pin Engagement Repins';
    const CALL_USER                    = 'User';
    const CALL_USER_BOARDS             = 'User Boards';
    const CALL_USER_FOLLOWERS          = 'User Followers';
    const CALL_USER_PINS               = 'User Pins';
    const CALL_BOARD_PINS              = 'Board Pins';
    const CALL_BOARD_FOLLOWERS         = 'Board Followers';

    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    public
        $api_call,
        $object_id,
        $parameters,
        $bookmark,
        $track_type,
        $running,
        $timestamp;
    public $pinterest_method_name;
    public
        $table = 'status_api_calls_queue',
        $columns =
        array(
            'api_call',
            'object_id',
            'parameters',
            'bookmark',
            'track_type',
            'running',
            'timestamp'
        ),
        $primary_keys = array();

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    /**
     * @author  Will
     *
     * @param $api_call
     */
    public function __construct($api_call)
    {
        parent::__construct();
        $this->api_call = trim($api_call);
        $this->running = 0;
        $this->timestamp = time();

        $this->determine_pinterest_method_name();
    }

    /*
    |--------------------------------------------------------------------------
    | Static Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Add a new API call
     *
     * @author  Will
     */
    public static function add(QueuedApiCall $call)
    {
        $STH = $call->DBH->prepare('
                insert ignore into status_api_calls_queue
                set api_call = :api_call,
                   object_id = :object_id,
                   parameters = :parameters,
                   bookmark = :bookmark,
                   track_type = :track_type,
                   timestamp = :timestamp
            ');

        $call->timestamp = time();

        $STH->execute(
            array(
                 ':api_call'   => $call->api_call,
                 ':object_id'  => $call->object_id,
                 ':parameters' => $call->parameters,
                 ':bookmark'   => $call->bookmark,
                 ':track_type' => $call->track_type,
                 ':timestamp'  => $call->timestamp
            )
        );

        return $call;
    }

    /**
     * @param $data
     * @param $api_call
     * @param $track_type
     * @param $object_id
     *
     * @return \QueuedApiCall
     */
    public static function loadBookmarkFromApi($data, $api_call, $track_type, $object_id)
    {
        $call             = new QueuedApiCall($api_call);
        $call->object_id  = $object_id;
        $call->bookmark   = $data->bookmark;
        $call->parameters = '';
        $call->track_type = $track_type;
        $call->running    = 0;
        $call->timestamp  = time();

        return $call;
    }

    /**
     * @author   yesh
     *
     * @param $data
     * @param $api_call
     * @param $track_type
     *
     * @throws QueuedApiCallException
     * @internal param $ [type] $data [description]
     * @return \QueuedApiCall [type]       [description]
     */
    public static function loadPinFromAPI($data, $api_call, $track_type)
    {
        if (empty($data)) {
            throw new QueuedApiCallException('There is no data to load');
        }
        $call             = new QueuedApiCall($api_call);
        $call->object_id  = $data;
        $call->parameters = '';
        $call->bookmark   = '';
        $call->track_type = $track_type;
        $call->running    = 0;
        $call->timestamp  = time();

        return $call;
    }

    /*
    |--------------------------------------------------------------------------
    | Instance methods
    |--------------------------------------------------------------------------
    */

    /** This function would convert the db object
     * to a queuedapicall object
     *
     * @author   Alex
     *
     * @param string $api_call The type of call
     *                         to be set
     * @param        $board
     *
     * @return QueuedApiCall object
     */
    public static function addFromStatusBoards($api_call, $board)
    {
        $call             = new QueuedApiCall($api_call);
        $call->object_id  = $board->board_id;
        $call->parameters = '';
        $call->bookmark   = '';
        $call->track_type = $board->track_type;
        $call->timestamp  = time();

        return $call;
    }


    /** This function would convert the db object
     * to a QueuedApiCall object
     *
     * @author Yesh
     *
     * @param string $api_call The type of call
     *                         to be set
     * @param        $dbo
     *
     * @return A QueuedApiCall object
     */
    public static function readyDBObject($api_call, $dbo)
    {
        $call             = new QueuedApiCall($api_call);
        $call->object_id  = $dbo->user_id;
        $call->parameters = '';
        $call->bookmark   = '';
        $call->track_type = $dbo->track_type;
        $call->timestamp  = time();

        return $call;
    }

    /** This function would convert the keyword db object
     * to a queuedapicall object
     *
     * @author Yesh
     *
     * @param string $api_call The type of call
     *                         to be set
     * @param        $dbo
     *
     * @return QueuedApiCall object
     */
    public static function readyDBObjectDomain($api_call, $dbo)
    {
        $call             = new QueuedApiCall($api_call);
        $call->object_id  = $dbo->domain;
        $call->parameters = '';
        $call->bookmark   = '';
        $call->track_type = $dbo->track_type;
        $call->timestamp  = time();

        return $call;
    }


    /** This function would convert a keyword db object
     * from status_keywords into a queuedapicall object
     *
     * @author Yesh
     *
     * @param string $api_call The type of call
     *                         to be set
     * @param        $dbo
     *
     * @return QueuedApiCall object
     */
    public static function readyDBObjectKeyword($api_call, $dbo)
    {
        $call             = new QueuedApiCall($api_call);
        $call->object_id  = $dbo->keyword;
        $call->parameters = '';
        $call->bookmark   = '';
        $call->track_type = $dbo->track_type;
        $call->timestamp  = time();

        return $call;
    }

    /**
     * Figures out what method to call based on the api call
     * string
     *
     * @author  Will
     */
    public function determine_pinterest_method_name()
    {
        switch (strtolower($this->api_call)) {
            case 'pin engagement repins':
                $this->pinterest_method_name = 'getPinRepins';
                break;

            case 'user pins':
                $this->pinterest_method_name = 'getUserPins';
                break;

            case 'pin engagement likes':
                $this->pinterest_method_name = 'getPinLikes';
                break;

            case 'pin engagement comments':
                $this->pinterest_method_name = 'getPinComments';
                break;

            case 'user followers':
                $this->pinterest_method_name = 'getProfileFollowers';
                break;

            case 'keyword search':
                $this->pinterest_method_name = 'getSearchPinsFromKeyword';
                break;

            case 'search boards':
                $this->pinterest_method_name = 'getSearchBoardsFromKeyword';
                break;

            case 'board pins':
                $this->pinterest_method_name = 'getBoardsPins';
                break;

            case 'board followers':
                $this->pinterest_method_name = 'getBoardFollowers';
                break;

            case 'domain pins':
                $this->pinterest_method_name = 'getDomainPins';
                break;

            case 'user boards':
                $this->pinterest_method_name = 'getProfileBoards';
                break;

            default:
                throw new QueuedApiCallException('No method associated with that api_call');
                break;
        }

        return $this;
    }

    /**
     * Remove from the DB
     *
     * @author  Will
     */
    public function removeFromDB()
    {
        $STH = $this->DBH->prepare('
                delete from status_api_calls_queue
                where api_call = :api_call
                and object_id = :object_id
                and parameters = :parameters
                and bookmark = :bookmark
            ');

        return $STH->execute(
            array(
                 ':api_call'   => $this->api_call,
                 ':object_id'  => $this->object_id,
                 ':parameters' => $this->parameters,
                 ':bookmark'   => $this->bookmark
            )
        );
    }


    /**
     * Reset the running flag of the call
     *
     * @author Yesh
     *         Alex
     */
    public function rerunCall(){

        $STH = $this->DBH->prepare("UPDATE status_api_calls_queue
                                    SET running = 0
                                    WHERE api_call = :api_call
                                    AND object_id = :object_id
                                    AND parameters = :parameters
                                    AND bookmark = :bookmark");

        $STH->execute(array(':api_call'   => $this->api_call,
                            ':object_id'  => $this->object_id,
                            ':parameters' => $this->parameters,
                            ':bookmark'   => $this->bookmark
                      ));
    }

    /**
     * Update timestamp
     *
     * @author  Alex
     */
    public function updateTimestamp()
    {
        $STH = $this->DBH->prepare('
                update status_api_calls_queue
                set timestamp = :timestamp
                where api_call = :api_call
                and object_id = :object_id
                and parameters = :parameters
                and bookmark = :bookmark
            ');

        return $STH->execute(
                   array(
                        ':timestamp'  => time(),
                        ':api_call'   => $this->api_call,
                        ':object_id'  => $this->object_id,
                        ':parameters' => $this->parameters,
                        ':bookmark'   => $this->bookmark
                   )
        );
    }
}

class QueuedApiCallException extends DBModelException
{
    const BLANK_RESULT = 934;
}
