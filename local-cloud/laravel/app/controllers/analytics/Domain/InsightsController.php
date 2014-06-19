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
class InsightsController extends BaseController
{

    /*
    |--------------------------------------------------------------------------
    | INSIGHTS Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Displays insights for a set of topics.
     *
     * @route /domain/insights
     *
     */
    public function insightsDefault()
    {
        $vars = $this->baseLegacyVariables();
        extract($vars);

        $secure = $this->secureQuery();
        if (!$secure || $secure instanceof RedirectResponse) {
            return $secure;
        }

        $start_date = date("m-d-Y", strtotime("-1 week", flat_date('day')));
        $end_date   = date("m-d-Y", flat_date('day'));

        return $this->insights($this->query_string, $start_date, $end_date);
    }

    /**
     * Displays insights for a set of topics.
     *
     * @route /domain/insights
     *
     * @param $query
     * @param $start_date
     * @param $end_date
     *
     * @return void
     */
    public function insights($query, $start_date, $end_date)
    {
        Log::setLog(__FILE__,'Reporting','Domain_Insights');

        $vars = $this->baseLegacyVariables();
        extract($vars);

        $secure = $this->secureQuery();
        if (!$secure || $secure instanceof RedirectResponse) {
            return $secure;
        }

        $this->buildLayout('domain-insights');

//        $keywords = array_get($this->topics, 'keywords');
        $domains  = array_get($this->topics, 'domains');

        $daily_counts = array();
//        $this->buildKeywordsDailyCounts($daily_counts);
        $metric_extras = $this->buildDomainsDailyCounts($daily_counts);

        // Get the last time that data was pulled.
//        if (!empty($keywords)) {
//            $last_keyword    = $keywords[count($keywords) - 1];
//            $status_keyword  = StatusKeyword::find($last_keyword);
//            $cache_timestamp = $status_keyword ? $status_keyword->last_calced : 0;
//        } else

        if (!empty($domains)) {
            $last_domain     = $domains[count($domains) - 1];
            $status_domain   = StatusDomain::find($last_domain);
            $cache_timestamp = $status_domain ? $status_domain->last_calced : 0;
        }

        /*
         * Ensure customer doesn't see more data than they are allowed
         */

        $last_date = getTimestampFromDate($start_date);
        $current_date = getTimestampFromDate($end_date);

        $max_data_age = $this->maxDataAge();

        if ($max_data_age > $last_date) {
            $last_date = $max_data_age;
        }

        $day_spread = ($current_date - $last_date)/60/60/24;


        /*
         * Now we'll set the closest periodic date range to the range chosen.
         */
        if($current_date == flat_date('day')){
            if ($day_spread < 8) {
                $day_range = 7;
            } else if ($day_spread < 15) {
                $day_range = 14;
            } else if ($day_spread < 32) {
                $day_range = 30;
            } else {
                if ($customer->hasFeature('domain_history_alltime')) {
                    $day_range = 0;
                } else {
                    $day_range = 30;
                }
            }
        } else {
            $day_range = 7;
        }

        $cache_timestamp_print = date("m-d-Y", $cache_timestamp);
        $last_date_print       = date("m-d-Y", $last_date);
        $current_date_print    = date("m-d-Y", $current_date);

//        $selected_keywords = ($keywords == $this->accountKeywords()) ? array('all-keywords') : $keywords;

        $topics             = array_slice(array_keys($domains), 0, 5, true);
        $trending_topics    = Topic::trendingImages($domains, $day_range);

        $plan = $this->logged_in_customer->plan();

        $top_pinners_report_popover = '';
        if ($customer->maxAllowed('domain_insights_toppinners') < 25) {
            $top_pinners_report_popover = createPopover('#js-top-pinners-full-report', 'click', 'top');
        }

        $trending_images_report_popover = "";
        if (!$customer->hasFeature('domain_trending_images')) {
            $trending_images_report_popover = createPopover('#domain-insights-trending-images-cta', 'click', 'top');
        }

        $show_impressions_chart = "";
        if (!$customer->hasFeature('domain_impressions_history')) {
            $show_impressions_chart = View::make('analytics.pages.domain.hide_impressions');
        }

        $wordcloud_data = $this->buildDomainsWordcloudSnapshot($start_date, $end_date);
        if ($example_wordcloud = array_get($wordcloud_data, 'example', false)) {
            unset($wordcloud_data['example']);
        }

        $insights_vars = array_merge(array(
            'plan'                           => $plan,
            'type'                           => 'insights',
            'query_args'                     => $this->query_args,
            'daily_counts'                   => $daily_counts,
            'metric_extras'                  => $metric_extras,
            'new_curr_chart_date'            => $current_date * 1000,
            'new_last_chart_date'            => max($last_date * 1000, strtotime("-1 year", flat_date('day')) * 1000),
            'day_range'                      => $day_range,
            'day_spread'                     => $day_spread,
            'max_data_age'                   => $max_data_age,
            'current_date'                   => $current_date,
            'last_date'                      => $last_date,
            'trending_topics'                => array_slice($trending_topics, 0, 3, true),
            'influencers'                    => $this->buildTopInfluencers($day_range),
            'wordcloud_data'                 => json_encode($wordcloud_data),
            'example_wordcloud'              => $example_wordcloud,
            'customer'                       => $this->logged_in_customer,
            'topic_bar'                      => $this->buildTopicBar("insights"),
            'query_string'                   => $this->query_string,
            'cache_timestamp_print'          => $cache_timestamp_print,
            'current_date_print'             => $current_date_print,
            'last_date_print'                => $last_date_print,
            'top_pinners_report_popover'     => $top_pinners_report_popover,
            'trending_images_report_popover' => $trending_images_report_popover,
            'show_impressions_chart'         => $show_impressions_chart,

            'nav_domain_benchmarks_class' => $nav_domain_benchmarks_class, // from baseLegacyVariables
            'has_competitors'             => $has_competitors, // from baseLegacyVariables
            'nav_competitors_enabled'     => $nav_competitors_enabled, // from baseLegacyVariables
            'nav_link_traffic'            => $nav_link_traffic,
            'nav_traffic_class'           => $nav_traffic_class // from baseLegacyVariables
        ),$this->baseLegacyVariables());

//        if (empty($domains)) {
//            $insights_vars['sources'] = $this->buildTopSources($keywords, $day_range);
//        }

        $this->layout->sub_navigation = View::make('analytics.components.sub_nav.domain', $insights_vars);

        $this->layout->main_content = View::make('analytics.pages.domain.insights', $insights_vars);

        $this->logged_in_customer->recordEvent(
            UserHistory::VIEW_REPORT,
            array(
               'report' => 'domain-insights',
               'view'   => $day_spread
            )
        );
    }

