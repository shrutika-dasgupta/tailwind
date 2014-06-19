<?php namespace Scrapers;

use Symfony\Component\DomCrawler\Crawler,
    Guzzle\Http,
    Pinleague\CLI;

/**
 * Class scraper
 *
 * @package Scrapers
 */
class Scraper extends \model
{
    /**
     * The base endpoint, like http://www.pinterest.com/
     *
     * @var string
     */
    protected $base_url;
    /**
     * The user agent string that will be faking
     *
     * @var string
     */
    protected $user_agent;
    /**
     * How we'll get the page to scrape | using Guzzle right now
     *
     * @var
     */
    protected $transport;
    /**
     * @var string html of the page
     */
    protected $response;

    /**
     * Construct
     *
     * @author  Will
     */
    public function __construct()
    {

        $this->base_url   = "http://www.pinterest.com/";
        $this->user_agent =
            'Mozilla/5.0 (iPhone; U; CPU iPhone OS 3_0 like Mac OS X; ' .
            'en-us) AppleWebKit/528.18 (KHTML, like Gecko) ' .
            'Version/4.0 Mobile/7A341 Safari/528.16';

        $this->transport = new Http\Client($this->base_url);
        $this->transport->setUserAgent($this->user_agent);
    }

    /**
     * @param $endpoint
     *
     * @return $this
     */
    public function get($endpoint)
    {
        $this->response = $this->transport->get($endpoint)->send();

        return $this;
    }


}