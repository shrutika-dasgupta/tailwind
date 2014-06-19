<?php

/**
 * User account keyword model.
 * 
 * @author Daniel
 */
class UserAccountKeyword extends PDODatabaseModel
{
    public $table = 'user_accounts_keywords';

    public $columns = array(
        'account_id',
        'keyword'
    );

    public $primary_key = array('account_id', 'keyword');

    public $account_id;
    public $keyword;

    /**
     * Creates a new account keyword.
     *
     * @param UserAccount $account
     * @param string      $keyword
     * @param array       $options
     *
     * @throws UserAccountKeywordException
     * @return bool
     */
    public static function create(UserAccount $account, $keyword, $options = array())
    {
        $account_keywords = self::find(array('account_id' => $account->account_id));
        $account_limit    = $account->keywordLimit();

        // Check if account is already at its keyword limit.
        if (!empty($account_limit) && count($account_keywords) >= $account_limit) {
            throw new UserAccountKeywordException(
                'Account keyword limit exceeded.',
                UserAccountKeywordException::KEYWORD_LIMIT
            );
        }

        $keyword = preg_replace("/[^a-zA-Z0-9 #&!',_\-]/s", '', trim($keyword));

        $validator = Validator::make(
            array('keyword' => $keyword),
            array('keyword' => 'required')
        );

        if ($validator->fails()) {
            return false;
        }

        $user_account_keyword             = new UserAccountKeyword();
        $user_account_keyword->account_id = $account->account_id;
        $user_account_keyword->keyword    = $keyword;
        $user_account_keyword->insertUpdateDB();

        $status_keyword             = new StatusKeyword();
        $status_keyword->keyword    = $keyword;
        $status_keyword->track_type = 'user';
        $status_keyword->insertIgnore();

        if ($user = User::getLoggedInUser()) {
            $user->recordEvent(
                UserHistory::ADD_KEYWORD,
                array_merge(
                    array_get($options, 'event_data', array()),
                    array('keyword' => $keyword)
                )
            );
        }

        return true;
    }

    /**
     * Deletes an account keyword.
     *
     * @param UserAccount $account
     * @param string      $keyword
     * @param array       $options
     *
     * @return bool
     */
    public static function delete(UserAccount $account, $keyword, $options = array())
    {
        $validator = Validator::make(
            array('keyword' => $keyword),
            array('keyword' => 'required')
        );

        if ($validator->fails()) {
            return false;
        }

        $query = "DELETE FROM user_accounts_keywords
                  WHERE account_id = ? AND keyword = ?";
        
        $deleted = DB::delete($query, array($account->account_id, $keyword));
        if (!$deleted) {
            return false;
        }

        if ($user = User::getLoggedInUser()) {
            $user->recordEvent(
                UserHistory::REMOVE_KEYWORD,
                array_merge(
                    array_get($options, 'event_data', array()),
                    array('keyword' => $keyword)
                )
            );
        }

        return true;
    }

    /**
     * Gets the top domains for a keyword(s).
     *
     * @param mixed   $keyword
     * @param integer $period
     *
     * @return array
     */
    public static function topDomains($keyword, $period = 0)
    {
        if (is_array($keyword)) {
            $keywords_csv = '"' . implode('", "', $keyword) . '"';

            return DB::select(
                "SELECT domain, SUM(keyword_mentions) AS keyword_mentions
                 FROM cache_keyword_domains
                 WHERE keyword IN ($keywords_csv)
                    AND period = ?
                    AND domain != ''
                 GROUP BY domain
                 ORDER BY keyword_mentions DESC
                 LIMIT 10",
                array($period)
            );
        }

        return DB::select(
            "SELECT *
             FROM cache_keyword_domains
             WHERE keyword = ?
                AND period = ?
                AND domain != ''
             ORDER BY keyword_mentions DESC
             LIMIT 10",
            array($keyword, $period)
        );
    }

