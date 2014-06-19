<?php

use Pinleague\Pinterest;

/**
 * Collection of Pins
 *
 * @author Will
 */
class Pins extends DBCollection
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
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
            'timestamp'
        ),
        $primary_keys = array('pin_id');

    protected $_boards = false;

    /*
    |--------------------------------------------------------------------------
    | Instance Methods
    |--------------------------------------------------------------------------
    */

    /**
     * @author Yesh
     *
     * @param array $dont_update_these_columns
     * @param bool  $dont_log_error
     *
     * @return $this
     */
    public function insertUpdateDB($dont_update_these_columns = array(),$dont_log_error = false)
    {
        array_push($dont_update_these_columns,'image_id');

        $append = "ON DUPLICATE KEY UPDATE ";

        foreach ($this->columns as $column) {
            if($column != "track_type"){
                if(!in_array($column,$dont_update_these_columns)) {
                    $append .= "$column = VALUES($column),";
                }
            }
        }

        if (!in_array('track_type',$dont_update_these_columns)) {
            $append .=
                "track_type=IF(VALUES(track_type)='user',
                'user',IF(VALUES(track_type)='competitor', 'competitor', IF(track_type='traffic', VALUES(track_type), track_type)))";
        } else {
            $append = rtrim($append,',');
        }

        return $this->saveModelsToDB('INSERT INTO',$append);
    }


    /**
     * For a given set of pins, this will queue up api calls on the queue
     *
     * @author  Will
     * @param $api_call
     *
     * @return $this
     */
    public function queueApiCalls($api_call)
    {
        /*
         * Queue up Pin Engagement Api Calls to request individual repins, likes and comments
         * of each pin
         */

        $calls = new QueuedApiCalls();

        foreach ($this->models as $pin) {

            $add_call = false;

            switch ($api_call) {

                default:
                    $add_call = true;
                    break;

                case QueuedApiCall::CALL_PIN_ENGAGEMENT_COMMENTS:

                    if ($pin->comment_count > 0) {
                        $add_call = true;
                    }
                    break;

                case QueuedApiCall::CALL_PIN_ENGAGEMENT_LIKES:

                    if ($pin->like_count > 0) {
                        $add_call = true;
                    }
                    break;

                case QueuedApiCall::CALL_PIN_ENGAGEMENT_REPINS:

                    if ($pin->repin_count > 0) {
                        $add_call = true;
                    }
                    break;
            }

            if ($add_call) {

                $call             = new QueuedApiCall($api_call);
                $call->object_id  = $pin->pin_id;
                $call->track_type = 'pin_engagement';

                $calls->add($call);
            }
        }

        return $calls->insertIgnoreDB();
    }


    /**
     * @author Will
     *         Alex
     *
     * @return void
     *
     * Add comment data to the pins collection
     */
    public function comments()
    {
        if($this->count() > 0){

            $pin_ids_csv = $this->stringifyField("pin_id");
            $comments = DB::select(
                "SELECT subq.pin_id, subq.commenter_user_id, subq.comment_text, subq.created_at,
                         prof.username, prof.first_name, prof.last_name, prof.image,
                         prof.domain_url, prof.website_url, prof.facebook_url, prof.twitter_url,
                         prof.location, prof.pin_count, prof.follower_count
                FROM
                    (
                        SELECT pin_id, commenter_user_id, comment_text, created_at
                        FROM data_pins_comments
                        where pin_id in ($pin_ids_csv)
                    ) AS subq
                LEFT JOIN data_profiles_new AS prof ON (subq.commenter_user_id=prof.user_id)"
            );

            $pins_comments = new PinsComments();

            foreach($comments as $comment){
                $pins_comment                    = new PinsComment();
                $pins_comment->pin_id            = $comment->pin_id;
                $pins_comment->commenter_user_id = $comment->commenter_user_id;
                $pins_comment->comment_text      = str_replace("href='/", "target='_blank' href='http://pinterest.com/", $comment->comment_text);
                $pins_comment->created_at        = $comment->created_at;

                $commenter = new Profile();
                $commenter->user_id = $comment->commenter_user_id;
                $commenter->username = $comment->username;
                $commenter->first_name = $comment->first_name;
                $commenter->last_name = $comment->last_name;
                $commenter->image = $comment->image;
                $commenter->domain_url = $comment->domain_url;
                $commenter->website_url = $comment->website_url;
                $commenter->facebook_url = $comment->facebook_url;
                $commenter->twitter_url = $comment->twitter_url;
                $commenter->location = $comment->location;
                $commenter->pin_count = $comment->pin_count;
                $commenter->follower_count = $comment->follower_count;

                $pins_comment->commenter = $commenter;

                $pins_comments->add($pins_comment);
            }

            $pins_comments->sortBy('created_at', SORT_DESC);


            foreach ($this->models as $pin) {
                $comments_copy = $pins_comments->copy()->filter(
                                      function($model)use($pin){
                                          if($model->pin_id == $pin->pin_id){
                                              return true;
                                          }
                                          return false;
                                      }
                );

                $pin->setCache('comments',$comments_copy);
            }
        }
    }

    /**
     * Gets the boards for all these particular pins
     *
     * @author  Will
     *
     * @param bool $force_update
     *
     * @throws Exception
     * @return Boards
     */
    public function boards($force_update = false) {

        if($this->_boards AND !$force_update) {
            return $this->_boards;
        }

        throw new Exception('We need to use cached boards for now');

    }

    /**
     * If we know we have the board data cached, go through and fetch it from each pin
     * @author  Will
     */
    public function useCachedBoards(){
        $boards = new Boards();
        foreach ($this->models as $pin) {
            $boards->add($pin->board());
        }

        $this->_boards = $boards;

        return $this;
    }

    /**
     * This will calculate the number of repins per board from this set of pins
     *
     * @author  Will
     */
    public function boardsRepinCounts()
    {

        $boards_repins = array();

        /** @var $pin Pin */
        foreach ($this->models as $pin) {

            if (array_key_exists($pin->board_id,$boards_repins)) {
                $boards_repins[$pin->board_id] += $pin->repin_count;
                continue;
            }

           $boards_repins[$pin->board_id] = $pin->repin_count;

        }

        arsort($boards_repins);

        return $boards_repins;

    }

    /**
     * Returns the board with the most repins
     * (of this pin set)
     *
     * @author  Will
     *
     * @throws PinsException
     * @return Board
     */
    public function mostRepinnedBoard()
    {
        if ($this->count() == 0) {
            throw new PinsException('No pins in collection');
        }
        $boards_repins = $this->boardsRepinCounts();
        reset($boards_repins);
        $top_board_id  = key($boards_repins);

        $pin = array_first($this->models, function ($key,$model) use ($top_board_id) {
            if ($model->board_id == $top_board_id) {
                return true;
            }
            return false;
        });

        return $pin->board();
    }

    /**
     * Generates wordcloud data for the collection of pins.
     *
     * @param array $ignored_words
     *
     * @return array
     */
    public function wordcloud($ignored_words = array())
    {
        // Concatenate all pin descriptions together.
        $wordcloud = '';
        foreach ($this->getModels() as $pin) {
            $wordcloud .= ' ' . preg_replace('/[^A-Za-z0-9# ]/', ' ', strtolower($pin->description));
        }

        // Ignore certain words when parsing pin descriptions.
        $ignored_words = array_merge(Config::get('keywords.ignored_words'), $ignored_words);

        $words = array();
        foreach (explode(' ', $wordcloud) as $word) {
            if (in_array($word, $ignored_words) || strlen($word) <= 2) {
                continue;
            }

            if (!isset($words["$word"])) {
                $words["$word"] = array(
                    'word'  => $word,
                    'count' => 1,
                );
            } else {
                $words["$word"]['count'] += 1;
            }
        }

        usort($words, function ($a, $b) {
            if ($a['count'] < $b['count']) {
                return 1;
            } else if ($a['count'] == $b['count']) {
                return 0;
            } else {
                return -1;
            }
        });

        return $words;
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/

class PinsException extends CollectionException {}