    /**
     * Builds an HTML view of top pinners for a given date and query.
     *
     * @route /domain/top-pinners
     *
     * @return string
     */
    public function buildTopPinnersSnapshot()
    {
        $this->secureQuery();

//        $keywords = array_get($this->topics, 'keywords', array());
        $domains  = array_get($this->topics, 'domains', array());

        $wheres = array();

//        if (!empty($keywords)) {
//            $keywords_csv = '"' . implode('", "', $keywords) . '"';
//            $wheres[]     = "keyword IN ($keywords_csv)";
//        }

        /*
         * We use the domain IN (CSV) methodology here for when this might be available across
         * multiple domains.  However, this method should only receive ONE domain as a parameter
         * from the Ajax call, because a user will be clicking on ONE of the lines in a chart
         * and passing the domain of the line being clicked on.
         */
        if (!empty($domains)) {
            $domains_csv = '"' . implode('", "', $domains) . '"';
            $wheres[]    = "domain IN ($domains_csv)";
        }

        $wheres[] = 'created_at > ?';
        $wheres[] = 'created_at < ?';

        $where_clause = 'WHERE ' . implode(' AND ', $wheres);

        $date = Input::get('date', flat_date());

        if(!empty($keywords)){
            $query = "SELECT subq.pinner_id, subq.count, (subq.count * prof.follower_count) as reach,
                      prof.username, prof.gender, prof.first_name, prof.last_name, prof.image,
                      prof.domain_url, prof.website_url, prof.facebook_url, prof.twitter_url,
                      prof.location, prof.pin_count, prof.follower_count
                      FROM
                          (SELECT pinner_id, count(pin_id) as count
                             FROM map_pins_keywords
                             $where_clause
                             GROUP BY pinner_id
                             LIMIT 25) AS subq
                      LEFT JOIN data_profiles_new AS prof
                      ON subq.pinner_id = prof.user_id
                      ORDER BY reach DESC";
        } else {
            $query = "SELECT subq.user_id, subq.count, (subq.count * prof.follower_count) as reach,
                      prof.username, prof.gender, prof.first_name, prof.last_name, prof.image,
                      prof.domain_url, prof.website_url, prof.facebook_url, prof.twitter_url,
                      prof.location, prof.pin_count, prof.follower_count
                      FROM
                          (SELECT user_id, count(pin_id) as count
                             FROM data_pins_new
                             $where_clause
                             GROUP BY user_id
                             LIMIT 25) AS subq
                      LEFT JOIN data_profiles_new AS prof
                      ON subq.user_id = prof.user_id
                      ORDER BY reach DESC";
        }

        $pinners = DB::select(
                     $query,
                         array($date, strtotime('+1 day', $date))
        );

        $pinners_html = '';
        foreach ($pinners as $pinner) {
            $pinners_html .= View::make('analytics.pages.domain.profile', array('profile' => $pinner, 'component' => 'Top Pinners Modal'));
        }

        return $pinners_html;
    }

