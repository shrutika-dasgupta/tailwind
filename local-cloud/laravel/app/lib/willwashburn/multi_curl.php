<?php namespace willwashburn;

use Log,
    Exception;

/**
 * Class multi_curl
 *
 * @package willwashburn
 */
class multi_curl
{

    protected $urls = array();

    /**
     * Add Url
     *
     * @use     add a page to cURL
     *
     * @author  Will
     */
    public function add($url, $description = false)
    {
        $group = array();

        /*
         * If we didn't include a description, use the address
         */
        if ($description !== false) {
            $group['meta'] = $description;
        } else {
            $group['meta'] = $url;
        }

        $group['address'] = $url;

        $this->urls[] = $group;

    }

    /**
     * Send the requests
     *
     * @author  Will
     */

    public function send()
    {


        $mh = curl_multi_init();
        Log::debug('Multi curl init',$mh);

        $x = 0;
        foreach ($this->urls as $url) {
            //set up each cURL

            $url = $url['address'];

            $$x = curl_init();
//                curl_setopt($$x, CURLOPT_VERBOSE, true);
            curl_setopt($$x, CURLOPT_URL, $url);
            curl_setopt($$x, CURLOPT_HEADER, 0);
            curl_setopt($$x, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($$x, CURLOPT_TIMEOUT, 100);
            curl_setopt($$x, CURLOPT_MAXCONNECTS, 10);


            ///add to the multi
            curl_multi_add_handle($mh, $$x);

            ///iterate
            $x++;
        }

        Log::debug('Added curl handles to multi curl handle');

        //run the multi curl
        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running);

        ///add each result to an array
        $y    = 0;
        $data = array();

        foreach ($this->urls as $url) {
            $what        = $url['meta'];

            if(curl_errno($$y))
            {
                Log::error(curl_error($$y), $$y);
            }

            $data[$what] = json_decode(curl_multi_getcontent($$y));
            $y++;
        }

        Log::debug('Curl requests sent');

        curl_multi_close($mh);

        Log::debug('Curl multi close called');

        if ($data) {
            return $data;
        } else {
            return false;
        }

    }

    /**
     * Rolling curls to not waste processing speeds
     *
     * @param      $callback
     * @param null $custom_options
     *
     * @return bool
     */
    public function rolling_curl($callback, $custom_options = null)
    {

        $urls = $this->urls;

        // make sure the rolling window isn't greater than the # of urls
        $rolling_window = 5;
        $rolling_window = (sizeof($urls) < $rolling_window) ? sizeof($urls) : $rolling_window;

        $master   = curl_multi_init();
        $curl_arr = array();

        // add additional curl options here
        $std_options = array(CURLOPT_RETURNTRANSFER > true,
                             CURLOPT_FOLLOWLOCATION > true,
                             CURLOPT_MAXREDIRS > 5);
        $options     = ($custom_options) ? ($std_options + $custom_options) : $std_options;

        // start the first batch of requests
        for ($i = 0; $i < $rolling_window; $i++) {
            $ch                   = curl_init();
            $options[CURLOPT_URL] = $urls[$i];
            curl_setopt_array($ch, $options);
            curl_multi_add_handle($master, $ch);
        }

        do {
            while (($execrun = curl_multi_exec($master, $running)) == CURLM_CALL_MULTI_PERFORM) ;
            if ($execrun != CURLM_OK)
                break;
            // a request was just completed -- find out which one
            while ($done = curl_multi_info_read($master)) {
                $info = curl_getinfo($done['handle']);
                if ($info['http_code'] == 200) {
                    $output = curl_multi_getcontent($done['handle']);

                    // request successful.  process output using the callback function.
                    $callback($output);

                    // start a new request (it's important to do this before removing the old one)
                    $ch                   = curl_init();
                    $options[CURLOPT_URL] = $urls[$i++]; // increment i
                    curl_setopt_array($ch, $options);
                    curl_multi_add_handle($master, $ch);

                    // remove the curl handle that just completed
                    curl_multi_remove_handle($master, $done['handle']);
                } else {
                    // request failed.  add error handling.
                }
            }
        } while ($running);

        curl_multi_close($master);

        return true;
    }

}

class MultiCurlException extends Exception {}