    /**
     * Gets the top influencers for the keywords.
     *
     * @param array   $keywords
     * @param integer $period
     *
     * @return array
     */
    public static function topInfluencers(array $keywords, $period = 0)
    {
        $keywords_csv = '"' . implode('", "', $keywords) . '"';

        return DB::select(
            "SELECT *
             FROM cache_keyword_influencers
             WHERE keyword IN ($keywords_csv)
                AND period = ?
             ORDER BY follower_count DESC
             LIMIT 25",
            array($period)
        );
    }

    /**
     * Gets an account's most popular keywords (based on pin count).
     *
     * @param UserAccount $account
     * @param int         $start_date
     * @param int         $end_date
     *
     * @return array
     */
    public static function popular(UserAccount $account, $start_date = null, $end_date = null)
    {
        $keywords = self::find(array('account_id' => $account->account_id));
        if (empty($keywords)) {
            return array();
        }

        $keywords     = array_map(function($keyword) { return $keyword->keyword; }, $keywords);
        $keywords_csv = '"' . implode('", "', $keywords) . '"';

        $end_date   = !empty($end_date) ? $end_date : time();
        $start_date = !empty($start_date) ? $start_date : strtotime("-7 days", $end_date);

        return DB::select(
            "SELECT keyword, sum(pin_count) AS pin_count
             FROM cache_keyword_daily_counts
             WHERE keyword IN ($keywords_csv) AND date >= ? AND date <= ?
             GROUP BY keyword
             ORDER BY pin_count DESC
             LIMIT 25",
            array(flat_date('day', $start_date), flat_date('day', $end_date))
        );
    }

    /**
     * Gets keyword recommendations for a set of keywords.
     *
     * @param array $keywords
     * @param int   $start_date
     * @param int   $end_date
     *
     * @return array
     */
    public static function recommendations(array $keywords, $start_date = null, $end_date = null)
    {
        $keywords_csv = '"' . implode('", "', $keywords) . '"';

        $end_date   = !empty($end_date) ? $end_date : time();
        $start_date = !empty($start_date) ? $start_date : strtotime("-7 days", $end_date);

        $recommendations = array();

        $wordcloud = DB::select(
            "SELECT keyword, word, SUM(word_count) AS word_count
             FROM cache_keyword_wordclouds
             WHERE keyword IN ($keywords_csv)
               AND word NOT IN ($keywords_csv)
               AND `date` >= ?
               AND `date` <= ?
             GROUP BY word
             ORDER BY word_count DESC
             LIMIT 25",
             array(flat_date('day', $start_date), flat_date('day', $end_date))
        );

        foreach ($wordcloud as $item) {
            $recommendations[$item->keyword][$item->word] = $item->word_count;
        }

        return $recommendations;
    }

    /**
     * Gets trending pins based on a set of keywords.
     *
     * @param array $keywords
     *
     * @return Pins
     */
    public static function trendingPins(array $keywords)
    {
        $keywords_csv = '"' . implode('", "', $keywords) . '"';

        $query = "SELECT a.keyword, a.pin_id,
                    b.domain, b.method, b.is_repin, b.parent_pin, b.via_pinner, b.origin_pin,
                    b.origin_pinner, b.image_url, b.link, b.description, b.repin_count,
                    b.like_count, b.comment_count, b.created_at,
                    c.username, c.first_name, c.last_name, c.image, c.about, c.domain_url, c.website_url,
                    c.facebook_url, c.twitter_url, c.location, c.pin_count, c.follower_count, c.gender
                  FROM
                      (SELECT pin_id, keyword
                       FROM map_pins_keywords a
                       WHERE keyword IN ($keywords_csv)
                       ORDER BY created_at DESC
                       LIMIT 100) AS a
                  LEFT JOIN (data_pins_new b, data_profiles_new c)
                  ON (a.pin_id=b.pin_id AND b.user_id=c.user_id)";

        $data = DB::select($query);

        // Build the collection of pins.
        $pins = new Pins();
        foreach ($data as $item) {
            $pin = new Pin();
            $pin->loadDBData($item);
            $pin->topic = $item->keyword;

            $pins->add($pin);
        }

        return $pins;
    }
}

class UserAccountKeywordException extends DBModelException
{
    const KEYWORD_LIMIT = 2000;
}