    /**
     * Builds an array, by reference, of daily counts based on a set of domains
     * and sums them across is_repin=0 and is_repin=1 for each day.
     *
     * @param array &$daily_counts
     *
     * @return array $metric_extras
     */
    protected function buildDomainsDailyCounts(&$daily_counts)
    {
        $vars = $this->baseLegacyVariables();
        extract($vars);

        $domains = array_get($this->topics, 'domains');
        if (empty($domains)) {
            return;
        }

        $domains_csv = '"' . implode('", "', $domains) . '"';

        /*
         * Update today's daily counts if they are out of date
         */
        StatusDomain::updateTodaysDailyCounts($domains);

        /*
         * Grab daily counts for the requested set of domains
         */
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

            /*
             * Check to see if data is from within the last month so we can take averages
             * and standard deviations of this data later and identify spikes in activity
             * against it.
             */
            if ($data->date >= strtotime("-1 month", flat_date('day'))) {
                $pin_counts[$data->domain][]    = $data->pin_count;
                $pinner_counts[$data->domain][] = $data->pinner_count;
                $reach_counts[$data->domain][]  = $data->reach;
            }

            /**
             * Get the max value of each daily metric
             */
            if (!isset($pin_max[$data->domain])) {
                $pin_max[$data->domain] = $data->pin_count;
            } else if ($data->pin_count > $pin_max[$data->domain]) {
                $pin_max[$data->domain] = $data->pin_count;
            }

            if (!isset($pinner_max[$data->domain])) {
                $pinner_max[$data->domain] = $data->pinner_count;
            } else if ($data->pinner_count > $pinner_max[$data->domain]) {
                $pinner_max[$data->domain] = $data->pinner_count;
            }

            if (!isset($reach_max[$data->domain])) {
                $reach_max[$data->domain] = $data->reach;
            } else if ($data->reach > $reach_max[$data->domain]) {
                $reach_max[$data->domain] = $data->reach;
            }

            /**
             * Set the value for our prediction for today's count by end of day.
             * This is determined by finding previous values from the same day of the week
             * and averaging them in the view.
             */
            if (date("w", time()) == date("w", $data->date) && $data->date >= strtotime("-3 month", flat_date('day'))) {
                $pin_prediction_counts[$data->domain][]    = $data->pin_count;
                $pinner_prediction_counts[$data->domain][] = $data->pinner_count;
                $reach_prediction_counts[$data->domain][]  = $data->reach;
            }
        }

