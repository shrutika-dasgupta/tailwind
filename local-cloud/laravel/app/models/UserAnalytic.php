<?php

/**
 * User Analytics model.
 * 
 * @author Alex
 */
class UserAnalytic extends PDODatabaseModel
{
    public $table = 'user_analytics';

    public $columns = array(
        'user_id',
        'org_id',
        'account_id',
        'profile',
        'token',
        'name',
        'timezone',
        'currency',
        'webPropertyId',
        'eCommerceTracking',
        'websiteUrl',
        'last_pulled',
        'last_calced',
        'track_type',
        'added_at',
        'timestamp'
    );

    public $primary_keys = array('account_id', 'profile', 'token', 'track_type');
    public $user_id,
            $org_id,
            $account_id,
            $profile,
            $token,
            $name,
            $timezone,
            $currency,
            $webPropertyId,
            $eCommerceTracking,
            $websiteUrl,
            $last_pulled,
            $last_calced,
            $track_type,
            $added_at,
            $timestamp;

    /**
     * Class initializer.
     *
     * @return \UserAnalytic
     */
    public function __construct()
    {
        $this->last_calced = 0;
        $this->last_pulled = 0;
        $this->timestamp   = time();

        parent::__construct();
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

        $db_analytic = DB::select("SELECT * FROM user_analytics WHERE account_id = ? LIMIT 1", array($account_id));

        if ($db_analytic) {
            return  UserAnalytic::createFromDBData($db_analytic[0]);
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

class UserAnalyticException extends DBModelException {}
