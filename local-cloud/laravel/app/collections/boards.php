<?php


/**
 * Collection of Boards
 *
 * @author  Will
 * @author  Yesh
 */

use Pinleague\Pinterest;
use Pinleague\CLI;

class Boards extends DBCollection
{

    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */
    const RATING_MIN_TOTAL_BOARDS     = 10;
    const RATING_MIN_TOTAL_CATEGORIES = 2;
    const RATING_SCORE_GOOD           = 'good';
    const RATING_SCORE_BAD            = 'bad';

    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    public $categories = array();
    public $pins = array();
    public
        $table = 'data_boards',
        $columns =
        array(
            'board_id',
            'user_id',
            'owner_user_id',
            'url',
            'is_collaborator',
            'is_owner',
            'collaborator_count',
            'image_cover_url',
            'name',
            'description',
            'category',
            'layout',
            'pin_count',
            'follower_count',
            'created_at',
            'last_pulled',
            'track_type',
            'timestamp'
        ),
        $primary_keys = array('board_id', 'user_id');

    /*
    |--------------------------------------------------------------------------
    | Static Methods
    |--------------------------------------------------------------------------
    */

    /**
     * @author  Will
     *
     * @param $data
     * @param $user_id
     *
     * @return Boards
     */
    public static function createFromApiDataSet($data, $user_id)
    {

        $boards = new Boards();
        foreach ($data as $api_board) {
            $board          = new Board();
            $board->user_id = $user_id;
            $board->loadApiData($api_board);
            $boards->add($board);
        }

        return $boards;

    }

    /**
     * @author                Yesh
     *
     * A static method to find all the pins for a
     * given set of board_slug Db objects
     *
     * @param $board_slugs
     *
     * @internal              param $pins_arr : array of pins returned from the API
     * @internal              param $dead_board : Board which are not found (Error 40)
     * @internal              param $rerun_boards : All boards returning other errors (Error 12)
     *                        from the API.
     * @internal              param $bookmark_id : A hashmap which keeps track of bookmark
     *                        and its related board_id
     *
     * @return Array of $pins_arr and $dead_board
     */
    public static function fetchPinsBoardSlugs($board_slugs)
    {
        $pins_arr    = array();
        $dead_boards = array();

        $counter      = 0;
        $error_codes  = array(8, 12, 13, 16);
        $rerun_boards = array();

        /*
         * Since we are doing a batch request, we want a persistant instance of the Pinterest
         * object. We also want to designate that the calls should be batched.
         */

        $Pinterest              = Pinterest::getInstance();
        $Pinterest->batch_calls = true;

        foreach ($board_slugs as $board) {
            if (is_object($board)){
                if(!empty($board->board_slug)){
                    $Pinterest->getBoardsPins($board->board_slug);
                    echo $board->board_slug . ' object' . PHP_EOL;
                }

            } else {
                if(!empty($board)){
                    $Pinterest->getBoardsPins($board);
                    echo $board . ' array' . PHP_EOL;
                }
            }
        }

        echo str_repeat("-", 20) . "\n";

        $data = $Pinterest->sendBatchRequests();

        while ($data) {
            $Pinterest              = new Pinterest();
            $Pinterest->batch_calls = true;

            $more_calls_to_make = false;

            foreach ($data as $curl_key => $curl_result) {

                if (!($curl_result->code === 0)) {

                    /**
                     * @todo var dump in the model? we should avoid this practice :)
                     */
                    echo $curl_result->code . " on " . $board_slugs[$curl_key]->board_slug . "\n";
                    $slug_string = $board_slugs[$curl_key]->board_slug;

                    if ($curl_result->code === 40) {
                        array_push($dead_boards, $board_slugs[$curl_key]);

                        CLI::write(Log::warning(
                                      "Board slug (" . $board_slugs[$curl_key]->board_slug .
                                      ") not found - switching calced_flag to 2 " .
                                      "in map_traffic_pins_boards so we know not to run it again."
                        ));
                    } else if (in_array($curl_result->code, $error_codes)) {
                        array_push($rerun_boards, $board_slugs[$curl_key]);

                        CLI::write(Log::warning(
                                      "Error getting response from Pinterest - code: $curl_result->code." .
                                      " message: $curl_result->message."
                        ));
                    } else {
                        CLI::write(Log::error(
                                      "Uncaught Pinterest API error - code: $curl_result->code." .
                                      " message: $curl_result->message." .
                                      " board: $slug_string."
                        ));
                    }

                    ApiError::create(
                            'Boards Pins',
                                $board_slugs[$curl_key]->board_slug,
                                $curl_result->message,
                                'update_pins_info->collection/boards::fetchPinsBoardSlugs.' . __LINE__,
                                $curl_result->code,
                                $curl_result->bookmark
                    );
                }

                /*
                 * We want to store this data in a pin object and then ultimately in the
                 * Pins collection so we can easily manipulate it
                 */
                if (isset($curl_result->data)) {

                    foreach ($curl_result->data as $pinData) {
                        $pins_arr[] = $pinData;
                    }
                }

                /*
                 * If a bookmark exists, we need to start another set of multi curls to capture
                 * all of the pins on that board. In an effort to save time, we'll do another
                 * batch request in case there are multiple bookmarked returned sets
                 */
                if (isset($curl_result->bookmark)) {
                    echo $counter . " " . "wave:" . $curl_result->data[0]->board->id . "\n";

                    $bookmark_id                                   = array();
                    $bookmark_id[$curl_result->data[0]->board->id] = $curl_result->bookmark;

                    foreach ($bookmark_id as $board_id => $bk) {
                        $Pinterest->getBoardsPins($board_id, array('bookmark' => $bk));
                    }
                    $more_calls_to_make = true;
                }
            }

            if ($more_calls_to_make and $counter < 4) {
                echo str_repeat("-", 20) . "\n";
                $counter += 1;
                $data = $Pinterest->sendBatchRequests();
            } else {
                $data = false;
            }

        }

        return array('pins_arr' => $pins_arr, 'dead_boards' => $dead_boards, 'rerun_boards' => $rerun_boards);
    }

