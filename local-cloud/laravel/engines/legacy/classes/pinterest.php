<?php

use Pinleague\CLI;


class Pinterest {

    private $client_id, $secret;
    private $api_version;
    private $DBH, $conn;


    /*
     * If set to true, calls will be added to a batch CURL request instead of being
     * called. You'll need to call $Pinterest->sendBatchRequests(); to actually send
     * the request.
     */
    public $batch_calls = false;
    private $batch_number = 0;
    private $calls = array();


    /*
     * This is for when we have multiple batch calls and we need to reuse the instance
     */
    protected static $instance;

    /**
     * Sets the client ID, secret and connects to DB
     * @author John
     * @author Will
     *
     */
    function __construct()
    {
        $this->client_id   = Config::get('pinterest.client_id');
        $this->secret      = Config::get('pinterest.secret');
        $this->api_version = '3';

        $this->DBH = DatabaseInstance::DBO();

        /*
         * To be depreciated
         */
        $this->conn = DatabaseInstance::mysql_connect();

    }

    /**
     * Gets a persistent instance
     * @author  Will
     *
     * @returns Pinterest
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            return self::$instance = new Pinterest();
        }

        return self::$instance;
    }

    function getPinLikes($pin_id, $parameters_inputted = array()) {
        $parameters = array();
        foreach($parameters_inputted as $key => $value) {
            $parameters["$key"] = $value;
        }

        $parameters['page_size'] = 100;
        $parameters['add_fields'] = "user.follower_count,user.following_count,user.pin_count,user.like_count,user.board_count,user.domain_url,user.domain_verified,user.email,user.facebook_url,user.gplus_url,user.location,user.twitter_url,user.website_url,user.created_at,user.about";

        $data = $this->call("/v3/pins/$pin_id/likes/", $parameters, __FUNCTION__);
        return $data;
    }

    function getPinRepins($pin_id, $parameters_inputted = array()) {
        $parameters = array();
        foreach($parameters_inputted as $key => $value) {
            $parameters["$key"] = $value;
        }

        $parameters['page_size'] = 250;
        $parameters["add_fields"] = "board.owner,board.follower_count";

        $data = $this->call("/v3/pins/$pin_id/repinned_onto/", $parameters, __FUNCTION__);
        return $data;
    }

    function getPinComments($pin_id, $parameters_inputted = array()) {
        $parameters = array();
        foreach($parameters_inputted as $key => $value) {
            $parameters["$key"] = $value;
        }

        $parameters['add_fields'] = "user.follower_count,user.following_count,user.pin_count,user.like_count,user.board_count,user.domain_url,user.domain_verified,user.email,user.facebook_url,user.gplus_url,user.location,user.twitter_url,user.website_url,user.created_at,user.about";

        $data = $this->call("/v3/pins/$pin_id/comments/", $parameters, __FUNCTION__);
        return $data;
    }

    function getUserIDFromUsername($username, $parameters_inputted = array()) {
        $parameters = array();
        foreach($parameters_inputted as $key => $value) {
            $parameters["$key"] = $value;
        }

        $data = $this->call("/v3/users/$username/", $parameters, __FUNCTION__);

        if (!isValidAPIReturn($data, array(), $this->conn)) {
            return 0;
        }

        $api_data = getAPIDataFromCall($data);
        if (($api_data['id'] == "") || (!$api_data)) {
            return 0;
        } else {
            return $api_data['id'];
        }
    }

    function getProfileInformation($username, $parameters_inputted = array()) {
        $parameters = array();
        foreach($parameters_inputted as $key => $value) {
            $parameters["$key"] = $value;
        }

        $parameters["add_fields"] = "user.email";
        $data                     = $this->call("/v3/users/$username/", $parameters, __FUNCTION__);
        return $data;
    }

    function getProfileBoards($username, $parameters_inputted = array()) {
        $parameters = array();
        foreach($parameters_inputted as $key => $value) {
            $parameters["$key"] = $value;
        }

        $parameters["add_fields"] = "board.owner,board.pin_count,board.follower_count,board.description,board.collaborator_count,board.image_cover_url,board.created_at";
        $data                     = $this->call("/v3/users/$username/boards/", $parameters, __FUNCTION__);
        return $data;
    }

    function getPinInformation($pin_id, $parameters_inputted = array()) {
        $parameters = array();
        foreach($parameters_inputted as $key => $value) {
            $parameters["$key"] = $value;
        }
        $parameters['add_fields'] = "pin.parent_pin,pin.dominant_color,pin.client_id,pin.embed,pin.method,pin.rich_metadata,pin.via_pinner,pin.board,pin.pinner";

        $data = $this->call("/v3/pins/$pin_id/", $parameters, __FUNCTION__);
        return $data;
    }

    function getUserPins($user_id, $parameters_inputted = array()) {
        $parameters = array();
        $parameters['add_fields'] = "pin.parent_pin,pin.dominant_color,pin.client_id,pin.embed,pin.method,pin.rich_metadata,pin.via_pinner,pin.board,pin.pinner";
        $parameters['page_size'] = 250;

        foreach($parameters_inputted as $key => $value) {
            $parameters["$key"] = $value;
        }

        $data = $this->call("/v3/users/$user_id/pins/", $parameters, __FUNCTION__);


        return $data;
    }

    function getBoardsPins($board_id, $parameters_inputted = array()) {
        $parameters = array();
        $parameters['add_fields'] = "pin.parent_pin,pin.dominant_color,pin.client_id,pin.embed,pin.method,pin.rich_metadata,pin.rich_summary,pin.via_pinner,pin.board,pin.pinner";
        $parameters['page_size'] = 250;

        foreach($parameters_inputted as $key => $value) {
            $parameters["$key"] = $value;
        }

        $data = $this->call("/v3/boards/$board_id/pins/", $parameters, __FUNCTION__);

        return $data;
    }

    function getBoardInformation($board_id, $parameters_inputted = array()) {
        $parameters = array();
        foreach($parameters_inputted as $key => $value) {
            $parameters["$key"] = $value;
        }
        $parameters["add_fields"] = "board.owner,board.pin_count,board.follower_count,board.description,board.collaborator_count,board.image_cover_url";
        $data                     = $this->call("/v3/boards/$board_id/", $parameters, __FUNCTION__);
        return $data;
    }

    function getSearchPinsFromKeyword($keyword, $parameters_inputted = array()) {
        $parameters = array();
        $parameters['add_fields'] = "pin.parent_pin,pin.dominant_color,pin.client_id,pin.embed,pin.method,pin.rich_metadata,pin.via_pinner,pin.board,pin.pinner";
        $parameters['page_size'] = 100;
        $parameters['query'] = $keyword;
        foreach($parameters_inputted as $key => $value) {
            $parameters["$key"] = $value;
        }

        $data = $this->call("/v3/search/pins/", $parameters, __FUNCTION__);
        return $data;
    }

    function getBrandMentions($domain, $parameters_inputted = array()) {
        $parameters = array();
        $parameters['add_fields'] = "pin.parent_pin,pin.dominant_color,pin.client_id,pin.embed,pin.method,pin.rich_metadata,pin.via_pinner,pin.board,pin.pinner";
        $parameters['page_size'] = 250;

        foreach($parameters_inputted as $key => $value) {
            $parameters["$key"] = $value;
        }

        $data = $this->call("/v3/domains/$domain/pins/", $parameters, __FUNCTION__);
        return $data;
    }

    /**
     * @author  Alex
     *
     * @param       $domain
     * @param array $parameters_inputted
     *
     * @param int   $priority
     *
     * @return mixed
     *
     * There aren't any parameters to input on this call right now.
     *
     */
    public function getDomainInformation(
        $domain,
        $parameters_inputted = array()
    )
    {
        $parameters               = array();

        foreach ($parameters_inputted as $key => $value) {
            $parameters["$key"] = $value;
        }

        $data = $this->call("/v3/domains/$domain/", $parameters, __FUNCTION__, $priority);

        return $data;
    }

