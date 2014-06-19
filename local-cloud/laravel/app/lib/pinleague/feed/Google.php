<?php

namespace Pinleague\Feed;

use Content\DataFeed,
    Content\DataFeedEntries,
    Content\DataFeedEntry;

/**
 * Handles interactions with the Google Feed API.
 *
 * @author Daniel
 * @author Yesh
 */
class Google extends Adapter
{
    /**
     * API URL to retrieve a feed's details.
     *
     * @var string
     */
    const API_FEED_URL = 'http://www.google.com/uds/Gfeeds?v=1.0&num=200&q=';

    /**
     * API URL to retrieve a feed's entries.
     *
     * @var string
     */
    const API_ENTRIES_URL = 'http://www.google.com/uds/Gfeeds?v=1.0&num=200&q=';

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
            $this->queries[] = urlencode($query);
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
        $responses = parent::request($urls);

        foreach ($responses as $key => $response) {
            /*
             * The body we get back from google is not formatted to be properly
             * decoded as a JSON.
             *
             * We get the first occurrence of "{" in @var $body and the last
             * occurrence of "}" so that we can find out the exact start and end of
             * the JSON string.
             */
            $body_start  = strpos($response, '{');
            $body_length = strrpos($response, '}') - $body_start + 1;

            /*
             * In @var $feed_body, we use substring function to trim the
             * extraneous wrapper text so we can decode the JSON.
             */
            $feed_body = substr($response, $body_start, $body_length);
            $feed_data = json_decode($feed_body);

            $responses[$key] = $feed_data->responseData->feed;
        }

        return $responses;
    }

    /**
     * Loads the details for the provided feeds.
     *
     * @return array
     */
    public function load()
    {
        $urls = array();
        foreach ($this->queries as $query) {
            $urls[] = self::API_FEED_URL . $query;
        }

        $responses = $this->request($urls);

        $feeds        = new \stdClass();
        $feeds->feeds = array();
        $query_feeds  = array();

        foreach ($responses as $key => $response) {
            $feed              = new \stdClass();
            $feed->url         = $response->feedUrl;
            $feed->domain      = str_replace('www.', '', parse_url($response->feedUrl, PHP_URL_HOST));
            $feed->title       = $response->title;
            $feed->description = $response->description;

            $feeds->feeds[] = $feed;
        }

        $query_feeds[] = $feeds;

        return $query_feeds;
    }

    /**
     * Loads the entries for the provided feeds.
     *
     * @return array
     */
    public function loadEntries()
    {
        $urls = array();
        foreach ($this->queries as $query) {
            $urls[] = self::API_ENTRIES_URL . $query;
        }

        $responses = $this->request($urls);

        $feed_entries = array();

        foreach ($responses as $feed) {
            $entries = new DataFeedEntries();

            foreach ($feed->entries as $api_entry) {
                $entry               = new DataFeedEntry();
                $entry->url          = $api_entry->link;
                $entry->domain       = str_replace('www.', '', parse_url($api_entry->link, PHP_URL_HOST));
                $entry->title        = strip_tags(nl2br(trim($api_entry->title)));
                $entry->description  = strip_tags(nl2br(trim($api_entry->contentSnippet)));
                $entry->published_at = strtotime($api_entry->publishedDate);

                $meta             = new \stdClass();
                $meta->content    = trim($api_entry->content);
                $meta->categories = $api_entry->categories;

                $entry->meta      = $meta;

                $entries->add($entry);
            }

            if ($entries->isNotEmpty()) {
                $feed_entries[] = $entries;
            }
        }

        return $feed_entries;
    }
}

