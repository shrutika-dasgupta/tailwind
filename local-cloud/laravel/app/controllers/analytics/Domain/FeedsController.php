<?php

namespace Analytics\Domain;

use
    Config,
    DB,
    Input,
    Log,
    Pin,
    Pins,
    Illuminate\Http\RedirectResponse as RedirectResponse,
    Route,
    Session,
    StatusTraffic,
    URL,
    UserAccountsDomain,
    UserHistory,
    View;

/**
 * domain controller.
 * 
 * @author Alex
 * @author Daniel
 *
 * @package Analytics
 */
class FeedsController extends BaseController
{

    /*
    |--------------------------------------------------------------------------
    | Feed Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Displays a feed of query-matching pins for various views (latest, most popular, etc).
     *
     * @route /domain/[latest|most-repinned|most-liked|most-commented|most-clicked]
     *
     * @param $type
     *
     * @return $this->feed()
     */
    public function feedDefault($type = 'latest')
    {

        if($type == "latest"){
            $start_date = date("m-d-Y", strtotime("-1 days", flat_date('day')));
        } else {
            $start_date = date("m-d-Y", strtotime("-7 days", flat_date('day')));
        }
        $end_date   = date("m-d-Y", flat_date('day'));

        $secure = $this->secureQuery();
        if (!$secure || $secure instanceof RedirectResponse) {
            return $secure;
        }

        return $this->feed($type, $this->query_string, $start_date, $end_date);
    }

