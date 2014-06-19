<?php namespace Pinleague\Pinterest\Transports;

use Pinleague\Curl,
    Log,
    stdClass;

/**
 * Class cURLAdapter
 *
 * @package Pinleague\Pinterest\Transports
 */
class CurlAdapter implements TransportInterface
{
    const CURL_ERROR = 794; // The error number of a curl error in a response
    /**
     * The abstraction over curl so it can be mocked / stubbed
     *
     * @var Curl
     */
    protected $curl;

    /**
     * When debugging, turn this on to see curl output
     * @var bool
     */
    protected $debug = false;

    /**
     * @author  Will
     *
     * @param Curl $curl
     */
    public function __construct(Curl $curl = null)
    {
        $this->curl = is_null($curl) ? new Curl : $curl;
    }

    /**
     * @param string $method
     *
     * @param string $url
     * @param array  $data
     *
     * @throws TransportException
     * @return array
     */
    public function makeRequest($method, $url,$data = array())
    {
        if (!in_array($method, array('GET', 'PUT', 'DELETE', 'POST'))) {
            throw new TransportException("$method is not supported");
        }

        if($method == 'GET') {
            if (!empty($data)) {
                $url = $url.'?'.http_build_query($data);
            }
        }

        $ch = $this->curl->curl_init($url);
        $this->curl->curl_setopt($ch, CURLOPT_HEADER, true);
        $this->curl->curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $this->curl->curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
        $this->curl->curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $this->curl->curl_setopt($ch, CURLOPT_MAXREDIRS, 6);
        $this->curl->curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $this->curl->curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->curl->curl_setopt($ch, CURLOPT_HTTPHEADER,
                                 array(
                                      'Accept: application/json',
                                 )
        );

        if ($method != 'GET') {
            $this->curl->curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

            if(!empty($data)) {
                /*
                 * We use urldecode here because it was encoding the
                 * redirect_uri and Pinterest wasn't expecting that
                 */
                $this->curl->curl_setopt($ch, CURLOPT_POSTFIELDS,urldecode(http_build_query($data))) ;
            }
        }

        if ($this->debug) {
            $this->curl->curl_setopt($ch, CURLOPT_VERBOSE, 1);
            $this->curl->curl_setopt($ch, CURLOPT_STDERR, fopen('php://output', 'w+'));
        }

        $response = $this->curl->curl_exec($ch);

        if ($this->curl->curl_errno($ch)) {
            $curl_error = $this->curl->curl_error($ch);
            $this->curl->curl_close($ch);

            throw new TransportException($curl_error);
        }

        /*
         * To separate the headers from the body we need to know how long the
         * headers were. Then we just take the substrings, and parse them out
         * respectively
         *
         * To simplfy things, we're adding the headers to the body response
         */
        $header_length = $this->curl->curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header        = substr($response, 0, $header_length);
        $body          = json_decode(substr($response, $header_length), true);

        $body['x_headers'] = $this->getHeadersFromCurlResponse($header);

        $this->curl->curl_close($ch);

        return $body;
    }

    /**
     * Make a bunch of requests at once
     *
     * @param array $urls
     *
     * @throws TransportException
     * @return array|bool|mixed
     */
    public function makeBatchRequests($urls)
    {

        if (empty($urls)) {
            throw new TransportException(
                "The urls list is empty, making a multi curl sad."
            );
        }

        $responses = array();

        $mh = $this->curl->curl_multi_init();
        Log::debug('Multi curl init', $mh);

        $x = 0;
        foreach ($urls as $url) {

            $$x = $this->curl->curl_init();
            $this->curl->curl_setopt($$x, CURLOPT_URL, $url);
            $this->curl->curl_setopt($$x, CURLOPT_HEADER, 0);
            $this->curl->curl_setopt($$x, CURLOPT_RETURNTRANSFER, 1);
            $this->curl->curl_setopt($$x, CURLOPT_TIMEOUT, 100);
            $this->curl->curl_setopt($$x, CURLOPT_MAXCONNECTS, 10);

            $this->curl->curl_multi_add_handle($mh, $$x);

            $x++;
        }

        Log::debug('Added curl handles to multi curl handle');

        $running = null;
        do {
            $this->curl->curl_multi_exec($mh, $running);
        } while ($running);

        ///add each result to an array
        $y    = 0;
        $data = array();

        foreach ($urls as $url) {


            if ($this->curl->curl_errno($$y)) {

                $curl_result          = new stdClass;
                $curl_result->code    = self::CURL_ERROR;
                $curl_result->message = $this->curl->curl_error($$y);
                $curl_result->host    = 'Curl Adapter Error';
                $curl_result->data    = '';

                $responses[$url] = $curl_result;

                $y++;
                continue;
            }

            $responses[$url] = json_decode(
                $this->curl->curl_multi_getcontent($$y)
            );

            $y++;
        }

        Log::debug('Curl requests sent');

        $this->curl->curl_multi_close($mh);

        Log::debug('Curl multi close called');

        if (empty($responses)) {
            throw new TransportException(
                'There are no responses even though we tried to send requests'
            );
        }

        return $responses;

    }

    /**
     * @param $response
     *
     * @return array
     */
    private function getHeadersFromCurlResponse($response)
    {
        $headers = array();

        $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));

        foreach (explode("\r\n", $header_text) as $i => $line)
            if ($i === 0)
                $headers['http_code'] = $line;
            else
            {
                list ($key, $value) = explode(': ', $line);

                $headers[$key] = $value;
            }

        return $headers;
    }
}
