<?php

namespace Analytics;

use Config,
    DB,
    Input,
    Log,
    Pin,
    Pins,
    Redirect,
    Illuminate\Http\RedirectResponse as RedirectResponse,
    Request,
    Response,
    StatusKeyword,
    StatusDomain,
    Topic,
    URL,
    UserAccountKeyword,
    UserAccountKeywordException,
    UserAccountsDomain,
    UserAccountsDomainException,
    UserAccountTag,
    UserHistory,
    View;

/**
 * Discover / "listening" controller.
 * 
 * @author Daniel
 * @author Alex
 *
 * @package Analytics
 */
class DiscoverController extends BaseController
{
    protected $layout = 'layouts.analytics';

    protected $query_string;

    protected $query_args;

    protected $topics;

    protected $account_topics;

    protected $account_keywords;

    protected $account_domains;

    protected $account_tags;


    /**
     * Construct
     *
     * @author  Will
     */
    public function __construct()
    {

        parent::__construct();

        Log::setLog(__FILE__, 'Discover', 'Legacy_Discover_Listening');
    }

    /**
     * Displays the default (Pulse) view.
     * 
     * @route /discover
     *
     * @return void
     */
    public function index()
    {
        extract($this->baseLegacyVariables());

        $this->buildLayout('listening-pulse');

        $top_topics = $this->buildTopTopics();
        $topics     = array_slice(array_keys($top_topics), 0, 5, true);

        $trending_topics    = Topic::trendingPins($topics);
        $recommended_topics = Topic::recommendations($this->active_user_account, $topics);

        $this->layout->main_content = View::make('analytics.pages.discover.index', array(
            'topic_bar'          => $this->buildTopicBar(),
            'account_topics'     => $this->accountTopics(),
            'trending_topics'    => array_slice($trending_topics, 0, 3, true),
            'recommended_topics' => $recommended_topics,
            'top_topics'         => $top_topics,
        ));

        $customer->recordEvent(
            UserHistory::VIEW_REPORT,
            array(
                'report' => 'Listening-Start',
                'view'   => 'start',
            )
        );
    }

    /**
     * Builds an array of formatted source data for the Topic Bar.
     *
     * @route /discover/topic-bar
     *
     * @return array
     */
    public function buildTopicBarSourceData()
    {
        $keywords = $this->accountKeywords();
        $domains  = $this->accountDomains();

        $source_data = $this->prepareTopicBarData(array_merge($keywords, $domains));

        return $source_data;
    }

    /**
     * Adds a new topic.
     *
     * @route /discover/add-topic
     * 
     * @return mixed
     */
    public function addTopic()
    {
        $topic      = urldecode(Input::get('topic'));
        $topic_type = $this->topicType($topic);
        $data       = Input::get('data');

        $created = false;
        $code    = null;

        try {
            if ($topic_type == 'keyword') {
                $created = UserAccountKeyword::create($this->active_user_account, $topic, $data);
            } else if ($topic_type == 'domain') {
                $created = UserAccountsDomain::create($this->active_user_account, $topic, $data);
            }
        } catch (UserAccountKeywordException $e) {
            $code = $e->getCode();
        } catch (UserAccountsDomainException $e) {
            $code = $e->getCode();
        }

        if (Request::ajax()) {
            $response = array(
                'topic'      => $topic,
                'topic_type' => $topic_type,
                'success'    => $created,
            );

            if ($code) {
                $response['code'] = $code;
            }

            return $response;
        }

        return Redirect::route(
            'discover-feed', 
            array('trending', str_replace('+', ' ', urlencode($topic)))
        );
    }

    /**
     * Removes a topic.
     * 
     * @route /discover/remove-topic
     * 
     * @param string $topic
     * 
     * @return mixed
     */
    public function removeTopic($topic)
    {
        $topic_type = $this->topicType($topic);
        $data       = Input::get('data');

        if ($topic_type == 'keyword') {
            $deleted = UserAccountKeyword::delete($this->active_user_account, $topic, $data);
        }
        elseif ($topic_type == 'domain') {
            $deleted = UserAccountsDomain::delete($this->active_user_account, $topic, $data);
        }

        if (Request::ajax()) {
            return Response::json(array(
                'topic'      => $topic,
                'topic_type' => $topic_type,
                'success'    => $deleted,
            ));
        }

        return Redirect::back();
    }

