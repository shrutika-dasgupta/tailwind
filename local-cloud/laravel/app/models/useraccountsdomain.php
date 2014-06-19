<?php

/**
 * User account domain model.
 * 
 * @author Will
 * @author Daniel
 */
class UserAccountsDomain extends PDODatabaseModel
{
    public $table = 'user_accounts_domains';

    public $columns = array(
        'account_id',
        'domain'
    );

    public $primary_key = array('account_id', 'domain');

    public $account_id;
    public $domain;

    /**
     * A cache of $custom_date_limit array for a set of domains to
     * determine the max custom date range allowed for a given domain
     *
     * @var $_custom_date_limit array
     */
    protected $_custom_date_limit = false;


    /**
     * @author  Will
     */
    public function __construct($url = false)
    {
        $this->domain = $url;
        if ($url) {
            $this->cleanDomain($url);
        }
        parent::__construct();
    }

    /**
     * @author  Will
     * @return string
     */
    public function __toString()
    {
        if ($this->domain) {
            return $this->domain;
        }

        return '';
    }

    /**
     * Creates a new account domain.
     *
     * @param UserAccount $account
     * @param string      $domain
     * @param array       $options
     *
     * @throws UserAccountsDomainException
     * @return bool
     */
    public static function create(UserAccount $account, $domain, $options = array())
    {
        $account_domains = self::find(array('account_id' => $account->account_id));
        $account_limit   = $account->domainLimit();

        // Check if account is already at its domain limit.
        if (!empty($account_limit) && count($account_domains) >= $account_limit) {
            throw new UserAccountsDomainException(
                'Account domain limit exceeded.',
                UserAccountsDomainException::DOMAIN_LIMIT
            );
        }

        $domain = preg_replace('/[^a-zA-Z0-9\-\.]/s', '', trim($domain));

        $validator = Validator::make(
            array('domain' => $domain),
            array('domain' => 'required')
        );

        if ($validator->fails()) {
            return false;
        }

        $user_account_domain             = new UserAccountsDomain();
        $user_account_domain->account_id = $account->account_id;
        $user_account_domain->domain     = $domain;
        $user_account_domain->insertUpdateDB();

        $status_domain             = new StatusDomain();
        $status_domain->domain     = $domain;
        $status_domain->track_type = 'user';
        $status_domain->insertIgnore();

        if ($user = User::getLoggedInUser()) {
            $user->recordEvent(
                UserHistory::ADD_ACCOUNT_DOMAIN,
                array_merge(
                    array_get($options, 'event_data', array()),
                    array('domain' => $domain)
                )
            );
        }

        return true;
    }

    /**
     * Deletes an account domain.
     *
     * @param UserAccount $account
     * @param string      $domain
     * @param array       $options
     *
     * @return bool
     */
    public static function delete(UserAccount $account, $domain, $options = array())
    {
        $validator = Validator::make(
            array('domain' => $domain),
            array('domain' => 'required')
        );

        if ($validator->fails()) {
            return false;
        }

        $query = "DELETE FROM user_accounts_domains
                  WHERE account_id = ? AND domain = ?";
        
        $deleted = DB::delete($query, array($account->account_id, $domain));
        if (!$deleted) {
            return false;
        }

        if ($user = User::getLoggedInUser()) {
            $user->recordEvent(
                UserHistory::REMOVE_ACCOUNT_DOMAIN,
                array_merge(
                    array_get($options, 'event_data', array()),
                    array('domain' => $domain)
                )
            );
        }

        return true;
    }

    /**
     * Gets an account's most popular domains (based on pin count).
     *
     * @param UserAccount $account
     * @param int         $start_date
     * @param int         $end_date
     *
     * @return array
     */
    public static function popular(UserAccount $account, $start_date = null, $end_date = null)
    {
        $domains = self::find(array('account_id' => $account->account_id));
        if (empty($domains)) {
            return array();
        }

        $domains     = array_map(function($domain) { return $domain->domain; }, $domains);
        $domains_csv = '"' . implode('", "', $domains) . '"';

        $end_date   = !empty($end_date) ? $end_date : time();
        $start_date = !empty($start_date) ? $start_date : strtotime("-7 days", $end_date);

        return DB::select(
            "SELECT domain, sum(pin_count) AS pin_count
             FROM cache_domain_daily_counts
             WHERE domain IN ($domains_csv) AND date >= ? AND date <= ?
             GROUP BY domain
             ORDER BY pin_count DESC
             LIMIT 25",
            array(flat_date('day', $start_date), flat_date('day', $end_date))
        );
    }

