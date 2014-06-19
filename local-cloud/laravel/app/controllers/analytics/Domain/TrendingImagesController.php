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
class TrendingImagesController extends BaseController
{


    /**
     * Show default view of trending images feed:
     *      main account domain as query
     *      "week" as date range
     *
     * @author  Alex
     *
     * @return $this->trendingImagesFeed()
     *
     */
    public function trendingImagesFeedDefault()
    {
        $vars = $this->baseLegacyVariables();
        extract($vars);

        if ($customer->hasFeature('domain_custom_date_range')) {
            $start_date = date("m-d-Y", strtotime("-1 week", flat_date('day')));
            $end_date   = date("m-d-Y", flat_date('day'));

            return $this->trendingImagesFeedCustomDate($cust_domain, $start_date, $end_date);
        } else {
            return $this->trendingImagesFeedDateRange($cust_domain, "week");
        }
    }

    /**
     * Displays trending images feed for a domain.
     *
     * @route    /domain/trending-images
     *
     * @param $query
     * @param $range
     *
     * @return $this->trendingImagesFeedCustomDate()
     *
     * TODO: THIS IS JUST COPIED OVER TO MAKE IT WORK - NEEDS REFACTORING - BIG TIME.
     */
    public function trendingImagesFeedDateRange($query, $range)
    {
        Log::setLog(__FILE__,'Reporting','Domain_Trending_Images_Range');

        $vars = $this->baseLegacyVariables();
        extract($vars);

        /*
         * Check if user has trending images enabled, and if not, send them to the upgrade page.
         */
        if (!$customer->hasFeature('domain_trending_images')) {
            $query_string = http_build_query(
                array(
                     'ref'  => 'website',
                     'plan' => $customer->plan()->plan_id
                )
            );
            header('location:/upgrade?'.$query_string);
            exit;
        }

        $secure = $this->secureQuery();
        if (!$secure || $secure instanceof RedirectResponse) {
            return $secure;
        }

        $this->buildLayout("domain-trending-images");

        /*
         * TODO: We'll need to find a way to handle this long-term, for domains with a
         * TODO: high volume of pins (probably use cached summary tables?)
         */
        if (!$range || $range == "") {
            $range = "week";
        }

        if ($range == "day" || $range == "week" || $range == "month") {
            //do nothing
        } else {
            $range = "week";
        }

        $domain = $this->topics['domains'][0];

        $day_pill = "";
        $week_pill = "";
        $month_pill = "";

        $day_link = "href='/domain/trending-images/$domain/day'";
        $week_link = "href='/domain/trending-images/$domain/week'";
        $month_link = "href='/domain/trending-images/$domain/month'";

        $day_limit = UserAccountsDomain::_customDateRangeLimit($domain);

        if ($day_limit < 31) {
            $month_link = "";
            $month_pill = "inactive";

            if ($range == "month" || $range == "week") {
                if ($day_limit < 7) {
                    $week_link = "";
                    $week_pill = "inactive";
                    $range = "day";
                } else {
                    $range = "week";
                }
            }
        }

        if ($range == "day") {
            $last_date = strtotime("-1 day", flat_date('day'));
            $day_class = "active";
        } else if ($range == "week") {
            $last_date = strtotime("-1 week", flat_date('day'));
            $week_class = "active";
        } else if ($range == "month") {
            $last_date = strtotime("-30 days", flat_date('day'));
            $month_class = "active";
        }

        $current_date          = flat_date('day');
        $cache_timestamp       = $current_date;
        $cache_timestamp_print = date("m-d-Y", $cache_timestamp);
        $last_date_print       = date("m-d-Y", $last_date);
        $current_date_print    = date("m-d-Y", $current_date);
        $day_range             = ($current_date - $last_date)/60/60/24;

        $this->layout->pre_body_close .= View::make(
            'analytics.components.pre_body_close.wordcloud',
            array(
                'wordcloud_return' => $this->buildTrendingImagesWordcloud($last_date, $current_date)
            )
        );

        $route               = Route::currentRouteName();
        $type                = "trending-images";
        $pretty_report_name  = $this->prettyReportName($route, $type);

        /*
         * Return main content of trending pins page
         */
        $pins_vars = array_merge(array(
            'customer'              => $this->logged_in_customer,
            'current_date'          => $current_date,
            'last_date'             => $last_date,
            'plan'                  => $this->logged_in_customer->plan(),
            'topic_bar'             => $this->buildTopicBar('trending-images'),
            'type'                  => "trending-images",
            'query_string'          => $this->query_string,
            'day_link'              => $day_link,
            'day_class'             => $day_class,
            'week_link'             => $week_link,
            'week_class'            => $week_class,
            'month_link'            => $month_link,
            'month_class'           => $month_class,
            'range'                 => $range,
            'day_limit'             => $day_limit,
            'day_range'             => $day_range,
            'cache_timestamp_print' => $cache_timestamp_print,
            'current_date_print'    => $current_date_print,
            'last_date_print'       => $last_date_print,
            'this_domain'           => $domain,
            'pretty_report_name'    => $pretty_report_name,

            'nav_domain_benchmarks_class' => $nav_domain_benchmarks_class, // from baseLegacyVariables
            'has_competitors'             => $has_competitors, // from baseLegacyVariables
            'nav_competitors_enabled'     => $nav_competitors_enabled, // from baseLegacyVariables
            'nav_link_traffic'            => $nav_link_traffic,
            'nav_traffic_class'           => $nav_traffic_class // from baseLegacyVariables
        ),$this->baseLegacyVariables());

        $pins_vars = array_merge($vars, $pins_vars);

        $this->layout->sub_navigation = View::make('analytics.components.sub_nav.domain', $pins_vars);

        $this->layout->main_content = View::make('analytics.pages.domain.trending_images', $pins_vars);

        $this->logged_in_customer->recordEvent(
            UserHistory::VIEW_REPORT,
            $parameters = array(
                'report' => 'trending-images',
                'view'   => "$range",
            )
        );
    }

