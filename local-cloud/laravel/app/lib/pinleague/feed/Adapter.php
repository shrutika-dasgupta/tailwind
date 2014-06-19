<?php

namespace Pinleague\Feed;

use Guzzle\Http\Client;

/**
* Feed adapter class.
*
* @author Yesh
* @author Daniel
*/
abstract class Adapter
{
    /**
     * Feed queries (typically URLs or topic strings).
     *
     * @var array
     */
    public $queries = array();

    /**
     * Initializes the class.
     * 
     * @param array $queries
     * 
     * @return void
     */
    public function __construct(array $queries)
    {
        foreach ($queries as $query) {
            $this->queries[] = $query;
        }
    }

    /**
     * Sends a request for multiple urls.
     *
     * @param array $urls
     *
     * @return array
     */
    protected function request($urls)
    {
        $client = new Client();

        $requests = array();
        foreach ($urls as $url) {
            $requests[] = $client->createRequest('GET', $url);
        }

        $responses = $client->send($requests);

        $results = array();
        foreach ($responses as $response) {
            $results[] = $response->getBody(true);
        }

        return $results;
    }

    /**
     * Loads the details for a feed.
     */
    abstract function load();

    /**
     * Loads the entries for a feed.
     */
    abstract function loadEntries();
}