    /**
     * Gets keyword recommendations for a set of domains.
     *
     * @param array $domains
     * @param int   $start_date
     * @param int   $end_date
     *
     * @return array
     */
    public static function recommendations(array $domains, $start_date = null, $end_date = null)
    {
        $recommendations = array();
        foreach ($domains as $domain) {
            $pins = self::trendingPins(array($domain));

            $wordcloud = $pins->wordcloud();
            foreach ($wordcloud as $i => $item) {
                $recommendations[$domain][$item['word']] = $item['count'];
            }
        }

        return $recommendations;
    }

    /**
     * Gets trending pins based on a set of domains.
     *
     * @param array $domains
     *
     * @return Pins
     */
    public static function trendingPins(array $domains)
    {
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
                       ORDER BY created_at DESC
                       LIMIT 100) AS a
                  LEFT JOIN data_profiles_new b
                  ON a.user_id = b.user_id";

        $data = DB::select($query);

        // Build the collection of pins.
        $pins = new Pins();
        foreach ($data as $item) {
            $pin = new Pin();
            $pin->loadDBData($item);
            $pin->topic = $item->domain;

            $pins->add($pin);
        }

        return $pins;
    }


    /**
     * @author Alex
     *
     * Gets trending images based on a set of domains.
     *
     * @param array $domains
     *
     * @param int   $day_range
     *
     * @return Pins
     */
    public static function trendingImages(array $domains, $day_range)
    {

        /*
         * Check how many pins a domain has before trying to pull more data than we can handle
         */
        $domains_csv = '"' . implode('", "', $domains) . '"';
        if($day_range == 0){
            $time_limit = strtotime("-1 month", flat_date('day'));
        } else {
            $time_limit = strtotime("-$day_range days", flat_date('day'));
        }

        $day_limit = UserAccountsDomain::_customDateRangeLimit($domains[0]);

        if($day_limit < $day_range){
            $time_limit = strtotime("-$day_limit days", flat_date('day'));
        }

        $query = "SELECT pin_id, domain, image_url, link, dominant_color,
                    count(*) as count, sum(repin_count) as sum_repins,
                    sum(like_count) as sum_likes, sum(comment_count) as sum_comments
                  FROM data_pins_new use index (domain_created_at_idx)
                  WHERE domain in ($domains_csv)
                  AND created_at > $time_limit
                  GROUP BY domain, image_url
                  ORDER BY count desc
                  LIMIT 100";

        $data = DB::select($query);

        /*
         * TODO: this is all wrong, but need to get it working quickly.
         */

        // Build the collection of pins.
        $cumulative_pin_count = 0;
        $pins = new Pins();
        foreach ($data as $item) {
            $cumulative_pin_count += $item->count;
            $pin = new Pin();
            $pin->loadDBData($item);
            $pin->topic = $item->domain;
            $pin->count = $item->count;
            $pin->sum_repins = $item->sum_repins;
            $pin->sum_likes = $item->sum_likes;
            $pin->sum_comments = $item->sum_comments;
            $pin->total_engagement = $item->sum_repins + $item->sum_likes + $item->sum_comments;
            $pin->dominant_color = $item->dominant_color;

            $pins->add($pin);
        }
        return $pins;
    }


    /**
     * @author  Will
     *
     * @param bool $time
     *
     * @return CalcDomainHistory|bool
     */
    public function findCalculationBefore($time = false)
    {
        if (!$time) {
            $time = time();
        } else if (is_string($time)) {
            $time = strtotime($time);
        }

        $STH = $this->DBH->prepare("
            select * from calcs_domain_history
            where date < :time
            AND domain = :domain
            order by date DESC
            Limit 1
        ");

        $STH->execute(
            array(
                 ':time'   => $time,
                 ':domain' => $this->domain
            )
        );

        if ($STH->rowCount() == 0) {
            return false;
        }

        $calc = new CalcDomainHistory();

        return $calc->loadDBData($STH->fetch());
    }

    /**
     * Remove www and everything after the domain
     * aka the domain and relevent subdomain
     *
     * @param $url
     */
    protected function cleanDomain($url)
    {
        $url = parse_url($url);

        if (isset($url['host'])) {
            $domain = $url['host'];
        } else {
            $pieces = explode('/', $url['path']);
            $domain = $pieces[0];
        }

        if (substr($domain, 0, 3) == 'www') {
            $domain = substr($domain, 3);
        }

        $domain = str_replace(" ","",$domain); 

        $this->domain = ltrim($domain, '.');
    }

    /**
     * Remove www and everything after the domain
     * aka the domain and relevent subdomain
     *
     * @author Alex
     *
     * @param $url
     *
     * @return string
     */
    public static function cleanDomainInput($url)
    {
        $url = parse_url($url);

        if (isset($url['host'])) {
            $domain = $url['host'];
        } else {
            $pieces = explode('/', $url['path']);
            $domain = $pieces[0];
        }

        if (substr($domain, 0, 3) == 'www') {
            $domain = substr($domain, 3);
        }

        $domain = str_replace(" ","",$domain);

        return ltrim($domain, '.');
    }

    /**
     * @author  Will
     *
     * @return int
     */
    public function pinCount()
    {
        return 0;
    }

    /**
     * Gets the top influencers for the domains.
     * 
     * @author Daniel
     *
     * @param array   $domains
     * @param integer $period
     *
     * @return array
     */
    public static function topInfluencers(array $domains, $period = 0)
    {
        $domains_csv = '"' . implode('", "', $domains) . '"';

        return DB::select(
            "SELECT *, follower_count*domain_mentions as reach
             FROM cache_domain_influencers
             WHERE domain IN ($domains_csv)
                AND period = ?
             ORDER BY reach DESC
             LIMIT 25",
            array($period)
        );
    }


    /**
     * Cache of customDateRangeLimit method
     *
     * @author  Alex
     *
     * @param   string $domain
     *
     * @param   bool $force_update
     *
     * @return  int $day_limit
     */
    public static function _customDateRangeLimit($domain, $force_update = false)
    {

        if (!$force_update) {
            if (Session::has('domain_custom_date_limit')) {
                if(array_key_exists($domain, Session::get('domain_custom_date_limit'))){
                    return Session::get('domain_custom_date_limit')[$domain];
                } else {
                    $domain_counts = Session::get('domain_custom_date_limit');
                    $domain_counts = array_merge(
                        $domain_counts,
                        array("$domain" => self::customDateRangeLimit($domain))
                    );
                    Session::put('domain_custom_date_limit', $domain_counts);
                }
            } else {
                $domain_counts = array("$domain" => self::customDateRangeLimit($domain));
                Session::put('domain_custom_date_limit', $domain_counts);
            }
        } else {
            $domain_counts = array("$domain" => self::customDateRangeLimit($domain));
            Session::put('domain_custom_date_limit', $domain_counts);
        }

        return Session::get('domain_custom_date_limit')[$domain];
    }

    /**
     * Return the maximum allowed custom date range for a domain's reports
     * (e.g. domains with 10's of thousands of pins/day will take too long to generate reports
     * for more than a week at a time when run on the fly)
     *
     * @author  Alex
     *
     * @param   string $domain
     *
     * @return  int $custom_day_limit
     */
    public static function customDateRangeLimit($domain)
    {

        /*
         * First, we find out how many pins we have for a given domain across different time
         * periods
         */
        $month_ago   = strtotime("-1 month", flat_date('day'));
        $quarter_ago = strtotime("-3 months", flat_date('day'));
        $year_ago    = strtotime("-1 year", flat_date('day'));

        $query = "SELECT domain, sum(pin_count) as pin_count
                  FROM cache_domain_daily_counts
                  WHERE domain = :domain
                  AND date >= :month_ago
                  GROUP BY domain";
        $month_count = DB::select($query, array(":domain" => $domain, ":month_ago" => $month_ago))[0]->pin_count;

        $query = "SELECT domain, sum(pin_count) as pin_count
                  FROM cache_domain_daily_counts
                  WHERE domain = :domain
                  AND date >= :quarter_ago
                  GROUP BY domain";
        $quarter_count = DB::select($query, array(":domain" => $domain, ":quarter_ago" => $quarter_ago))[0]->pin_count;

        $query = "SELECT domain, sum(pin_count) as pin_count
                  FROM cache_domain_daily_counts
                  WHERE domain = :domain
                  AND date >= :year_ago
                  GROUP BY domain";
        $year_count = DB::select($query, array(":domain" => $domain, ":year_ago" => $year_ago))[0]->pin_count;

        /*
         * Now we try to keep the user from running on-the-fly reports
         * on more than 100k pins at a time
         */
        $daily_average_count = $month_count/31;

        if ($year_count < 100000) {
            $custom_day_limit = 365;
        } else {
            if ($quarter_count < 100000) {
                $custom_day_limit = 93;
            } else {
                if ($month_count < 100000) {
                    $custom_day_limit = 31;
                } else {
                    $custom_day_limit = ceil(100000/$daily_average_count);
                }
            }
        }

        return $custom_day_limit;
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/

class UserAccountsDomainException extends DBModelException
{
    const DOMAIN_LIMIT = 1000;
}

class InvalidDomainException extends UserAccountsDomainException {}
