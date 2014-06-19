<?php

namespace Content;

use DatabaseInstance;

/*
 * Collection of Status Feed
 *
 * @author Yesh
 * @author Daniel
 */
class StatusFeeds extends \DBCollection
{
    const MODEL = 'Content\StatusFeed';
    const TABLE = 'status_feeds';

    public $table = 'status_feeds';

    public $columns = array(
        'id',
        'url',
        'subscribers_count',
        'velocity',
        'engagement',
        'curated',
        'last_pulled',
        'added_at',
        'updated_at',
    );

    public $primary_keys = array('id');


    /**
     * Fetches one or more status feeds.
     * The various curated flags below are:
     *  2 - Curated specifically by us
     *  3 - Domains added from status_domains
     *
     * @param int $limit
     *
     * @return array
     */
    public static function fetch($limit = 40)
    {
        $DBH = DatabaseInstance::DBO();

        $current_day = flat_date(time());

        $query = $DBH->prepare("SELECT *
                                FROM status_feeds
                                WHERE last_pulled < :current_day
                                  AND curated = 2
                                ORDER BY last_pulled, id
                                LIMIT $limit");
        $query->execute(array(":current_day" => $current_day));

        $results = $query->fetchAll();

        if (empty($results)) {
            $query = $DBH->prepare("SELECT *
                                FROM status_feeds
                                WHERE last_pulled < :current_day
                                  AND curated = 3
                                ORDER BY last_pulled, id
                                LIMIT $limit");
            $query->execute(array(":current_day" => $current_day));

            $results = $query->fetchAll();
        }

        if (empty($results)) {
            $query = $DBH->prepare("SELECT *
                                    FROM status_feeds
                                    WHERE last_pulled < :current_day
                                      AND curated = 1
                                    ORDER BY last_pulled, id
                                    LIMIT $limit");
            $query->execute(array(":current_day" => $current_day));
            $results = $query->fetchAll();
        }

        if (empty($results)) {
            $query = $DBH->prepare("SELECT *
                                    FROM status_feeds
                                    WHERE last_pulled < :current_day
                                      AND curated = 0
                                    ORDER BY last_pulled, id
                                    LIMIT $limit");
            $query->execute(array(":current_day" => $current_day));
            $results = $query->fetchAll();
        }

        if (empty($results)) {
            return false;
        }

        return self::createFromDBData($results);
    }


    public static function fetchByWOTFlag($limit = 40)
    {
        $DBH = DatabaseInstance::DBO();

        $query = $DBH->query("SELECT *
                              FROM status_feeds
                              WHERE last_pulled_wot = 0
                                AND curated = 2
                              ORDER BY id
                              LIMIT $limit");
        $results = $query->fetchAll();

        if (empty($results)) {
            $query = $DBH->query("SELECT *
                                  FROM status_feeds
                                  WHERE last_pulled_wot = 0
                                    AND curated = 1
                                  ORDER BY id
                                  LIMIT $limit");
            $results = $query->fetchall();
        }

        if (empty($results)) {
            $query = $DBH->query("SELECT *
                                  FROM status_feeds
                                  WHERE last_pulled_wot = 0
                                    AND curated = 0
                                  ORDER BY id
                                  LIMIT $limit");
            $results = $query->fetchall();
        }

        if (empty($results)) {
            $query = $DBH->query("SELECT *
                                  FROM status_feeds
                                  WHERE last_pulled_wot = 0
                                  ORDER BY id
                                  LIMIT $limit");
            $results = $query->fetchall();
        }

        if (empty($results)) {
            return false;
        }

        return self::createFromDBData($results);
    }

    /**
     * Loads details for the collection of feeds.
     *
     * @param string $driver_name
     *
     * @return array
     */
    public function loadFeeds($driver_name = 'feedly')
    {
        $feed = $this->getFeedDriver($driver_name);
        return $feed->load();
    }

    /**
     * Loads entries for the collection of feeds..
     *
     * @param string $driver_name
     *
     * @return array
     */
    public function loadFeedsEntries($driver_name = 'feedly')
    {
        $feed = $this->getFeedDriver($driver_name);
        return $feed->loadEntries();
    }

    /**
     * Instantiates the feed driver for the given driver name.
     *
     * @param string $driver_name
     *
     * @return Pinleague\Feed\Adapter
     */
    protected function getFeedDriver($driver_name = 'feedly')
    {
        $class = 'Pinleague\\Feed\\' . ucfirst(strtolower($driver_name));
        return new $class($this->getUrls());
    }

    /**
     * Gets all of the collection's model urls.
     *
     * @return array
     */
    public function getUrls()
    {
        return array_map(function($feed) { return $feed->url; }, $this->getModels());
    }
}