    /**
     * Builds HTML output for the active account's tags.
     * 
     * @route /discover/tags
     *
     * @return string
     */
    public function tags()
    {
        return View::make(
            'analytics.pages.discover.tags',
            array('tags' => $this->accountTags())
        );
    }

    /**
     * Adds a new tag for one or more topics.
     * 
     * @route /discover/add-tag
     * 
     * @return mixed
     */
    public function addTag()
    {
        $name   = Input::get('name');
        $topics = (array) Input::get('topics');

        $created = false;
        foreach ($topics as $topic) {
            $created = UserAccountTag::create($this->active_user_account, $name, $topic);
            if (!$created) {
                break;
            }
        }

        if ($created) {
            $this->logged_in_customer->recordEvent(
                UserHistory::ADD_TAG,
                array('name' => $name, 'topics' => $topics)
            );
        }

        if (Request::ajax()) {
            return Response::json(array(
                'tag'     => $name,
                'success' => $created,
            ));
        }

        return Redirect::back();
    }

    /**
     * Removes a tag.
     *
     * @route /discover/remove-tag
     *
     * @param string $name
     *
     * @return mixed
     */
    public function removeTag($name)
    {
        $deleted = UserAccountTag::delete($this->active_user_account, $name);

        if (Request::ajax()) {
            return Response::json(array(
                'tag'     => $name,
                'success' => $deleted,
            ));
        }

        return Redirect::back();
    }

    /**
     * Processes and secures the topics query.
     *
     * @return bool
     */
    private function secureQuery()
    {
        $account_keywords = $this->accountKeywords();
        $account_domains  = $this->accountDomains();

        // Redirect to main page if account has no keywords or domains.
        if (empty($account_keywords) && empty($account_domains)) {
            return Redirect::route('discover');
        }

        /*
         * Manually grab the query string segment instead of relying on a route var.
         * (Laravel 4.1 auto runs urldecode() on route vars which breaks the + delimited query string.)
         */
        $query_string = Request::segment(3);

        // Set intelligent defaults for empty an empty query.
        if (empty($query_string)) {
            if (!empty($account_domains)) {
                $query_string = current($account_domains);
            } elseif (!empty($account_keywords)) {
                $query_string = current($account_keywords);
            }
        }

        $topics = $this->parseQueryString($query_string);

        // Make sure this account has access to the queried keyword(s)/domain(s).
        foreach ($topics as $type => $items) {
            foreach ($items as $topic) {
                if ($type == 'keywords' && !in_array($topic, $account_keywords)) {
                    return $this->denyAccess($topic);
                }
                else if ($type == 'domains' && !in_array($topic, $account_domains)) {
                    return $this->denyAccess($topic);
                }
            }
        }

        return true;
    }

