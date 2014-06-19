<?php

use Pinleague\Pinterest;

/**
 * Class QueuedApiCalls
 */
class QueuedApiCalls extends DBCollection
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
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
        $primary_keys = array('api_call','object_id','parameters','bookmark');

    /**
     * Gets a set of ApiCalls from the queue
     *
     * @author  Alex
     *
     * @param      $call_type
     * @param      $track_type
     * @param      $number_of_calls
     * @param bool $new_client_id
     *
     * @throws NoApiCallsOnQueueException
     * @throws ApiCallsAccessException
     * @returns QueuedApiCalls collection
     */
    public static function fetchAndUpdateRunning($call_type, $track_type, $number_of_calls, $new_client_id = false)
    {
        $calls = new QueuedApiCalls();

        $updating_flag = 1;
        $run_times = 0;

        while ($updating_flag == 1 && $run_times < 20) {

            /*
             * Check to see if we're currently updating this call type and try again after
             * sleeping for 2 seconds if we are
             */
            $STH = $calls->DBH->query(
                "SELECT updating_flag, last_updated
                FROM status_api_call_types
                WHERE api_call = '$call_type'
                AND track_type = '$track_type'"
            );

            $call_type_row = $STH->fetch();
            $updating_flag = $call_type_row->updating_flag;
            $last_updated  = $call_type_row->last_updated;

            /*
             * If we're currently updating api calls on the queue, we'll try again in a few seconds
             */
            if ($updating_flag == 1) {
                sleep(2);
                $run_times++;

                /*
                 * Make sure that this flag isn't stuck for some reason so engines don't just stop
                 */
                $time = time();
                if($last_updated < strtotime("-1 minute", time())){
                    $STH = $calls->DBH->query(
                                "UPDATE status_api_call_types
                                SET updating_flag = 0,
                                last_updated = $time
                                WHERE api_call = '$call_type'
                                AND track_type = '$track_type'"
                    );
                }
                continue;
            }

            /*
             * Since we're not currently updating, we're clear to go ahead.
             * First, we'll update the updating_flag to 1 so other engines won't interfere.
             */
            $time = time();
            $STH = $calls->DBH->query(
                                "UPDATE status_api_call_types
                                SET updating_flag = 1,
                                last_updated = $time
                                WHERE api_call = '$call_type'
                                AND track_type = '$track_type'"
            );


            /*
             * Pull oldest calls from the queue
             *
             * EXCEPTION: When pulling Board followers, we're going to try and sort by least
             * amount of followers first (within each track_type), so we can get as many complete
             * sets of followers as possible quickly (most followed boards will be pulled last).
             */
            if ($call_type == "Board Followers") {

                $STH = $calls->DBH->prepare("
                    SELECT a.* from status_api_calls_queue a
                    INNER JOIN data_boards b
                    ON a.object_id = b.board_id
                    WHERE a.api_call = :call_type
                    AND a.track_type = :track_type
                    AND a.running = 0
                    GROUP BY a.object_id
                    ORDER BY b.follower_count ASC
                    LIMIT $number_of_calls
                ");
            } else {
                $STH = $calls->DBH->prepare("
                    SELECT * from status_api_calls_queue
                    WHERE api_call = :call_type
                    AND track_type = :track_type
                    AND running = 0
                    ORDER BY timestamp ASC
                    LIMIT $number_of_calls
                ");
            }


            $STH->execute(
                array(
                     ':call_type'  => $call_type,
                     ':track_type' => $track_type
                )
            );

            if ($STH->rowCount() == 0) {
                /*
                 * Looks like we didn't find any calls on the queue.
                 * We're going to set the updating flag back to 0 and throw an
                 * exception so that it can bubble up into the engine and be handled
                 * appropriately.
                 */
                $time = time();
                $STH = $calls->DBH->query(
                                "UPDATE status_api_call_types
                                SET updating_flag = 0,
                                last_updated = $time
                                WHERE api_call = '$call_type'
                                AND track_type = '$track_type'"
                );

                Log::debug("No calls found for call: $call_type and track_type: $track_type");
                throw new NoApiCallsOnQueueException('There were no calls found');
            }

            foreach ($STH->fetchAll() as $DBdata) {
                $call = new QueuedApiCall($DBdata->api_call);
                $call->loadDBData($DBdata);
                $calls->add($call);
            }

            /*
             * We're going to explicitly define the object_id and bookmark of each call we want to
             * update in the database to ensure we update the exact calls we want and nothing else.
             */
            $calls->sortByPrimaryKeys();
            $call_clause_count = 0;
            $call_clauses = "";
            foreach ($calls as $call) {

                $call_clauses .= ($call_clause_count == 0 ? "" : " OR ") ."
                (object_id='$call->object_id' and bookmark='$call->bookmark')";

                $call_clause_count++;
            }

            /*
             * Update the running flag for the calls we've selected on the queue to 1
             */
            Log::info('Setting Flag to running in queue', $calls);
            $time = time();
            $STH = $calls->DBH->prepare(
                              "UPDATE status_api_calls_queue
                                SET running = 1,
                                timestamp = $time
                                WHERE api_call = :call_type
                                AND track_type = :track_type
                                AND running = 0
                                AND (
                                  $call_clauses
                                )");

            $STH->execute(
                array(
                     ":call_type" => $call_type,
                     ":track_type" => $track_type
                )
            );

            /*
             * Now that we've updated the running flags of the individual calls, we can
             * reset the updating_flag back to 0, since we're done.
             */
            $time = time();
            $STH = $calls->DBH->query(
                                "UPDATE status_api_call_types
                                SET updating_flag = 0,
                                last_updated = $time
                                WHERE api_call = '$call_type'
                                AND track_type = '$track_type'"
            );
        }

        if ($run_times == 20) {
            throw new ApiCallsAccessException('Calls could not be accessed');
        }

        return $calls;
    }

    /**
     * Gets a set of ApiCalls from the queue
     *
     * @author  Will
     *
     * @returns QueuedApiCalls collection
     */
    public static function fetch($call_type, $track_type, $number_of_calls)
    {
        $calls = new QueuedApiCalls();

        $STH = $calls->DBH->prepare("
            SELECT * from status_api_calls_queue
            WHERE api_call = :call_type
            AND track_type = :track_type
            AND running = 0
            ORDER BY timestamp ASC
            LIMIT $number_of_calls
        ");

        $STH->execute(
            array(
                 ':call_type'  => $call_type,
                 ':track_type' => $track_type
            )
        );

        if ($STH->rowCount() == 0) {
            throw new NoApiCallsOnQueueException('There were no calls found');
        }

        foreach ($STH->fetchAll() as $DBdata) {
            $call = new QueuedApiCall($DBdata->api_call);
            $call->loadDBData($DBdata);
            $calls->add($call);
        }

        return $calls;
    }

    /**
     * Get new data from any table in the
     * database that you want to add on to
     * the api_call_queue
     *
     * @author Yesh
     *
     * @param  string $table_name Table name for grabbing data
     * @param  string $track_type Track type to be filtered on
     * @param  int    $limit
     *
     * @return array
     */
    public static function push($table_name, $track_type, $limit)
    {
        $DBH       = DatabaseInstance::DBO();
        $flat_time = flat_date('day');
        $time      = time();

        /*
         * If pushing new calls from status_profile_followers,
         * we want to check to make sure this user_id
         * is not already on the api calls queue from before
         * (some users have a lot of followers and therefore have new bookmarked calls which
         * continue to be added for more than 24 hours sometimes.
         * we do not want to just start pulling those users from the beginning on top of already
         * pulling their followers deeper in their followers list).
         *
         */
        $queue_check_clause = "";
        if ($table_name == 'status_profile_followers') {
            $queue_check_clause = "AND user_id NOT IN
            	(SELECT object_id FROM status_api_calls_queue
            	 WHERE api_call = 'User Followers'
            	 AND track_type = '$track_type')";
        }

        

        $new_users = $DBH->prepare("
            SELECT user_id, track_type
            FROM $table_name
            WHERE last_pulled < :flat_time
            AND track_type = :track_type
            $queue_check_clause
            ORDER BY last_pulled ASC
            LIMIT $limit"
        );
        $new_users->execute(array(':flat_time'  => $flat_time,
                                  ':track_type' => $track_type));
        $users = $new_users->fetchAll();

        foreach ($users as $user) {
            $STH = $DBH->query("UPDATE $table_name
                            SET last_pulled = $time
                            WHERE user_id = $user->user_id");
        }

        return $users;
    }


    /**
     * Get domains from status_domains table in the
     * database to find domains we want to add on to
     * the api_call_queue in order to pull latest pins
     *
     * @author Yesh
     *         Alex
     *
     * @param  string $track_type Track type to be filtered on
     * @param  int    $limit
     *
     * @return array
     */
    public static function pushDomains($track_type, $limit)
    {
        $DBH       = DatabaseInstance::DBO();
        $flat_time = flat_date('day');
        $time      = time();

        $new_domains = $DBH->prepare("
            SELECT domain, pins_per_day, track_type, last_pulled
            FROM status_domains
            WHERE track_type = :track_type
            ORDER BY last_pulled ASC"
        );
        $new_domains->execute(array(':track_type' => $track_type));
        $domains = $new_domains->fetchAll();

        $domains_to_pull = array();
        $domain_strings = array();

        /*
         * Domain rules array set how often to pull domain pins based on pins_per_day:
         *   [pull_frequency] => (less than) pins_per_day (upper limit of the range)
         *
         * We will keep track of the lower limit of a given range as we iterate through
         * further below
         */
        $domain_pull_rules = array(
            "-1 day" => 250,
            "-12 hours" => 400,
            "-8 hours"  => 600,
            "-6 hours"  => 800,
            "-4 hours"  => 1000,
            "-3 hours"  => 1500,
            "-2 hours"  => 2500,
            "-1 hour"   => 5000,
            "-30 minutes" => 10000,
            "-20 minutes" => 15000,
            "-15 minutes" => 20000,
            "-10 minutes" => 25000,
            "-5 minutes" => 60000,
            "-4 minutes" => 75000,
            "-3 minutes" => 100000,
            "-2 minutes" => 200000,
            "-1 minute"  => 300000,
            "-30 seconds" => 700000
        );
        /*
         * We'll iterate through each domain now and based on how often it should be pulled,
         * we'll decide whether we should queue it up for a run.
         *
         * This decision is based on the "pin_per_day" field, where we want to make sure we pull
         * enough times in a given day to never miss any new pins.
         *
         * "pins_per_day" is calculated as
         */
        foreach ($domains as $domain) {

            $domain->days_ago = number_format((time() - $domain->last_pulled)/60/60/24,2);
            $last_rule_ppd = 0;
            foreach ($domain_pull_rules as $rule_time => $rule_ppd) {

                /*
                 * Typecast values as numbers
                 */
                $pins_per_day = (float)$domain->pins_per_day;
                $last_pulled  = (int)$domain->last_pulled;

                /*
                 * Check to see if pins_per_day for the current domain we're looking at falls
                 * within the current rule range.
                 */
                if ($pins_per_day < $rule_ppd && $pins_per_day >= $last_rule_ppd) {

                    /*
                     * Check to see if the last_pulled time is before the rule time we have set
                     * for the current rule range.
                     */
                    if ($last_pulled < strtotime($rule_time)) {

                        array_push($domains_to_pull, $domain);
                        array_push($domain_strings, "'$domain->domain'");
                    }
                }

                /*
                 * set value for lower limit of rule range for the next iteration.
                 */
                $last_rule_ppd = $rule_ppd;
            }

        }

        if(count($domain_strings) > 0){
            $STH = $DBH->query("UPDATE status_domains
                                SET last_pulled = $time
                                WHERE domain in (" . implode(",", $domain_strings) . ")");
        }

        return $domains_to_pull;
    }


    /**
     * Get boards to pull from status_keywords tables based on "last_pulled_boards" column
     * where boards have not been pulled yet today, in order to add them to the API queue.
     *
     * @author Alex
     *
     * @param  string     $table_name Table name for grabbing data
     * @param bool|string $track_type Track type to be filtered on
     * @param bool|int    $limit
     *
     * @return array
     */
    public static function pushKeywordBoardPulls($table_name, $track_type = false, $limit = false)
    {

        $DBH               = DatabaseInstance::DBO();
        $current_hour      = date('G', time());
        $flat_time         = flat_date('day');
        $check_time        = $flat_time;
        $time              = time();
        $track_type_clause = "";
        $params            = array(
            ":check_time" => $check_time
        );

        /*
         * Check to see if track_type was defined.  If not, then we do not need to use it.
         */
        if(!empty($track_type)){
            $track_type_clause = "AND track_type = :track_type";
            $params = array_merge($params,
                array(
                     ":track_type" => $track_type
                )
            );
        }

        if(!$limit){
            $limit = 100;
        }

        $new_keywords = $DBH->prepare("
            SELECT keyword, track_type
            FROM $table_name
            WHERE last_pulled_boards < :check_time
            $track_type_clause
            ORDER BY last_pulled_boards ASC
            LIMIT $limit"
        );
        $new_keywords->execute($params);
        $keywords = $new_keywords->fetchAll();

        foreach ($keywords as $keyword) {

            $STH = $DBH->prepare("UPDATE $table_name
                            SET last_pulled_boards = :time
                            WHERE keyword = :keyword");

            $STH->execute(array(
                               ":time"    => time(),
                               ":keyword" => $keyword->keyword
                          )
            );
        }

        return $keywords;
    }

    /**
     * Get records from the status_boards based on any timestamp column
     * (last_pulled, last_calced, etc.) that you want to add on to
     * the api_call_queue
     *
     * @author Alex
     *
     * @param  string $column_name Column name for grabbing data
     * @param  string $track_type Track type to be filtered on
     * @param  int    $limit
     *
     * @return array
     */
    public static function pushBoards($column_name, $track_type, $limit)
    {
        $DBH       = DatabaseInstance::DBO();
        $flat_time = flat_date('day');
        $time      = time();

        $new_boards = $DBH->prepare("
            SELECT board_id, track_type
            FROM status_boards
            WHERE $column_name < :flat_time
            AND track_type = :track_type
            ORDER BY $column_name ASC
            LIMIT $limit"
        );
        $new_boards->execute(array(':flat_time'  => $flat_time,
                                   ':track_type' => $track_type));
        $boards = $new_boards->fetchAll();

        /**
         * Make a CSV of board_ids we've pulled
         */
        $board_ids_csv = "";
        foreach ($boards as $board) {
            if ($board_ids_csv == "") {
                $board_ids_csv = "'$board->board_id'";
            } else {
                $board_ids_csv .= ", '" . $board->board_id . "'";
            }
        }

        /**
         * Now we want to check to see if any of these board_ids are already on the queue,
         * in which case we'll want to exclude them.
         */
        if (count($boards) > 0) {
            $boards_on_queue = $DBH->prepare(
                "SELECT distinct object_id FROM status_api_calls_queue
                 WHERE api_call = 'Board Followers'
                 AND object_id in ($board_ids_csv)"
            );

            $boards_on_queue->execute();
            $boards_on_queue = $boards_on_queue->fetchAll();

            $boards_on_queue_array = array();
            foreach ($boards_on_queue as $board) {
                $boards_on_queue_array[] = $board->object_id;
            }
        }

        $boards_to_pull = array();
        foreach ($boards as $board) {
            $STH = $DBH->query("UPDATE status_boards
                            SET $column_name = $time
                            WHERE board_id = $board->board_id");

            /**
             * Check to see if any of the board_ids match the ones we've found to already be
             * on the queue.  If they do not match, we'll add them to an array of objects
             * that we'll return as the boards we actually do want to pull.
             */
            if (isset($boards_on_queue_array)) {
                if (!in_array($board->board_id, $boards_on_queue_array)) {
                    $boards_to_pull[] = $board;
                }
            }
        }

        return $boards_to_pull;
    }





    /**
     * Delete models from the database
     *
     * @author Yesh
     */
    public static function DeleteModelsFromDB($call)
    {
        $DBH = DatabaseInstance::DBO();

        $STH = $DBH->prepare("DELETE FROM status_api_calls_queue
                            WHERE api_call = :api_call AND object_id = :object_id
                            AND bookmark = :bookmark AND parameters = :parameters");
        $STH->execute(array(":api_call" => $call->api_call
        , ":object_id" => $call->object_id
        , ":bookmark" => $call->bookmark
        , ":parameters" => $call->parameters));
    }

    /*
    |--------------------------------------------------------------------------
    | Instance Methods
    |--------------------------------------------------------------------------
    */

    /** Get new data from keyword status table
     *
     * @author Yesh
     *
     * @param  string $table_name Table name for grabbing data
     * @param  string $track_type Track type to be filtered on
     * @param  int $limit
     * @return array
     */
    public static function pushKeyword($table_name, $track_type, $limit)
    {
        $DBH = DatabaseInstance::DBO();
        $flat_time = flat_date('day');
        $time = time();


        $new_users = $DBH->prepare("
            SELECT keyword, track_type
            FROM $table_name
            WHERE last_pulled < :flat_time
            AND track_type = :track_type
            ORDER BY last_pulled ASC
            LIMIT $limit");

        $new_users->execute(array(':flat_time' => $flat_time,
            ':track_type' => $track_type));
        $users = $new_users->fetchAll();

        foreach ($users as $user) {
            $STH = $DBH->prepare("UPDATE $table_name
                                SET last_pulled = :current_time
                                WHERE keyword = :keyword");
            $STH->execute(array(":current_time" => $time, ":keyword" => $user->keyword));
        }
        return $users;
    }

    /**
     * @author Alex
     *
     * Check to see whether bookmark is NULL, and if so, assign an empty string to avoid
     *         SQL integrity constraint error (column can't be NULL).
     */
    public function insertUpdateDB($dont_update_these_columns = array())
    {
        foreach($this->models as &$call){
            if(is_null($call->bookmark)){
                Log::debug('Null bookmark found');
                $call->bookmark = "";
            }
        }

        return parent::insertUpdateDB($dont_update_these_columns);
    }


    /**
     * Send the calls to Pinterest API
     *
     * @author  Will
     */
    public function send($use_new_client_id = false)
    {
        /*
         * Since we are doing a batch request, we want a persistant instance
         * of the Pinterest object. We also want to designate that the calls
         * should be batched.
         */
        if ($use_new_client_id) {
            $Pinterest              = Pinterest::getInstance($use_new_client_id);
            $Pinterest->batch_calls = true;
        } else {
            $Pinterest              = Pinterest::getInstance();
            $Pinterest->batch_calls = true;
        }

        foreach ($this as $call) {

            $parameters = array();

            if ($call->bookmark) {
                $parameters['bookmark'] = $call->bookmark;
            }

            $method = $call->pinterest_method_name;
            $Pinterest->$method($call->object_id, $parameters);

        }

        return $Pinterest->sendBatchRequests();
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/
class QueuedApiCallsException extends CollectionException {}

class NoApiCallsOnQueueException extends QueuedApiCallsException {}

class ApiCallsAccessException extends QueuedApiCallsException {}
