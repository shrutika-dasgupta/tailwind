<?php namespace Pinleague\Pinterest\Transports;

/**
 * Interface Transport
 *
 * @package Pinleague\Pinterest
 */
interface TransportInterface
{

    /**
     * @param       $method
     *
     * @param       $url string
     * @param array $data
     *
     * @return array
     */
    public function makeRequest($method, $url, $data = array());

    /**
     * @param $urls array of urls
     *
     * @return array
     */
    public function makeBatchRequests($urls);

}