    function getProfileFollowers($username, $parameters_inputted = array()) {

        $parameters = array();
        $parameters['page_size'] = 65;
        foreach($parameters_inputted as $key => $value) {
            $parameters["$key"] = $value;
        }

        $parameters["add_fields"] = $this->sortAddedFields("user.first_name,user.last_name,user.email,user.about,user.domain_url,user.domain_verified,user.website_url,user.facebook_url,user.gplus_url,user.twitter_url,user.location,user.board_count,user.pin_count,user.like_count,user.follower_count,user.following_count,user.created_at");

        $data = $this->call("/v3/users/$username/followers/", $parameters, __FUNCTION__);
        return $data;
    }

    function sortAddedFields($fields_string) {
        $fields = explode(",", $fields_string);
        $keys = array();
        foreach($fields as $field) {
            $keys["$field"] = 1;
        }

        ksort($keys);

        $string = "";
        foreach($keys as $field => $true) {
            if ($string == "") {
                $string = "$field";
            } else {
                $string .= ",$field";
            }
        }

        return $string;
    }

    function getProfileFollowing($username, $parameters_inputted = array()) {
        $parameters = array();
        foreach($parameters_inputted as $key => $value) {
            $parameters["$key"] = $value;
        }

        $data = $this->call("/v3/users/$username/following/", $parameters, __FUNCTION__);
        return $data;
    }

    function ensureCorrectUrl($url) {
        if ((strpos($url, "https://") === false) && (strpos($url, "http://") === false)) {
            $url = "https://api.pinterest.com" . $url;
        }

        $spot = strlen($url) - 1;

        if ($url{$spot} != "/") {
            $url = "$url/";
        }

        return $url;
    }

