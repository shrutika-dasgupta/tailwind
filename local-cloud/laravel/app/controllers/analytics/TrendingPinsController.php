<?php namespace Analytics;

use View,
    Log;

use DatabaseInstance,
    UserHistory;

/**
 * Class TrendingPinsController
 *
 * @package Analytics
 */
class TrendingPinsController extends BaseController
{

   protected $layout = 'layouts.analytics';

    /**
     * Construct
     * @author  Will
     */
    public function __construct() {

        parent::__construct();

        Log::setLog(__FILE__,'Reporting','TrendingPins_Report');
    }

    /**
     * Returns javascript necessary to create wordcloud to insert into the @head_tag
     *
     * @author  Alex
     * @author  Will
     *
     * TODO: @Will should this just be a method in the UserAccountsDomain model?
     * TODO: @Will did I do the prepared statement correctly??
     */
    public function getWordCloudScript($cust_domain)
    {


    }

    /**
     * Shows the Trending Organic Pins Page
     *
     * @author  Alex
     * @author  Will
     */
    public function showTrendingPins($range)
    {


        $vars = $this->baseLegacyVariables();
        extract($vars);
        $this->layout_defaults['page'] = 'trending pins';
        $this->layout_defaults['top_nav_title'] = 'Trending Pins';
        $this->layout->top_navigation = $this->buildTopNavigation();

        $this->layout->side_navigation = $this->buildSideNavigation('trending_pins');
        $customer = $this->logged_in_customer;

        /*
         * Create Wordcloud javascript to insert into the <head>
         */
        //        $acc2 = "select description from data_pins_new where domain='$cust_domain' order by created_at desc limit 500";
//        $acc2_res = mysql_query($acc2,$conn) or die(mysql_error(). __LINE__);
//        while ($b = mysql_fetch_array($acc2_res)) {
//
//            if(!$wordcloud){
//                $wordcloud = preg_replace('/[^A-Za-z0-9 ]/',' ', strtolower($b['description']));
//            } else {
//                $wordcloud = $wordcloud . ' ' . preg_replace('/[^A-Za-z0-9 ]/',' ', strtolower($b['description']));
//            }
//        }

        $DBH = DatabaseInstance::DBO();


        if(!$range || $range == ""){
            $range = "week";
        }

        if($range == "day" || $range == "week" || $range == "month"){
            //do nothing
        } else {
            $range = "week";
        }

        $last_week = strtotime("-7 days", flat_date('day'));
        $last_month = strtotime("-30 days", flat_date('day'));

        $query = "SELECT domain, sum(pin_count) as pin_count
                  FROM cache_domain_daily_counts
                  WHERE domain = '$cust_domain'
                  AND date > $last_week
                  GROUP BY domain";
        $week_pin_count = $DBH->query($query)->fetch()->pin_count;


        $query = "SELECT domain, sum(pin_count) as pin_count
                  FROM cache_domain_daily_counts
                  WHERE domain = '$cust_domain'
                  AND date > $last_month
                  GROUP BY domain";
        $month_pin_count = $DBH->query($query)->fetch()->pin_count;


        $day_pill = "";
        $week_pill = "";
        $month_pill = "";

        $day_link = "href='/pins/domain/trending/day'";
        $week_link = "href='/pins/domain/trending/week'";
        $month_link = "href='/pins/domain/trending/month'";


        if ($month_pin_count > 20000) {
            $month_link = "";
            $month_pill = "inactive";

            if($range == "month"){
                if($week_pin_count > 20000) {
                    $range = "day";
                } else {
                    $range = "week";
                }
            }
        }

        if ($week_pin_count > 20000) {
            $month_link = "";
            $month_pill = "inactive";
            $week_link = "";
            $week_pill = "inactive";

            if($range == "month" || $range == "week"){
                $range = "day";
            }
        }

        if ($range == "day") {
            $time_period = strtotime("-1 day", time());
            $day_pill = "active";
        } else if ($range == "week") {
            $time_period = strtotime("-1 week", flat_date('day'));
            $week_pill = "active";
        } else if ($range == "month") {
            $time_period = strtotime("-1 month", flat_date('day'));
            $month_pill = "active";
        }


        if($cust_domain!=""){
            $STH = $DBH->prepare("
                    select description from data_pins_new
                    where domain = :domain
                    and created_at > $time_period
                    order by created_at desc
                    limit 1000;

                ");

            $STH->execute(array(
                               ':domain' => $cust_domain
                          ));

            $descriptions = $STH->fetchAll();

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


            /*
             * TODO: should this be abstracted into a global variable somewhere or something?
             */
            $word_remove = array('the','from','for','and','this','with','your','into','that','these','those','you','are');

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

            $this->layout->pre_body_close .= View::make('analytics.components.pre_body_close.wordcloud', array('wordcloud_return' => $wordcloud_return));
        }


        /*
      |--------------------------------------------------------------------------
      | Header Vars
      |--------------------------------------------------------------------------
      */
        
        /*
         * Return main content of trending pins page
         */
        /*
         * Insert variables into the profile_pins view for the main_content area
         */
        $pins_vars = array(
            'customer'                  => $this->logged_in_customer,
            'range'                     => $range,
            'month_pill'                => $month_pill,
            'month_link'                => $month_link,
            'week_pill'                 => $week_pill,
            'week_link'                 => $week_link,
            'day_pill'                  => $day_pill,
            'day_link'                  => $day_link,
            'time_period'               => $time_period,
            'plan'                      => $this->logged_in_customer->plan()
        );

        $combined                   = array_merge($vars, $pins_vars);
        $this->layout->sub_navigation = View::make('analytics.components.sub_nav.discover', $combined);
        $this->layout->main_content = View::make('analytics.pages.trending_pins', $combined);

        $this->logged_in_customer->recordEvent(
                                 UserHistory::VIEW_REPORT,
                                 $parameters = array(
                                     'report' => 'trending pins'
                                 )
        );

    }

    /**
     * Shows the default Trending Organic Pins page
     *
     * @author  Alex
     * @author  Will
     */
    public function showTrendingPinsDefault()
    {
        //whatever the default page should show should go here
        return $this->showTrendingPins('50');
    }

}


function word_count($a, $b) {
    $t = "word_count";

    if ($a["$t"] < $b["$t"]) {
        return 1;
    } else if ($a["$t"] == $b["$t"]) {
        return 0;
    } else {
        return -1;
    }
}


function grabPage($url) {
    $c = curl_init ($url);
    curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($c, CURLOPT_USERAGENT,
        "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
    $page = curl_exec ($c);
    curl_close ($c);

    return $page;
}

function pin_count($a, $b) {
    $t = "pin_count";

    if ($a["$t"] < $b["$t"]) {
        return 1;
    } else if ($a["$t"] == $b["$t"]) {
        return 0;
    } else {
        return -1;
    }
}



function created_at($a, $b) {
    $t = "created_at";

    if ($a["$t"] < $b["$t"]) {
        return 1;
    } else if ($a["$t"] == $b["$t"]) {
        return 0;
    } else {
        return -1;
    }
}

function extract_date($txt) {

    $txt = str_replace('Pinned ', '', $txt);

    $cutoff = strpos($txt, 'ago');

    $txt = substr($txt, 0, $cutoff);

    return $txt;

}

function getGoogleDateFormat($date) {
    $t = getTimestampFromDate($date);

    return date("Y-m-d", $t);
}

function getTimestampFromDate($date) {
    $m = substr($date, 0, 2);
    $d = substr($date, 3, 2);
    $y = substr($date, 6, 4);
    $t = mktime(0,0,0,$m, $d, $y);
    return $t;
}

function GetDateStringFromTime($t) {
    $date_string = date('F d, Y H:i:s', $t);

    return $date_string;
}

function cmp($a, $b) {
    $t = $_GET['t'];
    if (!$t) {
        $t = "repins";
    }

    if ($a["$t"] < $b["$t"]) {
        return 1;
    } else if ($a["$t"] == $b["$t"]) {
        return 0;
    } else {
        return -1;
    }
}

function pinners($a, $b) {
    $t = "count";

    if ($a["$t"] < $b["$t"]) {
        return 1;
    } else if ($a["$t"] == $b["$t"]) {
        return 0;
    } else {
        return -1;
    }
}

function engagement_count($a, $b) {
    $t = "engagement";

    if ($a["$t"] < $b["$t"]) {
        return 1;
    } else if ($a["$t"] == $b["$t"]) {
        return 0;
    } else {
        return -1;
    }
}

function repins($a, $b) {
    $t = "repins";

    if ($a["$t"] < $b["$t"]) {
        return 1;
    } else if ($a["$t"] == $b["$t"]) {
        return 0;
    } else {
        return -1;
    }
}

function getPinterestUrl($p) {
    return "http://pinterest.com/pin/$p/";
}

