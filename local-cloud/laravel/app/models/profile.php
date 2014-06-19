<?php

use
    Carbon\Carbon,
    Caches\EngagementInfluencer,
    Caches\EngagementInfluencers,
    Pinleague\Pinterest;

/**
 * Profile Model
 *
 * data_profiles_new
 *
 * @author  Will
 * @author  John
 */
class Profile extends PDODatabaseModel
{
    /*
    |--------------------------------------------------------------------------
    | Table Schema info
    |--------------------------------------------------------------------------
    */
    public
        $table = 'data_profiles_new',
        $columns =
        array(
            'user_id',
            'username',
            'first_name',
            'last_name',
            'email',
            'image',
            'about',
            'domain_url',
            'domain_verified',
            'website_url',
            'facebook_url',
            'twitter_url',
            'google_plus_url',
            'location',
            'board_count',
            'pin_count',
            'like_count',
            'follower_count',
            'following_count',
            'created_at',
            'gender',
            'p_gender',
            'locale',
            'last_pulled',
            'track_type',
            'timestamp'
        ),
        $primary_keys = array('user_id');

    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    public
        /**
         * Pinterest declared id
         *
         * @var $user_id
         */
        $user_id,
        /**
         * The username associated with profile, maximum 15 characters
         *
         * @var $username
         */
        $username,
        /**
         * The first name associated with the username
         *
         * @var $first_name
         */
        $first_name,
        /**
         * The last name associated with the username
         *
         * @var $last_name
         */
        $last_name,
        /**
         * Pinterest used to return an email address, but no longer does.
         * If we can get it somewhere, we will try to keep it
         *
         * @var $email
         */
        $email,
        /**
         * The url of the the image associated with this account
         *
         * @var $image
         */
        $image,
        $about,
        $domain_url,
        $domain_verified,
        $website_url,
        $facebook_url,
        $twitter_url,
        $google_plus_url,
        $location,
        $board_count,
        $pin_count,
        $like_count,
        $follower_count,
        $following_count,
        /**
         * The last time it was put on the queue to be updated.
         * Last pulled is 0 when it is started
         *
         * @var $last_pulled
         */
        $last_pulled,
        /**
         * The epoch timestamp of when the Pinterest profile was created, from Pinterest
         *
         * @var $created_at
         */
        $created_at,
        /**
         * Our calculated estimate of the profile's gender based on an analysis of the prfoile's
         * first name
         *
         * @var $gender
         */
        $gender,
        /**
         * The user inputted gender from Pinterest, which first appeared in early November 2013
         *
         * @var $p_gender
         */
        $p_gender,
        /**
         * The locale of the user's facebook account, if imported.
         *
         * @var $locale
         */
        $locale,
        /**
         *
         * @var $track_type
         */
        $track_type,
        /**
         * The last time this was updated
         *
         * @var $timestamp
         */
        $timestamp;

    /*
    |--------------------------------------------------------------------------
    | Cached Fields
    |--------------------------------------------------------------------------
    */
    protected
        $_pins = false,
        $_api_pins = false,
        $_boards = false,
        $_api_boards = false,
        $_history_calc = false;
    protected $pinterest;

    /**
     * Construct
     * for new profiles / instances
     *
     * @author   John
     * @author   Will
     */
    public function __construct()
    {
        parent::__construct();
        $this->pinterest = new Pinterest();
    }

    /**
     * Load a profile from a username
     * for creating an instance of a username not in our DB
     *
     * @author  Will
     *
     * @param $username
     *
     * @return Profile
     */
    public static function createViaApi($username)
    {
        $username = self::removeUnacceptableUsernameCharacters($username);

        $profile           = new Profile();
        $profile->username = strtolower($username);
        $profile->updateViaAPI();

        return $profile;
    }

    /**
     * @author  Will
     */
    public static function removeUnacceptableUsernameCharacters($username)
    {
        $username = trim($username);
        $username = str_replace(' ', '', $username);

        return $username;
    }

    /**
     * Load up the latest pinterest Data
     *
     * @author  Will
     */
    public function updateViaAPI()
    {
        $data = $this->pinterest->getProfileInformation($this->username);
        $this->loadAPIData($data);

        return $this;
    }

    /**
     * @author  Will
     *
     * @param $data
     *
     * @return $this
     * @throws ProfileException
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

        if (isset($data['code'])) {
            if (($data['code']) > 0) {
                throw new ProfileException('No data to load into profile', $data['code']);
            }
        }

        /*
         * Again, with the array typecasting from ^^^
         */
        if (isset($data['data'])) {
            $data = (array)$data['data'];
        } else {
            $data = (array)$data;
        }

        if (array_key_exists('id', $data)) {
            $this->user_id         = $data['id'];
            $this->username        = $data['username'];
            $this->first_name      = $data['first_name'];
            $this->last_name       = $data['last_name'];
            $this->image           = $data['image_medium_url'];
            $this->about           = $data['about'];
            $this->domain_url      = $data['domain_url'];
            $this->domain_verified = $data['domain_verified'];
            $this->website_url     = $data['website_url'];
            $this->facebook_url    = $this->processFacebook($data['facebook_url']);
            $this->twitter_url     = $this->processTwitter($data['twitter_url']);
            $this->google_plus_url = $this->processGooglePlus($data['gplus_url']);
            $this->location        = $data['location'];
            $this->board_count     = $data['board_count'];
            $this->pin_count       = $data['pin_count'];
            $this->like_count      = $data['like_count'];
            $this->follower_count  = $data['follower_count'];
            $this->following_count = $data['following_count'];
            $this->p_gender        = $data['gender'];
            $this->last_pulled     = time();
            $this->created_at      = Pinterest::creationDateToTimeStamp($data['created_at']);
        } else {
            throw new ProfileException('There was a successful response, but no data to load');
        }