    /**
     * Displays a feed of query-matching pins for various views (latest, most popular, etc).
     *
     * @route /domain/[latest|most-repinned|most-liked|most-commented|most-clicked]
     *
     * @param string $type
     * @param        $query_string
     * @param string $start_date
     * @param string $end_date
     *
     * @return void
     */
    public function feed($type = 'latest', $query_string, $start_date, $end_date)
    {
        Log::setLog(__FILE__,'Reporting','Domain_Feed_'.$type);

        extract($this->baseLegacyVariables());
        $report_overlay    = "";
        $order_by          = "";
        $timeframe         = "";
        $engagement_type   = "";
        $eCommerceTracking = false;

        $secure = $this->secureQuery();
        if (!$secure || $secure instanceof RedirectResponse) {
            return $secure;
        }

        $this->buildLayout("domain-" . $type);

        if (is_numeric(Input::get('date'))) {
            return $this->feedSnapshot();
        }

        if ($type == "hashtag") {
            return $this->hashtagSnapshot($start_date, $end_date, Input::get('hashtag'));
        }

        $current_date          = getTimestampFromDate($end_date);
        $last_date             = getTimestampFromDate($start_date);
        $cache_timestamp_print = $end_date;
        $current_date_print    = $end_date;
        $last_date_print       = $start_date;

        $date = Input::get('date', 'week');
        if ($date == "week") {
            $period = "period = 7 ";
            $timeframe = "in the last 7 days";
        } else if ($date == "2weeks") {
            $period = "period = 14 ";
            $timeframe = "in the last 14 days";
        } else if ($date == "month") {
            $period = "period = 30 ";
            $timeframe = "in the last 30 days";
        } else if ($date == "2months") {
            $period = "period = 60 ";
            $timeframe = "in the last 60 days";
        } else if ($date == "alltime") {
            $period = "period = 0 ";
            $timeframe = "";
        }

        $keywords = array_get($this->topics, 'keywords', array());
        $domains  = array_get($this->topics, 'domains', array());

        $trending_images_enabled = $customer->hasFeature('domain_trending_images');

        if ($type == "latest") {
            $cache_timestamp_print = date("m-d-Y", flat_date('day'));
            $max_data_age = $this->maxDataAge();
            $pins = $this->buildLatestPins($end_date);

        } else if (in_array($type, array("most-clicked","most-visits","most-pageviews","most-transactions","most-revenue"))) {
            $pins = new Pins();

            $sort_type = Input::get('sort');

            if ($sort_type == "visits" || $type == "most-visits") {
                $order_by = "visits";
            } else if ($sort_type == "transactions" || $type == "most-transactions") {
                $order_by = "transactions";
            } else if ($sort_type == "revenue" || $type == "most-revenue") {
                $order_by = "revenue";
            } else {
                $order_by = "visits";
            }

            /**
             * Check whether google analytics enabled, and whether it matches a domain the user
             * has already added.
             */



            $traffic_id = $this->active_user_account->trafficId();
            if (!empty($traffic_id)) {

                if ($analytic = StatusTraffic::findByAccountId($this->active_user_account->account_id)) {

                    $analytics_match = false;
                    $analytic->websiteUrl = UserAccountsDomain::cleanDomainInput($analytic->websiteUrl);
                    $tracked_domain = $this->query_string;
                    $this->query_string = $analytic->websiteUrl;
                    $websiteUrl = $analytic->websiteUrl;

                    /**
                     * Cycle through all of the user's domains to see if any match the domain of their
                     * google analytics integration
                     */
                    foreach ($this->account_domains as $domain) {
                        if (strpos($analytic->websiteUrl, $domain) || strpos ($domain, $analytic->websiteUrl) || $domain == $analytic->websiteUrl) {
                            $analytics_match = true;
                        }
                    }

                    $pins = $this->buildTrafficPins($traffic_id, $order_by, $period);
                }
            }

            if($this->isDemo()) {
                $this->layout->alert = '<div class="alert alert-info">
                <button class="close" data-dismiss="alert" style="border: 0; background-color: transparent;">×</button>
                <strong>Note:</strong> This report is displaying demo data.</div>';
            }

            $has_analytics = $this->active_user_account->hasAnalytics();
            $analytics_ready = $this->active_user_account->analyticsReady();
            $analytics_profile = $this->active_user_account->getAnalyticsProfile();
            $eCommerceTracking = $this->active_user_account->hasECommerceTracking();

            $analytics_vars = array(
                'has_analytics'     => $has_analytics,
                'analytics_ready'   => $analytics_ready,
                'analytics_profile' => $analytics_profile,
            );

            $report_overlay = View::make('analytics.components.google_analytics_overlay', $analytics_vars);

        } else {
            /**
             * Build the Popular Pins Feed (Most Repinned, Most Liked, Most Commented)
             */
            $pins = new Pins();
//            if (!empty($keywords)) {
//                $pins = $pins->merge($this->buildPopularPins($type, 'keyword'));
//            }

            if (!empty($domains)) {
                $pins = $this->buildPopularPins($type, 'domain');
            }

            if ($type == "most-repinned") {
                $sort_field = "repin_count";
                $engagement_type = "repins";
            } else if ($type == "most-liked") {
                $sort_field = "like_count";
                $engagement_type = "likes";
            } else if ($type == "most-commented") {
                $sort_field = "comment_count";
                $engagement_type = "comments";
            }

            // If combining data, sort the combined results.
            if (!empty($keywords) && !empty($domains)) {
                $pins = $pins->sortBy($sort_field, SORT_DESC);
            }
        }

        // Remove low-quality pins.
        foreach ($pins->getModels() as $key => $pin) {
            if (empty($pin->pinner()->follower_count)) {
                $pins->removeModel($key);
            }
        }

        $page   = Input::get('page', 1);
        $num    = 50;
        $offset = ($page - 1) * $num;

        $next_page_link = URL::route('domain-feed', array($type, $this->query_string, 'page=' . ($page + 1)));
        $prev_page_link = ($page > 1) ? URL::route('domain-feed', array($type, $this->query_string, 'page=' . ($page - 1))) : '';

        if (Input::get('date')) {
            $next_page_link .= '&date=' . Input::get('date');
            if (!empty($prev_page_link)) {
                $prev_page_link .= '&date=' . Input::get('date');
            }
        }

        $route              = Route::currentRouteName();
        $type               = Route::input('type');
        $pretty_report_name = $this->prettyReportName($route, $type);

        if ($type == "most-commented") {
            $wordcloud_data = json_encode($this->buildCommentsWordcloud($pins));
        } else {
            $wordcloud_data = json_encode($this->buildPinsWordcloud($pins));
        }

        $right_navigation = View::make(
            'analytics.pages.domain.rightnav',
            array(
                 'wordcloud_data' => $wordcloud_data,
                 'type'           => $type,
            )
        );

        $feed_vars = array_merge(array(
            'right_navigation'        => $right_navigation,
            'type'                    => $type,
            'keywords'                => $keywords,
            'domains'                 => $domains,
            'pins'                    => $pins->getModels(),
            'next_page_link'          => $next_page_link,
            'prev_page_link'          => $prev_page_link,
            'report_overlay'          => $report_overlay,
            'order_by'                => $order_by,
            'timeframe'               => $timeframe,
            'plan'                    => $this->logged_in_customer->plan(),
            'customer'                => $this->logged_in_customer,
            'topic_bar'               => $this->buildTopicBar($type),
            'query_string'            => $this->query_string,
            'report_overlay'          => $report_overlay,
            'pretty_report_name'      => $pretty_report_name,
            'range'                   => Input::get('date', 'week'),
            'max_data_age'            => $max_data_age,
            'cache_timestamp_print'   => $cache_timestamp_print,
            'current_date'            => $current_date,
            'last_date'               => $last_date,
            'current_date_print'      => $current_date_print,
            'last_date_print'         => $last_date_print,
            'engagement_type'         => $engagement_type,
            'ecommerce_tracking'      => $eCommerceTracking,
            'trending_images_enabled' => $trending_images_enabled,
            'most-valuable-reports'   => array('most-clicked', 'most-visits', 'most-pageviews', 'most-transactions', 'most-revenue'),

            'nav_domain_benchmarks_class' => $nav_domain_benchmarks_class, // from baseLegacyVariables
            'has_competitors'             => $has_competitors, // from baseLegacyVariables
            'nav_competitors_enabled'     => $nav_competitors_enabled, // from baseLegacyVariables
            'nav_link_traffic'            => $nav_link_traffic,
            'nav_traffic_class'           => $nav_traffic_class // from baseLegacyVariables
        ),$this->baseLegacyVariables());

        if (!$analytics_match
            && in_array($type, array("most-clicked","most-visits","most-pageviews","most-transactions","most-revenue"))
            && !empty($traffic_id)
        ) {
            $this->layout->alert = View::make('shared.components.alert_with_x',
                array(
                     'message' => "<strong>Note:</strong> The domain of your Google Analytics
                                    account ($websiteUrl) does not match the domain you're tracking
                                    on Pinterest ($tracked_domain).  You may be looking at two
                                    unrelated sets of data.",
                     'type'    => 'warning'
                )
            );
        }

        $header     = View::make('analytics.pages.domain.feed_header', $feed_vars);

        $feed_vars = array_merge($feed_vars, array(
            "header"     => $header
        ));

        $this->layout->sub_navigation = View::make('analytics.components.sub_nav.domain', $feed_vars);

        $this->layout->main_content = View::make('analytics.pages.domain.feed', $feed_vars);
    }

