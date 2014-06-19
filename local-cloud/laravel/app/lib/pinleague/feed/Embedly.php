<?php


namespace Pinleague\Feed;

/**
 * Handles interactions with the Embedly API.
 *
 * @author Daniel
 * @author Yesh
 */
class Embedly extends Adapter
{

    protected $api_entries_url = 'http://api.embed.ly/1/extract?key=5f04da9b9740427ab3b55e765a8b9ce1&url=';


    /**
     * @param string $feed_url
     */
    public function __construct($feed_url)
    {
        $this->api_entries_url .= urlencode($feed_url);
    }


    /**
     * @param string $url
     *
     * @return mixed|string
     */
    protected function request($url)
    {

        $response = parent::request($url);
        return json_decode($response);
    }


    /**
     * @return array
     */
    public function load()
    {
        $api_result= $this->request($this->api_entries_url);
        $api_keywords = $api_result->keywords;

        if (empty($api_keywords)) {
            return false;
        }

        return $api_keywords;
    }


    /**
     * @return Content\DataFeedEntries|string
     */
    public function loadEntries() {
        return "Enbedly does not support returning entries";
    }
}
