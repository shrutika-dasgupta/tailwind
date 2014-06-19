<?php

use Pinleague\Pinterest;
use Pinleague\Pinterest\PinterestException;

/**
 * Pin model
 */
class Pin extends PDODatabaseModel
{
    public static $track_types = array('user', 'free', 'competitor', 'orphan');
    /**
     * The unique id of the pin from the Pinterest API
     *
     * @var int
     */
    public $pin_id;
    /**
     * The unique id of the pinner's profile from the Pinterest API
     *
     * @var int
     */
    public $user_id;
    /**
     * The unique id of the the board this pin was pinned to from the Pinterest
     * API
     *
     * @var int
     */
    public $board_id;
    public
        $domain,
        $method,
        $is_repin,
        $parent_pin,
        $via_pinner,
        $origin_pin,
        $origin_pinner,
        $image_url,
        $image_square_url,
        $link,
        $description,
        $location,
        $dominant_color,
        $rich_product,
        $repin_count,
        $like_count,
        $comment_count,
        $created_at,
        $image_id = '',
        $timestamp,
        $track_type,
        $last_pulled;
    public
        $table = 'data_pins_new',
        $columns =
        array(
            'pin_id',
            'user_id',
            'board_id',
            'domain',
            'method',
            'is_repin',
            'parent_pin',
            'via_pinner',
            'origin_pin',
            'origin_pinner',
            'image_url',
            'image_square_url',
            'link',
            'description',
            'location',
            'dominant_color',
            'rich_product',
            'repin_count',
            'like_count',
            'comment_count',
            'created_at',
            'image_id',
            'last_pulled',
            'track_type',
            'timestamp',
        ),
        $primary_keys = array('pin_id');
    public $attribution = false;
    protected $_pinner = null;
    protected $_board = false;
    protected $_pin_history = false;
    protected $_comments = false;

