<?php

/**
 * Class ApiError
 * 
 * @author Will
 */
class ApiError extends PDODatabaseModel
{
    /*
     * Table meta data
     */
    public
        $id,
        $api_call,
        $object_id,
        $bookmark,
        $message,
        $message_detail,
        $code,
        $timestamp;
    public
        $table = 'status_api_errors',
        $columns =
        array(
            'id',
            'api_call',
            'object_id',
            'bookmark',
            'message',
            'message_detail',
            'code',
            'timestamp'
        ),
        $primary_keys = array('id');

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    /**
     * @author  will
     */
    public function __construct()
    {
        parent::__construct();
        $this->timestamp = time();
    }

    /*
    |--------------------------------------------------------------------------
    | Static Methods
    |--------------------------------------------------------------------------
    */
    /**
     * @author  Will
     *
     * @param      $api_call
     * @param      $object_id
     * @param      $message
     * @param      $message_detail
     * @param      $code
     *
     * @param bool $bookmark
     *
     * @return ApiError
     */
    public static function create(
        $api_call
        , $object_id
        , $message
        , $message_detail
        , $code
        , $bookmark = false
    )
    {
        $error = new ApiError();

        $error->api_call       = $api_call;
        $error->object_id      = $object_id;
        $error->message        = $message;
        $error->message_detail = $message_detail;
        $error->code           = $code;

        if ($bookmark) {
            $error->bookmark = $bookmark;
        } else {
            $error->bookmark = '';
        }

        $error->saveToDB();

        $error->id = $error->DBH->lastInsertId();

        return $error;
    }

    /**
     * @author  Will
     *
     * @param QueuedApiCall $call
     * @param int           $code
     *
     * @return int
     */
    public static function numberOfEntries(QueuedApiCall $call, $code)
    {
        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->prepare('
            select api_call from status_api_errors
            where api_call = :api_call
            AND object_id = :object_id
            AND bookmark = :bookmark
            AND code = :code
        ');

        $STH->execute(
            array(
                 ':api_call'  => $call->api_call,
                 ':object_id' => $call->object_id,
                 ':bookmark'  => $call->bookmark,
                 ':code'      => $code
            )
        );

        return $STH->rowCount();
    }

    /**
     * @author  Alex
     *
     * @param QueuedApiCall $call
     * @param int           $code
     * @param int           $within_last
     * @param bool          $exclude_bookmark
     *
     * @return int
     */
    public static function numberOfEntriesWithinTime(QueuedApiCall $call, $code, $within_last, $exclude_bookmark = false)
    {
        $DBH = DatabaseInstance::DBO();

        $parameter_array = array(
            ':api_call'  => $call->api_call,
            ':object_id' => $call->object_id,
            ':code'      => $code,
            ':timestamp' => $within_last
        );

        if ($exclude_bookmark) {
            $bookmark_clause = "";
        } else {
            $bookmark_clause = "AND bookmark = :bookmark";
            $parameter_array[':bookmark'] = $call->bookmark;
        }

        $STH = $DBH->prepare("
            select api_call from status_api_errors
            where api_call = :api_call
            AND object_id = :object_id
            $bookmark_clause
            AND code = :code
            AND timestamp > :timestamp
        ");

        $STH->execute($parameter_array);

        return $STH->rowCount();
    }

    /**
     * @author   Alex
     *
     * @param     $api_call
     * @param     $object_id
     * @param     $bookmark
     * @param int $code
     * @param int $within_last
     *
     * @internal param \QueuedApiCall $call
     * @return int
     */
    public static function numberOfEntriesExplicit($api_call, $object_id, $bookmark, $code, $within_last)
    {

        if(empty($bookmark)){
            $bookmark = "";
        }

        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->prepare('
            select api_call from status_api_errors
            where api_call = :api_call
            AND object_id = :object_id
            AND bookmark = :bookmark
            AND code = :code
            AND timestamp > :timestamp
        ');

        $STH->execute(
            array(
                 ':api_call'  => $api_call,
                 ':object_id' => $object_id,
                 ':bookmark'  => $bookmark,
                 ':code'      => $code,
                 ':timestamp' => $within_last
            )
        );

        return $STH->rowCount();
    }

    /**
     * @author  Alex
     *
     * @param QueuedApiCall $call
     * @param int           $code
     * @param int           $within_last
     *
     * @return int
     */
    public static function numberOfRateLimitExceptions(QueuedApiCall $call, $code, $within_last)
    {
        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->prepare('
            select api_call from status_api_errors
            where api_call = :api_call
            AND code = :code
            AND timestamp > :timestamp
        ');

        $STH->execute(
            array(
                 ':api_call'  => $call->api_call,
                 ':code'      => $code,
                 ':timestamp' => $within_last
            )
        );

        return $STH->rowCount();
    }
}