    /**
     * Displays trending images feed for a domain.
     *
     * @route    /domain/trending-images/{query}/{start_date}/{end_date}
     *
     * @param $query
     * @param $start_date
     * @param $end_date
     *
     * @return void
     *
     * TODO: THIS IS JUST COPIED OVER TO MAKE IT WORK - NEEDS REFACTORING - BIG TIME.
     */
    public function trendingImagesFeedCustomDate($query, $start_date, $end_date)
    {
        Log::setLog(__FILE__,'Reporting','Domain_Trending_Images_Custom_Date');

        $vars = $this->baseLegacyVariables();
        extract($vars);

        /*
         * Check if user has custom date range enabled, and if not, default them to the
         * usual weekly view.
         */
        if(!$customer->hasFeature('domain_custom_date_range')){
            return $this->trendingImagesFeedDateRange($cust_domain, "week");
        }

        $secure = $this->secureQuery();
        if (!$secure || $secure instanceof RedirectResponse) {
            return $secure;
        }

        $this->buildLayout("domain-trending-images");

        $last_date    = getTimestampFromDate($start_date);
        $current_date = getTimestampFromDate($end_date);

        /*
        * Determine the maximum date range we can show without slowing things down for real-time
        * results
        */
        $domain = $this->topics['domains'][0];
        $day_limit = UserAccountsDomain::_customDateRangeLimit($domain);
        $limit_date = strtotime("-$day_limit days", $current_date);

        if($last_date < $limit_date){
            $last_date = $limit_date;
        }

        $cache_timestamp       = $current_date;
        $cache_timestamp_print = date("m-d-Y", $cache_timestamp);
        $last_date_print       = date("m-d-Y", $last_date);
        $current_date_print    = date("m-d-Y", $current_date);
        $day_range             = ($current_date - $last_date)/60/60/24;

        $this->layout->pre_body_close .= View::make(
            'analytics.components.pre_body_close.wordcloud',
            array(
                'wordcloud_return' => $this->buildTrendingImagesWordcloud($last_date, $current_date)
            )
        );

        $route              = Route::currentRouteName();
        $type               = "trending-images";
        $pretty_report_name = $this->prettyReportName($route, $type);

        /*
         * Insert variables into the profile_pins view for the main_content area
         */
        $pins_vars = array(
            'customer'                    => $this->logged_in_customer,
            'current_date'                => $current_date,
            'last_date'                   => $last_date,
            'plan'                        => $this->logged_in_customer->plan(),
            'topic_bar'                   => $this->buildTopicBar('trending-images'),
            'type'                        => "trending-images",
            'query_string'                => $this->query_string,
            'cache_timestamp_print'       => $cache_timestamp_print,
            'current_date_print'          => $current_date_print,
            'last_date_print'             => $last_date_print,
            'day_limit'                   => $day_limit,
            'day_range'                   => $day_range,
            'this_domain'                 => $domain,
            'pretty_report_name'          => $pretty_report_name,

            'nav_domain_benchmarks_class' => $nav_domain_benchmarks_class, // from baseLegacyVariables
            'has_competitors'             => $has_competitors, // from baseLegacyVariables
            'nav_competitors_enabled'     => $nav_competitors_enabled, // from baseLegacyVariables
            'nav_link_traffic'            => $nav_link_traffic,
            'nav_traffic_class'           => $nav_traffic_class // from baseLegacyVariables
        );

        $pins_vars = array_merge($vars, $pins_vars);

        $this->layout->sub_navigation = View::make('analytics.components.sub_nav.domain', $pins_vars);

        $this->layout->main_content = View::make('analytics.pages.domain.trending_images', $pins_vars);

        $this->logged_in_customer->recordEvent(
            UserHistory::VIEW_REPORT,
            $parameters = array(
                'report' => 'trending-images',
                'view'   => 'custom date',
            )
        );
    }