    /*
    |--------------------------------------------------------------------------
    | Instance Methods
    |--------------------------------------------------------------------------
    */

    /**
     * @author  Will
     *
     * @return array with  the key the category name
     *          and the value the number of times it appears
     */
    public function determineCategories()
    {
        $categories = array();
        foreach ($this->models as $board) {

            $category = $board->category;

            if ($category == '') {
                $category = 'No category';
            }

            if (array_key_exists($category, $categories)) {
                $categories[$category]++;
            } else {
                $categories[$category] = 1;
            }
        }

        $this->categories = $categories;
        return $this;

    }


    /**
     * @param       $board_ids
     * @param array $bookmarks
     *
     * @return array
     */
    public function getMissingPinInfo(
                                $board_ids,
                                $bookmarks = array())
    {
        $pins_arr    = array();
        $dead_boards = array();

        $error_codes  = array(8, 12, 13);
        $rerun_boards = array();

        /*
         * Since we are doing a batch request, we want a persistant instance of the Pinterest
         * object. We also want to designate that the calls should be batched.
         */

        $Pinterest              = Pinterest::getInstance();
        $Pinterest->batch_calls = true;

        // If loop tries to accomadate all formats of the data sent
        // TODO: Reformat this block when fixing update_pins_info

        if (is_object($board_ids)){
            foreach ($board_ids as $board) {
                if (isset($bookmarks[$board->board_id])){
                    $Pinterest->getBoardsPins($board->board_id,
                                    array('bookmark' => $bookmarks[$board->board_id]));
                } else {
                    $Pinterest->getBoardsPins($board->board_id);
                };

            }
        } else {
            foreach ($board_ids as $board) {
                if(is_object($board)){
                    if (isset($bookmarks[$board->board_id])){
                    $Pinterest->getBoardsPins($board->board_id,
                                    array('bookmark' => $bookmarks[$board->board_id]));
                    } else {
                        $Pinterest->getBoardsPins($board->board_id);
                    }
                }
                else {
                    if (isset($bookmarks[$board])){
                        $Pinterest->getBoardsPins($board,
                                    array('bookmark' => $bookmarks[$board]));
                    } else {
                        $Pinterest->getBoardsPins($board);
                    }
                }
            }
        }

        $data = $Pinterest->sendBatchRequests();

        $Pinterest              = new Pinterest();
        $Pinterest->batch_calls = true;

        $bookmark_array = array();

        foreach ($data as $curl_key => $curl_result) {

            CLI::write("code: " . $curl_result->code);

            if (!($curl_result->code === 0)) {


                /**
                 * @todo var dump in the model? we should avoid this practice :)
                 */
                echo var_dump($curl_result->code) . " on " . var_dump($board_ids[$curl_key]) . "\n";

                if ($curl_result->code === 40) {
                    array_push($dead_boards, $board_ids[$curl_key]);
                } else if (in_array($curl_result->code, $error_codes)) {
                    array_push($rerun_boards, $board_ids[$curl_key]);
                }

                ApiError::create(
                        'Board Pins',
                            $board_ids[$curl_key]->board_id,
                            $curl_result->message,
                            'boards_collection.' . __LINE__,
                            $curl_result->code,
                            $curl_result->bookmark
                );

            } else if (count($curl_result->data) == 0) {

                ApiError::create(
                        'Board Pins',
                            $board_ids[$curl_key]->board_id,
                            $curl_result->message,
                            'boards_collection. No data returned even with a successful response.' . __LINE__,
                            $curl_result->code,
                            $curl_result->bookmark
                );

                $errors_found = ApiError::numberOfEntriesExplicit(
                                        "Board Pins",
                                            $board_ids[$curl_key]->board_id,
                                            $curl_result->bookmark,
                                            $curl_result->code,
                                            flat_date(time())
                );

                CLI::write(Log::debug($errors_found . " similar call errors found"));

                if($errors_found > 2){
                    array_push($dead_boards, $board_ids[$curl_key]);
                    CLI::write(Log::debug("Too many similar errors with the same board - marking it dead so we don't keep pulling."));
                }
            }

            /*
             * We want to store this data in a pin object and then ultimately in the
             * Pins collection so we can easily manipulate it
             */
            if (isset($curl_result->data)) {

                foreach ($curl_result->data as $pinData) {
                    $pins_arr[] = $pinData;
                }
            }

            /*
             * If a bookmark exists, we need to start another set of multi curls to capture
             * all of the pins on that board. In an effort to save time, we'll do another
             * batch request in case there are multiple bookmarked returned sets
             */
            if (isset($curl_result->bookmark)) {

                $bookmark_array[$curl_result->data[0]->board->id] = $curl_result->bookmark;
            }
        }


    return array(
                'pins_arr' => $pins_arr,
                'dead_boards' => $dead_boards,
                'rerun_boards' => $rerun_boards,
                'bookmarks' => $bookmark_array);
    }