        /**
         * Check for Google Analytics data from the same domain
         */
        $analytics_match = false;
        $analytics_domain = false;
        $metric_extras['show_analytics'] = false;
        if ($analytic = StatusTraffic::findByAccountId($this->active_user_account->account_id)) {

            $analytic->websiteUrl = UserAccountsDomain::cleanDomainInput($analytic->websiteUrl);
            /**
             * Cycle through all of the user's domains to see if any match the domain of their
             * google analytics integration
             */
            foreach ($domains as $domain) {

                if (strpos($analytic->websiteUrl, $domain)
                    || strpos ($domain, $analytic->websiteUrl)
                    || $domain == $analytic->websiteUrl
                ) {
                    $analytics_match = true;
                    $analytics_domain = $domain;
                    $metric_extras['show_analytics'] = true;

                    $traffic_update_frequency = $customer->maxAllowed('google_analytics_update_frequency');
                    $analytic->queueTrafficDataUpdate($traffic_update_frequency);

                    /**
                     * Pull Daily Traffic data to go into the chart
                     */
                    $daily_traffic_data = DB::select(
                                            "SELECT '$domain' as domain
                                            , date
                                            , visits
                                            , pageviews
                                            , transactions
                                            , revenue
                                            FROM data_traffic
                                            WHERE traffic_id = :traffic_id",
                                                array(":traffic_id" => $analytic->traffic_id)
                    );

                    foreach ($daily_traffic_data as $data) {
                        if ($data->date < $max_data_age) {
                            continue;
                        }

                        if (isset($daily_counts[$data->date][$data->domain])) {
                            $daily_counts[$data->date][$data->domain]['visits']       += $data->visits;
                            $daily_counts[$data->date][$data->domain]['pageviews']    += $data->pageviews;
                            $daily_counts[$data->date][$data->domain]['transactions'] += $data->transactions;
                            $daily_counts[$data->date][$data->domain]['revenue']      += $data->revenue;
                        } else {
                            $daily_counts[$data->date][$data->domain]['visits']       = $data->visits;
                            $daily_counts[$data->date][$data->domain]['pageviews']    = $data->pageviews;
                            $daily_counts[$data->date][$data->domain]['transactions'] = $data->transactions;
                            $daily_counts[$data->date][$data->domain]['revenue']      = $data->revenue;
                        }

                        /*
                         * Check to see if data is from within the last month so we can take averages
                         * and standard deviations of this data later and identify spikes in activity
                         * against it.
                         */
                        if ($data->date >= strtotime("-1 month", flat_date('day'))) {
                            $visits[$data->domain][]       = $data->visits;
                            $pageviews[$data->domain][]    = $data->pageviews;
                            $transactions[$data->domain][] = $data->transactions;
                            $revenue[$data->domain][]      = $data->revenue;
                        }

                        /**
                         * Get the max value of each daily traffic metric
                         */
                        if (!isset($visit_max[$data->domain])) {
                            $visit_max[$data->domain] = $data->visits;
                        } else if ($data->visits > $visit_max[$data->domain]) {
                            $visit_max[$data->domain] = $data->visits;
                        }

                        if (!isset($pageview_max[$data->domain])) {
                            $pageview_max[$data->domain] = $data->pageviews;
                        } else if ($data->pageviews > $pageview_max[$data->domain]) {
                            $pageview_max[$data->domain] = $data->pageviews;
                        }

                        if (!isset($transaction_max[$data->domain])) {
                            $transaction_max[$data->domain] = $data->transactions;
                        } else if ($data->transactions > $transaction_max[$data->domain]) {
                            $transaction_max[$data->domain] = $data->transactions;
                        }

                        if (!isset($revenue_max[$data->domain])) {
                            $revenue_max[$data->domain] = $data->revenue;
                        } else if ($data->revenue > $revenue_max[$data->domain]) {
                            $revenue_max[$data->domain] = $data->revenue;
                        }

                        /**
                         * Set the value for our prediction for today's count by end of day.
                         * This is determined by finding previous values from the same day of the week
                         * and averaging them in the view.
                         */
                        if (date("w", time()) == date("w", $data->date) && $data->date >= strtotime("-3 month", flat_date('day'))) {
                            $visit_prediction_counts[$data->domain][]    = $data->visits;
                            $pageview_prediction_counts[$data->domain][] = $data->pageviews;
                            $transaction_prediction_counts[$data->domain][]  = $data->transactions;
                            $revenue_prediction_counts[$data->domain][]  = $data->revenue;
                        }
                    }

                    /**
                     * Now that we've found the domain match, we don't need to cycle through
                     * any others
                     */
                    continue;
                }
            }
        }

