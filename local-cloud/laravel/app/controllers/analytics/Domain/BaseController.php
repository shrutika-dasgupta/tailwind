<?php

namespace Analytics\Domain;

use Carbon\Carbon,
    Config,
    DatePeriod,
    DateInterval,
    DateTime,
    DB,
    Input,
    Log,
    Pin,
    Pins,
    Redirect,
    Illuminate\Http\RedirectResponse as RedirectResponse,
    Request,
    Response,
    Route,
    StatusKeyword,
    StatusDomain,
    StatusTraffic,
    Topic,
    URL,
    UserAccountKeyword,
    UserAccountKeywordException,
    UserAccountsDomain,
    UserAccountsDomainException,
    UserAccountTag,
    UserAnalytic,
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
class BaseController extends \Analytics\BaseController
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
     * Displays the default (Pulse) view.
     * 
     * @route /domain
     *
     * @return void
     */
    public function index()
    {
        extract($this->baseLegacyVariables());

        //$this->buildLayout('pulse');

//        $top_topics = $this->buildTopTopics();
//        $topics     = array_slice(array_keys($top_topics), 0, 5, true);
//
//        $trending_topics    = Topic::trendingPins($topics);
//        $recommended_topics = Topic::recommendations($this->active_user_account, $topics);

        $account_domains = $this->accountDomains();
        if (empty($account_domains)) {
            $this->layout->main_content = View::make('analytics.pages.domain.add_domain_form', array(
//                'topic_bar'          => $this->buildTopicBar(),
                'account_domains'     => $this->accountDomains(),
//                'trending_topics'    => array_slice($trending_topics, 0, 3, true),
                //            'recommended_topics' => $recommended_topics,
//                'top_topics'         => $top_topics,
            ));
        } else {
            return Redirect::route('domain-insights-default');
        }

        $customer->recordEvent(
            UserHistory::VIEW_REPORT,
            array(
                'report' => 'domain',
                'view'   => 'add domain form',
            )
        );
    }

    /**
     * Builds an array of formatted source data for the Topic Bar.
     *
     * @route /domain/topic-bar
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
     * /domain/add-domain
     */
    public function addDomain()
    {
        $domain = urldecode(Input::get('domain'));

        $domain = UserAccountsDomain::cleanDomainInput($domain);

        if(strpos($domain, '.') === false || strpos($domain, " ") !== false) {
            return Redirect::back()
                ->with(
                    'flash_error',
                    "<strong>Whoops!</strong> We had some trouble validating you domain.
                    Please ensure it was entered correctly and try again :)"
                );
        }

        $created = false;
        $code    = null;

        try {
            $domain_added = $this->active_user_account->addDomain($domain);
            $created = true;
        } catch (UserAccountsDomainException $e) {
            $code = $e->getCode();
        }

        if (Request::ajax()) {
            $response = array(
                'domain'  => $domain,
                'success' => $created,
            );

            if ($code) {
                $response['code'] = $code;
            }

            return $response;
        }

        return Redirect::route(
            'domain-feed',
            array('latest', str_replace('+', ' ', urlencode($domain)))
        )->with(
            "flash_success",
            "<strong>Hooray!</strong> $domain successfully added!  Please note that data may
            take a few minutes to begin populating :)"
        );
    }


    /**
     * Adds a new topic.
     *
     * @route /domain/add-topic
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
            'domain-feed',
            array('latest', str_replace('+', ' ', urlencode($topic)))
        );
    }

    /**
     * Removes a topic.
     * 
     * @route /domain/remove-topic
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
     * @route /domain/tags
     *
     * @return string
     */
    public function tags()
    {
        return View::make(
            'analytics.pages.domain.tags',
            array('tags' => $this->accountTags())
        );
    }

    /**
     * Adds a new tag for one or more topics.
     * 
     * @route /domain/add-tag
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
     * @route /domain/remove-tag
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
    protected function secureQuery()
    {
//        $account_keywords = $this->accountKeywords();
        $account_domains  = $this->accountDomains();

        // Redirect to main page if account has no keywords or domains.
        if (empty($account_domains)) {
//            return Redirect::route('domain');
            return $this->denyAccess($topic);
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
            }

//            elseif (!empty($account_keywords)) {
//                $query_string = current($account_keywords);
//            }
        }

        $topics = $this->parseQueryString($query_string);

        // Make sure this account has access to the queried keyword(s)/domain(s).
        foreach ($topics as $type => $items) {
            foreach ($items as $topic) {
//                if ($type == 'keywords' && !in_array($topic, $account_keywords)) {
//                    return $this->denyAccess($topic);
//                }
//                else

                if ($type == 'domains' && !in_array($topic, $account_domains)) {
                    return $this->denyAccess($topic);
                }
            }
        }

        return true;
    }


    /**
     * Builds an array of hashtag wordcloud data for a given date and set of domains.
     *
     *
     * @param $start_date
     * @param $end_date
     *
     * @return array
     */
    public function buildDomainsWordcloudSnapshot($start_date, $end_date)
    {
        $this->secureQuery();

        $domains     = array_get($this->topics, 'domains', array());
        $domains_csv = '"' . implode('", "', $domains) . '"';

        $last_date = getTimestampFromDate($start_date);
        $current_date = getTimestampFromDate($end_date);
        
        if ($this->logged_in_customer->hasFeature('domain_insights_hashtags')) {
            $query = "SELECT word, SUM(word_count) AS word_count
                      FROM cache_domain_wordclouds
                      WHERE domain IN ($domains_csv)
                        AND `date` >= ?
                        AND `date` <= ?
                      GROUP BY word
                      ORDER BY word_count DESC
                      LIMIT 50";

            $words = DB::select($query, array($last_date, $current_date));
        }

        $wordcloud_data = array();

        /**
         * Fall back to example word cloud if empty, or feature not enabled.
         */
        if (empty($words)) {
            $words = DB::select(
                "SELECT word, SUM(word_count) AS word_count
                 FROM cache_domain_wordclouds
                 WHERE domain = 'demo.tailwindapp.com'
                 GROUP BY word
                 ORDER BY word_count DESC
                 LIMIT 50"
            );
            $wordcloud_data['example'] = true;
        }

        $ignored_words = $domains;

        foreach ($words as $i => $word) {
            if (!in_array($word->word, $ignored_words) && $i < 60) {

                if ($this->logged_in_customer->hasFeature('domain_hashtags_feed') && !$wordcloud_data['example']) {
                    $link = URL::route('domain-feed-custom', array('hashtag', $this->query_string, $start_date, $end_date, 'hashtag='.urlencode($word->word)));
                    $cta  = "Click to see them!";
                } elseif ($wordcloud_data['example']) {
                    $link = "#";
                    $cta = "";
                } else {
                    $link = "#";
                    $cta = "Analyze pins by #hashtag on the enterprise plan.";
                }


                $wordcloud_data[] = array(
                    'text'   => $word->word,
                    'weight' => $word->word_count,
                    'link'   => $link,
                    'html'   => array(
                        'class'          => '',
                        'data-toggle'    => 'popover',
                        'data-container' => 'body',
                        'data-placement' => 'left',
                        'data-content'   => $word->word_count . ' Pins with the <strong>' . $word->word . '</strong> hashtag. <br>' . $cta,
                    )
                );
            }
        }

        return $wordcloud_data;
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
        $this->layout_defaults['page']          = 'domain';
        $this->layout_defaults['top_nav_title'] = "Monitor Your Domain ($this->query_string)";
        $this->layout->body_id                  = $page;
        $this->layout->head                    .= View::make('analytics.components.head.domain');
        $this->layout->top_navigation           = $this->buildTopNavigation();
        $this->layout->side_navigation          = $this->buildSideNavigation($page);
        $this->layout->pre_body_close          .= View::make('analytics.components.pre_body_close.domain', array('page' => $page));
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
        return View::make('analytics.pages.domain.nav', array(
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
    protected function buildTopicBar($type = 'latest')
    {
        $keywords = $this->accountKeywords();
        $domains  = $this->accountDomains();

        return View::make('analytics.pages.domain.topicbar', array(
            'customer'      => $this->logged_in_customer,
            'type'          => $type,
            'query_data'    => $this->prepareTopicBarData(explode('+', $this->query_string)),
            'tags'          => $this->tags(),
            'domains'       => $domains,
            'domain_count'  => count($domains),
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

            if($this->topicType($item) == "domain"){
                $args[] = array(
                    'text'  => $item,
                    'value' => $item,
                    'type'  => $this->topicType($item),
                );
            }
        }
        
        return $args;
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
        $vars = $this->baseLegacyVariables();
        extract($vars);

        $route = Route::currentRouteName();
        if($route == "domain-feed"){
            $type      = Route::input('type');
            $page_name = $this->prettyReportName($route, $type);
            $link      = URL::route($route, array($type, $cust_domain));
        } else {
            $page_name = $this->prettyReportName($route);
            $link      = URL::route($route, array($cust_domain));
        }

        if (empty($cust_domain)) {
            $this->layout->main_content = View::make('analytics.pages.domain.add_domain_form');

        } else {
            if ($route == "domain-feed") {
                return Redirect::route($route, array($type, $cust_domain));
            } else {
                return Redirect::route($route, array($cust_domain));
            }
        }

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
     * Determines the max data age allowed for the active user's plan.
     *
     * @return int
     */
    protected function maxDataAge()
    {
        $customer     = $this->logged_in_customer;
        $max_data_age = $customer->maxAllowed('domain_data_age_max');

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
     * Builds a collection of pins.
     *
     * @param Object $data
     *
     * @param bool   $comment_data
     *
     * @return Pins
     */
    protected function buildPinCollection($data, $comment_data = false)
    {
        $pins = new Pins();
        foreach ($data as $item) {
            $pin = new Pin();
            $pin->loadDBData($item);
            $pin->pinner($item);
            $pins->add($pin);
        }

        if($comment_data){
            $pins->comments();
        }

        return $pins;
    }



    /**
     * Returns a pretty name for a given report type
     */
    public function prettyReportName($route, $type = false)
    {
        switch($route){
            case "domain-insights":
            case "domain-insights-default":
                $name = "Insights";
                break;

            case "domain-trending-images-default":
            case "domain-trending-images-range":
            case "domain-trending-images-custom-date":
            case "domain-insights-default":
                $name = "Most Pinned Images";
                break;

            case "domain-trending-images-default":
            case "domain-trending-images-range":
            case "domain-trending-images-custom-date":
            case "domain-insights-default":
                $name = "Most Pinned Images";
                break;
        }

        if (!empty($type)) {
            switch($type){
                case "latest":
                    $name = "Latest Pins";
                    break;

                case "most-repinned":
                    $name = "Most Repinned";
                    break;

                case "most-liked":
                    $name = "Most Liked";
                    break;

                case "most-commented":
                    $name = "Comments & Conversations";
                    break;

                case "most-clicked":
                    $name = "Most Clicked Pins";
                    break;

                case "most-visits":
                    $name = "Most Clicked Pins";
                    break;

                case "most-pageviews":
                    $name = "Most Valuable Pins";
                    break;

                case "most-transactions":
                    $name = "Top Converting Pins";
                    break;

                case "most-revenue":
                    $name = "Top Revenue-generating Pins";
                    break;

                case "trending-images":
                    $name = "Most Pinned Images";
                    break;

                case "insights":
                    $name = "Insights";
                    break;

                default:
                    $name = "Domain Pins";
                    break;
            }
        }

        return $name;
    }









/*
|--------------------------------------------------------------------------
| TODO: LEGACY KEYWORD METHODS - TO BE RE-USED FOR DOMAIN LISTENING
|--------------------------------------------------------------------------
*/








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
     * Builds an array of wordcloud data for a given date and set of keywords.
     *
     * @route /domain/wordcloud
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
                    'weight' => $word->word_count
//                    'link'   => 'javascript:void(0)',
//                    'html'   => array(
//                        'class'          => 'wordcloud-word',
//                        'data-word'      => $word->word,
//                        'data-toggle'    => 'popover',
//                        'data-container' => 'body',
//                        'data-placement' => 'left',
//                        'data-content'   => 'Click to add <strong>' . $word->word . '</strong> to your keywords.',
//                    )
                );
            }
        }

        return $wordcloud_data;
    }
}
