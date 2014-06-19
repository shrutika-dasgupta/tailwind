<?php namespace Pinleague\Pinterest;

use
    BadMethodCallException,
    Exception,
    InvalidArgumentException;

/**
 * SDK for the Pinterest API
 *
 * @author John
 * @author Will
 * @author Alex
 * @author Yesh
 */
class BasePinterest
{
    /**
     * If we should send the call right away or put them in the "calls" array
     * and call them using sendBatchRequests
     *
     * @see sendBatchRequests
     * @see $calls
     *
     * @var bool
     */
    public $batch_calls = false;

    /**
     * A static instance, usually used for sending batch requests
     *
     * @var BasePinterest
     */
    protected static $instance;

    /**
     * A count of the number of calls added to the calls array
     * Not sure why we didn't use count($calls). Will get back to you on that one
     *
     * @var int
     */
    protected $batch_number = 0;

    /**
     * The version of the Pinterest API we're using
     *
     * @var string
     */
    protected $api_version = 3;

    /**
     * The oauth endpoint for pinterest
     * @var string
     */
    protected $oauth_endpoint = 'https://www.pinterest.com/oauth';

    /**
     * The api oauth code exchange endpoint
     * @var string
     */
    protected $api_endpoint = 'https://api.pinterest.com/';

    /**
     * An Array of calls to be made using sendBatchRequests
     *
     * @see sendBatchRequests
     *
     * @var array
     */
    protected $calls = array();

    /**
     * Keys needed for Pinterest
     *
     * @var string
     */
    protected $client_id, $secret,$call_back_uri;

    /**
     * @var string
     */
    protected $access_token = null;

    /**
     * How we interface with the API
     *
     * @var \Pinleague\Pinterest\Transports\TransportInterface
     */
    protected $transport;

    /**
     * Add fields are what we are asking for back from the API
     * These are some sane defaults, but you could change them in each
     * place they are called if you want to switch it up
     *
     * @var array
     */
    protected $add_fields_defaults = array(
        'user'  => array(
            'user.follower_count',
            'user.following_count',
            'user.pin_count',
            'user.like_count',
            'user.board_count',
            'user.domain_url',
            'user.domain_verified',
            'user.email',
            'user.facebook_url',
            'user.gplus_url',
            'user.location',
            'user.twitter_url',
            'user.website_url',
            'user.created_at',
            'user.about'
        ),
        'pin'   => array(
            'pin.parent_pin',
            'pin.dominant_color',
            'pin.client_id',
            'pin.embed',
            'pin.method',
            'pin.rich_metadata',
            'pin.via_pinner',
            'pin.board',
            'pin.pinner',
            'pin.origin_pin',
            'pin.origin_pinner'
        ),
        'board' => array(
            'board.owner',
            'board.pin_count',
            'board.follower_count',
            'board.description',
            'board.collaborator_count',
            'board.image_cover_url'
        )
    );

    /**
     * Inital list of Pinterest categories
     * @var array
     */
    public static $categories = array(
        'animals',
        'apparel',
        'architecture',
        'art',
        'art_arch',
        'cars_motorcycles',
        'celebrities',
        'celebrities_public_figures',
        'corgis',
        'design',
        'diy_crafts',
        'education',
        'everything',
        'fashion',
        'featured',
        'film_music_books',
        'fitness',
        'food_drink',
        'for_dad',
        'gardening',
        'geek',
        'gift_guides',
        'gifts',
        'hair_beauty',
        'health_fitness',
        'history',
        'holidays',
        'holidays_events',
        'home',
        'home_decor',
        'home_improvement',
        'humor',
        'illustrations_posters',
        'kids',
        'men_apparel',
        'mens_fashion',
        'mylife',
        'other',
        'outdoors',
        'people',
        'pets',
        'photography',
        'popular',
        'prints_posters',
        'products',
        'quotes',
        'science',
        'science_nature',
        'sports',
        'tattoos',
        'technology',
        'travel',
        'travel_places',
        'videos',
        'wedding_events',
        'weddings',
        'women_apparel',
        'womens_fashion'
    );