    /**
     * Load up pinterest data into pin object
     *
     * @author  Will
     *
     * @param $data
     *
     * @throws Pinleague\Pinterest\PinterestException
     * @return $this
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
                throw new PinterestException('No data to load into pin', $data['code']);
            }

            /*
             * Again, with the array typecasting from ^^^
             */
            $data = (array)$data['data'];
        }

        if (array_key_exists('id', $data)) {

            $this->parseResponseForUserId($data);
            $this->parseResponseForBoardId($data);
            $this->parseResponseForOriginPin($data);
            $this->parseResponseForOriginPinner($data);
            $this->parseResponseForParentPin($data);
            $this->parseResponseForViaPinner($data);
            $this->parseResponseForRichMetadata($data);

            if (!is_null($data['attribution'])) {
                $this->parseResponseForAttribution($data);
            }

            $this->pin_id           = $data['id'];
            $this->domain           = $data['domain'];
            $this->method           = $data['method'];
            $this->is_repin         = $data['is_repin'];
            $this->image_url        = $data['image_medium_url'];
            $this->image_square_url = $data['image_square_url'];
            $this->link             = $data['link'];
            $this->description      = $data['description'];
            //$this->location         = $data['location'];
            $this->dominant_color = $data['dominant_color'];
            $this->repin_count    = $data['repin_count'];
            $this->like_count     = $data['like_count'];
            $this->comment_count  = $data['comment_count'];
            $this->last_pulled    = time();
            $this->timestamp      = time();

            $this->created_at = Pinterest::creationDateToTimeStamp($data['created_at']);

        } else {
            throw new PinterestException('There was a successful response, but no data to load');
        }

        return $this;
    }

    /**
     * Load up DBO object into pin object
     *
     * @author Yesh
     *
     * @param $data
     *
     * @throws Pinleague\PinterestException
     * @return $this
     */
    public function loadDBDataForTracking($data)
    {
        $this->pin_id           = $data->pin_id;
        $this->user_id          = $data->user_id;
        $this->board_id         = $data->board_id;
        $this->domain           = $data->domain;
        $this->method           = $data->method;
        $this->is_repin         = $data->is_repin;
        $this->parent_pin       = $data->parent_pin;
        $this->via_pinner       = $data->via_pinner;
        $this->origin_pin       = $data->origin_pin;
        $this->origin_pinner    = $data->origin_pinner;
        $this->image_url        = $data->image_url;
        $this->image_square_url = $data->image_square_url;
        $this->link             = $data->link;
        $this->description      = $data->description;
        //$this->location         = $data->location;
        $this->dominant_color = $data->dominant_color;
        $this->rich_product   = $data->rich_product;
        $this->repin_count    = $data->repin_count;
        $this->like_count     = $data->like_count;
        $this->comment_count  = $data->comment_count;
        $this->created_at     = $data->created_at;
        $this->match_type     = $data->match_type;
        $this->timestamp      = $data->timestamp;

        return $this;
    }

    /**
     * Gets this pin's comments.
     *
     * @return array
     */
    public function comments()
    {

        if($this->_comments){
            return $this->_comments;
        }

        return $this->_comments = PinsComment::find(
                          array(
                               'pin_id' => $this->pin_id,
                          )
        );
    }

    /**
     * Gets the pinner profile for the pin.
     *
     * @param array|Object $data
     *
     * @return Profile
     */
    public function pinner($data = array())
    {
        if (!empty($data)) {
            $pinner = new Profile();
            $pinner->loadDBData($data);

            $this->_pinner = $pinner;
        }

        if (empty($this->_pinner)) {
            $this->_pinner = Profile::find($this->user_id);
        }

        return $this->_pinner;
    }

    /**
     * Find the board from which teh pin came
     *
     * @param bool $force_update
     *
     * @return array|bool
     */
    public function board($force_update = false)
    {
        if ($this->_board AND !$force_update) {
            return $this->_board;
        }

        return Board::find(
                    array(
                         'board_id' => $this->board_id,
                         'user_id'  => $this->user_id
                    )
        );
    }

    /**
     * @author  Will
     *
     * @return PinsHistories
     */
    public function pinHistory()
    {
        if (!$this->_pin_history) {
            $this->_pin_history = new PinsHistories();
        }

        return $this->_pin_history;
    }

    /**
     * @author  Will
     *
     * @param $track_type
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setTrackType($track_type)
    {
        if (not_in_array($track_type, self::$track_types)) {
            throw new InvalidArgumentException("$track_type is not a valid track_type");
        }

        $this->track_type = $track_type;

        return $this;
    }

    /**
     * Sets up the PinAttribution object based on the API response
     *
     * @author  John
     * @author  Will
     *
     * @param $data
     *
     * @returns \PinAttribution
     */
    protected function parseResponseForAttribution($data)
    {
        if (array_key_exists("attribution", $data)) {

            if (is_object($data['attribution'])) {
                $data['attribution'] = (array)$data['attribution'];
            }

            if (is_array($data['attribution'])) {

                $this->attribution         = new PinAttribution();
                $this->attribution->pin_id = $this->pin_id;
                $this->attribution->loadAPIData($data['attribution']);

                return $this->attribution;
            }

        }

        return false;
    }

    /**
     * Get board_id from response data
     *
     * @author  John
     * @author  Will
     */

    protected function parseResponseForBoardId($data)
    {
        if (array_key_exists("board", $data)) {

            if (is_object($data['board'])) {
                $data['board'] = (array)$data['board'];
            }

            if (is_array($data['board'])) {

                if (array_key_exists("id", $data['board'])) {
                    $this->board_id = $data['board']['id'];
                }

            }
        } else {
            $this->board_id = '';
        }
    }

    /**
     * @author  John
     * @author  Will
     */
    protected function parseResponseForOriginPin($data)
    {
        if (array_key_exists("origin_pin", $data)) {

            if (is_object($data['origin_pin'])) {
                $data['origin_pin'] = (array)$data['origin_pin'];
            }

            if (is_array($data['origin_pin'])) {
                if (array_key_exists("id", $data['origin_pin'])) {
                    $this->origin_pin = $data['origin_pin']['id'];
                }
            }

        } else {
            $this->origin_pin = '';
        }
    }

    /**
     * @author  John
     * @author  Will
     */
    protected function parseResponseForOriginPinner($data)
    {
        if (array_key_exists("origin_pinner", $data)) {

            if (is_object($data['origin_pinner'])) {
                $data['origin_pinner'] = (array)$data['origin_pinner'];
            }

            if (is_array($data['origin_pinner'])) {
                if (array_key_exists("id", $data['origin_pinner'])) {
                    $this->origin_pinner = $data['origin_pinner']['id'];
                }
            }

        } else {
            $this->origin_pinner = '';
        }
    }

    /**
     * @author  John
     * @author  Will
     */
    protected function parseResponseForParentPin($data)
    {
        if (array_key_exists("parent_pin", $data)) {
            if (is_object($data['parent_pin'])) {
                $data['parent_pin'] = (array)$data['parent_pin'];
            }

            if (is_array($data['parent_pin'])) {
                if (array_key_exists("id", $data['parent_pin'])) {
                    $this->parent_pin = $data['parent_pin']['id'];
                }
            }
        } else {
            $this->parent_pin = '';
        }
    }

    /**
     * @author  John
     * @author  Will
     */
    protected function parseResponseForRichMetadata($data)
    {
        if (array_key_exists("rich_metadata", $data)) {

            if (is_object($data['rich_metadata'])) {
                $data['rich_metadata'] = (array)$data['rich_metadata'];
            }

            if (is_array($data['rich_metadata'])) {
                if (array_key_exists("id", $data['rich_metadata'])) {
                    return $this->rich_product = $data['rich_metadata']['id'];
                }
            }
        }

        return "";
    }

    /**
     * Set user_id from data response
     *
     * @author  John
     * @author  Will
     */
    protected function parseResponseForUserId($data)
    {
        if (array_key_exists("pinner", $data)) {
            /*
             * The multi-curl sends this back as an object, so we need to typecast it as an
             * array. I know, weaksauce.
             */
            if (is_object($data['pinner'])) {
                $data['pinner'] = (array)$data['pinner'];
            }

            if (is_array($data['pinner'])) {
                if (array_key_exists("id", $data['pinner'])) {
                    $this->user_id = $data['pinner']['id'];
                }
            }
        } else {
            $this->user_id = '';
        }
    }

    /**
     * @author  John
     * @author  Will
     */
    protected function parseResponseForViaPinner($data)
    {
        if (array_key_exists("via_pinner", $data)) {

            if (is_object($data['via_pinner'])) {
                $data['via_pinner'] = (array)$data['via_pinner'];
            }

            if (is_array($data['via_pinner'])) {

                if (array_key_exists("id", $data['via_pinner'])) {
                    $this->via_pinner = $data['via_pinner']['id'];
                }
            }

        } else {
            $this->via_pinner = '';
        }
    }
}