    /**
     * Displays a feed of query-matching pins for various views (trending, most popular, etc).
     * 
     * @route /discover/[trending|most-repinned|most-liked|most-commented]
     * 
     * @param $type
     * 
     * @return void
     */
    public function feed($type = 'trending')
    {
        extract($this->baseLegacyVariables());

        $this->buildLayout("listening-".$type);

        $secure = $this->secureQuery();
        if (!$secure || $secure instanceof RedirectResponse) {
            return $secure;
        }

        if (is_numeric(Input::get('date'))) {
            return $this->feedSnapshot();
        }

        $keywords = array_get($this->topics, 'keywords', array());
        $domains  = array_get($this->topics, 'domains', array());

        if ($type == "trending") {
            $pins = $this->buildTrendingPins();
        } else {
            // Build popular feed of pins.
            $pins = new Pins();
            if (!empty($keywords)) {
                $pins = $pins->merge($this->buildPopularPins($type, 'keyword'));
            }

            if (!empty($domains)) {
                $pins = $pins->merge($this->buildPopularPins($type, 'domain'));
            }

            if ($type == "most-repinned") {
                $sort_field = "repin_count";
            } else if ($type == "most-liked") {
                $sort_field = "like_count";
            } else if ($type == "most-commented") {
                $sort_field = "comment_count";
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

        $next_page_link = URL::route('discover-feed', array($type, $this->query_string, 'page=' . ($page + 1)));
        $prev_page_link = ($page > 1) ? URL::route('discover-feed', array($type, $this->query_string, 'page=' . ($page - 1))) : '';

        if (Input::get('date')) {
            $next_page_link .= '&date=' . Input::get('date');
            if (!empty($prev_page_link)) {
                $prev_page_link .= '&date=' . Input::get('date');
            }
        }

        $feed_vars = array(
            'navigation'       => $this->buildNavigation($type),
            'right_navigation' => View::make('analytics.pages.discover.rightnav', array(
                'wordcloud_data' => json_encode($this->buildPinsWordcloud($pins)),
            )),
            'type'             => $type,
            'keywords'         => $keywords,
            'domains'          => $domains,
            'pins'             => $pins->getModels(),
            'next_page_link'   => $next_page_link,
            'prev_page_link'   => $prev_page_link,
        );

        $this->layout->main_content = View::make('analytics.pages.discover.feed.pins', $feed_vars);

    }

    /**
     * Displays insights for a set of topics.
     *
     * @route /discover/insights
     *
     * @return void
     */
    public function insights()
    {
        $this->buildLayout('listening-insights');

        $secure = $this->secureQuery();
        if (!$secure || $secure instanceof RedirectResponse) {
            return $secure;
        }

        $keywords = array_get($this->topics, 'keywords');
        $domains  = array_get($this->topics, 'domains');

        $daily_counts = array();
        $this->buildKeywordsDailyCounts($daily_counts);
        $this->buildDomainsDailyCounts($daily_counts);

        // Get the last time that data was pulled.
        if (!empty($keywords)) {
            $last_keyword    = $keywords[count($keywords) - 1];
            $status_keyword  = StatusKeyword::find($last_keyword);
            $cache_timestamp = $status_keyword ? $status_keyword->last_calced : 0;
        } elseif (!empty($domains)) {
            $last_domain     = $domains[count($domains) - 1];
            $status_domain   = StatusDomain::find($last_domain);
            $cache_timestamp = $status_domain ? $status_domain->last_calced : 0;
        }
        $current_date = getFlatDate($cache_timestamp);

        $date = Input::get('date', 'week');

        // Set standard periodic date range values.
        if ($date == 'week') {
            $last_date    = getFlatDate(strtotime("-7 days", $cache_timestamp));
            $day_range    = 7;
        } else if ($date == '2weeks') {
            $last_date    = getFlatDate(strtotime("-14 days", $cache_timestamp));
            $day_range    = 14;
        } else if ($date == 'month') {
            $last_date    = getFlatDate(strtotime("-1 month", $cache_timestamp));
            $day_range    = 30;
        } else if ($date == 'alltime') {
            $last_date    = getFlatDate(0);
            $day_range    = 0;
        }

        $max_data_age = $this->maxDataAge();
        if ($max_data_age > $last_date) {
            $last_date = $max_data_age;
        }

        $selected_keywords = ($keywords == $this->accountKeywords()) ? array('all-keywords') : $keywords;

        $insights_vars = array(
            'plan'                => $this->logged_in_customer->plan(),
            'customer'            => $this->logged_in_customer,
            'type'                => 'insights',
            'navigation'          => $this->buildNavigation('insights'),
            'query_args'          => $this->query_args,
            'keywords'            => $selected_keywords,
            'daily_counts'        => $daily_counts,
            'new_curr_chart_date' => $current_date * 1000,
            'new_last_chart_date' => $last_date * 1000,
            'influencers'         => $this->buildTopInfluencers($day_range),
        );

        if (empty($domains)) {
            $insights_vars['sources'] = $this->buildTopSources($keywords, $day_range);
        }

        $this->layout->main_content = View::make('analytics.pages.discover.insights', $insights_vars);

        $this->logged_in_customer->recordEvent(
            UserHistory::VIEW_REPORT,
            array(
               'report' => 'Listening-Summary',
               'view'   => $date,
            )
        );
    }

    /**
     * Builds an array of wordcloud data for a given date and set of keywords.
     *
     * @route /discover/wordcloud
     *
     * @return array
     */
    public function buildKeywordsWordcloudSnapshot()
    {
        $this->secureQuery();

        $keywords     = array_get($this->topics, 'keywords', array());
        $keywords_csv = '"' . implode('", "', $keywords) . '"';
        
        $date = Input::get('date', flat_date());

        $query = "SELECT word, SUM(word_count) AS word_count
                  FROM cache_keyword_wordclouds
                  WHERE keyword IN ($keywords_csv)
                    AND `date` >= ?
                    AND `date` <= ?
                  GROUP BY word
                  ORDER BY word_count DESC";

        $words = DB::select($query, array(strtotime('-3 days', $date), $date));

        $ignored_words = $keywords;

        $wordcloud_data = array();
        foreach ($words as $i => $word) {
            if (!in_array($word->word, $ignored_words) && $i < 60) {
                $wordcloud_data[] = array(
                    'text'   => $word->word,
                    'weight' => $word->word_count,
                    'link'   => 'javascript:void(0)',
                    'html'   => array(
                        'class'          => 'wordcloud-word',
                        'data-word'      => $word->word,
                        'data-toggle'    => 'popover',
                        'data-container' => 'body',
                        'data-placement' => 'left',
                        'data-content'   => 'Click to add <strong>' . $word->word . '</strong> to your keywords.',
                    )
                );
            }
        }

        return $wordcloud_data;
    }

    /**
     * Builds an HTML view of top pinners for a given date and query.
     *
     * @route /discover/top-pinners
     *
     * @return string
     */
    public function buildTopPinnersSnapshot()
    {
        $this->secureQuery();

        $keywords = array_get($this->topics, 'keywords', array());
        $domains  = array_get($this->topics, 'domains', array());

        $wheres = array();

        if (!empty($keywords)) {
            $keywords_csv = '"' . implode('", "', $keywords) . '"';
            $wheres[]     = "keyword IN ($keywords_csv)";
        }

        if (!empty($domains)) {
            $domains_csv = '"' . implode('", "', $domains) . '"';
            $wheres[]    = "domain IN ($domains_csv)";
        }

        $wheres[] = 'created_at > ?';
        $wheres[] = 'created_at < ?';

        $where_clause = 'WHERE ' . implode(' AND ', $wheres);

        $date = Input::get('date', flat_date());

        $query = "SELECT DISTINCT(subq.pinner_id), prof.username, prof.gender, prof.first_name, prof.last_name, prof.image,
                    prof.domain_url, prof.website_url, prof.facebook_url, prof.twitter_url,
                    prof.location, prof.pin_count, prof.follower_count
                  FROM
                      (SELECT pinner_id
                         FROM map_pins_keywords
                         $where_clause
                         LIMIT 25) AS subq
                  LEFT JOIN data_profiles_new AS prof
                  ON subq.pinner_id = prof.user_id
                  ORDER BY prof.follower_count DESC";

        $pinners = DB::select(
            $query,
            array($date, strtotime('+1 day', $date))
        );

        $pinners_html = '';
        foreach ($pinners as $pinner) {
            $pinners_html .= View::make('analytics.pages.discover.profile', array('profile' => $pinner, 'component' => 'Top Pinners Modal'));
        }

        return $pinners_html;
    }

    /**
     * Builds an array, by reference, of daily counts based on a set of keywords.
     *
     * @param array $daily_counts
     *
     * @return void
     */
    protected function buildKeywordsDailyCounts(&$daily_counts)
    {
        $keywords = array_get($this->topics, 'keywords');
        if (empty($keywords)) {
            return;
        }

        $keywords_csv = '"' . implode('", "', $keywords) . '"';

        $daily_keyword_data = DB::select(
            "SELECT *
             FROM cache_keyword_daily_counts
             WHERE keyword IN ($keywords_csv)"
        );

        $max_data_age = $this->maxDataAge();

        foreach ($daily_keyword_data as $data) {
            if ($data->date < $max_data_age) {
                continue;
            }

            if (isset($daily_counts[$data->date][$data->keyword])) {
                $daily_counts[$data->date][$data->keyword]['pin_count'] += $data->pin_count;
                $daily_counts[$data->date][$data->keyword]['pinner_count'] += $data->pinner_count;
                $daily_counts[$data->date][$data->keyword]['reach'] += $data->reach;
            } else {
                $daily_counts[$data->date][$data->keyword]['pin_count'] = $data->pin_count;
                $daily_counts[$data->date][$data->keyword]['pinner_count'] = $data->pinner_count;
                $daily_counts[$data->date][$data->keyword]['reach'] = $data->reach;
            }
        }
    }

    /**
     * Builds an array, by reference, of daily counts based on a set of domains.
     *
     * @param array $daily_counts
     *
     * @return void
     */
    protected function buildDomainsDailyCounts(&$daily_counts)
    {
        $domains = array_get($this->topics, 'domains');
        if (empty($domains)) {
            return;
        }

        $domains_csv = '"' . implode('", "', $domains) . '"';

        $daily_domain_data = DB::select(
            "SELECT domain
            , date
            , sum(pin_count) as pin_count
            , sum(pinner_count) as pinner_count
            , sum(repin_count) as repin_count
            , sum(like_count) as like_count
            , sum(comment_count) as comment_count
            , sum(reach) as reach
            FROM cache_domain_daily_counts
            WHERE domain IN ($domains_csv)
            GROUP BY domain, date"
        );

        $max_data_age = $this->maxDataAge();

        foreach ($daily_domain_data as $data) {
            if ($data->date < $max_data_age) {
                continue;
            }

            if (isset($daily_counts[$data->date][$data->domain])) {
                $daily_counts[$data->date][$data->domain]['pin_count'] += $data->pin_count;
                $daily_counts[$data->date][$data->domain]['pinner_count'] += $data->pinner_count;
                $daily_counts[$data->date][$data->domain]['reach'] += $data->reach;
            } else {
                $daily_counts[$data->date][$data->domain]['pin_count'] = $data->pin_count;
                $daily_counts[$data->date][$data->domain]['pinner_count'] = $data->pinner_count;
                $daily_counts[$data->date][$data->domain]['reach'] = $data->reach;
            }
        }
    }

    /**
     * Builds common layout elements.
     *
     * @param string $page
     *
     * @return void
     */
    protected function buildLayout($page)
    {
        $this->layout_defaults['page']          = 'Discover';
        $this->layout_defaults['top_nav_title'] = 'Discover';
        $this->layout->head                    .= View::make('analytics.components.head.discover');
        $this->layout->top_navigation           = $this->buildTopNavigation();
        $this->layout->side_navigation          = $this->buildSideNavigation($page);
        $this->layout->pre_body_close          .= View::make('analytics.components.pre_body_close.discover', array('page' => $page));
        $this->layout->pre_body_close          .= View::make('analytics.components.pre_body_close.wordcloud');
    }

    /**
     * Builds the top navigation.
     *
     * @param string $type
     *
     * @return View
     */
    protected function buildNavigation($type)
    {
        return View::make('analytics.pages.discover.nav', array(
            'plan'         => $this->logged_in_customer->plan(),
            'customer'     => $this->logged_in_customer,
            'topic_bar'    => $this->buildTopicBar($type),
            'type'         => $type,
            'query_string' => $this->query_string,
        ));
    }

    /**
     * Builds the topic bar.
     *
     * @param string $type
     *
     * @return View
     */
    protected function buildTopicBar($type = 'trending')
    {
        $keywords = $this->accountKeywords();
        $domains  = $this->accountDomains();

        return View::make('analytics.pages.discover.topicbar', array(
            'customer'      => $this->logged_in_customer,
            'type'          => $type,
            'query_data'    => $this->prepareTopicBarData(explode('+', $this->query_string)),
            'tags'          => $this->tags(),
            'keywords'      => $keywords,
            'domains'       => $domains,
            'keyword_count' => count($keywords),
            'domain_count'  => count($domains),
            'keyword_limit' => (int) $this->active_user_account->keywordLimit(),
            'domain_limit'  => (int) $this->active_user_account->domainLimit(),
        ));
    }

    /**
     * Parses the query string into a list of keywords and domains.
     *
     * @param string $string
     *
     * @return array
     */
    protected function parseQueryString($string)
    {
        $topics = array(
            'keywords' => array(),
            'domains'  => array(),
        );

        if ($string == 'all-keywords') {
            $topics['keywords'] = $this->accountKeywords();
        } elseif ($string == 'all-domains') {
            $topics['domains'] = $this->accountDomains();
        } else {
            $query_args = explode('+', $string);
            foreach ($query_args as $key => $topic) {
                if ($this->topicType($topic) == 'domain') {
                    $topics['domains'][] = $topic;
                } else {
                    $topics['keywords'][] = urldecode($topic);
                    $query_args[$key]     = urldecode($topic);
                }
            }
        }

        $this->query_string = implode('+', array_merge($topics['keywords'], $topics['domains']));
        $this->query_args   = $query_args;
        $this->topics       = $topics;

        return $topics;
    }

    /**
     * Prepares an array of data to be used with the TagsInput jQuery plugin.
     *
     * @param array $data
     *
     * @return array
     */
    protected function prepareTopicBarData(array $data)
    {
        $args = array();
        foreach ($data as $item) {
            if (empty($item)) {
                continue;
            }

            $args[] = array(
                'text'  => $item,
                'value' => $item,
                'type'  => $this->topicType($item),
            );
        }
        
        return $args;
    }

    /**
     * Displays a feed of popular pins created within 24 hours of a specific date.
     *
     * @route /discover/feed
     *
     * @return void
     */
    protected function feedSnapshot()
    {
        $date = Input::get('date', flat_date());

        $keywords = array_get($this->topics, 'keywords');
        $domains  = array_get($this->topics, 'domains');

        if (!empty($keywords)) {
            $keywords_csv = '"' . implode('", "', $keywords) . '"';

            $query = "SELECT a.keyword, a.pin_id,
                        b.domain, b.method, b.is_repin, b.parent_pin, b.via_pinner, b.origin_pin,
                        b.origin_pinner, b.image_url, b.link, b.description, b.dominant_color,
                        b.repin_count, b.like_count, b.comment_count, b.created_at, 
                        c.username, c.first_name, c.last_name, c.image, c.about, c.domain_url, c.website_url,
                        c.facebook_url, c.twitter_url, c.location, c.pin_count, c.follower_count, c.gender
                      FROM
                          (SELECT pin_id, keyword, domain
                           FROM map_pins_keywords
                           WHERE keyword IN ($keywords_csv)
                             AND created_at > ? AND created_at < ?
                           ORDER BY repin_count DESC
                           LIMIT 50) AS a
                      LEFT JOIN (data_pins_new b, data_profiles_new c)
                      ON (a.pin_id=b.pin_id AND b.user_id=c.user_id)";
        }

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
            DB::select($query, array($date, strtotime('+1 day', $date)))
        );

        $feed_vars = array(
            'pins'     => $pins,
            'type'     => 'snapshot',
            'keywords' => $keywords,
            'header'   => View::make('analytics.pages.discover.header', array(
                'type'         => 'snapshot',
                'date'         => $date,
                'query_string' => $this->query_string,
            )),
        );

        $this->layout->main_content = View::make('analytics.pages.discover.feed.pins', $feed_vars);

        $this->logged_in_customer->recordEvent(
            UserHistory::VIEW_REPORT,
            array(
                'report' => 'Listening-Feed-Snapshot',
                'view'   => 'feed date snapshot',
            )
        );
    }

    /**
     * Denies access to a topic (keyword or domain).
     *
     * @param string $topic
     *
     * @return void
     */
    protected function denyAccess($topic)
    {
        $this->layout->main_content = View::make('analytics.pages.discover.access', array(
            'topic_bar' => $this->buildTopicBar(),
            'topic'     => $topic,
        ));
    }

    /**
     * Determines a topic's type (keyword or domain).
     *
     * @param string $topic
     *
     * @return string
     */
    protected function topicType($topic)
    {
        return strpos($topic, '.') !== false ? 'domain' : 'keyword';
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
                    'weight' => $word['count'],
                    'link'   => 'javascript:void(0)',
                    'html'   => array(
                        'class'          => 'wordcloud-word',
                        'data-word'      => $word['word'],
                        'data-toggle'    => 'popover',
                        'data-container' => 'body',
                        'data-placement' => 'left',
                        'data-content'   => 'Click to add <strong>' . $word['word'] . '</strong> to your keywords.',
                    )
                );
            }
        }

        return $wordcloud_data;
    }

    /**
     * Determines the max data age allowed for the active user's plan.
     *
     * @return int
     */
    protected function maxDataAge()
    {
        $customer     = $this->logged_in_customer;
        $max_data_age = $customer->maxAllowed('listening_data_age_max');

        if ($max_data_age == 0) {
            return 0;
        } else {
            $max_data_age--;
        }
        
        $oldest_date  = flat_date('day', strtotime("-{$max_data_age} days"));

        return $oldest_date;
    }

    /**
     * Builds an array of all topics associated with an account.
     *
     * @return array
     */
    protected function accountTopics()
    {
        if (!empty($this->account_topics)) {
            return $this->account_topics;
        }

        return $this->account_topics = array_merge($this->accountKeywords(), $this->accountDomains());
    }

    /**
     * Builds an array of all keywords associated with an account.
     *
     * @return array
     */
    protected function accountKeywords()
    {
        if (!empty($this->account_keywords)) {
            return $this->account_keywords;
        }

        $all_keywords = UserAccountKeyword::find(array(
            'account_id' => $this->active_user_account->account_id,
        ));

        $keywords = array();
        foreach ($all_keywords as $keyword) {
            if (!empty($keyword->keyword)) {
                $keywords[] = $keyword->keyword;
            }
        }

        return $this->account_keywords = $keywords;
    }

    /**
     * Builds an array of all domains associated with an account.
     *
     * @return array
     */
    protected function accountDomains()
    {
        if (!empty($this->account_domains)) {
            return $this->account_domains;
        }

        $all_domains = UserAccountsDomain::find(array(
            'account_id' => $this->active_user_account->account_id,
        ));

        $domains = array();
        foreach ($all_domains as $domain) {
            if (!empty($domain->domain)) {
                $domains[] = $domain->domain;
            }
        }

        return $this->account_domains = $domains;
    }

    /**
     * Builds an array of all tags associated with an account.
     *
     * @return array
     */
    protected function accountTags()
    {
        if (!empty($this->account_tags)) {
            return $this->account_tags;
        }

        $tags = array();

        $account_tags = UserAccountTag::tags($this->active_user_account);
        foreach ($account_tags as $name => $topics) {
            $tags[$name] = array(
                'topics'    => $topics,
                'topic_bar' => $this->prepareTopicBarData($topics)
            );
        }

        return $this->account_tags = $tags;
    }

    /**
     * Builds an array of an account's top topics based on pin count.
     *
     * @return array
     */
    protected function buildTopTopics()
    {
        $curr_top_topics = Topic::popular($this->active_user_account);
        $prev_top_topics = Topic::popular(
            $this->active_user_account,
            strtotime("-14 days", flat_date()),
            strtotime("-7 days", flat_date())
        );

        $prev_top_topics_array = array();
        foreach ($prev_top_topics as $item) {
            $topic = isset($item->keyword) ? $item->keyword : $item->domain;
            $prev_top_topics_array[$topic] = $item->pin_count;
        }

        $top_topics = array();
        foreach ($curr_top_topics as $item) {
            $topic = isset($item->keyword) ? $item->keyword : $item->domain;

            $top_topics[$topic]['current']  = $item->pin_count;
            $top_topics[$topic]['previous'] = array_get($prev_top_topics_array, $topic, 0);
        }

        uasort($top_topics, function ($a, $b) {
            if ($a['current'] + $a['previous'] < $b['current'] + $b['previous']) {
                return 1;
            } else if ($a['current'] + $a['previous'] == $b['current'] + $b['previous']) {
                return 0;
            } else {
                return -1;
            }
        });

        return array_slice($top_topics, 0, 25, true);
    }

    /**
     * Builds an array of top sources for a keyword(s).
     *
     * @param array $keywords
     * @param int   $period
     *
     * @return array
     */
    protected function buildTopSources(array $keywords, $period = 0)
    {
        // All account keywords.
        if ($keywords == $this->accountKeywords()) {
            return UserAccountKeyword::topDomains($keywords, $period);
        }

        $sources = array();
        foreach ($keywords as $keyword) {
            $sources = array_merge($sources, UserAccountKeyword::topDomains($keyword, $period));
        }

        // Build the keyword mentions breakdown for each source.
        $top_sources = array();
        foreach ($sources as $source) {
            foreach ($keywords as $keyword) {
                // Ensure keywords with no mentions are included.
                if (empty($top_sources[$source->domain][$keyword])) {
                    $top_sources[$source->domain][$keyword] = 0;
                }
            }

            $top_sources[$source->domain][$source->keyword] = (int) $source->keyword_mentions;
        }

        // Sorted top sources data by most keyword mentions.
        uasort($top_sources, function ($a, $b) {
            $a_sum = array_sum($a);
            $b_sum = array_sum($b);

            if ($a_sum < $b_sum) {
                return 1;
            } else if ($a_sum == $b_sum) {
                return 0;
            } else {
                return -1;
            }
        });

        return array_slice($top_sources, 0, 10, true);
    }

    /**
     * Builds an array of top influencers for the query.
     *
     * @param int $period
     *
     * @return array
     */
    protected function buildTopInfluencers($period = 0)
    {
        $top_influencers = array();

        $keywords = array_get($this->topics, 'keywords');
        if (!empty($keywords)) {
            $keywords_influencers = UserAccountKeyword::topInfluencers($keywords, $period);
            $top_influencers      = array_merge($top_influencers, $keywords_influencers);
        }

        $domains = array_get($this->topics, 'domains');
        if (!empty($domains)) {
            $domains_influencers  = UserAccountsDomain::topInfluencers($domains, $period);
            $top_influencers      = array_merge($top_influencers, $domains_influencers);
        }

        foreach ($top_influencers as $influencer) {
            if (!empty($influencer->keyword)) {
                $topic          = $influencer->keyword;
                $mentions_count = $influencer->keyword_mentions;
            } else {
                $topic          = $influencer->domain;
                $mentions_count = $influencer->domain_mentions;
            }

            $influencer->topic          = $topic;
            $influencer->mentions_count = $mentions_count;
        }

        // If combining data, sort the combined results.
        if (!empty($keywords) && !empty($domains)) {
            usort($top_influencers, function ($a, $b) {
                if ($a->follower_count < $b->follower_count) {
                    return 1;
                } else if ($a->follower_count == $b->follower_count) {
                    return 0;
                } else {
                    return -1;
                }
            });
        }

        return array_slice($top_influencers, 0, 25, true);
    }

    /**
     * Queries for the most recent pins based on a set of keyword(s) and/or domain(s).
     *
     * @return array
     */
    protected function buildTrendingPins()
    {
        $keywords = array_get($this->topics, 'keywords');
        $domains  = array_get($this->topics, 'domains');

        $index_clause = '';
        $wheres       = array();
        $page         = Input::get('page', 1);
        $num          = 50;
        $offset       = ($page - 1) * $num;

        if (!empty($keywords)) {
            $keywords_csv = '"' . implode('", "', $keywords) . '"';
            $wheres[]     = "a.keyword IN ($keywords_csv)";
        }

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
                               ORDER BY created_at DESC
                               LIMIT $offset, $num) AS a
                          LEFT JOIN data_profiles_new b
                          ON a.user_id = b.user_id";
            }
        }

        if (!empty($wheres)) {
            $where_clause = 'WHERE ' . implode(' AND ', $wheres);

            $query = "SELECT subq.keyword, subq.pin_id,
                        b.domain, b.method, b.is_repin, b.parent_pin, b.via_pinner, b.origin_pin,
                        b.origin_pinner, b.image_url, b.link, b.description, b.repin_count,
                        b.like_count, b.comment_count, b.created_at,
                        c.username, c.first_name, c.last_name, c.image, c.about, c.domain_url, c.website_url,
                        c.facebook_url, c.twitter_url, c.location, c.pin_count, c.follower_count, c.gender
                      FROM
                          (SELECT a.pin_id, a.keyword
                           FROM map_pins_keywords a
                           $index_clause
                           $where_clause
                           ORDER BY a.created_at DESC
                           LIMIT $offset, $num) AS subq
                      LEFT JOIN (data_pins_new b, data_profiles_new c)
                      ON (subq.pin_id=b.pin_id AND b.user_id=c.user_id)";
        }

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
              'report' => "Listening-Latest",
              'view'   => $type,
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

        $date = Input::get('date', 'week');
        if ($date == "week") {
            $wheres[] = "period = 7 ";
        } else if ($date == "2weeks") {
            $wheres[] = "period = 14 ";
        } else if ($date == "month") {
            $wheres[] = "period = 30";
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
              'report' => "Listening-Popular-$feed_type",
              'view'   => $date,
            )
        );

        return $this->buildPinCollection(DB::select($query));
    }

    /**
     * Builds a collection of pins.
     *
     * @param Object $data
     *
     * @return Pins
     */
    private function buildPinCollection($data)
    {
        $pins = new Pins();
        foreach ($data as $item) {
            $pin = new Pin();
            $pin->loadDBData($item);
            $pin->pinner($item);
            $pins->add($pin);
        }

        return $pins;
    }
}