    /**
     * constructor
     *
     * @param $client_id
     * @param $secret
     * @param $callback_uri
     * @param $transport
     */
    public function __construct($client_id, $secret, $callback_uri, $transport)
    {
        $this->client_id     = $client_id;
        $this->secret        = $secret;
        $this->call_back_uri = $callback_uri;
        $this->transport     = $transport;

    }

    /**
     * Set creation date to epoch time
     *
     * @author John
     */
    public static function creationDateToTimeStamp($date)
    {
        if (!$date) {
            return -1;
        }

        $parsed = strtotime($date);

        return $parsed;
    }

    /**
     * Gets a persistent instance
     *
     * @author  Will
     *
     * @param $client_id
     * @param $secret
     * @param $call_back_uri
     * @param $transport
     *
     * @returns BasePinterest
     */
    public static function getInstance($client_id, $secret, $call_back_uri, $transport)
    {
        if (!self::$instance) {
            return self::$instance = new self($client_id, $secret, $call_back_uri, $transport);
        }

        return self::$instance;
    }

    /**
     * Get the board information via the board id
     *
     * @author   John
     * @author   Will
     *
     * @param       $board_id
     * @param array $parameters
     *
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    public function getBoardInformation($board_id, $parameters = array())
    {

        if (is_array($parameters) == false) {
            throw new InvalidArgumentException(
                'The parameters passed to getBoardInformation is not an array'
            );
        }

        $default_add_fields = $this->add_fields_defaults['board'];

        $this->buildAddFields($default_add_fields, $parameters);

        return $this->makeRequest("/boards/$board_id/", $parameters);
    }

    /**
     * Get the pins of a given board
     *
     * @author   John
     * @author   Will
     *
     * @param       $board_id
     * @param array $parameters
     *
     *
     * @throws \InvalidArgumentException
     * @return array
     */
    public function getBoardsPins($board_id, $parameters = array())
    {
        if (is_array($parameters) == false) {
            throw new InvalidArgumentException(
                'The parameters passed to getBoardsPins is not an array'
            );
        }

        $default_add_fields = $this->add_fields_defaults['pin'];

        $this->buildAddFields($default_add_fields, $parameters);

        $parameters['page_size'] = 250;

        return $this->makeRequest("/boards/$board_id/pins/", $parameters);
    }

    /**
     * @author   Alex
     *
     * @param       $board
     * @param array $parameters
     *
     * @throws \InvalidArgumentException
     * @return array
     */
    public function getBoardFollowers($board, $parameters = array())
    {

        if (is_array($parameters) == false) {
            throw new InvalidArgumentException(
                'The parameters passed to getBoardFollowers is not an array'
            );
        }

        $default_add_fields   = $this->add_fields_defaults['user'];
        $default_add_fields[] = 'user.first_name';
        $default_add_fields[] = 'user.last_name';

        $this->buildAddFields($default_add_fields, $parameters);

        $parameters['page_size'] = 250;

        return $this->makeRequest("boards/$board/followers/", $parameters);
    }


    /**
     * @author   John
     * @author   Will
     *
     * @param       $domain
     * @param array $parameters
     *
     * @throws \InvalidArgumentException
     * @return array
     */
    public function getBrandMentions($domain, $parameters = array())
    {
        if (is_array($parameters) == false) {
            throw new InvalidArgumentException(
                'The parameters passed to getBrandMentions is not an array'
            );
        }

        $default_add_fields = $this->add_fields_defaults['pin'];

        $this->buildAddFields($default_add_fields, $parameters);

        $parameters['page_size'] = 250;

        return $this->makeRequest("/domains/$domain/pins/", $parameters);
    }

    /**
     * This API call returns the list of Pins from the domain ordered by click
     * count value over a specified period of time (daily, weekly or bi-weekly).
     * This endpoint returns up to 1,000 Pins. So, if the aggregation value is
     * set to "weekly", then the API returns the most-clicked Pins for the past
     * week on the specified domain. This endpoint is bookmarked, and each
     * bookmark returns up to 100 Pins. The bookmark value returned in the
     * response body allows you to page through to the next set of Pins.
     *
     * This endpoint is bookmarked
     *
     * @author  Will
     */
    public function getMostClickedPins($domain, $parameters = array())
    {
        return $this->makeRequest("domains/$domain/pins/top/clicks/");
    }