    /**
     * Get all the pins for a set of boards
     *
     * @author  Will
     *
     * @return array
     */
    public function fetchPins()
    {

        $pins = new Pins();

        /*
         * Since we are doing a batch request, we want a persistant instance of the Pinterest
         * object. We also want to designate that the calls should be batched.
         */
        $Pinterest              = Pinterest::getInstance();
        $Pinterest->batch_calls = true;

        foreach ($this->models as $board) {
            $Pinterest->getBoardsPins($board->board_id);

        }

        $data = $Pinterest->sendBatchRequests();

        while ($data) {

            $Pinterest              = new Pinterest();
            $Pinterest->batch_calls = true;

            $more_calls_to_make = false;

            foreach ($data as $curl_result) {
                /*
                 * We want to store this data in a pin object and then ultimately in the
                 * Pins collection so we can easily manipulate it
                 */
                if (isset($curl_result->data)) {

                    foreach ($curl_result->data as $pinData) {
                        $pin = new Pin();
                        $pin->loadApiData($pinData);
                        $pins->add($pin);
                    }

                }

                /*
                 * If a bookmark exists, we need to start another set of multi curls to capture
                 * all of the pins on that board. In an effort to save time, we'll do another
                 * batch request in case there are multiple bookmarked returned sets
                 */
                if (isset($curl_result->bookmark)) {
                    $Pinterest->getBoardsPins($board->board_id, array('bookmark' => $curl_result->bookmark));
                    $more_calls_to_make = true;
                }

            }

            if ($more_calls_to_make) {
                $data = $Pinterest->sendBatchRequests();
            } else {
                $data = false;
            }

        }

        $this->pins = $pins;

        return $pins;
    }

