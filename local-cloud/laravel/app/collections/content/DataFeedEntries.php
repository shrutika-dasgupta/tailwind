<?php

namespace Content;

use AlgoliaSearch\Client as SearchClient,
    Config,
    DatabaseInstance;

/*
 * Collection of Data Feed
 *
 * @author Yesh
 * @author Daniel
 */
class DataFeedEntries extends \DBCollection
{
    const MODEL = 'Content\DataFeedEntry';
    const TABLE = 'data_feed_entries';

    public $table = 'data_feed_entries';

    public $columns = array(
        'id',
        'feed_id',
        'domain',
        'url',
        'title',
        'description',
        'engagement',
        'engagement_rate',
        'social_score',
        'facebook_score',
        'googleplus_score',
        'pinterest_score',
        'twitter_score',
        'published_at',
        'curated',
        'reindex',
        'added_at',
        'updated_at',
    );

    public $primary_keys = array('id');

    /**
     * Fetches one or more feed entries where the last_pulled_fb
     * field is 0.
     *
     * This is done because Facebook and Google plus's rate limit is very low
     * whereas twitter and pinterest have much higher rate limits
     *
     * @param string $type Can be either "fb" or "twitter"
     * @param int $limit
     * @param int $server The server number is basically a easier way to not have the same feed entries being updated
     * @return array
     */
    public static function fetch($type, $limit = 50, $server = 0)
    {
        $DBH = DatabaseInstance::DBO();

        $updated_at = flat_date(time());

        if ($type == "fb") {

            $last_pulled_column = "last_pulled_fb";
            $index              = "use index (curated_last_pulled_facebook_idx)";

        } else if ($type == "twitter") {

            $last_pulled_column = "last_pulled_twitter";
            $index              = "use index (curated_last_pulled_twitter_idx)";

        }

        $current_day = flat_date(time());

        $query = $DBH->prepare("SELECT id
                                FROM data_feed_entries $index
                                WHERE $last_pulled_column < :current_day
                                AND curated = 2
                                AND MOD(id, 3) = :server
                                ORDER BY $last_pulled_column, id
                                LIMIT $limit");

        $query->execute([":current_day" => $current_day,
                        ":server" => $server ]);

        $results = $query->fetchAll();

        if (empty($results)) {
            $query = $DBH->prepare("SELECT id
                                    FROM data_feed_entries $index
                                    WHERE $last_pulled_column < :current_day
                                    AND curated = 1
                                    AND MOD(id, 3) = :server
                                    ORDER BY $last_pulled_column, id
                                    LIMIT $limit");

            $query->execute([":current_day" => $current_day,
                            ":server" => $server ]);

           $results = $query->fetchAll();
        }

        if (empty($results)) {
            $query = $DBH->prepare("SELECT id
                                    FROM data_feed_entries $index
                                    WHERE $last_pulled_column < :current_day
                                    AND curated = 0
                                    AND MOD(id, 3) = :server
                                    ORDER BY $last_pulled_column, id
                                    LIMIT $limit");

            $query->execute([":current_day" => $current_day,
                            ":server" => $server ]);

            $results = $query->fetchAll();
        }

        if (empty($results)) {
            return false;
        }

        $entry_ids = implode(', ', array_map(function($result) {
            return $result->id;
        }, $results));


        $STH = $DBH->query("SELECT *
                            FROM data_feed_entries
                            WHERE id IN ($entry_ids)");
        $entry_details = $STH->fetchAll();

        return self::createFromDBData($entry_details);
    }

    /**
     * Gets the number of new entries for this feed.
     *
     * @author Yesh
     *
     * @return int
     */
    public function numberOfNewEntries()
    {
        if ($this->count() > 0) {
            $urls_api_implode = $this->stringifyField('url');

            $STH = $this->DBH->prepare("SELECT COUNT(*) count
                                        FROM data_feed_entries
                                        WHERE url in (:imploded_urls)");
            $STH->execute([":imploded_urls" => $urls_api_implode]);
            $num_of_similar_feeds = $STH->fetch();

            if (!is_null($num_of_similar_feeds)) {
                $new_entries_count = count($this) - $num_of_similar_feeds->count;
            } else {
                $new_entries_count = count($this);
            }
        } else {
            return 0;
        }

        return $new_entries_count;
    }

    /**
     * Gets feed entries for a given topic.
     *
     * @param string $topic
     * @param int    $page
     * @param int    $num
     * @param array  $options
     *
     * @return array
     */
    public static function findByTopic($topic, $page = 1, $num = 100, array $options = array())
    {
        $offset = ($page - 1) * $num;
        $limit  = $num;

        $feeds = \DB::select(
            "SELECT mtf.feed_id
             FROM status_topics st
             JOIN map_topics_feeds mtf ON (st.id = mtf.topic_id)
             JOIN status_feeds sf ON (mtf.feed_id = sf.id)
             WHERE st.topic = ?
               AND sf.curated > 0
             ORDER BY mtf.score DESC
             LIMIT ?",
             array(
                $topic,
                array_get($options, 'feed_num', 10)
            )
        );

        $feed_ids     = array_map(function($feed) { return $feed->feed_id; }, $feeds);
        $feed_ids_csv = '"' . implode('", "', $feed_ids) . '"';

        $entries = \DB::select(
            "SELECT *
             FROM data_feed_entries
             WHERE feed_id IN ($feed_ids_csv)
             ORDER BY published_at DESC
             LIMIT ?, ?",
             array($offset, $limit)
        );

        return self::createFromDBData($entries);
    }

    /**
     * Flags the collection of entries as having been reindexed for search.
     *
     * @return void
     */
    public function reindexed()
    {
        foreach ($this->getModels() as $entry) {
            $entry->reindexed();
        }
    }

    /**
     * Finds entries that match a given search query.
     *
     * @see algoliasearch.php (Search SDK)
     *
     * @param string $query
     * @param int    $page
     * @param int    $num
     * 
     * @return DataFeedEntries
     */
    public function search($query, $page = 1, $num = 100)
    {
        $client = new SearchClient(
            Config::get('algolia.app_id'),
            Config::get('algolia.read_api_key')
        );

        $index = $client->initIndex('feed_entries');

        // Algolia pagination starts at 0.
        $args = array('page' => $page - 1, 'hitsPerPage' => $num);

        // Enable the advancedSyntax setting if the query contains double quotes.
        if (strpos($query, '"') !== false) {
            $args['advancedSyntax'] = true;
        }

        $results = $index->search($query, $args);
        $results = array_get($results, 'hits');

        $entries = new self();
        if (!empty($results)) {
            foreach ($results as $key => $result) {
                // Set the DB primary key.
                $result['id'] = $result['objectID'];

                $highlights = array_get($result, '_highlightResult');

                // Use the title and descriptions that include highlighted query matches.
                $result['title']       = array_get($highlights, 'title.value');
                $result['description'] = array_get($highlights, 'description.value');

                // Build a DataFeedEntry model.
                $entry = DataFeedEntry::createFromDBData($result);
                $entry->image_url = $result['image_url'];

                $entries->add($entry);
            }
        }

        return $entries;
    }
}