    /**
     * Queries for the most recent pins based on a set of keyword(s) and/or domain(s).
     *
     * @param $as_of_date
     *
     * @return array
     */
    protected function buildLatestPins($as_of_date)
    {
        if (!is_numeric($as_of_date)) {
            $as_of_date = getTimestampFromDate($as_of_date);
        }
        if (empty($as_of_date) || $as_of_date == flat_date('day')) {
            $as_of_date = time();
        }
//        $keywords = array_get($this->topics, 'keywords');
        $domains  = array_get($this->topics, 'domains');

        $index_clause = '';
        $wheres       = array();
        $page         = Input::get('page', 1);
        $num          = 50;
        $offset       = ($page - 1) * $num;



//        if (!empty($keywords)) {
//            $keywords_csv = '"' . implode('", "', $keywords) . '"';
//            $wheres[]     = "a.keyword IN ($keywords_csv)";
//        }
        if (!empty($domains)) {
            $domains_csv = '"' . implode('", "', $domains) . '"';

            if (!empty($keywords)) {
                $index_clause = 'USE INDEX (keyword_domain_created_at_idx)';
                $wheres[]    = "a.domain IN ($domains_csv)";
            } else {
                $query = "SELECT a.pin_id, a.domain, a.method, a.is_repin, a.parent_pin, a.via_pinner,
                            a.origin_pin, a.origin_pinner, a.image_url, a.link, a.description,
                            a.repin_count, a.like_count, a.comment_count, a.created_at,
                            b.username, b.first_name, b.last_name, b.image, b.about, b.domain_url, b.website_url,
                            b.facebook_url, b.twitter_url, b.location, b.pin_count, b.follower_count, b.gender
                          FROM
                              (SELECT pin_id, user_id, domain, method, is_repin, parent_pin, via_pinner,
                                  origin_pin, origin_pinner, image_url, link, description,
                                  repin_count, like_count, comment_count, created_at
                               FROM data_pins_new
                               WHERE domain IN ($domains_csv)
                               AND created_at < {$as_of_date}
                               ORDER BY created_at DESC
                               LIMIT $offset, $num) AS a
                          LEFT JOIN data_profiles_new b
                          ON a.user_id = b.user_id";
            }
        }


        /*
         * TODO: for future use when we enable tracking keywords within your domain
         */
//        if (!empty($wheres)) {
//            $where_clause = 'WHERE ' . implode(' AND ', $wheres);
//
//            $query = "SELECT subq.keyword, subq.pin_id,
//                        b.domain, b.method, b.is_repin, b.parent_pin, b.via_pinner, b.origin_pin,
//                        b.origin_pinner, b.image_url, b.link, b.description, b.repin_count,
//                        b.like_count, b.comment_count, b.created_at,
//                        c.username, c.first_name, c.last_name, c.image, c.about, c.domain_url, c.website_url,
//                        c.facebook_url, c.twitter_url, c.location, c.pin_count, c.follower_count, c.gender
//                      FROM
//                          (SELECT a.pin_id, a.keyword
//                           FROM map_pins_keywords a
//                           $index_clause
//                           $where_clause
//                           ORDER BY a.created_at DESC
//                           LIMIT $offset, $num) AS subq
//                      LEFT JOIN (data_pins_new b, data_profiles_new c)
//                      ON (subq.pin_id=b.pin_id AND b.user_id=c.user_id)";
//        }

        $pins = $this->buildPinCollection(DB::select($query));

        $max_data_age = $this->maxDataAge();
        foreach ($pins->getModels() as $key => $pin) {
            if ($pin->created_at < $max_data_age) {
                $pins->removeModel($key);
            }
        }

        $this->logged_in_customer->recordEvent(
                                 UserHistory::VIEW_REPORT,
                                     array(
                                          'report' => "domain-latest-pins"
                                     )
        );

        return $pins;
    }

