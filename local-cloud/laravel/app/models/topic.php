<?php

/**
 * Topic model.
 * 
 * @author Daniel
 */
class Topic extends Model
{
    /**
     * Gets an account's most popular topics (based on pin count).
     *
     * @param UserAccount $account
     * @param int         $start_date
     * @param int         $end_date
     *
     * @return array
     */
    public static function popular(UserAccount $account, $start_date = null, $end_date = null)
    {
        $end_date   = !empty($end_date) ? $end_date : time();
        $start_date = !empty($start_date) ? $start_date : strtotime("-7 days", $end_date);

        $top_keywords = UserAccountKeyword::popular($account, $start_date, $end_date);
        $top_domains  = UserAccountsDomain::popular($account, $start_date, $end_date);
        $top_topics   = array_merge($top_keywords, $top_domains);

        uasort($top_topics, function ($a, $b) {
            if ($a->pin_count < $b->pin_count) {
                return 1;
            } else if ($a->pin_count == $b->pin_count) {
                return 0;
            } else {
                return -1;
            }
        });

        return $top_topics;
    }

    /**
     * Gets keyword recommendations for a set of topics.
     *
     * @param UserAccount $account
     * @param array       $topics
     * @param int         $start_date
     * @param int         $end_date
     *
     * @return array
     */
    public static function recommendations(UserAccount $account, array $topics, $start_date = null, $end_date = null)
    {
        $keywords = $domains = array();
        foreach ($topics as $topic) {
            if (self::type($topic) == 'keyword') {
                $keywords[] = $topic;
            } else {
                $domains[] = $topic;
            }
        }

        $keyword_recommendations = UserAccountKeyword::recommendations($keywords, $start_date, $end_date);
        $domain_recommendations  = UserAccountsDomain::recommendations($domains, $start_date, $end_date);

        // Add the arrays instead of array_merge() to avoid issues with numeric keys/keywords.
        $topic_recommendations   = $keyword_recommendations + $domain_recommendations;

        // Ensure recommendations are in the same order as the array of topics.
        $recommendations = array();
        foreach ($topics as $topic) {
            $recommendations[$topic] = array_get($topic_recommendations, $topic);
        }

        $account_keywords = UserAccountKeyword::find(array('account_id' => $account->account_id));
        $account_keywords = array_map(function($keyword) { return $keyword->keyword; }, $account_keywords);

        // Filter out any words the account is already following.
        foreach ($recommendations as $topic => $words) {
            foreach ($words as $key => $word) {
                if (in_array($word, $account_keywords)) {
                    unset($recommendations[$topic][$key]);
                }
            }
        }

        return $recommendations;
    }

    /**
     * Gets trending pins based on a set of topics
     *
     * @param array $topics
     *
     * @return array
     */
    public static function trendingPins(array $topics)
    {
        $keywords = $domains = array();
        foreach ($topics as $topic) {
            if (self::type($topic) == 'keyword') {
                $keywords[] = $topic;
            } else {
                $domains[] = $topic;
            }
        }

        $keyword_pins = array();
        if (!empty($keywords)) {
            $keyword_pins = UserAccountKeyword::trendingPins($keywords);
            $keyword_pins = $keyword_pins->getModels();
        }

        $domain_pins = array();
        if (!empty($domains)) {
            $domain_pins  = UserAccountsDomain::trendingPins($domains);
            $domain_pins  = $domain_pins->getModels();
        }

        // Build the key/value pairs.
        $trending_pins = array();
        foreach (array_merge($keyword_pins, $domain_pins) as $pin) {
            $trending_pins[$pin->topic][] = $pin;
        }

        // Sort the results based on the total number of trending pins per topic.
        uasort($trending_pins, function ($a, $b) {
            if (count($a) < count($b)) {
                return 1;
            } else if (count($a) == count($b)) {
                return 0;
            } else {
                return -1;
            }
        });

        return $trending_pins;
    }

    /**
     * @author Alex
     *
     * Gets trending images based on a set of domains
     *
     * @param array $topics
     *
     * @param int   $day_range
     *
     * @return array
     */
    public static function trendingImages(array $topics, $day_range)
    {
        $keywords = $domains = array();
        foreach ($topics as $topic) {
            if (self::type($topic) == 'keyword') {
                $keywords[] = $topic;
            } else {
                $domains[] = $topic;
            }
        }

        $domain_pins = array();
        if (!empty($domains)) {
            $domain_pins  = UserAccountsDomain::trendingImages($domains, $day_range);
            $domain_pins  = $domain_pins->getModels();
        }

        // Build the key/value pairs.
        $trending_pins = array();
        foreach ($domain_pins as $pin) {
            $trending_pins[$pin->topic][] = $pin;
        }

        // Sort the results based on the total number of trending pins per topic.
        uasort($trending_pins, function ($a, $b) {
            if (count($a) < count($b)) {
                return 1;
            } else if (count($a) == count($b)) {
                return 0;
            } else {
                return -1;
            }
        });

        return $trending_pins;
    }

    /**
     * Determines a topic's type.
     *
     * @param string $topic
     *
     * @return string
     */
    public static function type($topic)
    {
        return strpos($topic, '.') !== false ? 'domain' : 'keyword';
    }
}