    /**
     * Get a return of an array of pins for a given category feed
     *
     * @author   Yesh
     * @author   Will
     *
     * @param string $category_name
     * @param array  $parameters
     *
     * @throws \InvalidArgumentException
     * @return array
     */
    public function getCategoryFeed($category_name, $parameters = array())
    {

        if (is_array($parameters) == false) {
            throw new InvalidArgumentException(
                'The parameters passed to getCategoryFeed is not an array'
            );
        }

        $default_add_fields = $this->add_fields_defaults['pin'];

        $this->buildAddFields($default_add_fields, $parameters);

        $parameters['page_size'] = 250;
        $parameters['category']  = $category_name;

        return $this->makeRequest("/feeds/$category_name/", $parameters);
    }

    /**
     * @author   John
     * @author   Will
     *
     *
     * @param       $pin_id
     * @param array $parameters
     *
     * @throws \InvalidArgumentException
     * @return array
     */
    public function getPinComments($pin_id, $parameters = array())
    {
        if (is_array($parameters) == false) {
            throw new InvalidArgumentException(
                'The parameters passed to getPinComments is not an array'
            );
        }

        $default_add_fields = $this->add_fields_defaults['user'];

        $this->buildAddFields($default_add_fields, $parameters);

        return $this->makeRequest("pins/$pin_id/comments/", $parameters);
    }

    /**
     * @author   John
     * @author   Will
     *
     *
     * @param       $pin_id
     * @param array $parameters
     *
     * @throws \InvalidArgumentException
     * @return array
     */
    public function getPinInformation($pin_id, $parameters = array())
    {
        if (is_array($parameters) == false) {
            throw new InvalidArgumentException(
                'The parameters passed to getPinInformation is not an array'
            );
        }

        $default_add_fields = $this->add_fields_defaults['pin'];

        $this->buildAddFields($default_add_fields, $parameters);

        return $this->makeRequest("pins/$pin_id/", $parameters);
    }

    /**
     * @author   John
     * @author   Will
     *
     *
     * @param       $pin_id
     * @param array $parameters
     *
     * @throws \InvalidArgumentException
     * @return array
     */
    public function getPinLikes($pin_id, $parameters = array())
    {
        if (is_array($parameters) == false) {
            throw new InvalidArgumentException(
                'The parameters passed to getPinLikes is not an array'
            );
        }

        $default_add_fields = $this->add_fields_defaults['user'];

        $this->buildAddFields($default_add_fields, $parameters);

        return $this->makeRequest("pins/$pin_id/likes/", $parameters);
    }

    /**
     * @author   John
     * @author   Will
     *
     * @param int   $pin_id
     * @param array $parameters
     *
     * @throws \InvalidArgumentException
     * @return array
     */
    public function getPinRepins($pin_id, $parameters = array())
    {
        if (is_array($parameters) == false) {
            throw new InvalidArgumentException(
                'The parameters passed to getPinRepins is not an array'
            );
        }

        $default_add_fields = array(
            'board.owner',
            'board.follower_count'
        );

        $this->buildAddFields($default_add_fields, $parameters);

        $parameters['page_size'] = 150;

        return $this->makeRequest("pins/$pin_id/repinned_onto/", $parameters);
    }

    /**
     * @author  John
     *
     * @param       $username
     *
     * @param array $parameters
     *
     * @throws \InvalidArgumentException
     * @return array
     */
    public function getProfileBoards($username, $parameters = array())
    {
        if (is_array($parameters) == false) {
            throw new InvalidArgumentException(
                'The parameters passed to getPinRepins is not an array'
            );
        }

        $default_add_fields   = $this->add_fields_defaults['board'];
        $default_add_fields[] = 'board.created_at';

        $this->buildAddFields($default_add_fields, $parameters);

        $parameters['page_size'] = 150;

        return $this->makeRequest("users/$username/boards/", $parameters);
    }

