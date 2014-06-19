<?php

use Pinleague\Pinterest,
    Pinleague\PinterestException;

/**
 * Boards Model
 *
 * data_boards
 *
 * @author  Will
 * @author  John
 */
class Board extends PDODatabaseModel
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */

    public
        $board_id,
        $user_id,
        $owner_user_id,
        $url,
        $is_collaborator,
        $is_owner,
        $collaborator_count,
        $image_cover_url,
        $name,
        $description,
        $category,
        $layout,
        $pin_count,
        $follower_count,
        $created_at,
        $last_pulled,
        $track_type,
        $timestamp;

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

    public $calc_board_histories = false;
    protected $_history_calc =false;

    /**
     * @author  Will
     *
     * @param string $time
     *
     * @return CalcBoardHistory
     */
    public function findCalculationBefore($time = 'now')
    {
        return CalcBoardHistory::find($this->board_id, $time);
    }

    /**
     * Gets the first calculation in the database instead of getting it by date
     *
     * @author  Will
     *
     * @return CalcBoardHistory
     */
    public function findFirstAvailableCalculation() {
        $STH = $this->DBH->prepare("
           select * from calcs_board_history
            where board_id = :board_id
            order by date ASC
            limit 1
        ");

        $STH->execute(array(
                           ':board_id'=>$this->board_id
                      )
        );

        if($STH->rowCount()== 0) {
            return false;
        }

        $calc = new CalcBoardHistory();
        $calc->loadDBData($STH->fetch());

        return $calc;
    }

    /**
     * Load up pinterest data into board object
     *
     * @author  Will
     * @author  Daniel
     * @author  Yesh
     */
    public function loadAPIData($data)
    {


        /*
         * The data comes as an std object from the multi curl
         * and as an array from the Pinterest class
         * so we cast it as an array to make it simpler
         */
        if (is_object($data)) {
            $data = (array)$data;
        }


        /*
         *If code isn't set, we are dealing with the data already and presumably already
         * checked to see if the response was valid
         */
        if (array_key_exists('code', $data)) {

            if ($data['code'] !== 0) {
                throw new PinterestException('No data to load into board', $data['code']);
            }

            /*
             * Again, with the array typecasting from ^^^
             */
            $data = (array)$data['data'];
        }

        /**
         * In the following if block we do a nested typecasting of
         * StdArray object to an Array object.
         */
        if (array_key_exists('id', $data)) {

            $owner_id = '';

            if (array_key_exists("owner", $data)) {
                if (is_object($data["owner"])){
                    $data["owner"] = (array)$data["owner"];
                }
                if (is_array($data['owner'])) {
                    if (array_key_exists("id", $data['owner'])) {
                        $owner_id = $data['owner']['id'];
                    }
                }
            }

            $this->board_id        = $data['id'];
            $this->owner_user_id   = $owner_id;
            $this->url             = $data['url'];
            $this->is_collaborator = $data['is_collaborative'];

            if ($this->user_id == $owner_id) {
                $this->is_owner = true;
            } else {
                $this->is_owner = false;
            }

            $this->collaborator_count = $data['collaborator_count'];
            $this->image_cover_url    = $data['image_cover_url'];
            $this->name               = $data['name'];
            $this->description        = $data['description'];
            $this->category           = $data['category'];
            $this->layout             = $data['layout'];
            $this->pin_count          = $data['pin_count'];
            $this->follower_count     = $data['follower_count'];
            $this->last_pulled        = time();
            $this->timestamp          = time();
            $this->created_at         = Pinterest::creationDateToTimeStamp($data['created_at']);

        } else {
            throw new PinterestException('There was a successful response, but no data to load');
        }

        return $this;
    }

    /**
     * Load up the latest Pinterest Data
     *
     * @author  Will
     */
    public function updateViaAPI()
    {
        $Pinterest = new Pinterest();
        $data      = $Pinterest->getBoardInformation($this->board_id);

        return $this->loadAPIData($data);
    }


    /**
     * @author   Will
     *
     * @param      $earliest
     * @param bool $latest
     *
     * @internal param $timeframe
     *
     * @return CalcBoardHistories
     */
    protected function getCalulcationDiff($earliest, $latest = false)
    {

        if ($latest === false) {
            $latest = time();
        }

        $calculations = new CalcBoardHistories();
        /**
         * This is the latest data we have on the boards
         * We don't want to run the board calcs in realtime, so if we don't have right now's
         * we'll use yesterday's
         */
        $latest_board_calc = $this->findCalculationBefore($latest);

        if (!$latest_board_calc) {
            Log::warning(
               'There is no board calculation available. Faking it.',
               $this
            );

            $latest_board_calc            = new CalcBoardHistory();
            $latest_board_calc->board_id  = $this->board_id;
            $latest_board_calc->comments  = 0;
            $latest_board_calc->followers = $this->follower_count;
            $latest_board_calc->date      = flat_date('day');
            $latest_board_calc->pins      = $this->pin_count;
            $latest_board_calc->repins    = 0;

        }

        $calculations->add($latest_board_calc, 'latest');

        Log::debug('Got latest board calc', $latest_board_calc);

        $early_board_calc = $this->findCalculationBefore($earliest);

        if (!$early_board_calc) {
            Log::notice(
               'Last weeks board history calculation was not available',
               $this
            );
            $early_board_calc = $this->findFirstAvailableCalculation();

            if (!$early_board_calc) {
                Log::warning('There was no board calculation at all. Crap');
                $early_board_calc = $latest_board_calc;
            }

        }

        $calculations->add($early_board_calc, 'earliest');

        Log::debug('Added early board calc', $early_board_calc);

        $calculations->sortBy('date', SORT_DESC);

        return $calculations;
    }

    /**
     * @param string $timeframe
     *
     * @return mixed
     */
    public function newPinsSince($timeframe = '7 days ago') {
        return $this->getCalulcationDiff($timeframe)->spread('pins');
    }

    /**
     * @author  Will
     * @param string $timeframe
     *
     * @return mixed
     */
    public function newRepinsSince($timeframe = '7 days ago') {
        return $this->getCalulcationDiff($timeframe)->spread('repins');
    }

    /**
     * @author  Will
     * @param string $timeframe
     *
     * @return mixed
     */
    public function newLikesSince($timeframe = '7 days ago') {
        return $this->getCalulcationDiff($timeframe)->spread('likes');
    }

    /**
     * @author  Will
     * @param string $timeframe
     *
     * @return mixed
     */
    public function newCommentsSince($timeframe = '7 days ago') {
        return $this->getCalulcationDiff($timeframe)->spread('comments');
    }

    /**
     * @author  Will
     * @param string $timeframe
     *
     * @return mixed
     */
    public function newFollowersSince($timeframe = '7 days ago') {
        return $this->getCalulcationDiff($timeframe)->spread('followers');
    }

    /**
     * @author Will
     *
     * @param int $limit
     *
     * @return Pins
     */
    public function pins($limit = 500) {

        $rows = DB::select("select * from data_pins_new where board_id = ? LIMIT $limit ",array($this->board_id));

        $pins = new Pins();

        foreach ($rows as $pin_data) {
            $pin = Pin::createFromDBData($pin_data);
            $pins->add($pin);
        }

        return $pins;

    }

    /**
     * Get the history calcs we may or may not have done
     *
     * @author  Will
     *
     * @return CalcBoardHistory
     */
    public function getLastHistoryCalc()
    {
        if (empty($this->_history_calc)) {
            $this->_history_calc = CalcBoardHistory::find($this->board_id, 'latest');
        }

        return $this->_history_calc;
    }

    /**
     * Get all history calcs
     *
     * @author  Will
     *
     * @return CalcBoardHistory
     */
    public function getAllHistoryCalcs()
    {
        return CalcBoardHistories::all($this);
    }


    /**
     * @author  Will
     *
     * @return string
     */
    public function categoryName()
    {
        if (!is_null($this->category)) {
            return str_replace('_','',Str::title($this->category));
        }

        return '';
    }

    /**
     * @ Will
     * @return string
     */
    public function viralityScore() {
        if ($this->getLastHistoryCalc()) {
            return $this->getLastHistoryCalc()->viralityScore();
        }
        return false;
    }

    /**
     * @author  Will
     * @return mixed
     */
    public function getRepins() {
        return $this->getLastHistoryCalc()->repins;
    }

    /**
     * @author  Will
     * @return Profile | bool
     */
    public function getOwnerProfile() {
       return Profile::find($this->owner_user_id);
    }
}

class BoardException extends DBModelException {}