        /**
         * TODO: Please Read!
         *
         * $metric_counts :: multi-dimensional array holding arrays of each metric's daily values
         * --------------
         *
         * $metric_extras :: multi-dimensional array of extra metadata which is actually
         * --------------    returned by this method
         *
         */
        $metric_counts['pin_count']         = $pin_counts;
        $metric_counts['pinner_count']      = $pinner_counts;
        $metric_counts['reach']             = $reach_counts;

        $metric_counts['pin_prediction']    = $pin_prediction_counts;
        $metric_counts['pinner_prediction'] = $pinner_prediction_counts;
        $metric_counts['reach_prediction']  = $reach_prediction_counts;

        /**
         * If we found a google analytics match, we'll append that data here as well
         */
        if ($analytics_match) {
            $metric_counts['visits']       = $visits;
            $metric_counts['pageviews']    = $pageviews;
            $metric_counts['transactions'] = $transactions;
            $metric_counts['revenue']      = $revenue;

            $metric_counts['visit_prediction']       = $visit_prediction_counts;
            $metric_counts['pageview_prediction']    = $pageview_prediction_counts;
            $metric_counts['transaction_prediction'] = $transaction_prediction_counts;
            $metric_counts['revenue_prediction']     = $revenue_prediction_counts;
        }


        /*
         * Calculate averages and standard deviations of each metric
         */
        foreach ($domains as $domain) {
            $metric_extras[$domain]['pin_count']['mean']               = array_sum($metric_counts['pin_count'][$domain])/count($metric_counts['pin_count'][$domain]);
            $metric_extras[$domain]['pin_count']['standard_deviation'] = stats_standard_deviation($metric_counts['pin_count'][$domain], true);
            $metric_extras[$domain]['pin_count']['threshold']          = $metric_extras[$domain]['pin_count']['mean'] + ($metric_extras[$domain]['pin_count']['standard_deviation']);
            $metric_extras[$domain]['pin_count']['max']                = $pin_max[$domain];

            $metric_extras[$domain]['pinner_count']['mean']               = array_sum($metric_counts['pinner_count'][$domain])/count($metric_counts['pinner_count'][$domain]);
            $metric_extras[$domain]['pinner_count']['standard_deviation'] = stats_standard_deviation($metric_counts['pinner_count'][$domain], true);
            $metric_extras[$domain]['pinner_count']['threshold']          = $metric_extras[$domain]['pinner_count']['mean'] + ($metric_extras[$domain]['pinner_count']['standard_deviation']);
            $metric_extras[$domain]['pinner_count']['max']                = $pinner_max[$domain];

            $metric_extras[$domain]['reach']['mean']               = array_sum($metric_counts['reach'][$domain])/count($metric_counts['reach'][$domain]);
            $metric_extras[$domain]['reach']['standard_deviation'] = stats_standard_deviation($metric_counts['reach'][$domain], true);
            $metric_extras[$domain]['reach']['threshold']          = $metric_extras[$domain]['reach']['mean'] + ($metric_extras[$domain]['reach']['standard_deviation']);
            $metric_extras[$domain]['reach']['max']                = $reach_max[$domain];

            $metric_extras[$domain]['pin_prediction']    = round(array_sum($metric_counts['pin_prediction'][$domain])/count($metric_counts['pin_prediction'][$domain]));
            $metric_extras[$domain]['pinner_prediction'] = round(array_sum($metric_counts['pinner_prediction'][$domain])/count($metric_counts['pinner_prediction'][$domain]));
            $metric_extras[$domain]['reach_prediction']  = round(array_sum($metric_counts['reach_prediction'][$domain])/count($metric_counts['reach_prediction'][$domain]));

            /**
             * If this is the same domain as the one we've matched up with a google analytics account,
             * then we'll prep the data here
             */
            if ($domain == $analytics_domain) {
                $metric_extras[$domain]['visits']['mean']               = array_sum($metric_counts['visits'][$domain])/count($metric_counts['visits'][$domain]);
                $metric_extras[$domain]['visits']['standard_deviation'] = stats_standard_deviation($metric_counts['visits'][$domain], true);
                $metric_extras[$domain]['visits']['threshold']          = $metric_extras[$domain]['visits']['mean'] + ($metric_extras[$domain]['visits']['standard_deviation']);
                $metric_extras[$domain]['visits']['max']                = $visit_max[$domain];

                $metric_extras[$domain]['pageviews']['mean']               = array_sum($metric_counts['pageviews'][$domain])/count($metric_counts['pageviews'][$domain]);
                $metric_extras[$domain]['pageviews']['standard_deviation'] = stats_standard_deviation($metric_counts['pageviews'][$domain], true);
                $metric_extras[$domain]['pageviews']['threshold']          = $metric_extras[$domain]['pageviews']['mean'] + ($metric_extras[$domain]['pageviews']['standard_deviation']);
                $metric_extras[$domain]['pageviews']['max']                = $pageview_max[$domain];

                $metric_extras[$domain]['transactions']['mean']               = array_sum($metric_counts['transactions'][$domain])/count($metric_counts['transactions'][$domain]);
                $metric_extras[$domain]['transactions']['standard_deviation'] = stats_standard_deviation($metric_counts['transactions'][$domain], true);
                $metric_extras[$domain]['transactions']['threshold']          = $metric_extras[$domain]['transactions']['mean'] + ($metric_extras[$domain]['transactions']['standard_deviation']);
                $metric_extras[$domain]['transactions']['max']                = $transaction_max[$domain];

                $metric_extras[$domain]['revenue']['mean']               = array_sum($metric_counts['revenue'][$domain])/count($metric_counts['revenue'][$domain]);
                $metric_extras[$domain]['revenue']['standard_deviation'] = stats_standard_deviation($metric_counts['revenue'][$domain], true);
                $metric_extras[$domain]['revenue']['threshold']          = $metric_extras[$domain]['revenue']['mean'] + ($metric_extras[$domain]['revenue']['standard_deviation']);
                $metric_extras[$domain]['revenue']['max']                = $revenue_max[$domain];

                $metric_extras[$domain]['visit_prediction']  = round(array_sum($metric_counts['visit_prediction'][$domain])/count($metric_counts['visit_prediction'][$domain]));
                $metric_extras[$domain]['pageview_prediction']  = round(array_sum($metric_counts['pageview_prediction'][$domain])/count($metric_counts['pageview_prediction'][$domain]));
                $metric_extras[$domain]['transaction_prediction']  = round(array_sum($metric_counts['transaction_prediction'][$domain])/count($metric_counts['transaction_prediction'][$domain]));
                $metric_extras[$domain]['revenue_prediction']  = round(array_sum($metric_counts['revenue_prediction'][$domain])/count($metric_counts['revenue_prediction'][$domain]));
            }

            /*
             * Check to see if there's enough data for a prediction to be made
             */
            $metric_extras[$domain]['prediction'] = false;
            if (count($metric_counts['pin_count'][$domain]) > 7 && $metric_extras[$domain]['pin_count']['mean'] > 5) {
                $metric_extras[$domain]['prediction'] = true;
            }
        }