        return $this;
    }

    /**
     * Normalize FB string into FB userid or username
     *
     * @param $fb
     *
     * @return mixed|string
     *
     * @author John
     */
    protected function processFacebook($fb)
    {
        if (!$fb) {
            return "";
        }

        //"http://www.facebook.com/john.david.busch"
        $fb = str_replace("https://", "", strtolower($fb));
        $fb = str_replace("http://", "", strtolower($fb));
        $fb = str_replace("www.", "", $fb);
        $fb = str_replace("facebook.com", "", $fb);
        $fb = str_replace("/", "", $fb);
        $fb = str_replace("profile.php?id=", "", $fb);

        return $fb;
    }

    /**
     * Normalize TW string into WT userid or username
     *
     * @param $tw
     *
     * @return mixed|string
     *
     * @author John
     */
    protected function processTwitter($tw)
    {
        if (!$tw) {
            return "";
        }
        //"http://twitter.com/RainyBoat/"
        $tw = str_replace("https://", "", strtolower($tw));
        $tw = str_replace("http://", "", strtolower($tw));
        $tw = str_replace("www.", "", $tw);
        $tw = str_replace("twitter.com", "", $tw);
        $tw = str_replace("/", "", $tw);

        return $tw;
    }

    /**
     * @author  Will
     *
     * @param $url
     *
     * @input   https://plus.google.com/100783813088885394550
     *
     * @return string
     */
    protected function processGooglePlus($url)
    {

        $pieces = parse_url($url);

        return str_replace('/', '', $pieces['path']);
    }

    /**
     * Find in DB and load as Profile object
     *
     * @author  Will
     *
     * @param $usernameOrUserId
     *
     * @throws ProfileException
     * @throws ProfileNotFoundException
     *
     * @return Profile
     */
    public static function findInDB($usernameOrUserId)
    {
        $usernameOrUserId = self::removeUnacceptableUsernameCharacters($usernameOrUserId);
        $DBH              = DatabaseInstance::DBO();

        /*
         * If its an int  we assume we are looking by userid
         */
        if (is_numeric($usernameOrUserId)) {

            $STH = $DBH->prepare('select * from data_profiles_new where user_id = :x order by last_pulled DESC');

        } /*
             *  If its a string we assume we're looking by a username
             */
        elseif (is_string($usernameOrUserId)) {

            $STH = $DBH->prepare('select * from data_profiles_new where username = :x order by last_pulled DESC');

        } else {
            throw new ProfileException('Find requires a string or an integer');
        }

        $STH->bindValue(':x', $usernameOrUserId);
        $STH->execute();

        if ($STH->rowCount() == 0) {
            throw new ProfileNotFoundException('Could not find the profile in our DB', $usernameOrUserId);
        }

        $profile = new Profile();
        $profile->loadDBData($STH->fetch());

        return $profile;
    }

    /**
     * Returns if the username is valid or not
     *
     * @author  Will
     *
     * @todo    actually validate it
     *
     * @param $username
     *
     * @returns string
     */
    public static function validateUsername($username)
    {
        return $username;
    }

    /**
     * @author  Will
     */
    public function calculateAveragePinsPerDay()
    {
        return $this->calculateAverage('pins', 'day');
    }

    /**
     * @author  Will
     *
     * @param $type
     * @param $timeframe
     *
     * @throws ProfileException
     * @return int
     */
    protected function calculateAverage($type, $timeframe)
    {

        switch ($type) {

            case 'repins':
                $metric = $this->getRepinCount();
                break;

            case 'likes':
                $metric = $this->like_count;
                break;

            case 'pins':
                $metric = $this->pin_count;
                break;

            case 'followers':
                $metric = $this->follower_count;
                break;

            default:
                throw new ProfileException("Not able to find the average $type per $timeframe");
                break;

        }

        if ($metric == 0) {
            return 0;
        }

        $difference = time() - $this->created_at;
        $days       = floor(((($difference / 60) / 60) / 24));
        $weeks      = floor($days / 7);
        $months     = floor($weeks / 4);


        switch ($timeframe) {
            case'day':

                return $metric / $days;

                break;

            case 'week':

                return $metric / $weeks;

                break;

            case'month':

                return $metric / $months;

                break;

            default:
                throw new ProfileException("Can't calculate average $type/$timeframe");
                break;
        }
    }

    /**
     * Get the number of repins
     * from calcs_profile_history
     *
     * @author  Will
     *
     */
    public function getRepinCount()
    {
        if ($this->getLastHistoryCalc() === null) {
            return null;
        }

        return $this->getLastHistoryCalc()->repin_count;
    }

    /**
     * Get the history calcs we may or may not have done
     *
     * @author  Will
     *
     * @return CalcProfileHistory
     */
    public function getLastHistoryCalc()
    {
        if ($this->_history_calc === false) {
            $this->_history_calc = CalcProfileHistory::find($this->user_id, 'latest');
        }

        return $this->_history_calc;
    }

    /**
     * @return CalcProfileHistories
     */
    public function getAllHistoryCalcs() {
        return CalcProfileHistories::all($this);
    }

    /**
     * @author  Will
     */
    public function calculateAveragePinsPerMonth()
    {
        return $this->calculateAverage('pins', 'month');

    }

    /**
     * @author  Will
     */
    public function calculateAveragePinsPerWeek()
    {
        return $this->calculateAverage('pins', 'week');
    }

    /**
     * @author  Will
     */
    public function calculateAverageLikesPerDay()
    {
        return $this->calculateAverage('likes', 'day');
    }

    /**
     * @author  Will
     */
    public function calculateAverageLikesPerMonth()
    {
        return $this->calculateAverage('likes', 'month');

    }

    /**
     * @author  Will
     */
    public function calculateAverageLikesPerWeek()
    {
        return $this->calculateAverage('likes', 'week');
    }

    /**
     * @author  Will
     */
    public function calculateAverageRepinsPerDay()
    {
        return $this->calculateAverage('repins', 'day');
    }

    /**
     * @author  Will
     * @return int
     */
    public function calculateAverageRepinsPerMonth()
    {
        return $this->calculateAverage('repins', 'month');
    }

    /**
     * @author  Will
     * @return int
     */
    public function calculateAverageRepinsPerWeek()
    {
        return $this->calculateAverage('repins', 'week');
    }

    /**
     * Influence Score
     *
     * @author  Will
     * @author  Alex
     *
     * @todo    0 cases for pin count and follower_count
     *
     * @return int
     *
     */
    public function calculateInfluenceScore()
    {
        if ($this->getRepinCount() === null) {
            $repins = 0;
        } else {
            $repins = $this->getRepinCount();
        }


        if ($this->getReach() === null || $this->getReach() == 0) {
            $reach = 0;
        } else {
            $reach = log10($this->getReach());
        }

        $influence =
            0.001 //the wash coeffcient
            + (0.08 * log10($this->follower_count))
            + (0.15 * log10($repins / $this->pin_count))
            + (0.005 * $reach);

        /*
         * We don't want to get an infinite number here
         * so we just return "calculating"
         * math - crazy AMIRITE?
         */
        if (is_infinite($influence)) {
            return 0;
        } else {
            return number_format(($influence * 100), 2);
        }
    }

    /**
     * Get Reach
     *
     * @author  Will
     *
     */
    public function getReach()
    {
        if ($this->getLastHistoryCalc() === null) {
            return null;
        }

        return $this->getLastHistoryCalc()->reach;
    }

    /**
     * @author Will
     */
    public function calculateLikesPerPin()
    {
        if ($this->pin_count == 0) {
            return 0;
        }

        return round($this->like_count / $this->pin_count);
    }

    /**
     * @author Will
     */
    public function calculatePinsPerBoard()
    {
        if ($this->board_count == 0) {
            return 0;
        }

        return round($this->pin_count / $this->board_count);
    }

    /**
     * Uses the Pinterest API to get everything we need for our history calculation
     * and then calculates it
     *
     * @author  Will
     * @return CalcProfileHistory
     */
    public function calculateAPIProfileHistory()
    {
        /*
         * Uses the Pinterest API to grab boards
         * stores them in the database
         */
        $boards = $this->getAPIBoards();
        $boards->setPropertyOfAllModels('track_type', $this->track_type);
        $boards->insertUpdateDB();

        /*
         * Uses the boards from the API (above), finds which one the username owns,
         * finds the pins of those boards via the API
         * and then saves those to the DB
         */
        $pins = $this->getAPIPinsFromOwnedBoards();
        $pins->setPropertyOfAllModels('track_type', $this->track_type);
        $pins->insertUpdateDB();

        $latest = $this->calculateProfileHistory();

        /*
         * Save the calc to the DB
         */
        $latest->insertUpdateDB();

        return $latest;
    }

    /**
     * Get cached api Boards if they exist
     *
     * @author  Will
     *
     * @return \Boards
     */
    public function getAPIBoards()
    {
        if ($this->_api_boards == false) {
            $this->fetchAPIBoards();
        }

        return $this->_api_boards;
    }

    /**
     * Get the api pins for owned boards
     *
     * @author  Will
     * @returns \Pins
     */
    public function getAPIPinsFromOwnedBoards()
    {
        return $this->getOwnedAPIBoards()->fetchPins();
    }

    /**
     * Calculate everything that goes into the calcs_profile_history table
     *
     * @todo add config option to disgreat follower followers
     *
     * @return CalcProfileHistory
     */
    public function calculateProfileHistory()
    {
        $STH = $this->DBH->prepare("
                select
                SUM(repin_count) as repin_count,
                SUM(like_count) as like_count,
                SUM(comment_count) as comment_count,
                SUM(case when repin_count > 0 then 1 else 0 end) as at_least_one_repin,
                SUM(case when like_count > 0 then 1 else 0 end) as at_least_one_like,
                SUM(case when comment_count > 0 then 1 else 0 end) as at_least_one_comment,
                SUM(case when (repin_count > 0 OR like_count > 0 OR comment_count > 0) then 1 else 0 end) as at_least_one_engagement
                from data_pins_new where user_id = :user_id
            ");

        $STH->execute(array('user_id' => $this->user_id));
        $calcs = $STH->fetch();

        $calc                           = new CalcProfileHistory();
        $calc->user_id                  = $this->user_id;
        $calc->date                     = flat_date('day');
        $calc->follower_count           = $this->follower_count;
        $calc->following_count          = $this->following_count;
        $calc->reach                    = 0;
        $calc->board_count              = $this->board_count;
        $calc->pin_count                = $this->pin_count;
        $calc->repin_count              = $calcs->repin_count;
        $calc->like_count               = $calcs->like_count;
        $calc->comment_count            = $calcs->comment_count;
        $calc->pins_atleast_one_repin   = $calcs->at_least_one_repin;
        $calc->pins_atleast_one_like    = $calcs->at_least_one_like;
        $calc->pins_atleast_one_comment = $calcs->at_least_one_comment;
        $calc->pins_atleast_one_engage  = $calcs->at_least_one_engagement;
        $calc->timestamp                = time();
        $calc->estimate                 = 0;

        return $calc;
    }

    /**
     * Get a new copy of the API boards
     *
     * @author  Will
     *
     * @return $this;
     */
    public function fetchAPIBoards()
    {
        $api_boards = $this->pinterest->getProfileBoards($this->username);

        $boards            = Boards::createFromApiDataSet($api_boards, $this->user_id);
        $this->_api_boards = $boards;

        return $this;
    }

    /**
     * Boards object of boards where the owner is this profile id
     *
     * @author  Will
     *
     * @returns Boards collection
     *
     */
    public function getOwnedAPIBoards()
    {
        $owned_boards = $this->getAPIBoards();

        foreach ($owned_boards as $key => $board) {
            if ($board->owner_user_id != $this->user_id) {
                $owned_boards->removeModel($key);
            }
        }

        return $owned_boards;
    }

    /**
     * Calculate reach
     * Reach is the sum of a profiles followers...followers
     *
     * @author  Will
     *
     * @todo
     *
     */
    public function calculateReach()
    {
        return 0;
    }

    /**
     * Repins per pin
     *
     * @author  Will
     */
    public function calculateRepinsPerPin()
    {
        if ($this->pin_count == 0) {
            return 0;
        }

        return round($this->getRepinCount() / $this->pin_count);
    }

    /**
     * @author  Will
     *          if we change the track type of the profile
     *          we also want to change the track type of the
     *          status profile follower
     *          status profile pins
     *          staus profile
     *          tables
     *
     * @param $track_type
     *
     * @return $this
     */
    public function changeTrackType($track_type)
    {
        $this->track_type = $track_type;

        $params = array(
            ':track_type' => $track_type,
            ':user_id'    => $this->user_id
        );

        /*
         * Status profiles
         */
        $STH = $this->DBH->prepare("
                      update status_profiles
                      set track_type = :track_type
                      where user_id = :user_id
                    ");
        $STH->execute($params);

        /*
         * Status profile pins
         */
        $STH = $this->DBH->prepare("
                      update status_profile_pins
                      set track_type = :track_type
                      where user_id  =:user_id
                    ");
        $STH->execute($params);

        /*
        * Status profile_followers
        */
        $STH = $this->DBH->prepare("
                      update status_profile_followers
                      set track_type = :track_type
                      where user_id = :user_id
                    ");
        $STH->execute($params);

        /*
         * Data profiles new
         */
        $STH = $this->DBH->prepare("
                      update data_profiles_new
                      set track_type = :track_type
                      where user_id = :user_id
                    ");
        $STH->execute($params);

        return $this;
    }

    /**
     * @author  Will
     *          Alex
     *
     * @param $track_type
     */
    public function changeStatusBoardsTrackType($track_type)
    {

        /**
         * When updating the track_type for status_boards records, we split it up into 3 buckets
         *
         *      1. Where owner_user_id is the current user's user_id, and is_owned = 1 (the latter
         *          should always be true in this case anyway).
         *
         *                  => UPDATE track_type to new track_type
         *
         *      2. where the current user_id is associated with the board (not necessarily owning
         *          it), and is_owned = 0 (meaning that no other customers are owners of this board)
         *
         *                  => UPDATE track_type to new track_type
         *
         *      3. where the current user_id is associated with the board and is_owned = 1
         *          (meaning that another customer actually owned the board)
         *
         *
         *                  => If changing to "user"       :: update track_type to "user"
         *                  => If changing to "competitor" :: update to "competitor" only
         *                                                   if it's currently "free"
         *                  => If changing to "free"       :: do nothing
         *
         *
         *      REFERENCE
         *          - data_boards.user_id    => the user_id where board is found on the profile
         *                                     (can be owned, or not owned)
         *          - data_boards.is_owner   => if user_id = owner_user_id
         *          - status_boards.is_owned => if any of our customers own this board
         */


        /**
         * Bucket #1
         */
        $STH = $this->DBH->prepare("
                      UPDATE status_boards
                      SET track_type = :status_track_type
                      WHERE owner_user_id = :owner_user_id
                      AND is_owned = 1
                    ");

        $params = array(
            ':status_track_type' => $track_type,
            ':owner_user_id'     => $this->user_id
        );

        $STH->execute($params);


        /**
         * Bucket #2
         */
        $STH = $this->DBH->prepare("
                      UPDATE status_boards a
                      INNER JOIN data_boards b
                      ON a.board_id = b.board_id
                      SET a.track_type = :status_track_type
                      WHERE b.user_id = :profile_user_id
                      AND a.is_owned = 0
                    ");

        $params = array(
            ':status_track_type' => $track_type,
            ':profile_user_id'   => $this->user_id
        );

        $STH->execute($params);


        /**
         * Bucket #3
         */
        if ($track_type == "user") {
            $STH = $this->DBH->prepare("
                      UPDATE status_boards a
                      INNER JOIN data_boards b
                      ON a.board_id = b.board_id
                      SET a.track_type = :status_track_type
                      WHERE b.user_id = :profile_user_id
                      AND b.is_owner = 0
                      AND a.is_owned = 1
                    ");

            $params = array(
                ':status_track_type' => $track_type,
                ':profile_user_id'   => $this->user_id
            );

            $STH->execute($params);

        } else if ($track_type == "competitor") {
            $STH = $this->DBH->prepare("
                      UPDATE status_boards a
                      INNER JOIN data_boards b
                      ON a.board_id = b.board_id
                      SET a.track_type = :status_track_type
                      WHERE b.user_id = :profile_user_id
                      AND b.is_owner = 0
                      AND a.is_owned = 1
                      AND (a.track_type = 'free' OR a.track_type = 'orphan')
                    ");

            $params = array(
                ':status_track_type' => $track_type,
                ':profile_user_id'   => $this->user_id
            );

            $STH->execute($params);
        }

    }

    /**
     * Checks to see if initialized profile is already in DB
     *
     * @author  Will
     */
    public function doesNotExistInOurDB()
    {
        $STH = $this->DBH
            ->prepare('select user_id from data_profiles_new where username = :username');

        $STH->execute(array(
                           ':username' => $this->username
                      ));

        if ($STH->rowCount() > 0) {
            return false; // ie it does exist
        }

        return true;
    }

    /**
     * Get cached pins if available or get from API
     *
     * @author             Will
     *
     * @see                Pins
     * @Pinterest_API_call true
     *
     * @returns Pins collection
     */
    public function getAPIpins()
    {
        if ($this->_api_pins === false) {
            $this->fetchAPIPins();
        }

        return $this->_api_pins;
    }

    /**
     * Get a new copy of all the API pins
     *
     * @author  Will
     * @returns Pins collection
     */
    public function fetchAPIPins()
    {
        $this->_api_pins = $this->getAPIBoards()->fetchPins();

        return $this;
    }

    /**
     * @author  Will
     */
    public function getCalculations()
    {
        $STH = $this->DBH->prepare("
                        select * from calcs_profile_history
                        where user_id = :user_id
                        order by timestamp DESC
                        limit 100
                    ");

        $STH->execute(
            array(
                 ':user_id' => $this->user_id
            )
        );


        $calcs = new CalcProfileHistories();
        foreach ($STH->fetchAll() as $calcData) {
            $calc = new CalcProfileHistory();
            $calc->loadDBData($calcData);
            $calcs->add($calc);
        }

        return $calcs;

    }

    /**
     * Get boards in our DB
     *
     * @author  Will
     *
     */
    public function getDBBoards($track_type = 'all')
    {

        switch ($track_type) {
            default:
                $where_clause = '';
                break;
            case 'active':
                $where_clause ='AND track_type != \'deleted\' ';
                break;

        }

        $STH = $this->DBH->prepare(
                         'select * from data_boards
                         where user_id = :user_id ' . $where_clause .
                         'order by created_at ASC'
        );


        $STH->execute(array(
                           'user_id' => $this->user_id
                      ));

        $boards = new Boards();

        foreach ($STH->fetchAll() as $boardData) {
            $board = new Board();
            $board->loadDBData($boardData);
            $boards->add($board, $board->board_id);
        }

        return $boards;
    }

    /**
     * Returns the boards that don't have track type deleted or not found
     * @return Boards
     */
    public function getActiveDBBoards() {
        return $this->getDBBoards('active');
    }

    /**
     * Get Pins (from our DB)
     *
     * @author  Will
     * @returns Pins collection
     */
    public function getDBPins($limit=1000)
    {
        $STH = $this->DBH->prepare(
                         'select * from data_pins_new where user_id = :user_id order by created_at ASC limit '.$limit
        );

        $STH->execute(array(
                           'user_id' => $this->user_id
                      ));

        $pins = new Pins();

        foreach ($STH->fetchAll() as $pinData) {
            $pin = new Pin();
            $pin->loadDBData($pinData);
            $pins->add($pin);
        }

        return $pins;
    }

    /**
     * Get followers from DB
     *
     * @author  Will
     *
     * @param int $limit
     *
     * @return \Followers
     */
    public function getRecentDBFollowers($limit = 1000)
    {
        $rows = DB::select("
            SELECT * from data_followers
            WHERE user_id = ?
            ORDER BY timestamp DESC
            LIMIT $limit
        ", array($this->user_id));

        $followers = new Followers();

        foreach ($rows as $followerData) {
            $follower = new Follower();
            $follower->loadDBData($followerData);

            $followers->add($follower);
        }

        if ($followers->count() > 0) {

            $user_ids = $followers->stringifyField('follower_user_id');

            $profiles_data = DB::select("
            SELECT * FROM data_profiles_new WHERE user_id IN($user_ids)
        ");

            $profiles = new Profiles();
            foreach ($profiles_data as $profile_data) {
                $profile = Profile::createFromDBData($profile_data);
                $profiles->add($profile, $profile->user_id);
            }

            $followers->loadCache('profile', 'follower_user_id', $profiles);
        }

        return $followers;

    }

    /**
     * Get the most appropriate profile image url based on size
     *
     * @author  Will
     *
     * @todo    let me select which size I want and in what dimensions
     *
     */
    public function getImageUrl($height = false, $width = false)
    {
        return $this->image;
    }

    /**
     * Latest API pins of a set amount (without going through boards)
     *
     * @author  Will
     *
     * @returns Pins collection
     */
    public function getLatestApiPins()
    {
        $api_pins = $this->pinterest->getUserPins($this->user_id);

        $pins = new Pins();

        foreach ($api_pins as $pinData) {
            $pin = new Pin();
            $pin->loadAPIData($pinData);
            $pins->add($pin);
        }

        return $pins;
    }

    /**
     * Get a name, beit their full name or username if no name is set
     *
     * @author Will
     *
     */
    public function getName()
    {
        if ($this->first_name == '' && $this->last_name == '') {
            return $this->username;
        } else {
            return $this->first_name . ' ' . $this->last_name;
        }
    }

    /**
     * Get the number of comments
     * from calcs_profile_history
     *
     * @author  Will
     *
     */
    public function getCommentCount()
    {
        if ($this->getLastHistoryCalc() === null) {
            return null;
        }

        return $this->getLastHistoryCalc()->comment_count;
    }

    /**
     * @author  Will
     */
    public function getStatusProfile()
    {
        return StatusProfile::find($this->user_id);
    }

    /**
     * @author Will
     */
    public function getfollowersPerRepin()
    {
        if ($this->getRepinCount() === null || $this->getRepinCount() == 0) {
            return 0;
        }

        return round($this->follower_count / $this->getRepinCount());
    }

    /**
     * @author   Will
     *
     */
    public function historyCalculatedLessThan($time_frame)
    {
        return !$this->historyCalculatedMoreThan($time_frame);
    }

    /**
     * @author  Will
     *
     */
    public function historyCalculatedMoreThan($time_frame)
    {
        $epoch = strtotime($time_frame);

        if ($this->getLastHistoryCalc()) {
            if ($this->getLastHistoryCalc()->timestamp <= $epoch) {
                return true;
            }
        } else {
            return true;

        }

        return false;
    }

    /**
     * @author  Will
     *
     * @param $time_frame
     *
     * @returns bool
     */
    public function latestBoardPulledLessThan($time_frame)
    {
        return !$this->latestPinPulledMoreThan($time_frame);
    }

    /**
     * @author Will
     *
     * @param $time_frame
     *
     * @returns bool
     */
    public function latestPinPulledMoreThan($time_frame)
    {
        $epoch = strtotime($time_frame);

        $STH = $this->DBH->query("select * from data_pins_new where last_pulled > $epoch limit 1");

        if ($STH->rowCount() == 0) {
            return true;
        }

        return false;
    }

    /**
     * @author Will
     *
     * @param $time_frame
     *
     * @returns bool
     */
    public function latestBoardPulledMoreThan($time_frame)
    {
        $epoch = strtotime($time_frame);

        $STH = $this->DBH->query("select * from data_boards where last_pulled > $epoch limit 1");

        if ($STH->rowCount() == 0) {
            return true;
        }

        return false;
    }

    /**
     * @author  Will
     *
     * @param $time_frame
     *
     * @returns bool
     */
    public function latestPinPulledLessThan($time_frame)
    {
        return !$this->latestPinPulledMoreThan($time_frame);
    }

    /**
     * Save to database
     *
     * this is used in one spot and should be depreciated
     *
     * @author  Will
     */
    public function saveToDB($insert_type = 'INSERT INTO', $append = false)
    {
        if ($append) {
            $append =
                "ON DUPLICATE KEY UPDATE
                 username = VALUES(username),
                 first_name = VALUES(first_name),
                 last_name = VALUES(last_name),
                 image = VALUES(image),
                 about = VALUES(about),
                 domain_url = VALUES(domain_url),
                 domain_verified = VALUES(domain_verified),
                 website_url = VALUES(website_url),
                 created_at = VALUES(created_at),
                 facebook_url = VALUES(facebook_url),
                 twitter_url = VALUES(twitter_url),
                 location = VALUES(location),
                 board_count = VALUES(board_count),
                 pin_count = VALUES(pin_count),
                 like_count = VALUES(like_count),
                 follower_count = VALUES(follower_count),
                 following_count = VALUES(following_count),
                 last_pulled = VALUES(last_pulled),
                 track_type=IF(VALUES(track_type)='user','user',IF(VALUES(track_type)='competitor', 'competitor', track_type)),
                 timestamp = VALUES(timestamp)
            ";
        }

        return parent::saveToDB($insert_type, $append);
    }

    /**
     * Inverse of wasPulledMoreThan
     *
     * @author  Will
     */
    public function wasPulledLessThan($time_frame)
    {
        if ($this->wasPulledMoreThan($time_frame)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if this profile was pulled X time frame ago
     *
     * @author  Will
     *
     * @example
     * $profile->wasPulledMoreThan('12 hours ago'); //false
     */
    public function wasPulledMoreThan($time_frame)
    {
        $epoch = strtotime($time_frame);

        if ($this->last_pulled <= $epoch) {
            return true;
        }

        return false;
    }

    /**
     * @author  Will
     *
     * @param        $growth             int the amount of new followers
     *
     * @param        $time_period        string the amount of time we should judge the growth by
     *                                   day | month | year
     *
     * @return array
     */
    public function followerGrowthAnalysis($growth, $time_period = 'week')
    {

        $follower_growth_average     = $this->calculateAverage('followers', $time_period);
        $period_difference_in_growth = $growth - $follower_growth_average;
        $change_in_growth            = round($growth - $follower_growth_average);

        $response = array(
            'follower_count'        => number_format($this->follower_count),
            'total'                 => number_format($this->follower_count),
            'change_in_growth'      => $change_in_growth,
            'followers_gained'      => number_format($growth),
            'average_growth'        => number_format($follower_growth_average),
            'time_period'           => $time_period,
            'follower_count_status' => "still in it's early stages.",
            'period_status'         => 'below average'
        );

        if ($this->follower_count > 100) {
            $response['follower_count_status'] = 'looking great!';
        }

        if (is_between($period_difference_in_growth, 0, 11)) {

            $response['period_status'] = 'a little bit above average';

        } else if (is_between($period_difference_in_growth, -2, 2)) {

            $response['period_status'] = 'right on par with what you are used to';

        } else if ($period_difference_in_growth > 10) {

            $response['period_status'] = 'above average';
        }

        return $response;
    }

    /**
     * @author   Will
     *
     * @param        $new_repins
     * @param        $time_period        string the amount of time we should judge the growth by
     *                                   day | month | year
     *
     * @internal param string $timeframe
     * @return array
     */
    public function repinGrowthAnalysis($new_repins, $time_period = 'week')
    {

        $average_repins           = $this->calculateAverage('repins', $time_period);
        $growth                   = $new_repins;
        $change_in_growth         = round($growth - $average_repins);
        $total_repins             = $this->getRepinCount();
        $period_repins_difference = $growth - $average_repins;

        $response = array(
            'repin_count'        => number_format($total_repins),
            'total'              => number_format($total_repins),
            'repins_gained'      => number_format($growth),
            'average_growth'     => number_format($average_repins),
            'change_in_growth'   => number_format($change_in_growth),
            'time_period'        => $time_period,
            'repin_count_status' => "still in it's early stages.",
            'period_status'      => 'below average'
        );

        if ($this->getRepinCount() > 100) {
            $response['repin_count_status'] = 'looking great!';
        }

        if (is_between($period_repins_difference, 0, 11)) {

            $response['period_status'] = 'a wee bit above average';

        } else if (is_between($period_repins_difference, -2, 2)) {

            $response['period_status'] = 'fairly normal';

        } else if ($period_repins_difference > 10) {

            $response['period_status'] = 'above average';
        }

        return $response;

    }

    /**
     * @author  Will
     *
     * @param string $timeframe
     *
     * @return int
     */
    public function newRepinsSince($timeframe = '7 days ago')
    {
        return $this->getCalulcationDiff($timeframe)->spread('repin_count');
    }

    /**
     * @author   Will
     *
     * @param      $earliest
     * @param bool $latest
     *
     * @internal param $timeframe
     *
     * @return CalcProfileHistories
     */
    protected function getCalulcationDiff($earliest, $latest = false)
    {

        if ($latest === false) {
            $latest = time();
        }

        $calculations = new CalcProfileHistories();
        /**
         * The latest data we have on this profile that has been calculated
         * This is better than using the profile stats because it has other calcs
         */
        $latest_profile_calc = $this->findCalculationOn($latest);

        if (!$latest_profile_calc) {
            Log::notice(
               'Todays profile history calculation was not available',
               $this
            );
            $latest_profile_calc = $this->getLastHistoryCalc();
        }

        $calculations->add($latest_profile_calc, 'latest');

        Log::debug('Got latest profile calc', $latest_profile_calc);

        /**
         * We get a calc from last week, but in the future this may be variable
         */
        $early_profile_calc = $this->findCalculationBefore($earliest);

        if (!$early_profile_calc) {
            Log::notice(
               'Last weeks profile history calculation was not available',
               $this
            );
            $early_profile_calc = $this->findFirstAvailableCalculation();
        }

        $calculations->add($early_profile_calc, 'earliest');

        Log::debug('Found early profile calc', $early_profile_calc);

        $calculations->sortBy('date', SORT_DESC);

        return $calculations;
    }

    /**
     * Find a calculation on a given date
     *
     * @author  Will
     *
     * @param $date
     *
     * @return $this
     */
    public function findCalculationOn($date)
    {
        $flat_date = flat_date('day', $date);

        return CalcProfileHistory::find($this->user_id, $flat_date, 'force exact time');
    }

    /**
     * @author  Will
     *
     * @param $time
     *
     * @return CalcProfileHistory
     */
    public function findCalculationBefore($time = 'latest')
    {
        return CalcProfileHistory::find($this->user_id, $time);
    }

    /**
     * Gets the first calculation in the database instead of getting it by date
     *
     * @author  Will
     *
     * @return CalcProfileHistory
     */
    public function findFirstAvailableCalculation()
    {
        $STH = $this->DBH->prepare("
           select * from calcs_profile_history
            where user_id = :user_id
            order by date ASC
            limit 1
        ");

        $STH->execute(array(
                           ':user_id' => $this->user_id
                      )
        );

        if ($STH->rowCount() == 0) {
            return false;
        }

        $calc = new CalcProfileHistory();
        $calc->loadDBData($STH->fetch());

        return $calc;
    }

    /**
     * @author  Will
     *
     * @param $timeframe
     *
     * @return array
     */
    public function likesGrowthAnalysis($timeframe)
    {
        return $this->growthAnalysis('likes', $timeframe);
    }

    /**
     * @author   Will
     *
     * @param        $type
     * @param string $timeframe
     * @param        $time_period        string the amount of time we should judge the growth by
     *                                   day | month | year
     *
     * @throws ProfileException
     * @return array
     */
    protected function growthAnalysis($type, $timeframe = '7 days ago', $time_period = 'week')
    {
        switch ($type) {

            case 'likes':
                $growth = $this->newLikesSince($timeframe);
                $total  = $this->like_count;
                break;

            case 'repins':
                $growth = $this->newRepinsSince($timeframe);
                $total  = $this->getRepinCount();
                break;

            case 'followers':
                $growth = $this->newFollowersSince($timeframe);
                $total  = $this->follower_count;
                break;

            default:
                throw new ProfileException("Unable to do growth analysis of $type");
                break;
        }

        $average           = $this->calculateAverage($type, $time_period);
        $period_difference = $growth - $average;

        $response = array(
            'total'          => number_format($total),
            'growth'         => number_format($growth),
            'average_growth' => number_format($average),
            'time_period'    => $time_period,
            'count_status'   => "still in it's early stages.",
            'period_status'  => 'below average'
        );

        if ($total > 100) {
            $response['count_status'] = 'looking great!';
        }

        if (is_between($period_difference, 0, 11)) {

            $response['period_status'] = 'a wee bit above average';

        } else if (is_between($period_difference, -2, 2)) {

            $response['period_status'] = 'fairly normal';

        } else if ($period_difference > 10) {

            $response['period_status'] = 'above average';
        }

        return $response;
    }

    /**
     * @author  Will
     *
     * @param string $timeframe
     *
     * @return int
     */
    public function newLikesSince($timeframe = '7 days ago')
    {
        return $this->getCalulcationDiff($timeframe)->spread('likes_count');
    }

    /**
     * @author  Will
     *
     * @param string $timeframe
     *
     * @return int
     */
    public function newFollowersSince($timeframe = '7 days ago')
    {
        return $this->getCalulcationDiff($timeframe)->spread('follower_count');
    }

    /**
     * Gets the most recent repinners of this profiles pins, in a given period
     *
     * @author
     */
    public function topRepinners($limit = 3, $period = 7)
    {


        $STH = $this->DBH->prepare("
            SELECT
                repinner_user_id,
                overall_engagement,
                username,
                first_name,
                last_name,
                follower_count,
                following_count,
                image,
                location,
                website_url,
                facebook_url,
                twitter_url,
                pin_count,
                board_count
            FROM
                cache_engagement_influencers
            WHERE
                user_id = :user_id
            AND
                period = :period
            ORDER BY overall_engagement DESC LIMIT $limit
        ");

        $STH->execute(
            array(
                 ':user_id' => $this->user_id,
                 ':period'  => $period
            )
        );

        $influencers = new EngagementInfluencers();
        foreach ($STH->fetchAll() as $influencerData) {
            $influencers->add(
                        EngagementInfluencer::createFromDBData($influencerData)
            );
        }

        return $influencers;

    }

    /**
     * Most repinned pins
     *
     * @author  Will
     * @see     mostViralPins()
     */
    public function mostRepinnedPins($limit = 3, $timeframe = '7 days ago', $end_date = false)
    {
        return $this->mostViralPins('repins', $limit, $timeframe, $end_date);
    }

    /**
     * Returns the most viral pins for a given profile
     *
     * @author  Will
     *
     * @see     mostRepinnedPins()
     * @see     mostLikedPins()
     * @see     mostCommentedPins()
     *
     */
    protected function mostViralPins($type, $limit, $timeframe, $end_date = false)
    {
        /*
         * This helps use figure out which column to calculate by
         * and allows us some flexibility how we write it. I basically
         * just didn't like writing "repin_count" vs "repins". I know.. I know.
         */
        switch ($type) {

            default:
                throw new Exception('Type required for most viral pin');
                break;

            case 'repins':
            case 'repin_count':
                $column = 'repin_count';
                break;

            case 'likes':
            case 'like_count':

                $column = 'like_count';
                break;

            case 'comments':
            case 'comment_count':

                $column = 'comment_count';

                break;
        }

        if(!$end_date){
            $end_date = flat_date('day');
        }

        if (!is_int($timeframe)) {
            $timeframe = strtotime($timeframe, $end_date);
        }

        /*
         * We want pins that have been repinned, so we only need to look at pins that have had
         * pins with history in the timeframe
         */
        $pins = $this->pinsWithChangesSince($timeframe, $end_date);
        Log::debug('Got pins with changes');

        if ($pins->count() == 0) {
            return false;
        }

        /*
         * We want a baseline for the changes for each of the pin ids in that list to compare to
         * So we group by the pin id and get the highest timestamp that is less than the timeframe
         */
        $pin_ids = $pins->stringifyField('pin_id', ',', '');

        $pin_histories_timeframe = DB::select("
              select q1.* from

				  (SELECT
                     pin_id,
                     user_id,
                     date,
                     repin_count,
                     like_count,
                     comment_count,
                     timestamp
                  FROM data_pins_history
                  WHERE pin_id in ($pin_ids)
                  AND timestamp < ?
                  ORDER BY timestamp DESC) as q1

              LEFT JOIN

                  (SELECT
                     pin_id,
                     user_id,
                     date,
                     repin_count,
                     like_count,
                     comment_count,
                     timestamp
                  FROM data_pins_history
                  WHERE pin_id in ($pin_ids)
                  AND timestamp < ?
                  ORDER BY timestamp DESC) as q2

              ON (q1.pin_id = q2.pin_id AND q1.date < q2.date)
              WHERE q2.date is NULL;
              ",
                                              array($timeframe, $timeframe)
        );

        /*
         * Now we want to add this baseline timestamp to the pinhistory collection in each pin
         * model, which we find by the pin_id key
         *
         * note: we might still not have everything, but if we don't by now we can't. It could (and
         * probably is) just a new pin.
         */
        foreach ($pin_histories_timeframe as $pinHistoryData) {
            $pin_history = PinHistory::createFromDBData($pinHistoryData);
            $pins->getModel($pinHistoryData->pin_id)->pinHistory()->add($pin_history, true);
        }

        $most_viral_pins = new Pins();

        /** @var $pin Pin */
        foreach ($pins as $pin) {

            $pin->pinHistory()->sortBy('date', SORT_DESC);

            /*
             * To find the repins in this week, we want to take the delta between
             * the oldest timestamp and the newest. If there is no pin history earlier than
             * the time frame, AND the pin was created within the timeframe,
             * then we just take the newest repin_count
             */

            /*
             * We set this
             * "repin_count_change", "like_count_change", and "comment_count_change"
             *  properties using a MAGIC METHOD
             * because they are not a properties of the pin object.
             *
             * It's here because we need to know the change in
             * a pin's repin_count/like_count/comment_count
             * over the time period we've specified, not
             * just the ending value.
             *
             *
             * First, we are checking to see if the earliest history record we have is within
             * the timeframe the user is looking for:
             */
            if ($pin->pinHistory()->last()->date >= $timeframe) {

                /*
                 * If the earliest history record is within the user's timeframe
                 * AND if the pin was also created within that timeframe, we need to consider
                 * it as if an additional history record exists for the moment
                 * that pin was created (where it had 0 engagement).
                 *
                 * To do this, we simply assign that the latest history record within the timeframe as
                 * the overall change in engagement.
                 *
                 *
                 * FOR EXAMPLE, consider the following timeline:
                 * ---------------------------------------------------------------------------
                 *      timeframe_start-----------> jan 1, 2014
                 *          pin created_at       -> jan 5, 2014
                 *              pin history 1    -> jan 6, 2014 (10 repins)
                 *              pin history 2    -> jan 7, 2014 (15 repins)
                 *      timeframe_end-------------> jan 31, 2014
                 *              pin history 3    -> feb 28, 2014 (20 repins)
                 *      today                    -> apr 14, 2014
                 * ---------------------------------------------------------------------------
                 *
                 * In this case, the pin received 15 repins within the timeframe the user
                 * is looking at (jan 1,2014 - jan 31, 2014).  However, if we only look at the
                 * pin history records available, it would look as if the pin only received
                 * 5 repins in this timeframe (15 - 10).  Therefore, we must consider the case when
                 * the pin was created within the timeframe (created_at > $timeframe). Otherwise,
                 * we'll look at the spread in history.
                 *
                 */
                if($pin->created_at > $timeframe){
                    $metric_count = $pin->pinHistory()->first()->$column;
                    $pin->repin_count_change = $pin->pinHistory()->first()->repin_count;
                    $pin->like_count_change = $pin->pinHistory()->first()->like_count;
                    $pin->comment_count_change = $pin->pinHistory()->first()->comment_count;
                } else {
                    $metric_count = $pin->pinHistory()->spread($column);
                    $pin->repin_count_change = $pin->pinHistory()->spread('repin_count');
                    $pin->like_count_change = $pin->pinHistory()->spread('like_count');
                    $pin->comment_count_change = $pin->pinHistory()->spread('comment_count');
                }
            } else {
                $metric_count = $pin->pinHistory()->spread($column);
                $pin->repin_count_change = $pin->pinHistory()->spread('repin_count');
                $pin->like_count_change = $pin->pinHistory()->spread('like_count');
                $pin->comment_count_change = $pin->pinHistory()->spread('comment_count');
            }

            if (is_null($metric_count)) {
                $metric_count = 0;
            }


            /*
             * We create a unique, yet sortable index using the number of repins prepended
             * to the pin id. We'll check if our in loop pin has more repins than the third place
             * one. We use sprintf to make sure there are leading 0's
             */
            $index = $pin->getIndex($metric_count);

            /*
             * If we don't have enough in the list yet, its in the top by default!
             */
            if ($most_viral_pins->count() < $limit) {

//                if ($pin->pinHistory()->count() != 1){
                    $most_viral_pins->add($pin, $index);
//                }
            } else {
                /*
                 * We want to rsort the pins by key and then find what the
                 * minimum repin amount
                 * if the in loop pin has more than that, it's in the top
                 */
                $most_viral_pins->rsort();

                $hash = explode('@', $most_viral_pins->nthKey($limit), 2);

                if ($metric_count > $hash[0]) {
                    $most_viral_pins->nthRemove($limit);
                    $most_viral_pins->add($pin, $index);

                }
            }

        }

        $most_viral_pins->rsort();


        return $most_viral_pins;
    }

    /**
     * Get pins with any history since a given date
     *
     * @param string $timeframe
     *
     * @return Pins
     */
    public function pinsWithChangesSince($timeframe = '7 days ago', $end_date = false)
    {

        if(!$end_date){
            $end_date = flat_date('day');
        }

        if (!is_int($timeframe)) {
            $timeframe = strtotime($timeframe);
        }

        /*
         * We join the pins with the history so we have all the information. I suppose
         * this could be optional, but it's handy
         */
        $STH = $this->DBH->prepare(
                         "select *,
                          data_pins_history.pin_id as history_pin_id,
                          data_pins_history.date as history_date,
                          data_pins_history.repin_count as history_repin_count,
                          data_pins_history.like_count as history_like_count,
                          data_pins_history.comment_count as history_comment_count,
                          data_pins_history.timestamp as history_timestamp
                          from data_pins_history
                          inner join data_pins_new on data_pins_new.pin_id = data_pins_history.pin_id
                          where data_pins_history.date >= :timeframe
                          and data_pins_history.date <= :end_date
                          and data_pins_history.user_id = :user_id
                          order by data_pins_history.date desc;"
        );
        $STH->execute(
            array(
                 ":timeframe" => $timeframe,
                 ":end_date" => $end_date,
                 ":user_id" => $this->user_id
            )
        );

        $viral_pins = $STH->fetchAll();

        Log::debug('Got all pins with pin history from last 7 days');

        /*
         * Here, we're checking to see which pins actually have more than one record of history,
         * since we don't want to show false results.
         *
         * For Example: we just pulled data for a user's pins for the
         * first time, and have only one record of history for their pins.
         * This does not necessarily mean that these pin received all of
         * their engagement on that day.  So we need to filter these out
         * and only show data for pins where we have more than one record of history.
         *
         * HOWEVER: we cannot exclude pins which were actually created during the timeframe
         * because we DO know that those pins received all of their engagement within that period.
         */
        $STH = $this->DBH->prepare(
                         'select data_pins_history.pin_id, count(data_pins_history.pin_id) as history_count
                        from data_pins_history
                        left join data_pins_new on data_pins_new.pin_id = data_pins_history.pin_id
                        where data_pins_history.user_id = :user_id
                        and data_pins_new.created_at < :timeframe
                        group by data_pins_history.pin_id
                        having history_count = 1
                        ORDER BY data_pins_history.date desc');
        $STH->execute(
                                 array(
                                     ":user_id" => $this->user_id,
                                     ":timeframe" => $timeframe
                                 )
        );
        $pins_without_history = $STH->fetchAll();


        /*
         * Iterate through pins which have only one record of history to create an array.
         */
        $pins_without_history_array = array();
        foreach($pins_without_history as $pin){
            $pins_without_history_array[$pin->pin_id] = array();
        }
        /*
         * We build out the pins into the Pin model and throw them all in the Pins collection
         * to hand back
         */
        $pins = new Pins();

        foreach ($viral_pins as $pinData) {
            /*
             * Check to make sure that a pin is not in the
             * list of pins with only one record of history.
             */
            if(!isset($pins_without_history_array[$pinData->pin_id])){

                if (!$pins->offsetExists($pinData->pin_id)) {

                    /*
                     * We force the pin_id as the key for collection
                     * so we can do the check above and not have duplicate pins
                     */
                    $pin = Pin::createFromDBData($pinData);
                    $pins->add($pin, $pinData->pin_id);

                } else {
                    $pin = $pins->getModel($pinData->pin_id);
                }

                $pin_history = PinHistory::createFromDBData($pinData, "history_");
                $pin->pinHistory()->add($pin_history, true);

            }
        }

        Log::debug('Got viral pins');
        return $pins;

    }

    /**
     * Most liked pins
     *
     * @author  Will
     * @see     mostViralPins()
     */
    public function mostLikedPins($limit = 3, $timeframe = '7 days ago')
    {
        return $this->mostViralPins('likes', $limit, $timeframe);
    }

    /**
     * Most commented pins
     *
     * @author  Will
     * @see     mostViralPins()
     */
    public function mostCommentedPins($limit = 3, $timeframe = '7 days ago')
    {
        return $this->mostViralPins('comments', $limit, $timeframe);
    }

    /**
     * @author  Will
     */
    public function recentComments($limit, $timeframe = '7 days ago')
    {
        if (!is_int($timeframe)) {
            $timeframe = strtotime($timeframe);
        }

        /**
         * This is the collection of the comments
         * we'll send back
         */
        $comments = new PinsComments();

        /**
         * There will be pin history for pins with comments,
         * so we only want to search for those
         */
        $pins = $this->pinsWithChangesSince($timeframe);

        /**
         * If there are no pins with history, there are no comments
         * so we return an empty set
         */
        if ($pins->count() < 1) {
            return $comments;
        }

        $pin_ids = $pins->stringifyField('pin_id');

        $comments_data = DB::select("
                SELECT
                    b.*,
                    a.comment_id as a_comment_id,
                    a.pin_id as a_pin_id,
                    a.commenter_user_id as a_commenter_user_id,
                    a.comment_text as a_comment_text,
                    a.created_at as a_created_at,
                    a.timestamp as a_timestamp
                from data_pins_comments a
                JOIN data_pins_new b
                ON a.pin_id = b.pin_id
                JOIN data_profiles_new c
                ON a.commenter_user_id = c.user_id
                WHERE a.pin_id IN ($pin_ids)
                AND a.created_at >= ?
                AND a.commenter_user_id != ?
                ORDER BY a.created_at DESC
                LIMIT $limit
        ", array($timeframe, $this->user_id));


        foreach ($comments_data as $comment_data) {

            $comment = PinsComment::createFromDBData($comment_data, 'a_');
            $comment
                ->setCache('pin', Pin::createFromDBData($comment_data))
                ->setCache('commenter', Profile::createFromDBData($comment_data));

            $comments->add($comment);

        }

        return $comments;
    }

    /**
     * @author  Will
     *
     * @param $timeframe
     *
     * @return \Boards
     */
    public function topBoardsByRepins($timeframe = '7 days ago')
    {
        return $this->topBoardsBy('repins', $timeframe);
    }

    /**
     * @author  Will
     *
     * @param        $type
     * @param string $timeframe
     *
     * @throws Exception
     * @return \Boards
     */
    protected function topBoardsBy($type, $timeframe)
    {
        /**
         * We want a collection of the boards ordered by whatever type
         * We'll use this collection to store the boards in, using the type
         * we are ordering by as an index
         */
        $top_boards = new Boards();

        /** @var $board \Board */
        foreach ($this->getOwnedDBBoards() as $board) {

            /**
             * We prepend the value of the pins/repins whatever to a unique index.
             * That way, we can sort and get the top ones
             */
            switch ($type) {
                case 'pins':

                    $board->new_pins_count = $board->newPinsSince($timeframe);
                    $index = $board->getIndex($board->new_pins_count);

                    break;

                case 'repins':

                    $board->new_repins_count = $board->newRepinsSince($timeframe);
                    $index = $board->getIndex($board->new_repins_count);
                    break;

                case 'followers':

                    $board->new_followers_count = $board->newFollowersSince($timeframe);
                    $index = $board->getIndex($board->new_followers_count);
                    break;

                default:
                    throw new Exception('That feature has yet to have been built ' . $type);
                    break;

            }

            $top_boards->add($board, $index);
        }

        $top_boards->rsort();

        return $top_boards;
    }

    /**
     * Get boards in our DB owned by the user
     *
     * @author  Will
     *
     * @return \Boards
     */
    public function getOwnedDBBoards()
    {
        $boardsData = DB::select("
            SELECT * from data_boards
            WHERE user_id = ?
            AND owner_user_id = user_id
            ORDER BY created_at ASC
        ", array($this->user_id));

        $boards = new Boards();

        foreach ($boardsData as $boardData) {
            $board = Board::createFromDBData($boardData);
            $boards->add($board);
        }

        return $boards;
    }

    /**
     * @author  Will
     *
     * @param string $timeframe
     *
     * @return \Boards
     */
    public function topBoardsByFollowers($timeframe = '7 days ago')
    {
        return $this->topBoardsBy('followers', $timeframe);
    }

    /**
     * @author  Will
     *
     * @param string $timeframe
     *
     * @return \Boards
     */
    public function topBoardsByPins($timeframe = '7 days ago')
    {
        return $this->topBoardsBy('pins', $timeframe);
    }

    /**
     * Returns the number of times we've calculated their profile history
     *
     * @author  Will
     *
     * @return mixed
     */
    public function daysOfCalcsAvailable()
    {
        $result = DB::select(
                    'select count(*) as days from calcs_profile_history where user_id =?',
                    array($this->user_id)
        );

        return $result[0]->days;
    }

    /**
     * @author  Alex
     * @author  Will
     *
     * @return int
     */
    public function taskCompleteness()
    {

        $profile_percentage   = 0;
        $profile_completeness = $this->getProfileCompleteness();

        if ($this->hasDescription()) {
            $profile_percentage += 10;
        }

        if ($this->hasFacebook()) {
            $profile_percentage += 3;
        }

        if ($this->hasTwitter()) {
            $profile_percentage += 3;
        }

        if ($this->hasWebsite()) {
            $profile_percentage += 8;
        }

        if ($this->hasWebsiteVerified()) {
            $profile_percentage += 8;
        }

        if ($this->hasLocation()) {
            $profile_percentage += 3;
        }

        if ($this->hasImage()) {
            $profile_percentage += 15;
        }

        if ($profile_completeness['board_count'] < 10) {
            $boards_points = $profile_completeness['board_count'] * 5;
        } else {
            $boards_points = 50;
        }
        $profile_percentage += $boards_points;

        $profile_percentage += min(-10, $profile_completeness['boards_less_than_ten_pins']);
        $profile_percentage += min(-10, $profile_completeness['boards_no_categories']);
        $profile_percentage += min(-10, $profile_completeness['boards_no_descriptions']);

        return $profile_percentage / 100;

    }

    /**
     * @author  Will
     * @return bool
     */
    public function hasDescription()
    {

        if (!empty($this->about)) {
            return true;
        }

        return false;
    }

    /**
     * @author  Will
     * @return bool
     */
    public function hasFacebook()
    {
        if (!empty($this->facebook_url)) {
            return true;
        }

        return false;
    }

    /**
     * @author  Will
     * @return bool
     */
    public function hasTwitter()
    {
        if (!empty($this->twitter_url)) {
            return true;
        }

        return false;

    }

    /**
     * @author  Will
     * @return bool
     */
    public function hasWebsite()
    {
        if (empty($this->website_url) AND empty($this->domain_url)) {
            return false;
        }

        return true;
    }

    /**
     * @author  Will
     * @return bool
     */
    public function hasWebsiteVerified()
    {
        if ($this->domain_verified) {
            return true;
        }

        return false;
    }

    /**
     * @author  Will
     * @return bool
     */
    public function hasLocation()
    {
        if (!empty($this->location)) {
            return true;
        }

        return false;
    }

    /**
     * @author  Will
     * @return bool
     */
    public function hasImage()
    {
        if (strpos($this->image, "user/default_75.png") !== false) {
            return false;
        }

        return true;
    }

    /**
     * @author  Alex
     * @author  Will
     */
    public function getProfileCompleteness()
    {

        $cust_user_id = $this->user_id;
        $conn         = DatabaseInstance::mysql_connect();

        $profile_completeness                              = array();
        $profile_completeness['boards']                    = array();
        $profile_completeness['num_boards']                = 0;
        $profile_completeness['boards_less_than_ten_pins'] = 0;
        $profile_completeness['boards_no_categories']      = 0;
        $profile_completeness['boards_no_descriptions']    = 0;

        $acc = "SELECT * FROM data_boards WHERE user_id=$cust_user_id";
        $acc_res = mysql_query($acc, $conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {

            $board_id                                    = $a['board_id'];
            $profile_completeness['boards']["$board_id"] = array();

            //check whether each of their boards has a category
            if ($a['category'] == "") {
                $profile_completeness['boards_no_categories'] -= 1;
                $profile_completeness['boards']["$board_id"]['no_category']     = true;
                $profile_completeness['boards']["$board_id"]['url']             = $a['url'];
                $profile_completeness['boards']["$board_id"]['name']            = $a['name'];
                $profile_completeness['boards']["$board_id"]['url']             = $a['url'];
                $profile_completeness['boards']["$board_id"]['description']     = $a['description'];
                $profile_completeness['boards']["$board_id"]['image_cover_url'] = $a['image_cover_url'];
                $profile_completeness['boards']["$board_id"]['pin_count']       = $a['pin_count'];
                $profile_completeness['boards']["$board_id"]['follower_count']  = $a['follower_count'];
                $profile_completeness['boards']["$board_id"]['created_at']      = $a['created_at'];
            }

            //check whether each of their boards has at least 10 pins
            if ($a['pin_count'] < 10) {
                $profile_completeness['boards_less_than_ten_pins'] -= 1;
                $profile_completeness['boards']["$board_id"]['ten_pins']        = true;
                $profile_completeness['boards']["$board_id"]['url']             = $a['url'];
                $profile_completeness['boards']["$board_id"]['name']            = $a['name'];
                $profile_completeness['boards']["$board_id"]['url']             = $a['url'];
                $profile_completeness['boards']["$board_id"]['description']     = $a['description'];
                $profile_completeness['boards']["$board_id"]['image_cover_url'] = $a['image_cover_url'];
                $profile_completeness['boards']["$board_id"]['pin_count']       = $a['pin_count'];
                $profile_completeness['boards']["$board_id"]['follower_count']  = $a['follower_count'];
                $profile_completeness['boards']["$board_id"]['created_at']      = $a['created_at'];
            }

            //check whether each of their boards has a description
            if ($a['description'] == "") {
                $profile_completeness['boards_no_descriptions'] -= 1;
                $profile_completeness['boards']["$board_id"]['no_description']  = true;
                $profile_completeness['boards']["$board_id"]['url']             = $a['url'];
                $profile_completeness['boards']["$board_id"]['name']            = $a['name'];
                $profile_completeness['boards']["$board_id"]['url']             = $a['url'];
                $profile_completeness['boards']["$board_id"]['description']     = $a['description'];
                $profile_completeness['boards']["$board_id"]['image_cover_url'] = $a['image_cover_url'];
                $profile_completeness['boards']["$board_id"]['pin_count']       = $a['pin_count'];
                $profile_completeness['boards']["$board_id"]['follower_count']  = $a['follower_count'];
                $profile_completeness['boards']["$board_id"]['created_at']      = $a['created_at'];
            }
        }
        $profile_completeness['board_count'] = $a['board_count'];

        return $profile_completeness;

    }

    /**
     * @return Tasks
     */
    public function tasks()
    {
        $profile_tasks = Tasks::$profile_task_names;

        $tasks = new Tasks();

        foreach ($profile_tasks as $name) {
            $task = new Task($name);
            $task->setType(Task::TYPE_PROFILE);
            $task->setIdentifier($this);

            switch ($name) {

                default:
                    Log::warning(
                       'A task was added that does not get evaluated',
                       $task
                    );
                    break;

                case 'has_description':
                    if ($this->hasDescription()) {
                        $task->setComplete();
                    }
                    break;
                case 'has_facebook':
                    if ($this->hasFacebook()) {
                        $task->setComplete();
                    }
                    break;
                case 'has_website':
                    if ($this->hasWebsite()) {
                        $task->setComplete();
                    }
                    break;
                case 'has_website_verified':
                    if ($this->hasWebsiteVerified()) {
                        $task->setComplete();
                    }
                    break;
                case 'has_location':
                    if ($this->hasLocation()) {
                        $task->setComplete();
                    }
                    break;

                case 'has_image':
                    if ($this->hasImage()) {
                        $task->setComplete();
                    }
                    break;

                case 'board_count':
                    if($this->board_count > 15) {
                        $task->setComplete();
                    }
                    break;
                case 'boards_less_than_ten_pins':

                    /** @var $board \Board */
                    foreach ($this->getActiveDBBoards() as $board) {
                        $task = new Task($name);
                        $task->setType(Task::TYPE_BOARD_PINS);
                        $task->setIdentifier($board);
                        if ($board->pin_count > 9) {
                            $task->setComplete();
                        }
                        $tasks->add($task);
                    }
                    unset($task);
                    break;

                case 'boards_no_categories':

                    /** @var $board \Board */
                    foreach ($this->getActiveDBBoards() as $board) {
                        $task = new Task($name);
                        $task->setType(Task::TYPE_BOARD_PINS);
                        $task->setIdentifier($board);

                        if (
                            !empty($board->category) AND
                            $board->category != 'no_category'
                        ) {
                            $task->setComplete();
                        }
                        $tasks->add($task);
                    }
                    unset($task);
                    break;

                case 'boards_no_description':

                    /** @var $board \Board */
                    foreach ($this->getActiveDBBoards() as $board) {
                        $task = new Task($name);
                        $task->setType(Task::TYPE_BOARD_PINS);
                        $task->setIdentifier($board);

                        if (!empty($board->description)) {
                            $task->setComplete();
                        }
                        $tasks->add($task);
                    }
                    unset($task);
                    break;

                    break;
            }

            if ($task) {
                $tasks->add($task);
            }
        }

        $profile_completeness = $this->getProfileCompleteness();

        foreach ($profile_completeness as $key => $value) {

            $task = $tasks->get($key . '-' . $this->user_id);

            if(!$task) {
                continue;
            }

            switch ($key) {

                default:
                    continue;
                    break;

                    break;

                case 'board_count':

                    if ($value >= 10) {
                        $task->setComplete();
                        $tasks->add($task);
                    }
                    break;

                case 'boards_less_than_ten_pins':
                case 'boards_no_categories':
                case 'boards_no_descriptions':

                    if ($value >= 0) {
                        $task->setComplete();
                        $tasks->add($task);
                    }

                    break;
            }

        }

        return $tasks;
    }

    /**
     * Returns information about peak days and times to pin.
     *
     * @return array
     */
    public function getPeakDaysAndTimesData()
    {
        // Get day-time counts.
        $total_pin_count   = 0;
        $total_repin_count = 0;
        $pin_counter       = array();

        $STH = $this->DBH->prepare("
            SELECT count(*) AS pinCount, sum(repin_count) AS repinCount, FROM_UNIXTIME(created_at,'%w-%k') AS pDate
            FROM data_pins_new
            WHERE user_id = :user_id
            GROUP BY pDate
        ");

        $STH->execute(array(
            ':user_id' => $this->user_id,
        ));

        foreach ($STH->fetchAll() as $data) {
            $period = $data->pDate;
            $pins   = $data->pinCount;
            $repins = $data->repinCount;

            $pin_counter["$period"] = array(
                'period'         => $period,
                'pins'           => $pins,
                'repins'         => $repins,
                'repins_per_pin' => number_format($repins / $pins, 1, '.', ''),
            );

            $total_pin_count   += $pins;
            $total_repin_count += $repins;
        }

        $total_repins_per_pin = number_format($total_repin_count / $total_pin_count, 2, '.', '');

        // Get day counts only.
        $day_counter = array();

        $STH = $this->DBH->prepare("
            SELECT count(*) AS pinCount, sum(repin_count) AS repinCount, FROM_UNIXTIME(created_at,'%w') AS pDate
            FROM data_pins_new
            WHERE user_id = :user_id
            GROUP BY pDate
        ");

        $STH->execute(array(
            ':user_id' => $this->user_id,
        ));

        foreach ($STH->fetchAll() as $data) {
            $period = $data->pDate;
            $pins   = $data->pinCount;
            $repins = $data->repinCount;

            $day_counter["$period"] = array(
                'period'         => $period,
                'pins'           => $pins,
                'repins'         => $repins,
                'repins_per_pin' => number_format($repins / $pins, 1, '.', ''),
            );
        }

        // Get hour counts only.
        $hour_counter = array();

        $STH = $this->DBH->prepare("
            SELECT count(*) AS pinCount, sum(repin_count) AS repinCount, FROM_UNIXTIME(created_at,'%k') AS pDate
            FROM data_pins_new
            WHERE user_id = :user_id
            GROUP BY pDate
        ");

        $STH->execute(array(
            ':user_id' => $this->user_id,
        ));

        foreach ($STH->fetchAll() as $data) {
            $period = $data->pDate;
            $pins   = $data->pinCount;
            $repins = $data->repinCount;

            $hour_counter["$period"] = array(
                'period'         => $period,
                'pins'           => $pins,
                'repins'         => $repins,
                'repins_per_pin' => number_format($repins / $pins, 1, '.', ''),
            );
        }

        // Figure out the most active and effective day.
        $best_pins_day           = -1;
        $best_repins_day         = -1;
        $best_repins_per_pin_day = -1;
        $max_day_pins            = 0;
        $max_day_repins          = 0;
        $max_day_repins_per_pin  = 0;

        for ($i = 0; $i < 7; $i++) {
            if (isset($day_counter[$i])) {
                $this_pins = $day_counter["" . ($i) . ""]['pins'];
                if (!$this_pins) {
                    $this_pins = 0;
                }

                $this_repins = $day_counter["" . ($i) . ""]['repins'];
                if (!$this_repins) {
                    $this_repins = 0;
                }

                $this_repins_per_pin = $day_counter["" . ($i) . ""]['repins_per_pin'];
                if (!$this_repins_per_pin) {
                    $this_repins_per_pin = 0;
                }
            } else {
                $this_pins   = 0;
                $this_repins = 0;
                $this_repins_per_pin = 0;
            }

            if ($this_pins > $max_day_pins) {
                $max_day_pins                    = $this_pins;
                $best_pins_day                   = $i;
                $max_day_pins_avg_repins_per_pin = $this_repins_per_pin;
            }

            if ($this_repins > $max_day_repins) {
                $max_day_repins  = $this_repins;
                $best_repins_day = $i;
            }

            if ($this_repins_per_pin > $max_day_repins_per_pin) {
                $max_day_repins_per_pin       = $this_repins_per_pin;
                $best_repins_per_pin_day      = $i;
                $best_repins_per_pin_day_pins = $this_pins;
            }
        }

        // Figure out the most active and effective hour.
        $best_pins_hour           = -1;
        $best_repins_hour         = -1;
        $best_repins_per_pin_hour = -1;
        $max_hour_pins            = 0;
        $max_hour_repins          = 0;
        $max_hour_repins_per_pin  = 0;

        for ($i = 0; $i < 24; $i++) {
            if (isset($hour_counter[$i])) {
                $this_pins = $hour_counter["" . ($i) . ""]['pins'];
                if (!$this_pins) {
                    $this_pins = 0;
                }

                $this_repins = $hour_counter["" . ($i) . ""]['repins'];
                if (!$this_repins) {
                    $this_repins = 0;
                }

                $this_repins_per_pin = $hour_counter["" . ($i) . ""]['repins_per_pin'];
                if (!$this_repins_per_pin) {
                    $this_repins_per_pin = 0;
                }
            } else {
                $this_pins = 0;
                $this_repins = 0;
                $this_repins_per_pin = 0;
            }

            if ($this_pins > $max_hour_pins) {
                $max_hour_pins                    = $this_pins;
                $best_pins_hour                   = $i;
                $max_hour_pins_avg_repins_per_pin = $this_repins_per_pin;
            }

            if ($this_repins > $max_hour_repins) {
                $max_hour_repins  = $this_repins;
                $best_repins_hour = $i;
            }

            if ($this_repins_per_pin > $max_hour_repins_per_pin) {
                $max_hour_repins_per_pin  = $this_repins_per_pin;
                $best_repins_per_pin_hour = $i;
            }
        }

        return array(
            'total_pin_count'                  => $total_pin_count,
            'total_repin_count'                => $total_repin_count,
            'pin_counter'                      => $pin_counter,
            'total_repins_per_pin'             => $total_repins_per_pin,
            'day_counter'                      => $day_counter,
            'hour_counter'                     => $hour_counter,
            'best_pins_day'                    => $best_pins_day,
            'best_repins_day'                  => $best_repins_day,
            'best_repins_per_pin_day'          => $best_repins_per_pin_day,
            'max_day_pins'                     => $max_day_pins,
            'max_day_repins'                   => $max_day_repins,
            'max_day_repins_per_pin'           => $max_day_repins_per_pin,
            'max_day_pins_avg_repins_per_pin'  => $max_day_pins_avg_repins_per_pin,
            'best_repins_per_pin_day_pins'     => $best_repins_per_pin_day_pins,
            'best_pins_hour'                   => $best_pins_hour,
            'best_repins_hour'                 => $best_repins_hour,
            'best_repins_per_pin_hour'         => $best_repins_per_pin_hour,
            'max_hour_pins'                    => $max_hour_pins,
            'max_hour_repins'                  => $max_hour_repins,
            'max_hour_repins_per_pin'          => $max_hour_repins_per_pin,
            'max_hour_pins_avg_repins_per_pin' => $max_hour_pins_avg_repins_per_pin,
        );
    }

    /**
     * @return int
     */
    public function daysSinceCreated() {
        return Carbon::createFromFormat('U',$this->created_at)->diffInDays();
    }

}

class ProfileException extends DBModelException {}

class ProfileNotFoundException extends ProfileException
{
    protected $identifier;

    public function __construct($message, $identifier = false, $code = 0)
    {
        $this->identifier = $identifier;

        if ($identifier) {
            $message .= "($identifier)";
        }

        return parent::__construct($message, $code);
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }
}
