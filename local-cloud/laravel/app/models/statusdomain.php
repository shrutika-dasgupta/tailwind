<?php

/**
 * Status domain model.
 * 
 * @author Daniel
 */
class StatusDomain extends PDODatabaseModel
{
    public $table = 'status_domains';

    public $columns = array(
        'domain',
        'last_pulled',
        'last_calced',
        'calculate_influencers_footprint',
        'pins_per_day',
        'track_type',
        'timestamp'
    );

    public $primary_keys = array('domain');

    public $domain;
    public $last_pulled;
    public $last_calced;
    public $pins_per_day;
    public $track_type;
    public $timestamp;

    /**
     * Class initializer.
     *
     * @return \StatusDomain
     */
    public function __construct()
    {
        $this->last_calced = 0;
        $this->last_pulled = 0;
        $this->timestamp   = time();

        parent::__construct();
    }

    /**
     * Find user by domain
     *
     * @author  Alex
     *
     * @param $domain
     *
     * @return StatusDomain
     */
    public static function findByDomain($domain)
    {

        $db_domain = DB::select("SELECT * FROM status_domains WHERE domain = ? LIMIT 1", array($domain));

        if ($db_domain) {
            return  StatusDomain::createFromDBData($db_domain[0]);
        }

        return false;
    }

    /**
     * Load from DB result
     *
     * @author  Will
     */
    public static function createFromDBData($data,$prefix='')
    {
        $class = get_called_class();

        if (empty($data)) {
            $exception_class = $class . 'Exception';
            throw new $exception_class('The dataset is empty to create a ' . $class);
        }
        /** @var $model PDODatabaseModel */
        $model = new $class();

        $model->loadDBData($data,$prefix);

        return $model;
    }

    /**
     * @author   Alex
     *
     * @return array counts
     */
    public static function getActiveCount()
    {
        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->query(
                   "SELECT track_type, count(*) as count
                    FROM status_domains
                    WHERE track_type in ('user', 'competitor', 'free')
                    GROUP BY track_type"
        );

        return $STH->fetchAll();
    }

    /**
     * @author   Alex
     *
     * @return array counts
     */
    public static function getLastPulledTodayCount()
    {
        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->prepare(
                   "SELECT track_type, count(*) as count
                    FROM status_domains
                    WHERE last_pulled > :flat_date
                    AND track_type in ('user', 'competitor', 'free')
                    GROUP BY track_type"
        );

        $STH->execute(
            array(
                 ':flat_date' => strtotime("-1 day", time())
            ));

        $counts = $STH->fetchAll();

        return $counts;
    }

    /**
     * @author   Alex
     *
     * @param $domain
     *
     * @return int $last_calced
     */
    public static function lastCalced($domain)
    {
        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->prepare(
                   "SELECT last_calced
                    FROM status_domains
                    WHERE domain = :domain"
        );

        $STH->execute(
            array(
                 ':domain' => $domain
            ));

        $last_calced = $STH->fetch()->last_calced;

        return $last_calced;
    }

    /**
     * @author   Alex
     *
     * @param $domain
     *
     * @return void
     */
    public static function updateLastCalced($domain)
    {
        $DBH = DatabaseInstance::DBO();

        $STH = $DBH->prepare(
                   "UPDATE status_domains
                    SET last_calced = :timestamp
                    WHERE domain = :domain"
        );

        $STH->execute(
            array(
                 ':timestamp' => time(),
                 ':domain' => $domain
            )
        );
    }

    /**
     * @author   Alex
     *
     * @param array $domains
     *
     * @return void
     */
    public static function updateTodaysDailyCounts($domains)
    {
        $domains_to_calc = array();

        /*
         * Check the last_calced times for the set of domains.
         * If any are longer than an hour ago and they have a reasonable number of new pins
         * added per day, we'll update their daily counts calculation for today.
         */
        foreach ($domains as $domain) {

            $status_domain = self::findByDomain($domain);

            if ($status_domain->last_calced < strtotime("-1 hour", time())) {
                array_push($domains_to_calc, $domain);
                self::updateLastCalced($domain);
            }
        }

        /*
         * Update todays daily counts for any out of date domains
         */
        if(!empty($domains_to_calc)){

            $DBH = DatabaseInstance::DBO();
            $flat_date = flat_date('day');
            $domains_csv = '"' . implode('", "', $domains_to_calc) . '"';
            $cache_daily_counts = new Caches\DomainDailyCounts();


            $daily_counts = $DBH->query("
                                  SELECT a.domain
                                    , '$flat_date' as date
                                    , a.is_repin
                                    , count(a.pin_id) as pin_count
                                    , count(distinct a.user_id) as pinner_count
                                    , sum(a.repin_count) as repin_count
                                    , sum(a.like_count) as like_count
                                    , sum(a.comment_count) as comment_count
                                    , sum(b.follower_count) as reach
                                 FROM
                                    data_pins_new a USE INDEX (domain_created_at_idx)
                                    JOIN data_profiles_new b
                                    ON a.user_id = b.user_id
                                 WHERE a.domain in ($domains_csv)
                                 AND a.created_at > $flat_date
                                 GROUP BY a.domain, date, a.is_repin")->fetchAll();

            foreach($daily_counts as $daily){

                $cache_daily_count = new Caches\DomainDailyCount();
                $cache_daily_count->domain = $daily->domain;
                $cache_daily_count->date = $daily->date;
                $cache_daily_count->is_repin = $daily->is_repin;
                $cache_daily_count->pin_count = $daily->pin_count;
                $cache_daily_count->pinner_count = $daily->pinner_count;
                $cache_daily_count->repin_count = $daily->repin_count;
                $cache_daily_count->like_count = $daily->like_count;
                $cache_daily_count->comment_count = $daily->comment_count;
                $cache_daily_count->reach = $daily->reach;

                $cache_daily_counts->add($cache_daily_count);
            }

            if($cache_daily_counts->count() > 0){
                $cache_daily_counts->insertUpdateDB();
            }


        }
    }
}