    /**
     * @author   John
     *
     * @param       $username
     * @param array $parameters
     *
     * @throws \InvalidArgumentException
     * @return array
     */
    public function getProfileFollowers($username, $parameters = array())
    {

        if (is_array($parameters) == false) {
            throw new InvalidArgumentException(
                'The parameters passed to getProfileFollowers is not an array'
            );
        }

        $default_add_fields   = $this->add_fields_defaults['user'];
        $default_add_fields[] = 'user.first_name';
        $default_add_fields[] = 'user.last_name';

        $this->buildAddFields($default_add_fields, $parameters);

        $parameters['page_size'] = 65;

        return $this->makeRequest("users/$username/followers/", $parameters);
    }

    /**
     * @author  John
     * @author  Will
     *
     * @param       $username
     * @param array $parameters
     *
     * @throws \InvalidArgumentException
     * @return array
     */
    public function getProfileFollowing($username, $parameters = array())
    {

        if (is_array($parameters) == false) {
            throw new InvalidArgumentException(
                'The parameters passed to getProfileFollowing is not an array'
            );
        }

        return $this->makeRequest("users/$username/following/", $parameters);
    }

    /**
     * @author  John
     * @author  Will
     *
     * @param       $username
     * @param array $parameters
     *
     * @throws BasePinterestException
     * @return array
     */
    public function getProfileInformation($username, $parameters = array())
    {

        if (is_array($parameters) == false) {
            throw new BasePinterestException(
                'The parameters passed to getProfileInformation is not an array'
            );
        }

        return $this->makeRequest("users/$username/", $parameters);
    }

    /**
     * Uses the public API to get information about a pin id
     * namely the board ID so we can fill in the rest of the information later
     *
     * @author  Yesh
     * @author  Will
     *
     * @example
     * $Pinterest->getPublicPinInformation(array($pin_1,$pin_2,$pin_3));
     *
     */
    public function getPublicPinInformation($pin_ids_or_pin_id)
    {

        if (!is_array($pin_ids_or_pin_id)) {
            $pin_ids_or_pin_id = [$pin_ids_or_pin_id];
        }

        if (count($pin_ids_or_pin_id) > 10) {
            throw new InvalidArgumentException(
                'Too many Pin ids. Only 10 are allowed at a time.'
            );
        }

        $parameters['pin_ids'] = implode(',', $pin_ids_or_pin_id);

        $url = 'http://api.pinterest.com/v3/pidgets/pins/info/';

        $response = $this->transport->makeRequest('GET', $url, $parameters);

        $result = array();

        foreach ($response['data'] as $pin) {
            if (array_key_exists('id', $pin)) {
                $result[$pin['id']] = $pin;
            }
        }

        return $result;
    }

    /**
     * @author   Alex
     * @author   Will
     *
     * @param       $keyword
     * @param array $parameters
     *
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public function getSearchBoardsFromKeyword($keyword, $parameters = array())
    {
        if (is_array($parameters) == false) {
            throw new InvalidArgumentException(
                'The parameters passed getSearchBoardsFromKeyword is not an array'
            );
        }

        $default_add_fields   = $this->add_fields_defaults['board'];
        $default_add_fields[] = 'board.created_at';

        $this->buildAddFields($default_add_fields, $parameters);

        $parameters['page_size'] = 250;
        $parameters['query']     = $keyword;

        return $this->makeRequest("/search/boards/", $parameters);
    }

    /**
     * @author   John
     * @author   Will
     *
     * @param       $keyword
     * @param array $parameters
     *
     * @throws \InvalidArgumentException
     * @return array
     */
    public function getSearchPinsFromKeyword($keyword, $parameters = array())
    {

        if (is_array($parameters) == false) {
            throw new InvalidArgumentException(
                'The parameters passed getSearchPinsFromKeyword is not an array'
            );
        }

        $default_add_fields = $this->add_fields_defaults['pin'];

        $this->buildAddFields($default_add_fields, $parameters);

        $parameters['page_size'] = 100;
        $parameters['query']     = $keyword;

        return $this->makeRequest("/search/pins/", $parameters);
    }

