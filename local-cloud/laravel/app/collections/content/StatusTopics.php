<?php

namespace Content;

use DatabaseInstance;

/*
 * Status topics collection.
 *
 * @author Daniel
 */
class StatusTopics extends \DBCollection
{
    const MODEL = 'Content\StatusTopic';
    const TABLE = 'status_topics';

    public $table = 'status_topics';

    public $columns = array(
        'id',
        'topic',
        'type',
        'curated',
        'last_pulled',
    );

    public $primary_keys = array('id');

    /**
     * @param int $limit
     *
     * @return mixed
     */
    public static function fetch($limit = 40)
    {
        $DBH = DatabaseInstance::DBO();

        $query = $DBH->query("SELECT *
                              FROM status_topics
                              WHERE last_pulled = 0
                              LIMIT $limit");

        $results = $query->fetchAll();

        return self::createFromDBData($results);
    }

    /**
     * Loads the Feed and related results for a given Topic
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
     * Instantiates the feed driver for the given driver name.
     *
     * @param string $driver_name
     *
     * @return mixed
     */
    protected function getFeedDriver($driver_name = 'feedly')
    {
        $class = 'Pinleague\\Feed\\' . ucfirst(strtolower($driver_name));

        return new $class($this->getTopics());
    }

    /**
     * Gets all of the collection's model topics.
     *
     * @return array
     */
    public function getTopics()
    {
        $topics = array();
        foreach ($this->getModels() as $model) {
            $topic = str_replace(' ', '+', trim(preg_replace( "/[^0-9a-z]+/i", ' ', $model->topic)));
            if (!empty($topic)) {
                $topics[] = $topic;
            }
        }

        return $topics;
    }
}
