<?php

use Pinleague\Pinterest;
use Pinleague\CLI;

/**
 * Category Feed Queue Model
 *
 * @author Yesh
 */
class CategoryFeedQueue extends PDODatabaseModel
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
     */

    public
        $table = 'status_category_feed_queue',

        $pin_id,
        $user_id,
        $board_id,
        $domain,
        $method,
        $is_repin,
        $parent_pin,
        $via_pinner,
        $origin_pin,
        $origin_pinner,
        $image_url,
        $image_square_url,
        $link,
        $description,
        $location,
        $dominant_color,
        $rich_product,
        $repin_count,
        $like_count,
        $comment_count,
        $created_at,
        $category_name,
        $match_type,
        $timestamp;

    /*
     * Table meta data
     */
    public
        $columns =
        array(
            'pin_id',
            'user_id',
            'board_id',
            'domain',
            'method',
            'is_repin',
            'parent_pin',
            'via_pinner',
            'origin_pin',
            'origin_pinner',
            'image_url',
            'image_square_url',
            'link',
            'description',
            'location',
            'dominant_color',
            'rich_product',
            'repin_count',
            'like_count',
            'comment_count',
            'created_at',
            'category_name',
            'match_type',
            'timestamp',

        ),
        $primary_keys = array('pin_id');
    /**
     * @author Yesh
     */
    public function __construct($category_name)
    {
        parent::__construct();
        $this->category_name = $category_name;
        $this->timestamp     = time();
    }


    /*
    |--------------------------------------------------------------------------
    | public instance methods
    |--------------------------------------------------------------------------
     */

    /**
     * Load up pinterest data into trackcategory object
     *
     * @author  Yesh
     *
     * @param $data
     *
     * @throws Pinleague\PinterestException
     * @return $this
     */
    public function loadAPIData($data, $match_type = null)
    {
        $this->pin_id           = $data->pin_id;
        $this->user_id          = $data->user_id;
        $this->board_id         = $data->board_id;
        $this->domain           = $data->domain;
        $this->method           = $data->method;
        $this->is_repin         = $data->is_repin;
        $this->parent_pin       = $data->parent_pin;
        $this->via_pinner       = $data->via_pinner;
        $this->origin_pin       = $data->origin_pin;
        $this->origin_pinner    = $data->origin_pinner;
        $this->image_url        = $data->image_url;
        $this->image_square_url = $data->image_square_url;
        $this->link             = $data->link;
        $this->description      = $data->description;
        $this->location         = $data->location;
        $this->dominant_color   = $data->dominant_color;
        $this->rich_product     = $data->rich_product;
        $this->repin_count      = $data->repin_count;
        $this->like_count       = $data->like_count;
        $this->comment_count    = $data->comment_count;
        $this->created_at       = $data->created_at;
        $this->match_type       = $match_type;

        return $this;
    }


    /** The main check for a match with user data
     * and domain
     *
     * @author Yesh
     */
    public function matchAndNotifyDomain($category_feeds)
    {
        $DBH            = DatabaseInstance::DBO();
        $DBH->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET sql_log_bin = 0");

        $pins               = new Pins();
        $pin_descriptions   = new PinDescriptions();
        $notifications      = new CategoryFeedsMatches();
        $domains_from_db    = array();
        $domain_match_count = 0;

        // Array of all the domains with respective pins as
        // values
        $domain_hash = array();

        try{
        $STH = $DBH->query("SELECT domain
          FROM status_domains
          WHERE domain != ''");
        $domains_from_db = $STH->fetchAll();
        } catch( PDOException $Exception ) {
            $db_log = new DBErrorLog();
            $db_log->script_name = basename(__FILE__);
            $db_log->line_number = __line__;
            $db_log->loadErrorData($DBH->errorInfo());

            $db_log->saveToDB();
      }

        $DBH->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET sql_log_bin = 1");
        foreach($domains_from_db as $domain){
            $domains[] = $domain->domain;
        }


        foreach($category_feeds as $feed){
            if (array_key_exists($feed->domain, $domain_hash)){
                array_push($domain_hash[$feed->domain], $feed);
            } else {
                $domain_hash[$feed->domain] = array();
                array_push($domain_hash[$feed->domain], $feed);
            }
        }

        CLI::write('Creating the domain hash');
        foreach($domain_hash as $key => $values){
            if(in_array($key, $domains)){
                foreach($values as $feed){
                    $track_category = new CategoryFeedQueue($feed->category_name);
                    $notification = new CategoryFeedMatch();
                    $pin          = new Pin();
                    $pin_description = new PinDescription();

                    $pin->loadDBDataForTracking($feed);
                    $pin_description->loadPinData($pin);
                    $track_category->loadAPIData($feed, 'domain');
                    $notification->loadAPIData($track_category);

                    $notifications->add($notification);
                    $pins->add($pin);
                    $pin_descriptions->add($pin_description);
                }
            }
        }

        $domain_match_count = count($pins);

        try {
            $pins->insertUpdateDB();
        } catch (CollectionException $e){
          CLI::alert("No pins to save in domain match");
        }
        try{
        $pin_descriptions->saveModelsToDB();
        } catch (CollectionException $e){
          CLI::alert("No pins description to save in domain match");
        }

        $notifications_domains = array("notifications" => $notifications,
                                       "domain_match_count" => $domain_match_count);
        return $notifications_domains;
    }


    /** The main check for a match with user data
     * and pin_id, user_id, via_pinner and origin_pinner
     *
     * This is done such that all the user_ids are loaded
     * in to the memory, so that we don't have to keep
     * hitting the database every single time
     *
     * @author Yesh
     */
    public function matchAndNotifyUsers($category_feeds)
    {
        $DBH            = DatabaseInstance::DBO();
        $DBH->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET sql_log_bin = 0");

        $notifications     = new CategoryFeedsMatches();
        $pins              = new Pins();
        $pin_descriptions  = new PinDescriptions();
        $user_ids_from_db  = array();
        $users_match_count = 0;


        // Array of all the user_id, via_pinner and origin_pinner
        // with respective pins as values
        $user_id_hash       = array();
        $via_pinner_hash    = array();
        $origin_pinner_hash = array();

        $user_id_inner_range_total       = 0;
        $via_pinner_inner_range_total    = 0;
        $origin_pinner_inner_range_total = 0;
        $user_id_hash_range_total        = 0;

        try{
        $STH = $DBH->query("SELECT user_id
            FROM status_profiles");
        $user_ids_from_db = $STH->fetchAll();
        } catch( PDOException $Exception ) {
            $db_log = new DBErrorLog();
            $db_log->script_name = basename(__FILE__);
            $db_log->line_number = __line__;
            $db_log->loadErrorData($DBH->errorInfo());

            $db_log->saveToDB();
      }

        $DBH->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET sql_log_bin = 1");

        foreach($user_ids_from_db as $user_id){
            $user_ids[] = $user_id->user_id;
        }

        CLI::write('Creating the User data hash');
        $user_id_hash_start= microtime(true);

        foreach($category_feeds as $feed){
            // Creating user_id hash
            if (array_key_exists($feed->user_id, $user_id_hash)){
                array_push($user_id_hash[$feed->user_id], $feed);
            } else {
                $user_id_hash[$feed->user_id] = array();
                array_push($user_id_hash[$feed->user_id], $feed);
            }

            // Creating via_pinner hash
            if(!empty($feed->via_pinner)){
                if (array_key_exists($feed->via_pinner, $user_ids)){
                    array_push($via_pinner_hash[$feed->via_pinner], $feed);
                } else {
                    $via_pinner_hash[$feed->via_pinner] = array();
                    array_push($via_pinner_hash[$feed->via_pinner], $feed);
                }
            }

            // Creating origin_pinner hash
            if(!empty($feed->origin_pinner)){
                if (array_key_exists($feed->origin_pinner, $user_ids)){
                    array_push($origin_pinner_hash[$feed->origin_pinner], $feed);
                } else {
                    $origin_pinner_hash[$feed->origin_pinner] = array();
                    array_push($origin_pinner_hash[$feed->origin_pinner], $feed);
                }
            }
        }
        $user_id_hash_end           = microtime(true);
        $user_id_hash_range         = $user_id_hash_end - $user_id_hash_start;
        $user_id_hash_range_total  += $user_id_hash_range;


        $user_id_inner_start_time = microtime(true);
        foreach($user_id_hash as $key => $values){
            if(in_array($key, $user_ids)){
                foreach($values as $data){
                    $track_category = new CategoryFeedQueue($feed->category_name);
                    $notification = new CategoryFeedMatch();
                    $pin          = new Pin();
                    $pin_description = new PinDescription();

                    $track_category->loadAPIData($data, 'user_id');
                    $pin->loadDBDataForTracking($data);
                    $pin_description->loadPinData($pin);
                    $notification->loadAPIData($track_category);

                    $notifications->add($notification);
                    $pins->add($pin);
                    $pin_descriptions->add($pin_description);
                }
            }
        }
        $user_id_inner_stop_time = microtime(true);
        $user_id_inner_range = $user_id_inner_stop_time - $user_id_inner_start_time;
        $user_id_inner_range_total += $user_id_inner_range;

        $via_pinner_inner_start_time = microtime(true);
        foreach($via_pinner_hash as $key => $values){
            if(in_array($key, $user_ids)){
                foreach($values as $data){
                    $track_category = new CategoryFeedQueue($feed->category_name);
                    $notification = new CategoryFeedMatch();
                    $pin          = new Pin();
                    $pin_description = new PinDescription();

                    $track_category->loadAPIData($data, 'via_pinner');
                    $pin->loadDBDataForTracking($data);
                    $pin_description->loadPinData($pin);
                    $notification->loadAPIData($track_category);

                    $notifications->add($notification);
                    $pins->add($pin);
                    $pin_descriptions->add($pin_description);
                }
            }
        }
        $via_pinner_inner_stop_time = microtime(true);
        $via_pinner_inner_range = $via_pinner_inner_stop_time - $via_pinner_inner_start_time;
        $via_pinner_inner_range_total += $via_pinner_inner_range;

        $origin_pinner_inner_start_time = microtime(true);
        foreach($origin_pinner_hash as $key => $values){
            if(in_array($key, $user_ids)){
                foreach($values as $data){
                    $track_category = new CategoryFeedQueue($feed->category_name);
                    $notification = new CategoryFeedMatch();
                    $pin          = new Pin();
                    $pin_description = new PinDescription();

                    $track_category->loadAPIData($data, 'origin_pinner');
                    $pin->loadDBDataForTracking($data);
                    $pin_description->loadPinData($pin);
                    $notification->loadAPIData($track_category);

                    $notifications->add($notification);
                    $pins->add($pin);
                    $pin_descriptions->add($pin_description);
                }
            }
        }
        $origin_pinner_inner_stop_time = microtime(true);
        $origin_pinner_inner_range = $origin_pinner_inner_stop_time - $origin_pinner_inner_start_time;
        $origin_pinner_inner_range_total += $origin_pinner_inner_range;

        $users_match_count = count($pins);

        try {
            $pins->insertUpdateDB();
        } catch (CollectionException $e){
          CLI::alert("No pins to save in user_id match");
        }
        try{
        $pin_descriptions->saveModelsToDB();
        } catch (CollectionException $e){
          CLI::alert("No pins description to save in user_id match");
        }
        $timings_user_id_inner = array("user_hash_inner" => $user_id_hash_range_total,
                                       "user_id_inner" => $user_id_inner_range_total,
	                                   "via_pinner_inner" => $via_pinner_inner_range_total,
	                                   "origin_pinner_inner" => $origin_pinner_inner_range_total);

        $notifications_users = array("notifications" => $notifications,
                                     "user_id_timings" => $timings_user_id_inner,
                                     "users_match_count" => $users_match_count);
        return $notifications_users;
    }

    /*
    |--------------------------------------------------------------------------
    | static instance methods
    |--------------------------------------------------------------------------
     */

    /**The main check for a match with user data
     * and keyword
     *
     * @author Yesh
     */
    public static function matchAndNotifyKeyword($category_feeds)
    {
        $DBH            = DatabaseInstance::DBO();
        $DBH->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET sql_log_bin = 0");

        $notifications          = new CategoryFeedsMatches();
        $pins                   = new Pins();
        $pin_descriptions       = new PinDescriptions();
        $save_map_pins_keywords = new MapPinsKeywords();
        $profiles               = new Profiles();
        $keywords_from_db       = array();
        $keyword_match_count    = 0;

        try {
            $STH = $DBH->query("SELECT keyword
                FROM status_keywords
                WHERE keyword IS NOT NULL");
            $keywords_from_db = $STH->fetchAll();
        } catch (PDOException $exception) {
            $db_log = new DBErrorLog();
            $db_log->script_name = basename(__FILE__);
            $db_log->line_number = __line__;
            $db_log->loadErrorData($DBH->errorInfo());
            $db_log->saveToDB();
        }

        $DBH->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET sql_log_bin = 1");

        foreach ($keywords_from_db as $keyword) {
            if (empty($keyword->keyword)) {
                continue;
            }

            $pattern = \StatusKeyword::regexMatchPattern($keyword->keyword);

            foreach ($category_feeds as $feed) {
                // Add whitespace so that keywords at the beginning and end of the string will be matched.
                $description = ' ' . $feed->description . ' ';
                if (!preg_match($pattern, $description)) {
                    continue;
                }

                $track_category       = new CategoryFeedQueue($feed->category_name);
                $notification         = new CategoryFeedMatch();
                $pin                  = new Pin();
                $pin_description      = new PinDescription();
                $save_map_pin_keyword = new MapPinKeyword();
                $profile              = new Profile();

                $track_category->loadAPIData($feed, $keyword->keyword);
                $pin->loadDBDataForTracking($feed);
                $pin_description->loadPinData($pin);

                $profile->user_id = $feed->user_id;
                $profile->username = "";
                $profile->last_pulled = 0;
                $profile->track_type = 'keyword';
                $profile->timestamp = time();

                $save_map_pin_keyword->load($pin, $keyword->keyword);
                $notification->loadAPIData($track_category);

                $pins->add($pin);
                $profiles->add($profile);
                $pin_descriptions->add($pin_description);
                $save_map_pins_keywords->add($save_map_pin_keyword);
                $notifications->add($notification);
            }
        }

        $keyword_match_count = count($pins);

        try {
            $pins->insertUpdateDB();
        } catch (CollectionException $e){
            CLI::alert("No pins to save in keyword match");
        }

        try {
            $profiles->saveModelsToDB();
        } catch (CollectionException $e){
            CLI::alert("No profiles to save in keyword match");
        }
        try{
        $pin_descriptions->saveModelsToDB();
        } catch (CollectionException $e){
            CLI::alert("No pins description to save in keyword match");
        }

        try {
            $save_map_pins_keywords->insertUpdateDB();
        } catch (CollectionException $e){
            CLI::alert("No match pin keywords to save in keyword match");
        }

        $notifications_keywords = array("notifications" => $notifications,
                                        "keyword_match_count" => $keyword_match_count);
        return $notifications_keywords;
    }

    /** Checks to see if the data already exists in the database
     *
     * @author Yesh
     */
    public static function doesDataExist($data, $category_name)
    {
        $DBH = DatabaseInstance::DBO();
        $DBH->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET sql_log_bin = 0");
        $category_feed_freq = new CategoryFeedTrack($category_name);
        $pins_from_db = array();
        $pins_from_api = array();
        $pins_data_from_db = array();

        try{
          $STH = $DBH->query("SELECT pin_id
              FROM status_category_feed_queue
              where category_name = '$category_name'
              ORDER BY timestamp DESC
              LIMIT 250");
          $pins_data_from_db = $STH->fetchAll();
        } catch( PDOException $Exception ) {
            $db_log = new DBErrorLog();
            $db_log->script_name = basename(__FILE__);
            $db_log->line_number = __line__;
            $db_log->loadErrorData($DBH->errorInfo());

            $db_log->saveToDB();
        }

        $DBH->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET sql_log_bin = 1");
        foreach($pins_data_from_db as $pin){
            $pins_from_db[] = $pin->pin_id;
        }

        $pins_data_from_api = array();
        foreach($data as $d){
            $pin_id                                      = $d['id'];
            $is_repin                                    = $d['is_repin'];
            $created_at                                  = $d['created_at'];

            $pins_from_api[]                             = $d['id'];

            $pins_data_from_api["$pin_id"]                  = array();
            $pins_data_from_api["$pin_id"]['is_repin']      = $is_repin;
            $pins_data_from_api["$pin_id"]['created_at']    = Pinterest::creationDateToTimeStamp($created_at);
            $pins_data_from_api["$pin_id"]['domain']        = $d['domain'];
            $pins_data_from_api["$pin_id"]['method']        = $d['method'];
            $pins_data_from_api["$pin_id"]['pin_count']     = $d['pin_count'];
            $pins_data_from_api["$pin_id"]['repin_count']   = $d['repin_count'];
            $pins_data_from_api["$pin_id"]['like_count']    = $d['like_count'];
            $pins_data_from_api["$pin_id"]['comment_count'] = $d['comment_count'];

        }

        $common_pins = count(array_intersect($pins_from_db, $pins_from_api));
        $new_pins_count = count($pins_from_api) - $common_pins;

        /**
         * Get a list of new pins found
         */
        $new_pins = array_diff($pins_from_api, $pins_from_db);



        $recent_domain_stats = array();
        $recycled_domain_stats = array();
        /*
         * Count up number of new pins found that are repins vs. original pins
         */
        $hour_ago = strtotime("-1 hour", time());
        $new_recent_pins_count = 0;
        $new_is_repin_count = 0;
        foreach($new_pins as $pin){

            $pin_domain        = $pins_data_from_api[$pin]['domain'];
            $pin_repin_count   = $pins_data_from_api[$pin]['repin_count'];
            $pin_like_count    = $pins_data_from_api[$pin]['like_count'];
            $pin_comment_count = $pins_data_from_api[$pin]['comment_count'];

            if($pins_data_from_api[$pin]['created_at'] > $hour_ago){

                /*
                 * count up recent pins out of the newly found batch, and count up the number of
                 * them which are repins
                 */
                $new_recent_pins_count++;
                if($pins_data_from_api[$pin]['is_repin']){
                    $new_is_repin_count++;
                }

                /*
                 * Add up counts for each domain by method for recent pins (pinned < 1 hour ago)
                 */
                if(!$recent_domain_stats["$pin_domain"]){
                    $recent_domain_stats["$pin_domain"] = array();
                    $recent_domain_stats["$pin_domain"]['domain'] = $pin_domain;
                    $recent_domain_stats["$pin_domain"]['pin_count'] = 1;
                    $recent_domain_stats["$pin_domain"]['repin_count'] = $pin_repin_count;
                    $recent_domain_stats["$pin_domain"]['like_count'] = $pin_like_count;
                    $recent_domain_stats["$pin_domain"]['comment_count'] = $pin_comment_count;

                } else {
                    $recent_domain_stats["$pin_domain"]['pin_count'] += 1;
                    $recent_domain_stats["$pin_domain"]['repin_count'] += $pin_repin_count;
                    $recent_domain_stats["$pin_domain"]['like_count'] += $pin_like_count;
                    $recent_domain_stats["$pin_domain"]['comment_count'] += $pin_comment_count;

                }

            } else {
                /*
                 * Add up counts for each domain by method for recycled pins (pinned > 1 hour ago)
                 */
                if(!$recycled_domain_stats["$pin_domain"]){
                    $recycled_domain_stats["$pin_domain"] = array();
                    $recycled_domain_stats["$pin_domain"]['domain'] = $pin_domain;
                    $recycled_domain_stats["$pin_domain"]['pin_count'] = 1;
                    $recycled_domain_stats["$pin_domain"]['repin_count'] = $pin_repin_count;
                    $recycled_domain_stats["$pin_domain"]['like_count'] = $pin_like_count;
                    $recycled_domain_stats["$pin_domain"]['comment_count'] = $pin_comment_count;

                } else {
                    $recycled_domain_stats["$pin_domain"]['pin_count'] += 1;
                    $recycled_domain_stats["$pin_domain"]['repin_count'] += $pin_repin_count;
                    $recycled_domain_stats["$pin_domain"]['like_count'] += $pin_like_count;
                    $recycled_domain_stats["$pin_domain"]['comment_count'] += $pin_comment_count;

                }
            }
        }

        $recent_category_feed_stats = new CategoryFeedStats();
        $recycled_category_feed_stats = new CategoryFeedStats();

        foreach($recent_domain_stats as $recent_domain){
            $category_feed_stat                = new CategoryFeedStat($category_name);
            $category_feed_stat->recency       = "new";
            $category_feed_stat->domain        = $recent_domain['domain'];
            $category_feed_stat->pin_count     = $recent_domain['pin_count'];
            $category_feed_stat->repin_count   = $recent_domain['repin_count'];
            $category_feed_stat->like_count    = $recent_domain['like_count'];
            $category_feed_stat->comment_count = $recent_domain['comment_count'];

            $recent_category_feed_stats->add($category_feed_stat);
        }

        foreach($recycled_domain_stats as $recycled_domain){
            $category_feed_stat                = new CategoryFeedStat($category_name);
            $category_feed_stat->recency       = "recycled";
            $category_feed_stat->domain        = $recycled_domain['domain'];
            $category_feed_stat->pin_count     = $recycled_domain['pin_count'];
            $category_feed_stat->repin_count   = $recycled_domain['repin_count'];
            $category_feed_stat->like_count    = $recycled_domain['like_count'];
            $category_feed_stat->comment_count = $recycled_domain['comment_count'];

            $recycled_category_feed_stats->add($category_feed_stat);
        }

        try {
            $recent_category_feed_stats->insertUpdateDB();
        } catch (CollectionException $e){
            CLI::alert(Log::notice("No Recent Category Feed Stats to save"));
        }

        try {
            $recycled_category_feed_stats->insertUpdateDB();
        } catch (CollectionException $e){
            CLI::alert(Log::notice("No Recycled Category Feed Stats to save"));
        }



        $category_feed_freq->update($new_pins_count, count($pins_from_api), $new_recent_pins_count, $new_is_repin_count, $category_name);

        if($common_pins === 0){
            return true;
        } else {
            return false;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
 */
class CategoryFeedQueueException extends DBModelException {}
