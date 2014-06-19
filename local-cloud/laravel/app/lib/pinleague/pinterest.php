<?php namespace Pinleague;

use
    Board,
    Config,
    DatabaseInstance,
    PDO,
    Pin,
    Pinleague\Pinterest\BasePinterest,
    Pinleague\Pinterest\Transports\CurlAdapter;

/**
 * Class Pinterest
 *
 * This is the Pinterest class with some Tailwind specific code that probably
 * shouldn't be included with a more generic Pinterest Class
 *
 * @package Pinleague
 */
class Pinterest extends BasePinterest
{
    /**
     * The thresholds for the number of calls we can make in a given hour
     * before the calls are wrapped in sleep methods
     */
    const HIGH_THRESHOLD   = 85000;
    const NORMAL_THRESHOLD = 75000;
    const LOW_THRESHOLD    = 70000;
    /**
     * Number of seconds to wait if above threshold
     */
    const HIGH_SLEEP   = 5;
    const NORMAL_SLEEP = 10;
    const LOW_SLEEP    = 10;
    /**
     * For each individual call, we pass a priority level for the above
     * thresholds
     */
    const DO_NOT_DELAY    = 0;
    const HIGH_PRIORITY   = 1;
    const NORMAL_PRIORTIY = 2;
    const LOW_PRIORITY    = 3;
    /**
     * The database handle
     *
     * @var \PDO
     */
    public $DBH;
    /**
     * The number of api calls we've made to Pinterest in this hour
     *
     * @var int
     */
    protected $api_calls_this_hour = 0;

    /**
     * Construct
     *
     * @author  Will
     */
    public function __construct($database_handle = null, $alt = "")
    {
        parent::__construct(
              Config::get("pinterest." . $alt . "client_id"),
              Config::get("pinterest." . $alt . "secret"),
              Config::get("pinterest." . $alt . "call_back_uri"),
              new CurlAdapter
        );

        $this->DBH = is_null($database_handle) ? DatabaseInstance::DBO() : $database_handle;

        /*
         * We want to make sure we are handling the thresholds correctly,
         * so we check with our DB to make sure we are within the limit
         */
        $calls = self::totalCallsRecorded(
                     $hours_to_show = 1,
                     Config::get("pinterest." . $alt . "client_id"),
                     'include the current hour'
        );

        if ($calls) {
            $this->api_calls_this_hour = $calls[0];
        }
    }