    /**
     * Queries for the most popular pins based on a set of keyword(s) or domain(s).
     *
     * @param string  $feed_type  (most-repinned, most-liked, most-commented)
     * @param string  $topic_type (keyword or domain)
     *
     * @return array
     */
    protected function buildPopularPins($feed_type, $topic_type)
    {
        $wheres = array();

        if ($topic_type == 'keyword') {
            $table        = 'cache_keyword_pins';
            $subq_fields  = 'subq.keyword, subq.domain';
            $fields       = 'keyword, domain, image AS image_url';
            $keywords_csv = '"' . implode('", "', array_get($this->topics, 'keywords')) . '"';
            $wheres[]     = "keyword IN ($keywords_csv)";
        } else {
            $table       = 'cache_domain_pins';
            $subq_fields = 'subq.domain';
            $fields      = 'domain, image_url';
            $domains_csv = '"' . implode('", "', array_get($this->topics, 'domains')) . '"';
            $wheres[]    = "domain IN ($domains_csv)";
        }

        if ($feed_type == "most-repinned") {
            $order_by = "repin_count";
        } else if ($feed_type == "most-liked") {
            $order_by = "like_count";
        } else if ($feed_type == "most-commented") {
            $order_by = "comment_count";
        }

        $wheres[] = "$order_by != 0 ";

        $date = Input::get('date', 'week');
        if ($date == "week") {
            $wheres[] = "period = 7 ";
        } else if ($date == "2weeks") {
            $wheres[] = "period = 14 ";
        } else if ($date == "month") {
            $wheres[] = "period = 30";
        } else if ($date == "2months") {
            $wheres[] = "period = 60";
        } else if ($date == "alltime") {
            $wheres[] = "period = 0";
        }

        $where_clause = 'WHERE ' . implode(' AND ', $wheres);

        $page   = Input::get('page', 1);
        $num    = 50;
        $offset = ($page - 1) * $num;

        $query = "SELECT $subq_fields, subq.image_url, subq.pin_id, subq.user_id, subq.method,
                         subq.is_repin, subq.link, subq.description, subq.repin_count,
                         subq.like_count, subq.comment_count, subq.created_at,
                         prof.username, prof.first_name, prof.last_name, prof.image,
                         prof.domain_url, prof.website_url, prof.facebook_url, prof.twitter_url,
                         prof.location, prof.pin_count, prof.follower_count
                  FROM
                      (SELECT $fields, pin_id, user_id, method, is_repin,
                              link, description, repin_count, like_count, comment_count, created_at
                       FROM $table
                       $where_clause
                       ORDER BY $order_by DESC
                       LIMIT $offset, $num) AS subq
                  LEFT JOIN data_profiles_new AS prof ON (subq.user_id=prof.user_id)";

        $this->logged_in_customer->recordEvent(
                                 UserHistory::VIEW_REPORT,
                                     array(
                                          'report' => "domain-$feed_type",
                                          'view'   => $date,
                                     )
        );

        return $this->buildPinCollection(DB::select($query), $feed_type == "most-commented");
    }