    function buildCall($url, $parameters) {
        $url = $this->ensureCorrectUrl($url);
        $url_encoded = "GET&" . urlencode($url);
        $parameters["timestamp"] = time()*1000;
        $parameters["client_id"] = $this->client_id;

        ksort($parameters);

        $url = $this->buildUrlFromParameters($url, $parameters, false);
        $url_encoded = $this->buildUrlFromParameters($url_encoded, $parameters, true);

        $url = $this->finalizeUrl($url, $url_encoded);

        return $url;
    }

    function batchCalls($calls_information) {
        $calls = array();
        foreach($calls_information as $call_information) {
            $call = $this->buildCall($call_information['url'], $call_information['parameters']);

            array_push($calls, $call);
        }

        return $this->sendBatchCalls($calls);
    }

    function sendBatchCalls($calls) {
        /* TODO
        $curls = array();

        foreach($calls as $call) {
            $c = curl_init($call);
            curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($c, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($c, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($c, CURLOPT_MAXREDIRS, 2);
            curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($c, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json'
                ));

            array_push($curls, $c);
        }

        $mh = curl_multi_init();
        foreach($curls as $curl) {
            curl_multi_add_handle($mh, $curl);
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running);

        $data = array();
        foreach($curls as $curl) {
            $response = curl_multi_getcontent($crawl->curl);
            $crawl->setResponse(formatContent($response));
        }

        return $crawls;
        */

        return array();
    }

    /**
     * Sends a curl request to the Pinterest API
     * If batch calls, builds and adds to call array & returns call #
     * @author  John
     * @author Will
     *
     * @param $url
     * @param array $parameters
     *
     * @return mixed
     * @throws PinterestException
     *
     */
    function call($url, $parameters = array(), $method_name = false)
    {

        $call = $this->buildCall($url, $parameters);
        $this->incrementCallRateLimit($calls = 1, $method=$method_name);
        if ($this->batch_calls) {
            $batch_number = $this->batch_number;
            $this->batch_number++;

            $this->calls[$batch_number] = $call;

            return $batch_number;

        } else {
            $results = json_decode($this->grabPage($call), true);

            return $results;

        }

    }

    /**
     * Increases our count of the number of calls made
     * @author John
     * @author Will
     * @author Yesh
     *
     */
    protected function incrementCallRateLimit($calls = 1, $method)
    {
        $datetime = $this->getFlatDateHour(time());
        $STH = $this->DBH->prepare(
                         "INSERT into status_api_calls (datetime, calls, method)
                          VALUES (:datetime, :calls, :method)
                          ON DUPLICATE KEY UPDATE calls = calls + :calls, method = :method");
        $STH->execute(array(":datetime" => $datetime, ":calls" => $calls, ":method" => $method));
    }

    /**
     * @author  John
     *
     * @param $t
     * @return int
     */
    protected function getFlatDateHour($t)
    {
        return mktime(date("G", $t), 0, 0, date("n", $t), date("j", $t), date("Y", $t));
    }

    function finalizeUrl($url, $url_encoded) {
        $sig = hash_hmac('sha256', $url_encoded, $this->secret);

        $url = $url . "&oauth_signature=$sig";
        return $url;
    }

    function buildUrlFromParameters($url, $parameters, $encoded) {
        $first = true;
        foreach($parameters as $key => $value) {
            if ($encoded) {
                $value = str_replace(",", "%2C", $value);
                $value = str_replace("=", "%3D", $value);
                $value = str_replace(" ", "%20", $value);
                $value = str_replace("#", "%23", $value);
                $value = str_replace("'", "%27", $value);
            } else {
                $value = str_replace(" ", "%20", $value);
                $value = str_replace("#", "%23", $value);
                $value = str_replace("'", "%27", $value);
            }

            if ($first) {
                if (!$encoded) {
                    $url .= "?";
                } else {
                    $url .= "&";
                }
                $url .= "$key=$value";
                $first = false;
            } else {
                $url .= "&$key=$value";
            }
        }

        return $url;
    }

    function grabPage($url) {
        $c = curl_init($url);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($c, CURLOPT_MAXREDIRS, 2);
        curl_setopt($c, CURLOPT_TIMEOUT, 60);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_HTTPHEADER, array(
                                                 'Content-Type: application/json',
                                                 'Accept: application/json'
                                            ));
        $page = curl_exec($c);

        if(curl_errno($c))
        {
            CLI::alert(Log::error(curl_error($c), $c));
        }

        curl_close($c);

        return $page;
    }

}
class PinterestException extends Exception
{

    /*
     *
     * Give a more detailed error message
     *
     * @author Will
    public function __construct($message, $code = 0, Exception $previous = null)
    {

        $status = $this->status($code);
        $message = "$message -> $status->name:$status->message [code $status->code] (HTTP CODE: $status->http_code)";

        parent::__construct($message, $code, $previous);
    }
    /*/

}
