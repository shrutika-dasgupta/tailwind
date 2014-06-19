<?php

namespace Content;

use DB,
    URL,
    User,
    UserHistory;

/*
 * Data feed entry model.
 *
 * @author Yesh
 * @author Daniel
 */
class DataFeedEntry extends \PDODatabaseModel
{
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

    public $id;
    public $feed_id;
    public $domain;
    public $url;
    public $title;
    public $description;
    public $engagement;
    public $engagement_rate;
    public $social_score;
    public $facebook_score;
    public $googleplus_score;
    public $pinterest_score;
    public $twitter_score;
    public $published_at;
    public $curated;
    public $reindex;
    public $added_at;
    public $updated_at;

    // Non-DB property.
    public $meta;
    public $image;

    /**
     * Initializes the class.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->added_at = time();
        $this->updated_at = time();
    }

    /**
     * @param string $driver_name
     *
     * @return array
     */
    public function load($driver_name = 'feedly')
    {
        $feed = $this->getFeedDriver($driver_name);
        return $feed->load();
    }


    /**
     * TODO: Write docs
     * @param string $driver_name
     *
     * @return mixed
     */
    protected function getFeedDriver($driver_name = 'feedly')
    {
        $class = 'Pinleague\\Feed\\' . ucfirst(strtolower($driver_name));
        return new $class($this->url);
    }

    /**
     * Gets the primary image for this entry.
     *
     * @return Content\DataFeedEntryImage|bool
     */
    public function primaryImage()
    {
        if (!empty($this->image)) {
            return $this->image;
        }

        // Retrieve the entry's primary or first non-primary image.
        $data = DB::select(
            "SELECT *
             FROM map_feed_entry_images
             WHERE feed_entry_id = ?
             ORDER BY `primary` DESC
             LIMIT 1",
             array($this->id)
        );

        if (!$data = array_get($data, 0)) {
            return false;
        }

        $image = MapFeedEntryImage::createFromDBData($data);

        $this->image = $image;

        return $image;
    }

    /**
     * Gets the images associated with this entry.
     *
     * @return array
     */
    public function images()
    {
        return MapFeedEntryImage::find(array('feed_entry_id' => $this->id));
    }

    /**
     * Gets the description associated with this entry.
     *
     * @param boolean $clean
     *
     * @return string
     */
    public function description($clean = false)
    {
        if ($clean) {
            // Remove HTML tags.
            return strip_tags(nl2br($this->description));
        }

        return $this->description;
    }

    /**
     * Gets the full description associated with this entry.
     *
     * @param bool $clean
     *
     * @return string
     */
    public function fullDescription($clean = false)
    {
        $full_description = MapFeedEntryDescription::find_one(array('feed_entry_id' => $this->id));
        $full_description = $full_description ? $full_description->description : $this->description;

        if ($clean) {
            // Remove HTML tags.
            return strip_tags(nl2br($full_description));
        }

        return $full_description;
    }

    /**
     * Gets the Pinterest "Pin It" URL for this entry.
     *
     * @param string $image_url
     *
     * @return string
     */
    public function pinItURL($image_url = null)
    {
        // Default to the entry's primary image if one is not provided.
        $image_url = $image_url ? $image_url : $this->primaryImage()->image_url;

        // @TODO Move this logic into the route itself (just pass in an array of key-value pairs).
        $params = '?' . http_build_query(array(
            'url'         => $this->url,
            'media'       => $image_url,
            'description' => $this->description(true),
        ));

        return URL::route('pinterest-pin-it') . $params;
    }

    /**
     * Gets the entry data to index for search.
     *
     * @return array
     */
    public function getSearchIndexData()
    {
        $data = $this->getDBData(array(
            'reindex',
            'added_at',
            'updated_at',
        ));

        // Set the primary search key and remove the original id.
        $data['objectID'] = $data['id'];
        unset($data['id']);

        // Get the entry's full text description.
        $data['description'] = $this->fullDescription(true);

        // Get the entry's primary image.
        $image = $this->primaryImage();
        if ($image) {
            $data['image_url'] = $image->url;
        } else {
            return false;
        }

        return $data;
    }

    /**
     * Flags an entry.
     *
     * @param integer $code
     *
     * @return bool
     */
    public function flag($code = -7)
    {
        $feed = StatusFeed::find_one($this->feed_id);
        if (!$feed) {
            return false;
        }

        // Update the entry and flag it for reindexing (so it gets removed from the search index).
        $this->curated = $code;
        $this->reindex();
        $this->insertUpdateDB();

        // Update the feed.
        $feed->curated = $code;
        $feed->insertUpdateDB();

        if ($user = User::getLoggedInUser()) {
            $user->recordEvent(
                UserHistory::CONTENT_ENTRY_FLAGGED,
                array(
                    'feed_id'  => $feed->id,
                    'entry_id' => $this->id,
                )
            );
        }

        return true;
    }

    /**
     * Flags this entry to be reindexed for search.
     *
     * @return void
     */
    public function reindex()
    {
        $this->reindex = 1;
    }

    /**
     * Flags this entry as having been reindexed for search.
     *
     * @return void
     */
    public function reindexed()
    {
        $this->reindex = null;
    }
}