    /**
     * Queries for the most clicked pins based on traffic_id.
     *
     * @param        $traffic_id
     *
     * @param string $order_by
     *
     * @param        $period
     *
     * @return array
     */
    protected function buildTrafficPins($traffic_id, $order_by, $period)
    {
        $page   = Input::get('page', 1);
        $num    = 50;
        $offset = ($page - 1) * $num;

        $query = "SELECT subq.image_url, subq.pin_id, subq.visits, subq.transactions, subq.revenue,
                         subq.user_id, subq.method, subq.is_repin, subq.link, subq.description,
                         subq.repin_count, subq.like_count, subq.comment_count, subq.created_at,
                         prof.username, prof.first_name, prof.last_name, prof.image,
                         prof.domain_url, prof.website_url, prof.facebook_url, prof.twitter_url,
                         prof.location, prof.pin_count, prof.follower_count
                  FROM
                      (SELECT pin_id, visits, transactions, revenue,
                              user_id, method, is_repin, image as image_url, link, description,
                              repin_count, like_count, comment_count, created_at
                       FROM cache_traffic_pins
                       WHERE traffic_id = $traffic_id
                       AND $period
                       AND user_id != 0
                       AND $order_by > 0
                       ORDER BY $order_by DESC
                       LIMIT $offset, $num) AS subq
                  LEFT JOIN data_profiles_new AS prof ON (subq.user_id=prof.user_id)";

        $this->logged_in_customer->recordEvent(
                                 UserHistory::VIEW_REPORT,
                                     array(
                                          'report' => "most-valuable-pins - $order_by",
                                          'view'   => $period,
                                     )
        );

        $data = DB::select($query);

        $pins = new Pins();
        foreach ($data as $item) {
            $pin = new Pin();
            $pin->loadDBData($item);
            $pin->pinner($item);
            $pin->visits       = $item->visits;
            $pin->transactions = $item->transactions;
            $pin->revenue      = $item->revenue;

            $pins->add($pin, true);
        }

        return $pins;
    }

    /**
     * Builds an array of wordcloud data based on a set of pins.
     *
     * @param array $pins
     *
     * @return array
     */
    protected function buildPinsWordcloud($pins)
    {
        $words = $pins->wordcloud(explode('+', $this->query_string));

        $wordcloud_data = array();
        foreach ($words as $i => $word) {
            if ($i < 60) {
                $wordcloud_data[] = array(
                    'text'   => $word['word'],
                    'weight' => $word['count']
                    //'link'   => 'javascript:void(0)',
//                    'html'   => array(
//                        'class'          => 'wordcloud-word',
//                        'data-word'      => $word['word'],
//                        'data-toggle'    => 'popover',
//                        'data-container' => 'body',
//                        'data-placement' => 'left',
//                        'data-content'   => 'Click to add <strong>' . $word['word'] . '</strong> to your keywords.',
                    //)
                );
            }
        }

        return $wordcloud_data;
    }