    /**
     * @author  John
     * @author  Will
     *
     * @param       $username
     * @param array $parameters
     *
     * @throws \InvalidArgumentException
     * @return int
     */
    public function getUserIDFromUsername($username, $parameters = array())
    {
        if (is_array($parameters) == false) {
            throw new InvalidArgumentException(
                'The parameters passed to getUserIDFromUsername is not an array'
            );
        }

        $profile_data = $this->makeRequest("/users/$username/", $parameters);

        return array_get($profile_data, 'id', 0);
    }

    /**
     * @author   John
     * @author   Will
     *
     * @param       $user_id
     * @param array $parameters
     *
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public function getUserPins($user_id, $parameters = array())
    {

        if (is_array($parameters) == false) {
            throw new InvalidArgumentException(
                'The parameters passed to getUserPins is not an array'
            );
        }

        $default_add_fields = $this->add_fields_defaults['pin'];

        $this->buildAddFields($default_add_fields, $parameters);

        $parameters['page_size'] = 250;

        return $this->makeRequest("users/$user_id/pins/", $parameters);
    }

    /**
     * Set the access token
     *
     * @author  Will
     *
     * @param $access_token
     */
    public function setAccessToken($access_token)
    {
        $this->access_token = $access_token;
    }


    /**
     * Pin a pin to a board
     * PUT to https://api.pinterest.com/v3/pins/
     *
     * @author  Will
     */
    public function putPin($board_id, $image_url, $description, $source_url)
    {
        $this->methodRequiresAccessToken();

        return $this->makeRequest('/pins',
                           array(
                               'board_id'     => $board_id,
                               'description'  => $description,
                               'image_url'    => $image_url,
                               'source_url'   => $source_url,
                               'access_token' => $this->access_token
                           ),
                           $method = 'PUT',
                           $signature_required = false
        );
    }

    /**
     *
     * DELETE to /v3/pins/{pin_id}/
     *
     * @author  Will
     *
     * @param $pin_id
     *
     * @return int $pin_id
     */
    public function deletePin($pin_id)
    {

        $this->methodRequiresAccessToken();

        $this->makeRequest("/pins/$pin_id",
                           array(
                           'access_token' => $this->access_token
                           ),
                           $method = 'DELETE',
                           $signature_required = false
        );

        return $pin_id;
    }

    /**
     *
     * GET to /v3/users/me
     *
     * @author  Will
     *
     */
    public function getMe()
    {

        $this->methodRequiresAccessToken();

        return $this->makeRequest("/users/me",
                           array(
                                'access_token' => $this->access_token
                           ),
                           $method = 'GET',
                           $signature_required = false
        );

    }

    /**
     * @return bool
     * @author Will
     */
    public function accessTokenValid()
    {
        try {
            $this->getMe();

            return true;
        }
        catch (BasePinterestException $e) {

            return false;
        }
    }

    /**
     * Edit a pin description
     * POST to https://api.pinterest.com/v3/pins/{pin_id}/
     *
     * @author  Will
     *
     * @expects
     *           add_fields
     *           description
     *           pin
     *           place
     *
     * @param $pin_id
     * @param $description
     *
     * @return $this
     */
    public function editPin($pin_id, $description)
    {

        $this->methodRequiresAccessToken();

        $this->makeRequest("/pins/$pin_id",
                           array(
                               'add_fields'   => '',
                               'description'  => $description,
                               'pin'          => $pin_id,
                               'place'        => '',
                               'access_token' => $this->access_token
                           ),
                           $method = 'DELETE',
                           $signature_required = false
        );

        return $this;
    }

    /**
     *
     * @author  Will
     *
     * POST to https://api.pinterest.com/v3/pins/{pin_id}/repin/
     *
     * @expects
     *      board_id
     *      description
     *      share_faceboo
     *
     * @param $pin_id
     * @param $board_id
     * @param $description
     *
     * @return $this
     */
    public function postRepin($pin_id, $board_id, $description)
    {
        $this->methodRequiresAccessToken();

        return $this->makeRequest(
             "/pins/$pin_id/repin",
             array(
                 'board_id'       => $board_id,
                 'description'    => $description,
                 'share_facebook' => 0,
                 'access_token'   => $this->access_token
             ),
             $method = 'POST',
             $signature_required = false
        );
    }