        return $metric_extras;
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

//        $keywords = array_get($this->topics, 'keywords');
//        if (!empty($keywords)) {
//            $keywords_influencers = UserAccountKeyword::topInfluencers($keywords, $period);
//            $top_influencers      = array_merge($top_influencers, $keywords_influencers);
//        }

        $domains = array_get($this->topics, 'domains');
        if (!empty($domains)) {
            $domains_influencers  = UserAccountsDomain::topInfluencers($domains, $period);
            $top_influencers      = array_merge($top_influencers, $domains_influencers);
        }

        foreach ($top_influencers as $influencer) {
//            if (!empty($influencer->keyword)) {
//                $topic          = $influencer->keyword;
//                $mentions_count = $influencer->keyword_mentions;
//            } else {
            $topic          = $influencer->domain;
            $mentions_count = $influencer->domain_mentions;
            $reach          = $influencer->reach;
//            }

            $influencer->topic          = $topic;
            $influencer->mentions_count = $mentions_count;
            $influencer->reach          = $reach;
        }

        // If combining data, sort the combined results.
        if (!empty($keywords) && !empty($domains)) {
            usort($top_influencers, function ($a, $b) {
                if ($a->reach < $b->reach) {
                    return 1;
                } else if ($a->reach == $b->reach) {
                    return 0;
                } else {
                    return -1;
                }
            });
        }

        return array_slice($top_influencers, 0, 25, true);
    }
}