    /**
     * Gets the CalcsBoardHistory of each board
     *
     * @author  Will
     *
     * @param string $timestamp
     *
     * @BROKEN
     *
     * @return CalcBoardHistories
     */
    public function getBoardCalcsBefore($timestamp = 'yesterday')
    {
        if (is_string($timestamp)) {
            $timestamp = strtotime($timestamp);
        }

        $timestamp = flat_date('day', $timestamp);

        $board_ids = $this->stringifyField('board_id');

        /*
         * Ok so this sucks because if the calc is not there it's not going to grab the earlier one
         * we could loop through and do each one individually but thats like... the least
         * efficient thing I can think of
         *
         * We could also iterate through after and fill in the blanks but that feels like
         * cheating too..
         *
         * Perhaps a subquery is in order...
         * http://kristiannielsen.livejournal.com/6745.html
         *
         * either way...feels like an edge case I don't care about right now...sorry future me
         */
        $STH = $this->DBH->prepare("
                    SELECT m1.*
                    FROM calcs_board_history m1
                    LEFT JOIN calcs_board_history m2
                    ON (
                        m1.board_id = m2.board_id
                        AND m1.date > m2.date
                        and m1.date > :timestamp
                        and m2.date > :timestamp
                        )
                    WHERE m2.date IS NULL;
                    and board_id in ($board_ids)
            ");

        $STH->execute(
            array(
                 ':timestamp' => $timestamp
            )
        );


        $calc_board_histories = new CalcProfileHistories();
        foreach ($STH->fetchAll() as $boardCalcsData) {
            $board_history_calc = new CalcBoardHistory();
            $board_history_calc->loadDBData($boardCalcsData);
            $calc_board_histories->add($board_history_calc);
        }

        return $calc_board_histories;

    }

    /**
     * @author  Will
     * @return int
     */
    public function numberOfCategories()
    {
        if (empty($this->categories)) {
            $this->determineCategories();
        }

        return count($this->categories);
    }

    /**
     * @author  Will
     * @returns array
     */
    public function numberOfPinsInCategories()
    {

        $categories = array();
        foreach ($this->models as $board) {

            $category = $board->category;

            if ($category == '') {
                $category = 'No category';
            }

            if (array_key_exists($category, $categories)) {
                $categories[$category] = $categories[$category] + $board->pin_count;
            } else {
                $categories[$category] = $board->pin_count;
            }
        }

        arsort($categories);

        return $categories;

    }

    /**
     * Reorders the model list by the number of Repins since a given date
     *
     * @author  Will
     */
    public function orderByNewRepins($since = 'last week',$direction = 'DESC')
    {
        $boards       = $this->models;
        $this->models = array();

        $repinned = array();
        $calculations = new CalcBoardHistories();

        foreach ($boards as $board) {
            /** @var $board /Board */
            $latest_calc    = $board->findCalculationBefore();
            $last_week_calc = $board->findCalculationBefore('last week');

            /*
             * We want to send back the calculations so we can keep it with the board
             */
            $calculations->add($last_week_calc,$last_week_calc->date);
            $calculations->add($latest_calc,$latest_calc->date);
            $board->calc_board_histories = $calculations;

            $new_repins = $latest_calc->repins - $last_week_calc->repins;

            $repinned[$board->board_id] = $new_repins;

        }

        if ($direction == 'DESC') {
            sort($repinned);
        } else {
            rsort($repinned);
        }

        foreach ($repinned as $board_id => $repin_count) {
            $this->models[$board_id] = $boards[$board_id];
        }

        return $this;
    }

    /**
     * Reorders the model list by board name.
     *
     * @author Janell
     *
     * @param string $direction
     *
     * @return $this
     */
    public function orderByName($direction = 'DESC')
    {
        $boards       = $this->models;
        $board_names  = array();
        $this->models = array();

        foreach ($boards as $board) {
            $board_names[$board->board_id] = $board->name;
        }

        if ($direction == 'DESC') {
            asort($board_names, SORT_STRING);
        } else {
            arsort($board_names, SORT_STRING);
        }

        foreach ($board_names as $board_id => $board_name) {
            $this->models[$board_id] = $boards[$board_id];
        }

        return $this;
    }

    /**
     * A score we give them based on if we think they are doing well or not
     *
     * @author  Will
     * @author  Alex
     */
    public function score()
    {
        if ($this->count() < self::RATING_MIN_TOTAL_BOARDS
            || $this->numberOfCategories() < self::RATING_MIN_TOTAL_CATEGORIES
        ) {
            return self::RATING_SCORE_BAD;
        }

        return self::RATING_SCORE_GOOD;

    }

    /**
     * @author  Will
     *
     * @param bool $ignoreNoCategory
     *
     * @return array
     */
    public function topCategory($ignoreNoCategory = false,$make_pretty = false)
    {
        $categories = $this->numberOfPinsInCategories();
        reset($categories);
        $key = key($categories);

        if ($key == 'No category' && $ignoreNoCategory) {
            next($categories);
            $key = key($categories);
        }

        if($make_pretty) {
            $key = str_replace('_',' ',$key);
            $key = ucfirst($key);
        }
        return $key;
    }

}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/

class BoardsException extends CollectionException {}