    /**
     * Add a comment to a pin
     * POST https://api.pinterest.com/v3/pins/{pin_id}/comment/
     *
     * @expects
     *  text
     */
    public function postComment($pin_id, $comment)
    {
        $this->methodRequiresAccessToken();

        $this->makeRequest(
             "/pins/$pin_id/comment",
             array(
                  'text'         => $comment,
                  'access_token' => $this->access_token
             ),
             $method = 'POST',
             $signature_required = false
        );

        return $this;
    }

    /**
     * Create a board
     * PUT to https://api.pinterest.com/v3/boards/
     *
     * @param      $name
     * @param      $description
     * @param      $category
     * @param bool $privacy
     *
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function putBoard($name, $description, $category, $privacy = false)
    {
        $this->methodRequiresAccessToken();

        $category = strtolower(snake_case($category));

        if(!in_array($category,self::$categories)) {
            throw new InvalidArgumentException($category .'is not a valid category');
        }

        /*
         * If you  want to make a secret board, pass another parameter
         * privacy => secret
         *
         * to make it a "map" board, add
         * layout=>places
         */
        return $this->makeRequest(
             "/boards",
             array(
                  'name'         => $name,
                  'description'  => $description,
                  'category'     => $category,
                  'access_token' => $this->access_token
             ),
             $method = 'PUT',
             $signature_required = false
        );
    }

    /**
     * Send our batch requests over multi curl to Pinterest
     *
     * @author  Will
     *
     * @throws \BadMethodCallException
     * @throws NoBatchCallsException
     * @return array | bool
     */
    public function sendBatchRequests()
    {

        if (!$this->batch_calls) {
            throw new BadMethodCallException(
                'Batch calls were not enabled for these calls.' .
                ' The calls were sent at the time they were called.'
            );
        }

        if (count($this->calls) === 0) {
            throw new NoBatchCallsException(
                'There are no batched calls'
            );
        }

        return $this->transport->makeBatchRequests($this->calls);
    }

    /**
     * Throws an exception if the...
     *
     * @author Will
     *
     * @return bool
     * @throws BasePinterestException
     */
    protected function methodRequiresAccessToken()
    {
        if (is_null($this->access_token)) {
            throw new BasePinterestException('Access token is required');
        }

        return false;
    }

    /**
     * Build the string of "add_fields" from a set of defaults
     * and whatever was passed in as parameters
     *
     * @param $defaults
     * @param $parameters
     */
    protected function buildAddFields($defaults, &$parameters)
    {
        sort($defaults);

        $add_fields =
            array_merge(
                $defaults,
                array_get($parameters, 'add_fields', array())
            );

        $parameters['add_fields'] = implode(',', $add_fields);
    }

    /**
     * Makes the request to the Pinterest API
     *
     * @param        $endpoint
     * @param array  $parameters
     * @param string $method
     * @param bool   $signature_required
     *
     * @throws PinterestException
     * @throws BasePinterestException
     *
     * @return int | array
     */
    protected function makeRequest(
        $endpoint,
        $parameters = array(),
        $method = 'GET',
        $signature_required = true
    )
    {
        switch ($this->api_version) {
            default:
                throw new BasePinterestException(
                    'That API version is not supported'
                );
                break;
            case 3:
                $endpoint = 'v3/' . trim($endpoint, '/').'/';
                break;
        }

        $url = $this->api_endpoint . $endpoint;


        if ($signature_required) {
            $parameters['oauth_signature'] = $this->generateOAuthSignature($method, $url, $parameters);
        }

        /*
         * If we are going to send this batch of calls later, we just
         * want to add it to the calls array and pass back the key
         */
        if ($this->batch_calls) {

            /**
             * This is a temporary work around
             * What we need to do is refactor this to insert an array
             * of url, data parameters, and method since not all
             * are GET requests with the string appended anymore
             *
             * As it stands, this will only work for get requests
             */
            if (!empty($parameters)) {
                $url = $url.'?'.http_build_query($parameters);
            }

            $batch_number = $this->batch_number;
            $this->batch_number++;

            $this->calls[$batch_number] = $url;

            return $batch_number;
        }

        $results = $this->transport->makeRequest($method, $url, $parameters);

        if (array_key_exists('code', $results) == false) {
            throw new BasePinterestException(
                'There was no response code in the Pinterest request, ' .
                'but there was a successful response'
            );
        }

        if ($results['code'] != 0) {
            throw new PinterestException(
                $results['message'],
                $results['code'],
                $previous = null,
                $results['host']
            );
        }

        if (array_key_exists('data', $results)) {
            return $results['data'];
        }

        return $results;

    }

    /**
     * Generate the oAuth signature needed for some requests
     *
     * @param $method
     * @param $url
     * @param $parameters
     *
     * @return string
     */
    protected function generateOAuthSignature($method, $url, &$parameters)
    {
        $method = strtoupper($method);

        $signature = $method . '&' . rawurlencode($url);

        $parameters['timestamp']   = time();
        $parameters['client_id']   = $this->client_id;

        ksort($parameters);

        $signature_string = $signature . '&' . http_build_query($parameters, null, '&', PHP_QUERY_RFC3986);

        return hash_hmac("sha256", $signature_string, $this->secret);
    }

    /**
     * @param bool $state
     * @param bool $scope
     *
     * @return string
     */
    public function getOAuthUrl($state = false, $scope = false)
    {
        $params = [
            'consumer_id'   => $this->client_id,
            'redirect_uri'  => $this->call_back_uri,
            'response_type' => 'code',
        ];

        if ($state) {
            $params['state'] = $state;
        }

        if ($scope) {
            $params['scope'] = $scope;
        }

        return $this->oauth_endpoint . '?' . http_build_query($params);
    }

    /**
     * @param      $code
     * @param bool $state
     *
     * @return array|int
     */
    public function exchangeCodeForToken($code, $state = false)
    {
        $params = [
            'code'         => $code,
            'consumer_id'  => $this->client_id,
            'grant_type'   => 'authorization_code',
            'redirect_uri' => $this->call_back_uri,
            'state'        => $state
        ];

        return $response = $this->makeRequest('/oauth/code_exchange/', $params, 'PUT');
    }

}

