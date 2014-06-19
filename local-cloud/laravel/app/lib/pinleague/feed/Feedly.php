<?php

namespace Pinleague\Feed;

use Content\DataFeed,
    Content\DataFeedEntries,
    Content\DataFeedEntry;

/**
 * Handles interactions with the Feedly API.
 *
 * @author Daniel
 * @author Yesh
 */
class Feedly extends Adapter
{
    /**
     * API URL to retrieve a feed's details.
     *
     * @var string
     */
    const API_FEED_URL = 'http://cloud.feedly.com/v3/search/feeds/?count=500&q=';

    /**
     * API URL to retrieve a feed's entries.
     *
     * @var string
     */
    const API_ENTRIES_URL = 'http://cloud.feedly.com/v3/streams/contents?count=200&ranked=newest&streamId=feed/';

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
            $responses[$key] = json_decode($response);
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

        $query_feeds = array();

        foreach ($responses as $key => $response) {
            $feeds = new \stdClass();
            $feeds->feeds = array();

            foreach ($response->results as $result) {
                $feed = new \stdClass();
                $feed->url               = preg_replace('/^feed\//', '', $result->feedId);
                $feed->domain            = str_replace('www.', '', parse_url($feed->url, PHP_URL_HOST));
                $feed->title             = trim($result->title);
                $feed->description       = trim($result->description);
                $feed->visual_url        = $result->visualUrl;
                $feed->subscribers_count = $result->subscribers;
                $feed->velocity          = $result->velocity;
                $feed->twitter_followers = $result->twitterFollowers;
                $feed->fb_likes          = $result->facebookLikes;
                $feed->facebook_username = $result->facebookUsername;
                $feed->twitter_username  = $result->twitterScreenName;
                $feed->language          = $result->language;
                $feed->score             = $result->score;
                $feed->engagement        = $result->estimatedEngagement;
                $feed->dtags             = $result->deliciousTags;

                $feeds->feeds[] = $feed;
            }

            $feeds->topics = $response->related;

            $query_feeds[] = $feeds;
        }

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

        foreach ($responses as $key => $feed) {
            $entries = new DataFeedEntries();

            foreach ($feed->items as $api_entry) {
                $entry                  = new DataFeedEntry();
                $entry->title           = strip_tags(nl2br(trim($api_entry->title)));
                $entry->description     = strip_tags(nl2br(trim($api_entry->summary->content)));
                $entry->engagement      = $api_entry->engagement;
                $entry->engagement_rate = $api_entry->engagementRate;
                $entry->published_at    = substr($api_entry->published, 0, 10);

                if ($url = array_get($api_entry->canonical, 0)) {
                    $entry->url = $url->href;
                } else if ($url = array_get($api_entry->alternate, 0)) {
                    $entry->url = $url->href;
                } else {
                    $entry->url = $api_entry->originId;
                }

                $entry->domain = str_replace('www.', '', parse_url($entry->url, PHP_URL_HOST));

                $meta             = new \stdClass();
                $meta->content    = trim($api_entry->content->content);
                $meta->categories = $api_entry->keywords;

                if ($api_entry->visual->url != 'none') {
                    $meta->image  = $api_entry->visual;
                }

                $entry->meta      = $meta;

                $entries->add($entry);
            }

            $feed_entries[] = $entries;
        }

        return $feed_entries;
    }
}