    /**
     * Gets a persistent instance
     *
     * @author   Will
     *
     * @param bool $use_new_client_id
     *
     * @internal param bool $id
     *
     * @returns self
     */
    public static function getInstance($use_new_client_id = false)
    {
        if ($use_new_client_id) {
            if (!self::$instance) {
                return self::$instance = new self(null, 'new.');
            }
            return self::$instance;
        }

        if (!self::$instance) {
            return self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get the current number of calls
     *
     * @author  Will
     */
    public static function totalCallsRecorded(
        $hours_to_show,
        $client_id = false,
        $include_current_hour = false,
        PDO $database_handle = null
    )
    {
        $DBH = is_null($database_handle) ? DatabaseInstance::DBO() : $database_handle;

        $where_clause = "";
        if ($client_id) {
            $where_clause = "where client_id = $client_id";
        }

        $api_rates = $DBH->query("SELECT datetime,SUM(calls) calls
                                        FROM status_api_calls
                                        $where_clause
                                        GROUP BY datetime
                                        ORDER BY datetime DESC
                                        LIMIT $hours_to_show")
                         ->fetchAll();

        $calls = array();
        foreach ($api_rates as $rate) {
            $calls[] = $rate->calls;
        }

        if (!$include_current_hour) {
            unset($calls[0]);
        }

        return $calls;
    }

    /**
     * Get the board information via the board id
     *
     * @author   Will
     *
     * @param       $board_id
     * @param array $parameters
     * @param int   $priority
     *
     * @return mixed
     */
    public function getBoardInformation(
        $board_id,
        $parameters = array(),
        $priority = self::DO_NOT_DELAY
    )
    {
        $this->delayCall($priority);
        $data = parent::getBoardInformation($board_id, $parameters);

        $this->incrementCallRateLimit(1, __FUNCTION__);

        return $data;
    }

    /**
     * Get the pins of a given board
     *
     * @author   Will
     *
     * @param       $board_id
     * @param array $parameters
     * @param int   $priority
     *
     * @return mixed
     */
    public function getBoardsPins(
        $board_id,
        $parameters = array(),
        $priority = self::DO_NOT_DELAY
    )
    {
        $this->delayCall($priority);
        $data = parent::getBoardsPins($board_id, $parameters);

        $this->incrementCallRateLimit(1, __FUNCTION__);

        return $data;
    }

    /**
     * Get the pins of a given board
     *
     * @author   Will
     *
     * @param       $board_id
     * @param array $parameters
     * @param int   $priority
     *
     * @return mixed
     */
    public function getBoardFollowers(
        $board_id,
        $parameters = array(),
        $priority = self::DO_NOT_DELAY
    )
    {
        $this->delayCall($priority);
        $data = parent::getBoardFollowers($board_id, $parameters);

        $this->incrementCallRateLimit(1, __FUNCTION__);

        return $data;
    }

    /**
     * @author   Will
     *
     * @param       $domain
     * @param array $parameters
     * @param int   $priority
     *
     * @return array
     */
    public function getBrandMentions(
        $domain,
        $parameters = array(),
        $priority = self::DO_NOT_DELAY
    )
    {
        $this->delayCall($priority);
        $data = parent::getBrandMentions($domain, $parameters);

        $this->incrementCallRateLimit(1, __FUNCTION__);

        return $data;
    }

    /**
     *
     *  THIS IS an ALIAS OF getBrandMentions above
     *
     * @author  Alex
     *
     * @param       $domain
     * @param array $parameters_inputted
     *
     * @param int   $priority
     *
     * @return mixed
     */
    public function getDomainPins(
        $domain,
        $parameters_inputted = array(),
        $priority = self::DO_NOT_DELAY
    )
    {
        return $this->getBrandMentions($domain,$parameters_inputted,$priority);
    }

    /**
     * @author  Alex
     *
     * @author   Yesh
     * @author   Will
     *
     * @param string $category_name
     * @param array  $parameters
     * @param int    $priority
     *
     * @return array
     */
    public function getCategoryFeed(
        $category_name,
        $parameters = array(),
        $priority = self::DO_NOT_DELAY
    )
    {
        $this->delayCall($priority);
        $data = parent::getCategoryFeed($category_name, $parameters);

        $this->incrementCallRateLimit(1, __FUNCTION__);

        return $data;
    }

    /**
     * @author   Will
     *
     * @param       $pin_id
     * @param array $parameters
     * @param int   $priority
     *
     * @return array
     */
    public function getPinComments(
        $pin_id,
        $parameters = array(),
        $priority = self::DO_NOT_DELAY
    )
    {
        $this->delayCall($priority);
        $data = parent::getPinComments($pin_id, $parameters);

        $this->incrementCallRateLimit(1, __FUNCTION__);

        return $data;
    }

    /**
     * @author   Will
     *
     * @param       $pin_id
     * @param array $parameters
     * @param int   $priority
     *
     * @return array
     */
    public function getPinInformation(
        $pin_id,
        $parameters = array(),
        $priority = self::DO_NOT_DELAY
    )
    {
        $this->delayCall($priority);
        $data = parent::getPinInformation($pin_id, $parameters);

        $this->incrementCallRateLimit(1, __FUNCTION__);

        return $data;
    }

    /**
     * @author   Will
     *
     * @param       $pin_id
     * @param array $parameters
     * @param int   $priority
     *
     * @return array
     */
    public function getPinLikes(
        $pin_id,
        $parameters = array(),
        $priority = self::DO_NOT_DELAY
    )
    {
        $this->delayCall($priority);
        $data = parent::getPinLikes($pin_id, $parameters);

        $this->incrementCallRateLimit(1, __FUNCTION__);

        return $data;
    }

    /**
     * @author   Will
     *
     * @param int   $pin_id
     * @param array $parameters
     * @param int   $priority
     *
     * @return array
     */
    public function getPinRepins(
        $pin_id,
        $parameters = array(),
        $priority = self::DO_NOT_DELAY
    )
    {
        $this->delayCall($priority);
        $data = parent::getPinRepins($pin_id, $parameters);

        $this->incrementCallRateLimit(1, __FUNCTION__);

        return $data;
    }

    /**
     * @author   Will
     *
     * @param       $username
     * @param array $parameters
     * @param int   $priority
     *
     * @return array
     */
    public function getProfileBoards(
        $username,
        $parameters = array(),
        $priority = self::DO_NOT_DELAY
    )
    {
        $this->delayCall($priority);
        $data = parent::getProfileBoards($username, $parameters);

        $this->incrementCallRateLimit(1, __FUNCTION__);

        return $data;
    }

    /**
     * @author   Will
     *
     * @param       $username
     * @param array $parameters
     * @param int   $priority
     *
     * @return array
     */
    public function getProfileFollowers(
        $username,
        $parameters = array(),
        $priority = self::DO_NOT_DELAY
    )
    {
        $this->delayCall($priority);
        $data = parent::getProfileFollowers($username, $parameters);

        $this->incrementCallRateLimit(1, __FUNCTION__);

        return $data;
    }

    /**
     * @author   Will
     *
     * @param       $username
     * @param array $parameters
     * @param int   $priority
     *
     * @return array
     */
    public function getProfileFollowing(
        $username,
        $parameters = array(),
        $priority = self::DO_NOT_DELAY
    )
    {
        $this->delayCall($priority);
        $data = parent::getProfileFollowing($username, $parameters);

        $this->incrementCallRateLimit(1, __FUNCTION__);

        return $data;
    }

    /**
     * @author   Will
     *
     * @param       $username
     * @param array $parameters
     * @param int   $priority
     *
     * @return array
     */
    public function getProfileInformation(
        $username,
        $parameters = array(),
        $priority = self::DO_NOT_DELAY
    )
    {
        $this->delayCall($priority);
        $data = parent::getProfileInformation($username, $parameters);

        $this->incrementCallRateLimit(1, __FUNCTION__);

        return $data;
    }

    /**
     * @author  Alex
     * @author  Will
     *
     * @param array $pin_ids_or_pin_id
     * @param int $priority
     *
     * @return array
     *
     */
    public function getPublicPinInformation(
        $pin_ids_or_pin_id,
        $priority = self::DO_NOT_DELAY
    )
    {
        $this->delayCall($priority);
        $data = parent::getPublicPinInformation($pin_ids_or_pin_id);

        $this->incrementCallRateLimit(1, __FUNCTION__, 'public');

        return $data;
    }

    /**
     * @author   Will
     *
     * @param       $keyword
     * @param array $parameters
     * @param int   $priority
     *
     * @return array
     */
    public function getSearchBoardsFromKeyword(
        $keyword,
        $parameters = array(),
        $priority = self::DO_NOT_DELAY
    )
    {
        $this->delayCall($priority);
        $data = parent::getSearchBoardsFromKeyword($keyword, $parameters);

        $this->incrementCallRateLimit(1, __FUNCTION__);

        return $data;
    }

    /**
     * @author   Will
     *
     * @param       $keyword
     * @param array $parameters
     * @param int   $priority
     *
     * @return array
     */
    public function getSearchPinsFromKeyword(
        $keyword,
        $parameters = array(),
        $priority = self::DO_NOT_DELAY
    )
    {
        $this->delayCall($priority);
        $data = parent::getSearchPinsFromKeyword($keyword, $parameters);

        $this->incrementCallRateLimit(1, __FUNCTION__);

        return $data;
    }

    /**
     * @author   Will
     *
     * @param       $username
     * @param array $parameters
     * @param int   $priority
     *
     * @return array
     */
    public function getUserIDFromUsername(
        $username,
        $parameters = array(),
        $priority = self::DO_NOT_DELAY
    )
    {
        $this->delayCall($priority);
        $data = parent::getUserIDFromUsername($username, $parameters);

        $this->incrementCallRateLimit(1, __FUNCTION__);

        return $data;
    }

    /**
     * @author   Will
     *
     * @param       $user_id
     * @param array $parameters
     * @param int   $priority
     *
     * @return array
     */
    public function getUserPins(
        $user_id,
        $parameters = array(),
        $priority = self::DO_NOT_DELAY
    )
    {
        $this->delayCall($priority);
        $data = parent::getUserPins($user_id, $parameters);

        $this->incrementCallRateLimit(1, __FUNCTION__);

        return $data;
    }

    /**
     * @param $board_id
     * @param $image_url
     * @param $description
     * @param $source_url
     *
     * @return \Pin
     */
    public function putPin($board_id,$image_url,$description,$source_url) {
        $response = parent::putPin($board_id,$image_url,$description,$source_url);

        $pin = new Pin;
        $pin->loadAPIData($response);
        $pin->setTrackType('user');

        return $pin;
    }

    /**
     * @param      $name
     * @param      $description
     * @param      $category
     * @param bool $privacy
     *
     * @return \Board
     */
    public function putBoard($name,$description,$category,$privacy) {
        $response = parent::putBoard($name,$description,$category,$privacy);

        $board = new Board();
        $board->loadAPIData($response);
        $board->track_type = 'user';

        return $board;
    }

    /**
     * @author  Will
     *
     * @param $pin_id
     * @param $board_id
     * @param $description
     *
     * @return \Pin
     */
    public function postRepin($pin_id,$board_id,$description) {
        $response = parent::postRepin($pin_id,$board_id,$description);

        $pin = new Pin;
        $pin->loadAPIData($response);
        $pin->setTrackType('user');

        return $pin;
    }

    /**
     * If we need to delay the call due to having a lot of calls
     *
     * @param $priority
     */
    protected function delayCall($priority)
    {
        switch ($priority) {
            default:
                //do nothing
                break;

            case self::HIGH_PRIORITY:

                if ($this->api_calls_this_hour > self::HIGH_THRESHOLD) {
                    sleep(self::HIGH_SLEEP);
                }
                break;

            case self::NORMAL_PRIORTIY:
                if ($this->api_calls_this_hour > self::NORMAL_THRESHOLD) {
                    sleep(self::NORMAL_SLEEP);
                }
                break;

            case self::LOW_PRIORITY:
                if ($this->api_calls_this_hour > self::LOW_THRESHOLD) {
                    sleep(self::LOW_SLEEP);
                }
                break;
        }
    }

    /**
     * Increases our count of the number of calls made
     *
     * @author John
     * @author Will
     * @author Yesh
     *
     */
    protected function incrementCallRateLimit($calls = 1, $method, $client_id = null)
    {
        $client_id = is_null($client_id) ? $this->client_id : $client_id;

        /*
         * Add to internal count
         */
        if ($client_id != 'public') {
            $this->api_calls_this_hour += $calls;
        }

        /*
         * Save calls to the db
         */
        $datetime = $this->getFlatDateHour(time());
        $STH      = $this->DBH->prepare(
                              "INSERT INTO status_api_calls (datetime, calls, method, client_id)
                               VALUES (:datetime, :calls, :method, :client_id)
                               ON DUPLICATE KEY UPDATE
                               calls = calls + :calls, method = :method"
        );

        $STH->execute(
            array(
                 ":datetime"  => $datetime,
                 ":calls"     => $calls,
                 ":method"    => $method,
                 ":client_id" => $client_id
            )
        );

        return $calls;
    }

    /**
     * @author  John
     *
     * @param $t
     *
     * @return int
     */
    protected function getFlatDateHour($t)
    {
        return mktime(
            date("G", $t),
            0,
            0,
            date("n", $t),
            date("j", $t),
            date("Y", $t)
        );
    }


}
