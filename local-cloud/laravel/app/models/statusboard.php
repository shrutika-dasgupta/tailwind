<?php

use Pinleague\Pinterest,
    Pinleague\PinterestException;

/**
 * Status profile
 *
 */
class StatusBoard extends PDODatabaseModel
{
    public
        $board_id,
        $owner_user_id,
        $last_pulled_pins,
        $last_pulled_followers,
        $followers_found,
        $last_updated_followers_found,
        $last_calced,
        $track_type,
        $is_owned,
        $is_collaborator,
        $collaborator_count,
        $category,
        $layout,
        $created_at,
        $follower_count,
        $pin_count,
        $added_at,
        $updated_at,
        $timestamp;

    public $columns = array(
        'board_id',
        'owner_user_id',
        'last_pulled_pins',
        'last_pulled_followers',
        'followers_found',
        'last_updated_followers_found',
        'last_calced',
        'track_type',
        'is_owned',
        'is_collaborator',
        'collaborator_count',
        'category',
        'layout',
        'created_at',
        'follower_count',
        'pin_count',
        'added_at',
        'updated_at',
        'timestamp'
    );
    public $primary_keys = array('board_id');

    /**
     * Class initializer.
     *
     * @return \StatusBoard
     */
    public function __construct()
    {
        $this->last_pulled_pins             = 0;
        $this->last_pulled_followers        = 0;
        $this->followers_found              = 0;
        $this->last_updated_followers_found = 0;
        $this->last_calced                  = 0;
        $this->added_at                     = time();
        $this->updated_at                   = time();
        $this->timestamp                    = time();

        parent::__construct();
    }

    /**
     * @author  Will
     */
    public static function create($board, $track_type)
    {
        $status = new StatusBoard();

        $STH = $status->DBH->prepare("
            insert into status_boards
            (
                board_id,
                owner_user_id,
                last_pulled_pins,
                last_pulled_followers,
                followers_found,
                last_updated_followers_found,
                last_calced,
                track_type,
                is_owned,
                is_collaborator,
                collaborator_count,
                category,
                layout,
                created_at,
                follower_count,
                pin_count,
                added_at,
                updated_at,
                timestamp
            ) VALUES (
                :board_id,
                :owner_user_id,
                :last_pulled_pins,
                :last_pulled_followers,
                :followers_found,
                :last_updated_followers_found,
                :last_calced,
                :track_type,
                :is_owned,
                :is_collaborator,
                :collaborator_count,
                :category,
                :layout,
                :created_at,
                :follower_count,
                :pin_count,
                :added_at,
                :updated_at,
                :timestamp
              )
              ON DUPLICATE KEY UPDATE
                 last_calced= VALUES(last_calced),
                 track_type = IF(VALUES(track_type)='user','user',track_type),
                 is_owned = IF(VALUES(is_owned)=1,1,is_owned),
                 is_collaborator = VALUES(is_collaborator),
                 collaborator_count = VALUES(collaborator_count),
                 category = VALUES(category),
                 layout = VALUES(layout),
                 created_at = VALUES(created_at),
                 follower_count = VALUES(follower_count),
                 pin_count = VALUES(pin_count),
                 updated_at = VALUES(updated_at),
                 timestamp = VALUES(timestamp)
       ");

        $status->board_id                     = $board->board_id;
        $status->owner_user_id                = $board->owner_user_id;
        $status->last_pulled_pins             = 0;
        $status->last_pulled_followers        = 0;
        $status->followers_found              = 0;
        $status->last_updated_followers_found = 0;
        $status->last_calced                  = 0;
        $status->track_type                   = $track_type;
        $status->is_owned                     = $board->is_owner;
        $status->is_collaborator              = $board->is_collaborator;
        $status->collaborator_count           = $board->collaborator_count;
        $status->category                     = $board->category;
        $status->layout                       = $board->layout;
        $status->created_at                   = $board->created_at;
        $status->follower_count               = $board->follower_count;
        $status->pin_count                    = $board->pin_count;
        $status->updated_at                   = time();
        $status->added_at                     = time();
        $status->timestamp                    = time();

        $params = array();

        foreach ($status->columns as $column) {
            $key          = ':' . $column;
            $params[$key] = $status->$column;
        }

        $STH->execute($params);

        return $status;
    }

    /**
     * Load up pinterest board data into status_board object
     *
     * @author  Alex
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
            $this->is_collaborator = $data['is_collaborative'];
            $this->collaborator_count = $data['collaborator_count'];
            $this->category           = $data['category'];
            $this->layout             = $data['layout'];
            $this->pin_count          = $data['pin_count'];
            $this->follower_count     = $data['follower_count'];
            $this->created_at         = Pinterest::creationDateToTimeStamp($data['created_at']);

        } else {
            throw new PinterestException('There was a successful response, but no data to load');
        }

        return $this;
    }


    /**
     * @author   Alex
     *
     * @param array $board_ids
     *
     * @return array counts
     *
     * Returns the actual number of followers we've found so far, as recorded in the
     * status_boards table.  We do this so that we're not constantly running
     * "select count(followers) from data_board_followers where board_id = $board_id" on
     * every single run of our board followers pull script.
     *
     * If this count has not been updated recently, we'll pull the count
     * from the data_board_followers table and update it in the status_boards table.
     */
    public static function getFollowersFoundCount($board_ids)
    {
        $DBH = DatabaseInstance::DBO();
        $board_follower_counts = array();

        if (count($board_ids) > 0) {
            $board_ids_implode = implode(",", $board_ids);

            $STH = $DBH->prepare(
                       "SELECT board_id, followers_found, last_updated_followers_found
                        FROM status_boards
                        WHERE board_id IN ($board_ids_implode)"
            );

            $STH->execute();

            $boards = $STH->fetchAll();

            foreach($boards as $board){
                $followers_found = $board->followers_found;
                $board_follower_counts[$board->board_id] = $followers_found;

                if($board->last_updated_followers_found < strtotime("-20 minutes")) {
                    $followers_found = BoardFollower::getFollowersFoundCount($board->board_id);
                    self::updateFollowersFound($board->board_id, $followers_found);
                    $board_follower_counts[$board->board_id] = $followers_found;
                }
            }

            return $board_follower_counts;
        }

        return false;
    }

    /**
     * @author   Alex
     *
     * @param $board_id
     * @param $followers_found
     *
     * Updates the followers_found and last_updated_followers_found fields
     */
    public static function updateFollowersFound($board_id, $followers_found)
    {
        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->prepare(
                   "UPDATE status_boards
                    SET followers_found = :followers_found,
                    last_updated_followers_found = :last_updated_followers_found
                    WHERE board_id = :board_id"
        );

        $STH->execute(
            array(
                 ':followers_found'              => $followers_found,
                 ':last_updated_followers_found' => time(),
                 ':board_id'                     => $board_id
            )
        );
    }
}