/**
 * Class BasePinterestException
 *
 * @package Pinleague\Pinterest
 */
class BasePinterestException extends \Exception
{
}

/**
 * Class PinterestException
 * Mimicks the Pinterest exception codes sent back
 *
 * @package Pinleague\Pinterest
 */
class PinterestException extends BasePinterestException
{
    /**
     * Give a more detailed error message
     *
     * @author Will
     *
     */
    public function __construct(
        $message,
        $code = 0,
        Exception $previous = null,
        $host = null
    )
    {

        switch ($code) {
            default:
                parent::__construct($message, $code, $previous);

                break;
            case 30:

                throw New PinterestProfileNotFoundException($message, $code, $this);

                break;

            case 40:
                throw new PinterestBoardNotFoundException($message, $code, $this);
                break;
        }

    }
}

/**
 * Class PinterestProfileNotFoundException
 *
 * @author  Will
 * @package Pinleague
 *
 */
class PinterestProfileNotFoundException extends PinterestException
{
    /**
     * Use base exception construct instead of the parent so we don't
     * just keep throwing things in a loop
     *
     * @author  Will
     *
     * @param string    $message
     * @param int       $code
     * @param Exception $previous
     */
    public function __construct($message, $code, Exception $previous)
    {
        Exception::__construct($message, $code, $previous);
    }

}

/**
 * Class PinterestBoardNotFoundException
 *
 * @package Pinleague\Pinterest
 */
class PinterestBoardNotFoundException extends PinterestException
{
    /**
     * Use base exception construct instead of the parent so we don't
     * just keep throwing things in a loop
     *
     * @author  Will
     *
     * @param string    $message
     * @param int       $code
     * @param Exception $previous
     */
    public function __construct($message, $code, \Exception $previous)
    {
        Exception::__construct($message, $code, $previous);
    }
}

/**
 * Class NoBatchCallsException
 *
 * @package Pinleague\Pinterest
 */
class NoBatchCallsException extends BasePinterestException {}