    /**
     * Builds a wordcloud for a set of trending images
     *
     * @author Alex
     *
     * @param $last_date
     * @param $current_date
     *
     * @return array
     */
    public function buildTrendingImagesWordcloud($last_date, $current_date)
    {
        $this->secureQuery();

        $domains     = array_get($this->topics, 'domains', array());
        $domains_csv = '"' . implode('", "', $domains) . '"';

        if ($current_date == flat_date('day')) {
            $end_date_clause = "";
        } else {
            $end_date_clause = "AND created_at < $current_date";
        }

        $query = "SELECT description from data_pins_new
                        where domain in ($domains_csv)
                        and created_at > $last_date
                        $end_date_clause
                        order by created_at desc
                        limit 5000;";

        $descriptions = DB::select($query);

        /*
         * concatenate all pin descriptions together
         */
        foreach($descriptions as $desc){

            if(!isset($wordcloud)){
                $wordcloud = preg_replace('/[^A-Za-z0-9 ]/',' ', strtolower($desc->description));
            } else {
                $wordcloud = $wordcloud . ' ' . preg_replace('/[^A-Za-z0-9 ]/',' ', strtolower($desc->description));
            }
        }

        $word_remove = Config::get('keywords.ignored_words');

        $word = array();
        $words = explode(' ', $wordcloud);
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


        $wordcloud_js_array =
            "var word_array = [";

        $count = 0;
        foreach($word as $w){
            if($count < 60){
                if($count==0){
                    if($w['word_count'] > 1){
                        $wordcloud_js_array .= "
                                    {text: '" . $w['word'] . "', weight: " . $w['word_count'] . "}";
                    }
                } else {
                    if($w['word_count'] > 1){
                        $wordcloud_js_array .= "
                                    ,{text: '" . $w['word'] . "', weight: " . $w['word_count'] . "}";
                    }
                }
                $count++;
            }
        }

        $wordcloud_js_array .= "
                    ];";

        $wordcloud_js_execute =
            "$(function() {
                $('#wordcloud').jQCloud(word_array);
            });";

        /*
         * Return js to insert into head tag
         */
        $wordcloud_return = "
            <script>
                " . $wordcloud_js_array . $wordcloud_js_execute . "
            </script>";


        return $wordcloud_return;
    }
}