    /**
     * Builds a wordcloud from pin comments
     *
     * @author Alex
     *
     * @param Pins Collection $pins
     *
     * @return array
     */
    public function buildCommentsWordcloud($pins)
    {
        $regex_url = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";

        /*
         * concatenate all comment text together
         */
        foreach ($pins as $pin) {
            if ($pin->comments()->count() > 0) {
                foreach ($pin->comments() as $comment) {
                    if (!isset($wordcloud)) {
                        $wordcloud = preg_replace('/[^A-Za-z0-9 ]/',' ', strip_tags(strtolower(preg_replace($regex_url,' ', $comment->comment_text))));
                    } else {
                        $wordcloud = $wordcloud . ' ' . preg_replace('/[^A-Za-z0-9 ]/',' ', strip_tags(strtolower(preg_replace($regex_url,' ',$comment->comment_text))));
                    }
                }
            }
        }

        $word_remove = Config::get('keywords.ignored_words');

        $word = array();
        $words = explode(' ', $wordcloud);

        if(count($words) > 0){
            foreach($words as $k => $w){
                if(!in_array($w, $word_remove) && strlen($w) > 2){
                    if(!isset($word["$w"])){
                        $word["$w"] = array();
                        $word["$w"]['word'] = $w;
                        $word["$w"]['word_count'] = 1;
                    } else {
                        $word["$w"]['word_count'] += 1;
                    }
                }
            }

            usort($word, function ($a, $b) {
                $t = "word_count";

                if ($a["$t"] < $b["$t"]) {
                    return 1;
                } else if ($a["$t"] == $b["$t"]) {
                    return 0;
                } else {
                    return -1;
                }
            });

            foreach ($word as $i => $word_count) {
                if ($i < 60) {
                    $wordcloud_data[] = array(
                        'text'   => $word_count['word'],
                        'weight' => $word_count['word_count']
                    );
                }
            }

            return $wordcloud_data;
        }
    }

    /**
     * Displays a feed of popular pins created within 24 hours of a specific date.
     *
     * @route /domain/feed
     *
     * @return void
     */
    protected function feedSnapshot()
    {
        if (is_numeric(Input::get('date'))) {
            $last_date = Input::get('date', flat_date());
            $current_date = $cache_timestamp = strtotime("+1 day", $last_date);
        } else {
            $last_date = Input::get('sdate');
            $current_date = $cache_timestamp = Input::get('edate');
        }

        if ($current_date = flat_date('day')){
            $current_date = time();
            $timeframe = "newest";
        } else {
            $timeframe = "historical";
        }


//        $keywords = array_get($this->topics, 'keywords');
        $domains  = array_get($this->topics, 'domains');

//        if (!empty($keywords)) {
//            $keywords_csv = '"' . implode('", "', $keywords) . '"';
//
//            $query = "SELECT a.keyword, a.pin_id,
//                        b.domain, b.method, b.is_repin, b.parent_pin, b.via_pinner, b.origin_pin,
//                        b.origin_pinner, b.image_url, b.link, b.description, b.dominant_color,
//                        b.repin_count, b.like_count, b.comment_count, b.created_at,
//                        c.username, c.first_name, c.last_name, c.image, c.about, c.domain_url, c.website_url,
//                        c.facebook_url, c.twitter_url, c.location, c.pin_count, c.follower_count, c.gender
//                      FROM
//                          (SELECT pin_id, keyword, domain
//                           FROM map_pins_keywords
//                           WHERE keyword IN ($keywords_csv)
//                             AND created_at > ? AND created_at < ?
//                           ORDER BY repin_count DESC
//                           LIMIT 50) AS a
//                      LEFT JOIN (data_pins_new b, data_profiles_new c)
//                      ON (a.pin_id=b.pin_id AND b.user_id=c.user_id)";
//        }

        if (!empty($domains)) {
            $domains_csv = '"' . implode('", "', $domains) . '"';

            $query = "SELECT a.pin_id, a.domain, a.method, a.is_repin, a.parent_pin, a.via_pinner,
                        a.origin_pin, a.origin_pinner, a.image_url, a.link, a.description,
                        a.repin_count, a.like_count, a.comment_count, a.created_at,
                        b.username, b.first_name, b.last_name, b.image, b.about, b.domain_url, b.website_url,
                        b.facebook_url, b.twitter_url, b.location, b.pin_count, b.follower_count, b.gender
                      FROM
                          (SELECT pin_id, user_id, domain, method, is_repin, parent_pin, via_pinner,
                              origin_pin, origin_pinner, image_url, link, description,
                              repin_count, like_count, comment_count, created_at
                           FROM data_pins_new
                           WHERE domain IN ($domains_csv)
                             AND created_at > ? AND created_at < ?
                           ORDER BY repin_count DESC
                           LIMIT 50) AS a
                      LEFT JOIN data_profiles_new b
                      ON a.user_id = b.user_id";
        }


        $pins = $this->buildPinCollection(
                     DB::select($query, array($last_date, $current_date))
        );


        $max_data_age = $this->maxDataAge();
        if ($max_data_age > $last_date) {
            $last_date = $max_data_age;
        }

        $cache_timestamp_print = date("m-d-Y", $cache_timestamp);
        $last_date_print       = date("m-d-Y", $last_date);
        $current_date_print    = date("m-d-Y", $current_date);


        $feed_vars = array_merge(array(
            'pins'                  => $pins,
            'type'                  => 'snapshot',
//            'keywords'              => $keywords,
            'max_data_age'          => $max_data_age,
            'current_date'          => $current_date,
            'last_date'             => $last_date,
            'cache_timestamp_print' => $cache_timestamp_print,
            'current_date_print'    => $current_date_print,
            'last_date_print'       => $last_date_print,
            'header'   => View::make('analytics.pages.domain.header', array(
                                                                           'type'         => 'snapshot',
                                                                           'date'         => $last_date,
                                                                           'query_string' => $this->query_string,
                                                                      )),
        ),$this->baseLegacyVariables());

        $this->layout->main_content = View::make('analytics.pages.domain.feed', $feed_vars);

        $this->logged_in_customer->recordEvent(
                                 UserHistory::VIEW_REPORT,
                                     array(
                                          'report' => 'domain-feed-snapshot',
                                          'view'   => "$timeframe",
                                     )
        );
    }

