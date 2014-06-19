<?php

use Pinleague\CLI;

/**
 * StatusTraffic model.
 * 
 * @author Alex
 */
class StatusTraffic extends PDODatabaseModel
{
    public $table = 'status_traffic';

    public $columns = array(
        'traffic_id',
        'user_id',
        'org_id',
        'account_id',
        'profile',
        'token',
        'timezone',
        'currency',
        'eCommerceTracking',
        'websiteUrl',
        'last_pulled',
        'last_calced',
        'next_pull',
        'track_type',
        'added_at',
        'timestamp'
    );

    public $primary_keys = array('user_id', 'org_id','account_id', 'profile');
    public $traffic_id,
            $user_id,
            $org_id,
            $account_id,
            $profile,
            $token,
            $timezone,
            $currency,
            $eCommerceTracking,
            $websiteUrl,
            $last_pulled,
            $last_calced,
            $next_pull,
            $track_type,
            $added_at,
            $timestamp;

    /**
     * Class initializer.
     *
     * @return \StatusTraffic
     */
    public function __construct()
    {
        $this->last_calced = 0;
        $this->last_pulled = 0;
        $this->next_pull   = 0;
        $this->timestamp   = time();

        parent::__construct();
    }

    /**
     * @author   Alex
     *
     * @param $minutes_threshold
     *
     * @return void
     */
    public function queueTrafficDataUpdate($minutes_threshold)
    {
        $DBH = DatabaseInstance::DBO();

        /**
         * Check if the last_pulled time is prior to the given threshold
         * If so, we'll set the last_pulled timestamp to yesterday, so that the
         * google analytics engine will pick it up and update it.
         */
        if ($this->last_pulled < strtotime("-$minutes_threshold minutes", time())) {
            $this->updateLastPulled(strtotime("-1 days", $this->last_pulled));
        }
    }

    /**
     * @author   Alex
     *
     * @param $timestamp
     *
     * @return void
     */
    public function updateLastPulled($timestamp)
    {
        $DBH = DatabaseInstance::DBO();

        $STH = $DBH->prepare(
                   "UPDATE status_traffic
                    SET last_pulled = :timestamp
                    WHERE traffic_id = :traffic_id"
        );

        $STH->execute(
            array(
                 ':timestamp' => $timestamp,
                 ':traffic_id' => $this->traffic_id
            )
        );
    }

    /** Fetch the traffic ids to run through the google analytics engine
     *
     * @return array|null
     */

    public static function getAnalyticsToPull()
    {

        $traffic = null;

        /*
         * First, check to see if there are any newly added profiles to pull traffic for.
         * If these are found, we want to pull them one at a time since there will be a lot of data
         * coming in
         */
        $DBH = DatabaseInstance::DBO();

        $STH = $DBH->query("SELECT * from status_traffic
                            WHERE next_pull = 0
                            AND profile != ''
                            AND timezone IS NOT NULL
                            AND timezone != ''
                            ORDER by next_pull ASC
                            LIMIT 1");

        $traffic_ids_to_pull = $STH->fetchAll();

        foreach ($traffic_ids_to_pull as $traffic_id ) {
            $id = $traffic_id->traffic_id;

            if ($traffic == null) {
                $traffic = array();
            }
            $traffic_ids[] = $id;

            $traffic["$id"] = array();

            $traffic["$id"]['id']       = $traffic_id->traffic_id;
            $traffic["$id"]['user_id']  = $traffic_id->user_id;
            $traffic["$id"]['profile']  = $traffic_id->profile;
            $traffic["$id"]['token']    = $traffic_id->token;
            $traffic["$id"]['timezone'] = $traffic_id->timezone;

            $account_id = $traffic_id->account_id;

            CLI::write($account_id);
        }

        if ($traffic != null) {
            CLI::write(Log::debug("pulling traffic data for new account", $traffic_ids));

            return $traffic;
        }

        /*
         * If there are no new accounts to pull data for, we will pull 30 existing accounts that
         * need to be updated and grab their data together.
         */
        $STH = $DBH->prepare("SELECT * from status_traffic
                              WHERE next_pull <= :current_time
                              AND profile != ''
                              ORDER by last_pulled ASC
                              LIMIT 10");

        $STH->execute([":current_time" => time()]);

        $traffic_ids_to_pull = $STH->fetchAll();

        foreach ($traffic_ids_to_pull as $traffic_id ) {

            $id = $traffic_id->traffic_id;

            if ($traffic == null) {
                $traffic = array();
            }
            $traffic_ids[] = $id;

            $traffic["$id"] = array();

            $traffic["$id"]['id']      = $traffic_id->traffic_id;
            $traffic["$id"]['user_id'] = $traffic_id->user_id;
            $traffic["$id"]['profile'] = $traffic_id->profile;
            $traffic["$id"]['token']   = $traffic_id->token;
            $traffic["$id"]['timezone'] = $traffic_id->timezone;

            $account_id = $traffic_id->account_id;

            CLI::write($account_id);
        }

        CLI::write(Log::debug("pulling traffic data for accounts that need to be updated", $traffic_ids));

        return $traffic;
    }



    /**
     * Find user by account_id
     *
     * @author  Alex
     *
     * @param $account_id
     *
     * @return UserAnalytic
     */
    public static function findByAccountId($account_id)
    {

        $db_analytic = DB::select(
                         "SELECT * FROM status_traffic WHERE account_id = ?
                         AND track_type != 'orphan' LIMIT 1",
                             array($account_id));

        if ($db_analytic) {
            return  StatusTraffic::createFromDBData($db_analytic[0]);
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



}

class StatusTrafficException extends DBModelException {}
