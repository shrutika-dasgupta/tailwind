<?php

/**
 * Class Users
 *
 * @author  Alex
 */
class Users extends DBCollection
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    public
        $table = 'users',
        $columns =
        array(
            'cust_id',
            'email',
            'password',
            'first_name',
            'last_name',
            'verified',
            'org_id',
            'is_admin',
            'type',
            'invited_by',
            'source',
            'city',
            'region',
            'country',
            'timezone',
            'timestamp',
            'force_logout',
            'last_seen_ip'
        ),
        $primary_keys = array('cust_id');

    /*
    |--------------------------------------------------------------------------
    | Static methods
    |--------------------------------------------------------------------------
    */

    /**
     * @author  Will
     *          Alex
     *          
     * Fetches all customers in the database and preloads
     *  organization
     *  organization -> plan
     *  primary account
     *
     * @param array $preload
     * @param int   $upper_limit
     * @param int   $lower_limit
     * @param bool  $added_where_clause
     *
     * @internal param string $user_type
     *
     * @internal param int $limit
     *
     * @return Users
     */
    public static function all(
        $preload = array(),
        $upper_limit = 15000,
        $lower_limit = 0,
        $added_where_clause = false
    )
    {
        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->query(
            "SELECT
                  users.cust_id, users.email, users.first_name, users.last_name, users.is_admin, users.invited_by, users.timestamp, users.temporary_key, users.source,
                  user_organizations.org_id, user_organizations.org_name, user_organizations.plan, user_organizations.chargify_id,
                  user_accounts.account_id, user_accounts.account_name, user_accounts.username, user_accounts.user_id, user_accounts.track_type, user_accounts.competitor_of, user_accounts.created_at,
                  user_accounts_domains.domain,
                  user_plans.name
             FROM users
             JOIN user_organizations
             ON users.org_id = user_organizations.org_id
             JOIN user_accounts
             ON user_organizations.org_id = user_accounts.org_id
             JOIN user_plans
             ON user_organizations.plan = user_plans.id
             JOIN user_accounts_domains
             ON user_accounts.account_id = user_accounts_domains.account_id
             where user_accounts.track_type != 'orphan'
             $added_where_clause
             GROUP BY users.cust_id
             limit $lower_limit, $upper_limit
             "
        );

        $users = new Users();

        foreach ($STH->fetchAll() as $userData) {
            $user = new User();
            $user->loadDBData($userData);
            $user->preLoad('organization', $userData);
            $user->organization()->preLoad('plan', $userData);
            $user->organization()->chargify_id = $userData->user_organization_chargify_id;


            $primary_account = new UserAccount();
            $primary_account->loadDBData($userData);
            $user->organization()->loadPrimaryAccount($primary_account);

            $users->add($user);
        }

        return $users;
    }

    /**
     * @author  Alex
     *          Will
     *
     * Fetches all customers in the database and preloads
     *  organization
     *  organization -> plan
     *  primary account
     *
     * @param array $preload
     * @param       $search_term
     * @param int   $upper_limit
     * @param int   $lower_limit
     * @param bool  $added_where_clause
     *
     * @internal param string $user_type
     *
     * @internal param int $limit
     *
     * @return Users
     */
    public static function findByAnything(
        $preload = array(),
        $search_term,
        $upper_limit = 1000,
        $lower_limit = 0,
        $added_where_clause = false
    )
    {
        $DBH = DatabaseInstance::DBO();

        if(!empty($search_term)){
            if(is_numeric($search_term)){
                $where_clause = " and (users.cust_id = $search_term
                                OR user_organizations.org_id = $search_term
                                OR user_accounts.account_id = $search_term
                                OR user_organizations.chargify_id = $search_term
                                OR users.invited_by = $search_term
                                OR user_accounts.competitor_of = $search_term
                                OR user_accounts.user_id = $search_term) ";
            } else {
                $where_clause = " and (users.email like '%$search_term%'
                                  OR users.first_name like '%$search_term%'
                                  OR users.last_name like '%$search_term%'
                                  OR user_organizations.org_name like '%$search_term%'
                                  OR user_accounts.account_name like '%$search_term%'
                                  OR user_accounts.username like '%$search_term%'
                                  OR user_accounts_domains.domain like '%$search_term%') ";
            }
        }

        $STH = $DBH->query(
                   "SELECT
                  users.cust_id, users.email, users.first_name, users.last_name, users.is_admin, users.invited_by, users.timestamp, users.temporary_key, users.source,
                  user_organizations.org_id, user_organizations.org_name, user_organizations.plan, user_organizations.chargify_id,
                  user_accounts.account_id, user_accounts.account_name, user_accounts.username, user_accounts.user_id, user_accounts.track_type, user_accounts.competitor_of, user_accounts.created_at,
                  user_accounts_domains.domain,
                  user_plans.name
             FROM users
             LEFT JOIN (user_organizations, user_accounts)
             ON (users.org_id = user_organizations.org_id and user_organizations.org_id = user_accounts.org_id)
             LEFT JOIN (user_plans, user_accounts_domains)
             ON (user_organizations.plan = user_plans.id and user_accounts.account_id = user_accounts_domains.account_id)
             where user_accounts.track_type != 'orphan'
             $where_clause
             $added_where_clause
             GROUP BY users.cust_id
             limit $lower_limit, $upper_limit
             "
        );

        $users = new Users();

        foreach ($STH->fetchAll() as $userData) {
            $user = new User();
            $user->loadDBData($userData);
            $user->preLoad('organization', $userData);
            $user->organization()->preLoad('plan', $userData);
            $user->organization()->chargify_id = $userData->user_organization_chargify_id;


            $primary_account = new UserAccount();
            $primary_account->loadDBData($userData);
            $user->organization()->loadPrimaryAccount($primary_account);

            $users->add($user);
        }

        return $users;
    }

    /*
    |--------------------------------------------------------------------------
    | Instance methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get a comma separated string of the cust ids
     *
     * @author  Alex
     * @author  Will
     *
     */
    public function cust_ids_string($seperator = ',')
    {
        return $this->stringifyField('cust_id', $seperator);
    }

    /**
     * Get a comma separated string of the customer emails
     *
     * @author  Alex
     * @author  Will
     */
    public function emails_string($seperator = ',')
    {
        return $this->stringifyField('email', $seperator);
    }

    /**
     * Filters this collection of users and returns those that are active.
     *
     * @author Janell
     *
     * @return Users
     */
    public function active()
    {
        return $this->copy()->filter(function($model) {
            if ($model->type != User::DELETED || $model->type == null) {
                return true;
            }
            return false;
        });
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/
class UsersExceptions extends CollectionException {}