    /**
     * Displays a feed of pins with a specific hashtag from a given domain, within a date range.
     *
     * @route /domain/feed
     *
     * @param $start_date
     * @param $end_date
     * @param $hashtag
     *
     * @return void
     */
    protected function hashtagSnapshot($start_date, $end_date, $hashtag)
    {
        $hashtag = urldecode($hashtag);

        $last_date = getTimestampFromDate($start_date);
        $current_date = $cache_timestamp = getTimestampFromDate($end_date);

        if ($current_date = flat_date('day')){
            $current_date = time();
            $timeframe = "newest";
        } else {
            $timeframe = "historical";
        }

        $domains  = array_get($this->topics, 'domains');

        $query = "SELECT a.keyword, a.pin_id,
                    b.domain, b.method, b.is_repin, b.parent_pin, b.via_pinner, b.origin_pin,
                    b.origin_pinner, b.image_url, b.link, b.description, b.dominant_color,
                    b.repin_count, b.like_count, b.comment_count, b.created_at,
                    c.username, c.first_name, c.last_name, c.image, c.about, c.domain_url, c.website_url,
                    c.facebook_url, c.twitter_url, c.location, c.pin_count, c.follower_count, c.gender
                  FROM
                      (SELECT pin_id, keyword, domain
                       FROM map_pins_keywords
                       WHERE keyword = '$hashtag'
                       AND domain = '$this->query_string'
                         AND created_at > ? AND created_at < ?
                       ORDER BY repin_count DESC
                       LIMIT 50) AS a
                  LEFT JOIN (data_pins_new b, data_profiles_new c)
                  ON (a.pin_id=b.pin_id AND b.user_id=c.user_id)";



        $pins = $this->buildPinCollection(
                     DB::select($query, array($last_date, $current_date))
        );

        $max_data_age = $this->maxDataAge();
        if ($max_data_age > $last_date) {
            $last_date = $max_data_age;
        }

        $cache_timestamp_print = date("m-d-Y", $cache_timestamp);
        $last_date_print       = date("m-d-Y", $last_date);
        $current_date_print    = date("m-d-Y", $current_date);


        $feed_vars = array_merge(array(
            'pins'                  => $pins,
            'type'                  => 'snapshot',
            //'keywords'              => $keywords,
            'max_data_age'          => $max_data_age,
            'current_date'          => $current_date,
            'last_date'             => $last_date,
            'cache_timestamp_print' => $cache_timestamp_print,
            'current_date_print'    => $current_date_print,
            'last_date_print'       => $last_date_print,
            'header'   => View::make('analytics.pages.domain.header', array(
                                                                           'type'         => 'snapshot',
                                                                           'last_date'    => $last_date,
                                                                           'current_date' => $current_date,
                                                                           'is_hashtag'   => true,
                                                                           'hashtag'      => $hashtag,
                                                                           'query_string' => $this->query_string,
                                                                      )),
        ),$this->baseLegacyVariables());

        $this->layout->main_content = View::make('analytics.pages.domain.feed', $feed_vars);

        $this->logged_in_customer->recordEvent(
                                 UserHistory::VIEW_REPORT,
                                     array(
                                          'report' => 'domain-feed-snapshot',
                                          'view'   => "$timeframe",
                                     )
        );
    }
}
