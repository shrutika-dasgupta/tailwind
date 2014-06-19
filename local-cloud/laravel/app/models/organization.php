<?php

use
    Carbon\Carbon,
    Collections\Tailwind\Features,
    Models\Tailwind\Feature,
    Models\Tailwind\OrganizationFeature,
    Pinleague\MailchimpWrapper;
/**
 *
 * @author Will
 */
class Organization extends PDODatabaseModel
{
    const FREE_MAX_USERS    = 2;
    const FREE_MAX_ACCOUNTS = 1;
    const LITE_MAX_USERS    = 2;
    const LITE_MAX_ACCOUNTS = 3;
    const PRO_MAX_USERS     = 5;
    const PRO_MAX_ACCOUNTS  = 5;

    public $columns = array(
        'org_id',
        'org_name',
        'org_type',
        'plan',
        'max_accounts',
        'max_users',
        'max_competitors',
        'plan_level',
        'is_legacy',
        'chargify_id',
        'chargify_id_alt',
        'coupon_code',
        'subscription_state',
        'component_count',
        'trial_start_at',
        'trial_end_at',
        'trial_converted_at',
        'trial_stopped_at',
        'billing_event_count',
        'first_billing_event_at',
        'total_amount_billed',
        'last_billing_amount',
        'last_billing_event_at',
        'created_at'

    ),
        $table = 'user_organizations',
        $primary_keys = array('org_id');

    public
        /**
         * Unique id {Primary Key}
         *
         * @var $org_id
         */
        $org_id,
        /**
         * User set name of the organization
         *
         * @var $org_name
         */
        $org_name,
        /**
         *
         * @var $org_type
         */
        $org_type,
        $plan,
        $max_accounts,
        $max_users,
        $max_competitors,
        $plan_level,
        $is_legacy,
        $chargify_id,
        $chargify_id_alt,
        $coupon_code,
        $subscription_state,
        $component_count,
        $trial_start_at,
        $trial_end_at,
        $trial_converted_at,
        $trial_stopped_at,
        $billing_event_count,
        $first_billing_event_at,
        $total_amount_billed,
        $last_billing_amount,
        $last_billing_event_at,
        /**
         * The timestamp that the organization was created
         * @var $created_at
         */
        $created_at;

    /*
    |--------------------------------------------------------------------------
    | Cached Fields
    |--------------------------------------------------------------------------
    */
    protected
        $_plan = false,
        $_primary_account = false,
        $_active_user_accounts = false,
        $_competitor_user_accounts = false,
        $_subscription = false;

    /**
     * Cached features array
     * @var /Collections/Tailwind/Features
     */
    protected $_features;

    /*
    |--------------------------------------------------------------------------
    | Static methods
    |--------------------------------------------------------------------------
    */

    /**
     * Make a new organization
     *
     * @author  Will
     */
    public static function create(
        $name
        , $type = ''
        , $plan = Plan::FREE_PLAN_ID
        , $plan_level = 0
        , $max_users = Organization::FREE_MAX_USERS
        , $permission_level = 0)
    {
        $org = new Organization();
        $STH = $org->DBH->prepare("
              insert into user_organizations
              set org_name = :org_name,
                  org_type = :org_type,
                  plan = :plan,
                  max_users = :max_users,
                  plan_level = :plan_level
              ");

        $STH->execute(array(
                           ':org_name'   => $name,
                           ':org_type'   => $type,
                           ':plan'       => $plan,
                           ':max_users'  => $max_users,
                           ':plan_level' => $plan_level,
                      ));

        $org->org_id     = $org->DBH->lastInsertId();
        $org->org_name   = $name;
        $org->org_type   = $type;
        $org->plan       = $plan;
        $org->max_users  = $max_users;
        $org->plan_level = $plan_level;

        return $org;
    }

    /**
     * Find organization
     *
     * @author  Will
     *
     */
    public static function find($id)
    {
        $organization = new Organization();

        $STH = $organization->DBH->prepare('select * from user_organizations where org_id = :id');
        $STH->execute(array(
                           ':id' => $id
                      ));

        if ($STH->rowCount() === 1) {
            $org = $STH->fetch();

            /*
            * load that org data into this object
            */
            foreach ($org as $key => $value) {
                $organization->$key = $value;
            }

            return $organization;
        } else {
            throw new OrganizationException("Organization id:$id not found", 404);
        }
    }

    /**
     * @author  Will
     */
    public function __construct() {
        $this->_features = new Features;
        parent::__construct();
    }

    /**
     * Find the first organization with this chargify ID
     *
     * @param $chargify_id
     *
     * @throws OrganizationNotFoundException
     * @return \Organization
     * @author  Will
     */
    public static function findByChargifyID($chargify_id)
    {
        $DBH = DatabaseInstance::DBO();

        $STH = $DBH->prepare("
            SELECT * from user_organizations
            WHERE chargify_id = :chargify_id
            LIMIT 1
        ");

        $STH->execute(
            array(
                 ':chargify_id' => $chargify_id
            )
        );

        if ($STH->rowCount() == 0) {
            throw new OrganizationNotFoundException('No organization found with chargify customer id ' . $chargify_id);
        }

        $organization = new Organization();
        $organization->loadDBData($STH->fetch());

        return $organization;
    }

    /**
     * Alias function to add component
     *
     * @author  Will
     */
    public function addChargifyComponent()
    {
        $this->changeChargifyComponent('+');
    }

    /**
     * @param Models\Tailwind\Feature $feature
     *
     * @param bool    $search_recursively
     *                If set to true, we'll look for the feature setting in
     *                the organization and plan
     *
     * @return Models\Tailwind\Feature
     */
    public function getFeature($feature,$search_recursively = false)
    {
        if (!($feature instanceof Feature)) {
            $name = $feature;
            $feature = Feature::where('name' ,'=', $name)->get()->first();

            if (!$feature) {
                Log::warning("Looking for a feature that doesn't exist: $name");
                return false;
            }
        }

        if ($this->_features->has($feature->feature_id)) {
            Log::debug('Using feature cache for '.$feature->feature_id);

            return $this->_features->get($feature->feature_id);
        }

        $organization_feature =
            OrganizationFeature::where('org_id','=',$this->org_id)
                       ->where('feature_id','=',$feature->feature_id)
                       ->get()
                       ->first();

        if ($organization_feature) {

            $feature->value = $organization_feature->value;
            $feature->specificity = Feature::SPECIFICTY_ORG;
            $this->_features->add($feature);

            return $feature;
        }

        if($search_recursively) {
            return $this->plan()->getFeature($feature,$search_recursively);
        }

        return false;
    }

    /**
     * Determines whether this plan has a given feature.
     *
     * @author Daniel
     * @author Will
     *
     * @param string | Feature $feature
     *
     * @return bool
     */
    public function hasFeature($feature)
    {
        return $this->getFeature($feature,true)->isEnabled();
    }

    /**
     * Determines the limit for a feature of this plan.
     *
     * @author Daniel
     * @author Will
     *
     * @param Feature | string $feature
     *
     * @return int
     */
    public function maxAllowed($feature)
    {
        return $this->getFeature($feature,true)->maxAllowed();
    }


    /**
     * @param $feature
     *
     * @return bool|int
     */
    public function featureValue($feature)
    {
        return $this->getFeature($feature,true)->value;
    }

    /**
     * Enables a feature for this organization.
     *
     * @author Janell
     *
     * @param string|Feature $feature
     *
     * @return bool
     */
    public function enableFeature($feature)
    {
        return $this->editFeature($feature, 1);
    }

    /**
     * Disables a feature for this organization.
     *
     * @author Janell
     *
     * @param string|Feature $feature
     *
     * @return bool
     */
    public function disableFeature($feature)
    {
        return $this->editFeature($feature, 0);
    }

    /**
     * Changes the value of a feature for this organization.
     *
     * @author Janell
     *
     * @param string|Feature $feature
     * @param int|string $value
     *
     * @return bool
     */
    public function editFeature($feature, $value = 0)
    {
        $org_feature = $this->getFeature($feature);
        if ($org_feature instanceof OrganizationFeature) {
            $org_feature->value = $value;
            $org_feature->insertUpdate();
        } else {
            $org_feature = new OrganizationFeature();
            $org_feature->value      = $value;
            $org_feature->feature_id = $feature->feature_id;
            $org_feature->org_id     = $this->org_id;
            $org_feature->insertUpdate();

            $this->_features->add($org_feature);
        }

        return true;
    }

    /**
     * @author  Will
     *
     * @param         $account_name
     * @param Profile $profile
     *
     * @param         $account_type
     * @param         $industry_id
     *
     * @return  UserAccount
     */
    public function addUserAccount(
        $account_name,
        Profile $profile,
        $account_type,
        $industry_id
    )
    {
        return UserAccount::create(
                          $profile,
                          $this->org_id,
                          'user',
                          $account_type,
                          $account_name,
                          $industry_id
        );
    }

    /**
     * Gets the user in the organization who we attribute billing actions with
     * aka the user who is S or not invited by nobody, yo
     */
    public function billingUser()
    {
        $STH = $this->DBH->prepare("
           select * from users
           where org_id = :org_id
           and (invited_by IS NULL or invited_by = 0)
           LIMIT 1
       ");

        $STH->execute(
            array(
                 ':org_id' => $this->org_id,
            )
        );

        if ($STH->rowCount() == 0) {
            throw new UserNotFoundException('There is no billing user!');
        }

        $user = new User();
        $user->loadDBData($STH->fetch());

        return $user;
    }

    /**
     * Connects with Chargify and changes the number of components
     * by 1 (up or down) and makes the correct amount of adjustments
     * per the number of days in billing period
     *
     * @author   Will
     *
     *
     */
    public function changeChargifyComponent($add_or_subtract)
    {
        /*
         * Get the current number of components this customer has
         */
        $number_of_accounts = $this->connectedUserAccounts('active')->count();

        /*
         * Getting number of components (additional accounts)
         * by looking at total accounts and subtracting the main account that's included with
         * their plan
         */
        if($number_of_accounts > 0){
            $number_of_accounts--;
        }

        $subscription = $this->subscription();

        $components                     = new ChargifyQuantityBasedComponent();
        $components->allocated_quantity = $number_of_accounts;
        $components->update($subscription->id, $this->plan()->componentSlug());

        /*
         * Get detailed array of component pricing structure
         */
        $connector    = new ChargifyConnector();
        $comp_details = json_decode(
            $connector->retrieveComponentByComponentId(
                      Config::get('chargify.product_family'), $this->plan()->componentSlug(), ".json")
        );

        /*
         * If we are adding an account, we want the number of active accounts currently in the
         * user_accounts table (since we've just added their new account just prior to this)
         */
        if ($add_or_subtract == '+') {
            $quanity = $number_of_accounts;
        } else {
            /*
             * We need a quantity to figure out the right component price to prorate.
             * If we are removing an account, we want to prorate the component price of the
             * component we've just deleted from the table.  Therefore, we want to look at the
             * price of the component one above the number of active accounts currently in the
             * table.
             */
            $quanity = $number_of_accounts++;
        }

        /*
         * Update the number of components in chargify
         */
        $component_price = 0.00;

        foreach ($comp_details->component->prices as $range) {

            if ($quanity == 1 && $range->starting_quanity == 1) {
                $component_price = $range->unit_price;
                break;
            }

            if ($quanity <= $range->ending_quantity
                && $quanity >= $range->starting_quantity - 1
            ) {
                $component_price = $range->unit_price;
                break;
            }
        }

        /*
         * Calculate Prorated charge of component
         */
        $days_left_curr_bill_period =
            floor((strtotime($subscription->current_period_ends_at)
                      - time()) / 60 / 60 / 24);

        $days_in_curr_bill_period =
            number_format(
                (strtotime($subscription->current_period_ends_at)
                    - strtotime($subscription->current_period_started_at)
                ) / 60 / 60 / 24, 2
            );

        if ($days_in_curr_bill_period < 28) {
            $days_in_curr_bill_period = date("t");
            Log::notice('Changing number of components during trial.', $subscription);
        };

        $component_price_prorated = number_format(($days_left_curr_bill_period
            / $days_in_curr_bill_period
            * $component_price), 2);

        /*
         * Determine if we are crediting or charging
         */
        switch ($add_or_subtract) {
            case '+':
                $memo = 'Added Account: prorated charge';
                break;

            case '-':
                $component_price_prorated = -$component_price_prorated;
                $memo                     = 'Removed Account: prorated charge';
                break;
        }

        $adjustment         = new ChargifyAdjustment();
        $adjustment->amount = $component_price_prorated;
        $adjustment->memo   = $memo;
        $adjustment->createByAmount($subscription->id);


        /*
         * Update the component count in the user_organizations table
         */
        $this->component_count = $number_of_accounts;
        $STH                   = $this->DBH->prepare('
                                update user_organizations
                                set component_count = :component_count
                                where org_id = :org_id'
        );

        $STH->execute(
            array(
                 ':component_count' => $this->component_count,
                 ':org_id'          => $this->org_id
            )
        );
    }

    /**
     * Changes Plan
     *
     * @author   Will
     *
     * @param      $plan_id
     * @param bool $ignore_prorate
     * @param bool $ignore_chargify
     *
     * @throws PlanException
     * @throws OrganizationException
     * @return $this
     */
    public function changePlan(
        $plan_id,
        $ignore_prorate = false,
        $ignore_chargify = false
    )
    {

        $old_plan_id = $this->plan;

        Log::info('Changing plan for org_id: '.$this->org_id,$this);

        /*
         * Can't change the plan id unless they have an account with
         * chargify
         */
        if (!$ignore_chargify AND empty($this->chargify_id)) {
            Log::error('Trying to change plan with no chargify id');
            throw new OrganizationException('There is no chargify id');
        }

        Log::debug("Using chargify id: $this->chargify_id",$this);

        /*
         * If we are trialing we are going to ignore prorating regardless
         */
        if (!$ignore_chargify AND $this->subscription()->state == 'trialing') {

            Log::debug('User in still in trial while changing plan, so we are NOT prorating', $this->subscription());
            $ignore_prorate = true;
        }

        if (!$ignore_chargify) {
            Log::debug('Current subscription state:' . $this->subscription()->state, $this);
            /*
             * Before we change the plan we need to
             * remove all the components so we can add in new ones based on their new plan later
             */
            try {
                $components                     = new ChargifyQuantityBasedComponent();
                $components->allocated_quantity = 0;

                $components->update($this->subscription()->id, Config::get('chargify.lite_account_component_id'));
                Log::debug('Updated lite account components to 0', $components);

                $components->update($this->subscription()->id, Config::get('chargify.pro_account_component_id'));
                Log::debug('Updated pro account components to 0', $components);
            }

            catch (ChargifyException $e) {
                Log::error($e);
            }

            /*
             * Before we change the track types, we need to Prorate the account
             * down to FREE. This just simplifies things
             * So we take the monthly total and prorate
             */
            if (!$ignore_prorate) {
                $prorated_amount = $this->proratedMonthlyTotal();
            }
        }

        /*
         * When we come from a free plan to a paid plan (or vice versa)
         * we need to change the track type of the user_accounts and the
         * status tables (profiles/pins/followers/domains/boards)
         */
        if ($this->plan == 1 && $plan_id > 1) {

            /*
             * When we go from FREE to PAID
             */
            $track_type = 'user';

            // If upgrading from free -> paid, requeue their engagement calls so they get pulled correctly.
            foreach ($this->connectedUserAccounts('active') as $profile) {
                shell_exec(
                    'php ' . base_path() .
                    "/engines/calculations/requeue_engagement.php $profile->user_id > /dev/null 2>&1 &"
                );
            }

            Log::debug('Re-queuing Engagement for user who just upgraded from free to paid');

        } elseif ($this->plan > 1 && $plan_id == 1) {
            /*
             * When we go from PAID to FREE
             */
            $track_type = 'free';

        } elseif ($this->plan == $plan_id) {

            throw new PlanException('Cant change to the same plan');

        } else {
            $track_type = 'user';
        }

        /*
         * We only want to change accounts that aren't orphaned
         * nor are competitors
         */
        try {

            foreach ($this->connectedUserAccounts() as $user_account) {
                /** @var $user_account UserAccount */
                if (
                    $user_account->track_type != 'orphan'
                    AND ($user_account->competitor_of == 0 OR $user_account->competitor_of == null)
                ) {
                    $user_account->changeTrackType($track_type);
                }
            }
        }
        catch (Exception $e) {

            Log::error($e);

        }

        $this->setPlanMaximums($plan_id);

        /*
         * Change the plan
         */
        //@todo move this to insertUpdate method or saveToDB method
        $this->plan = $plan_id;
        $STH        = $this->DBH->prepare('
                                update user_organizations
                                set plan = :plan, max_users = :max_users
                                where org_id = :org_id'
        );

        $STH->execute(
            array(
                 ':plan'      => $this->plan,
                 ':org_id'    => $this->org_id,
                 ':max_users' => $this->max_users
            )
        );

        /*
         * Update plan with chargify
         * we use "force update" on the plan method
         * so that it looks the plan up again and gets the
         * correct chargify id
         */
        try {

            if (!$ignore_chargify) {
                $product     = new ChargifyProduct();
                $product->id = $this->plan('force update')->chargify_plan_id;
                $new_product = $product->getByID();

                $updated_subscription = $this->subscription()->updateProduct($new_product);

                Session::set('subscription', $updated_subscription);

                Log::debug(
                   'Updated plan in chargify to new one, and updated the Session subscription',
                   $updated_subscription
                );

                $this->parseSubscription($updated_subscription);

                if($this->subscription_state == "trialing"){
                    if($plan_id == Plan::FREE_PLAN_ID){
                        $this->trial_stopped_at = time();
                        Log::notice('User has downgraded during trial', $this->subscription());
                    } else if($old_plan_id == Plan::FREE_PLAN_ID){
                        Log::notice('User has re-upgraded their plan during a trial', $this->subscription());
                    }
                }



                /*
                 * Now that we have changed the plan, if it's not a free account we need
                 * to re-add the correct components
                 * and prorate the plan again
                 *
                 * we use the force update on the subscription so that the new subscription
                 * is loaded into the _subscription cache
                 */
                if ($this->plan > 1) {
                    $components = new ChargifyQuantityBasedComponent();

                    $components->allocated_quantity =
                        $this->connectedUserAccounts('active')->count() - 1;

                    $components->update(
                               $this->subscription('force update')->id,
                               $this->plan('force update')->componentSlug()
                    );

                    if (!$ignore_prorate) {
                        $prorated_amount = $prorated_amount - $this->proratedMonthlyTotal();
                    }
                }
            }

            /*
             * Make the adjustment with chargify
             */
            if (!$ignore_prorate) {
                $adjustment         = new ChargifyAdjustment();
                $adjustment->amount = -$prorated_amount;
                $adjustment->memo   =
                    "Prorated monthly charge when switching to " .
                    $this->plan()->name . " plan";
                $adjustment->createByAmount($this->subscription()->id);
            }
        }
        catch (ChargifyException $e) {
            Log::error($e);
        }

        $this->is_legacy = 0;

        /*
         * Update user_organizations table with new values
         */
        $this->insertUpdateDB();

        $this->updateUserAccountLimits();

        // Update MailChimp subscription information.
        try {
            $mailchimp = MailchimpWrapper::instance();

            foreach ($this->users() as $user) {
                $subscribed = $user->getEmailPreference(
                    UserEmail::MAILCHIMP_BLOG_RSS,
                    $this->primaryAccount()->user_id
                );
                if ($subscribed) {
                    $mailchimp->updateListMember(Config::get('mailchimp.BLOG_RSS_LIST_ID'), $user);
                }
            }
        } catch (Exception $e) {}

        /*
         * Make it chainable
         */

        return $this;
    }

    /**
     * @author  Will
     *
     * @param string $track_type
     *
     * @param bool   $use_cache
     *
     * @return UserAccounts
     */
    public function connectedUserAccounts($track_type = 'all',$use_cache = false)
    {
        switch ($track_type) {

            default:
            case 'all':

                $STH = $this->DBH->prepare('
                   select * from user_accounts where org_id = :org_id
                ');

                $STH->execute(
                    array(
                         ':org_id' => $this->org_id,
                    )
                );

                break;

            case 'active':
                $STH = $this->DBH->prepare('
                    select * from user_accounts where org_id = :org_id
                   and track_type != :orphan
                   and (competitor_of = 0 or competitor_of is NULL)
                ');

                $STH->execute(
                    array(
                         ':org_id' => $this->org_id,
                         ':orphan' => UserAccount::TRACK_TYPE_ORPHAN
                    )
                );

                break;

            case UserAccount::TRACK_TYPE_USER:
                /*
                 * THIS SHOULD NOT BE USED
                 */
                $STH = $this->DBH->prepare('
                   select * from user_accounts where org_id = :org_id
                   and track_type = :type
                   and (competitor_of =0 or competitor_of is NULL)
                ');

                $STH->execute(
                    array(
                         ':org_id' => $this->org_id,
                         ':type'   => $track_type
                    ));

                break;

            case UserAccount::TRACK_TYPE_COMPETITOR:
                $STH = $this->DBH->prepare('
                   select * from user_accounts where org_id = :org_id
                   and track_type != :orphan
                   and competitor_of != 0
                   and competitor_of is not NULL
                ');

                $STH->execute(
                    array(
                         ':org_id' => $this->org_id,
                         ':orphan' => UserAccount::TRACK_TYPE_ORPHAN
                    ));

                break;

            case UserAccount::TRACK_TYPE_ORPHAN:
            case UserAccount::TRACK_TYPE_FREE:

                $STH = $this->DBH->prepare('
                    select * from user_accounts where org_id = :org_id
                   and track_type = :type
                ');

                $STH->execute(
                    array(
                         ':org_id' => $this->org_id,
                         ':type'   => $track_type
                    ));

                break;
        }

        $accounts = new UserAccounts();

        if ($STH->rowCount() != 0) {
            foreach ($STH->fetchAll() as $rowData) {
                $account = UserAccount::createFromDBData($rowData);

                if(isset($rowData->industry)) {
                    $account->preLoad('industry',$rowData,'UserIndustry');
                }

                $accounts->add($account);
            }
        }

        return $accounts;
    }

    /**
     * This will check the cache for the connected user accounts, if they are there
     * @author  Will
     */
    public function getCachedConnectedUserAccounts($track_type = 'all') {

        if (Session::has('user_accounts')) {
            return Session::get('user_accounts');
        }
        return false;
    }

    /**
     * @author  Will
     *
     * @param bool $dont_use_cache If you need to run the query against the DB again or not
     *
     * @return bool|UserAccounts
     */
    public function activeUserAccounts($dont_use_cache = false)
    {
        if ($this->_active_user_accounts AND !$dont_use_cache) {
            return $this->_active_user_accounts;
        }

        return $this->_active_user_accounts = $this->connectedUserAccounts('active');
    }

    /**
     * @author  Will
     *
     * @param bool $dont_use_cache If you need to run the query against the DB again or not
     *
     * @return bool|UserAccounts
     */
    public function competitorAccounts($dont_use_cache = false)
    {
        if ($this->_competitor_user_accounts AND !$dont_use_cache) {
            return $this->_competitor_user_accounts;
        }

        return $this->_competitor_user_accounts = $this->connectedUserAccounts(UserAccount::TRACK_TYPE_COMPETITOR);
    }

    /**
     * @author  Will
     * @return bool
     */
    public function hasCompetitors()
    {
        if ($this->totalCompetitorsAdded() > 0) {
            return true;
        }

        return false;
    }

    /**
     * @author  Will
     *
     * @param $account_id
     *
     * @throws UserAccountException
     * @return UserAccount
     */
    public function getUserAccount($account_id)
    {
        $STH = $this->DBH->prepare(
                         "select *
                         from user_accounts
                         where account_id = :account_id
                         AND org_id = :org_id
                         AND track_type = 'user'"
        );

        $STH->execute(
            array(
                 ':account_id' => $account_id,
                 ':org_id'     => $this->org_id
            )
        );

        if ($STH->rowCount() == 0) {
            throw new UserAccountException('User account not found');
        }

        $user_account = new UserAccount();
        $user_account->loadDBData($STH->fetch());

        return $user_account;
    }

    /**
     * @author  Will
     * @return bool
     */
    public function hasCreditCardOnFile()
    {
        if ($this->chargify_id == 0
            || $this->chargify_id == 1
            || $this->chargify_id == ""
        ) {
            return false;
        }

        return true;
    }

    /**
     * @author  Will
     */
    public function hasGoogleAnalytics($responseType = 'bool')
    {
        $STH = $this->DBH->query("
              SELECT account_id
              FROM status_traffic
              WHERE org_id = '$this->org_id'
              ORDER BY timestamp desc limit 1 "
        );

        $rows = $STH->rowCount();

        switch ($responseType) {
            default:
            case 'bool':

                if ($rows > 0) {
                    return true;
                }

                return false;

                break;

            case 'string':

                if ($rows > 0) {
                    return 'YES';
                }

                return 'NO';

                break;
        }
    }

    /**
     * @author  Will
     */
    public function hasUserAccount($username)
    {
        $STH = $this->DBH->prepare(
                         "select account_id
                         from user_accounts
                         where username = :username
                         AND org_id = :org_id
                         AND track_type = 'user'"
        );

        $STH->execute(
            array(
                 ':username' => $username,
                 ':org_id'   => $this->org_id
            )
        );

        if ($STH->rowCount() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Load up the primary account if we already have the data
     *
     * @author  Will
     */
    public function loadPrimaryAccount(UserAccount $primary_account)
    {
        $this->_primary_account = $primary_account;

        return $this;
    }

    /**
     * Returns the monthly total based on
     * number of accounts
     * plan id
     *
     * @author  Will
     *
     */
    public function monthlyTotal()
    {
        $connector    = new ChargifyConnector();
        $comp_details = json_decode(
            $connector->retrieveComponentByComponentId(
                      Config::get('chargify.product_family'), $this->plan()->componentSlug(), ".json")
        );

        $comp_total_price = 0;
        $quantity         = $this->connectedUserAccounts('user')->count() - 1;

        foreach ($comp_details->component->prices as $range) {

            if ($quantity > $range->ending_quantity) {
                $comp_total_price += $range->unit_price * ($range->ending_quantity - $range->starting_quantity + 1);
            } else if ($quantity >= $range->starting_quantity - 1) {
                $comp_total_price += $range->unit_price * ($quantity - $range->starting_quantity + 1);
            }

        }
        $base_price = $this->subscription()->product_price_in_cents / 100;

        return $comp_total_price + $base_price;
    }

    /**
     * Get the plan of the organization is on
     *
     * @param bool $force_load
     *
     * @return Plan
     */
    public function plan($force_load = false)
    {
        if (!$this->_plan or $force_load) {
            if ($this->plan === null) {
                $this->plan = 1;
            }
            $this->_plan = Plan::find($this->plan);
        }

        if($this->is_legacy) {
            $this->_plan->useLegacy();
        }

        return $this->_plan;
    }

    /**
     * Main User Account (pinterest profile)
     * At some point we'll have multiple user accounts, so we want an object
     * that is only the main account for tracking purposes
     *
     * @author  Will
     *
     * @param bool $forced_update
     *
     * @throws UserAccountException
     * @return UserAccount
     */
    public function primaryAccount($forced_update = false)
    {
        if ($this->_primary_account AND !$forced_update) {
            return $this->_primary_account;
        }

        if ($account = $this->reloadPrimaryAccount()) {
            return $account;
        } else {
            throw new UserAccountException('No primary account attached to ' . $this->org_name . '(' . $this->org_id . ')');
        }
    }

    /**
     * Calculates the prorated monthly charge based on
     * number of accounts
     * plan id
     *
     * @author  Alex
     * @author  Will
     *
     */
    public function proratedMonthlyTotal()
    {
        $subscription = $this->subscription();

        $days_left_curr_bill_period =
            floor((strtotime($subscription->current_period_ends_at)
                      - time()) / 60 / 60 / 24);

        $days_in_curr_bill_period =
            number_format(
                (strtotime($subscription->current_period_ends_at)
                    - strtotime($subscription->current_period_started_at)
                ) / 60 / 60 / 24, 2
            );

        if ($days_in_curr_bill_period < 28) {
            $days_in_curr_bill_period = 31;
        };

        $proratedMonthlyTotal = number_format(($days_left_curr_bill_period
            / $days_in_curr_bill_period
            * $this->monthlyTotal()), 2);

        return $proratedMonthlyTotal;
    }

    /**
     * Reload Primary account
     *
     * @author  Will
     *
     * @see     get_primary_account()
     *
     */
    public function reloadPrimaryAccount()
    {
        $STH = $this->DBH->prepare("
                select *
                from user_accounts
                where org_id = :org_id and (track_type= 'user' or track_type= 'free')
                and (competitor_of= 0 or competitor_of is NULL) limit 1
            ");

        $STH->execute(array(
                           ':org_id' => $this->org_id
                      ));

        $account = $STH->fetch();

        if ($STH->rowCount() > 0) {
            return UserAccount::createFromDBData($account);
        }

        return false;
    }

    /**
     * Alias function to remove a component
     *
     * @author  Will
     */
    public function removeChargifyComponent()
    {
        $this->changeChargifyComponent('-');
    }

    /**
     * @author  Will
     */
    public function saveToDB($insert_type = 'INSERT INTO', $append = false)
    {
        $append = 'ON DUPLICATE KEY UPDATE ';

        foreach ($this->columns as $column) {
            $append .= "$column = VALUES($column),";
        }

        $append = rtrim($append, ',');

        parent::saveToDB('INSERT INTO', $append);

        return $this;
    }

    /**
     * Fetches the subscription by the customer chargify ID
     *
     * @author  Will
     *
     * @param bool $force_update
     *
     * @return ChargifySubscription
     */
    public function subscription($force_update = false)
    {
        if (!$force_update) {
            if (Session::has('subscription')) {
                $this->_subscription = Session::get('subscription');
            }
        }

        if ($this->_subscription AND !$force_update) {
            return $this->_subscription;
        } else {

            $subscription              = new ChargifySubscription();
            $subscription->customer_id = $this->chargify_id;
            $subscriptions             = $subscription->getByCustomerID();

            $this->_subscription = $subscriptions[0];

            $this->parseSubscription($this->_subscription);
            $this->insertUpdateDB();

            Session::set('subscription', $this->_subscription);

            return $this->_subscription;
        }
    }

    /**
     * Epoch time representation of when their trial ends with chargify
     * Returns 0 if they don't have a chargify subscription
     *
     * @author  Will
     */
    public function trialEndDate()
    {
        if ($this->hasCreditCardOnFile()) {

            return strtotime($this->subscription()->trial_ended_at);
        }

        return 0;
    }

    /**
     * @author  Alex
     *
     * @return Users / Customers
     */
    public function users()
    {
        $STH = $this->DBH->prepare('
               select * from users where org_id = :org_id
            ');
        $STH->execute(array(
                           ':org_id' => $this->org_id,
                      ));


        $users = new Users();

        if ($STH->rowCount() != 0) {
            foreach ($STH->fetchAll() as $row) {
                $user = User::createFromDBData($row);
                $users->add($user);
            }
        }

        return $users;
    }

    /**
     * Set plans maximums value depending on a given plan id
     *
     * @author  Will
     *
     * @param $plan_id
     *
     * @return $this
     */
    protected function setPlanMaximums($plan_id) {

        switch ($plan_id) {
            default:
            case Plan::FREE_PLAN_ID:
                $this->max_users    = Organization::FREE_MAX_USERS;
                $this->max_accounts = Organization::FREE_MAX_ACCOUNTS;

                break;

            case Plan::LITE_PLAN_ID:
                $this->max_users    = Organization::LITE_MAX_USERS;
                $this->max_accounts = Organization::LITE_MAX_ACCOUNTS;

                break;

            case Plan::PRO_PLAN_ID:
                $this->max_users    = Organization::PRO_MAX_USERS;
                $this->max_accounts = Organization::PRO_MAX_ACCOUNTS;

                break;
        }

        return $this;
    }

    /**
     * @author  Will
     */
    public function signupDate()
    {

        if (empty($this->created_at)) {

            $event = UserHistory::find(
                                array(
                                     'type'    => UserHistory::SIGNUP,
                                     'cust_id' => $this->billingUser()->cust_id
                                )
            );

            if ($event) {

                if (is_array($event)) {
                    $event = $event[0];
                }

                $this->created_at = $event->timestamp;
            } else {
                $accounts = $this->connectedUserAccounts();
                $accounts->sortBy('created_at');

                $this->created_at = $accounts->first()->created_at;
            }

            $this->insertUpdateDB();
        }

        return $this->created_at;

    }

    /**
     * @author  Will
     * @throws OrganizationException
     */
    public function daysSinceSignedUp()
    {
        if (!$signup_date = $this->signupDate()) {
            Log::warning('No signup date found');
            return 0;
        }

        $date = Carbon::createFromFormat('U', $signup_date);

        return $date->diffInDays(Carbon::now());
    }

    /**
     * Expects trial start at in epoch time
     *
     * @author  Will
     */
    public function daysSinceTrialStarted()
    {
        if (empty($this->trial_start_at)) {
            return -1;
        }
        $date = Carbon::createFromFormat('U', $this->trial_start_at);

        return $date->diffInDays(Carbon::now());
    }

    /**
     * Expects trial date at in epoch time
     * @author  Will
     */
    public function daysSinceTrialEnded() {
        if (empty($this->trial_end_at)) {
            return -1;
        }
        $date = Carbon::createFromFormat('U', $this->trial_end_at);
        return $date->diffInDays(Carbon::now());
    }

    /**
     * Expects trial date at in epoch time
     * @author  Will
     */
    public function daysSinceTrialConverted()
    {
        if (empty($this->trial_converted_at)) {
            return -1;
        }
        $date = Carbon::createFromFormat('U', $this->trial_converted_at);

        return $date->diffInDays(Carbon::now());
    }

    /**
     * Expects trial date at in epoch time
     *
     * @author  Will
     */
    public function daysSinceTrialStopped()
    {
        if (empty($this->trial_stopped_at)) {
            return -1;
        }

        $date = Carbon::createFromFormat('U', $this->trial_stopped_at);

        return $date->diffInDays(Carbon::now());
    }

    /**
     * @Author  Will
     * @return float|int
     */
    public function daysBeforeTrialStarted(){
        $seconds_before_trial = $this->signupDate() - $this->trial_start_at;

        if ($seconds_before_trial > 0) {
            return round($seconds_before_trial / 86400); // seconds in a day
        }
        return -1;

    }

    /**
     * @return float
     */
    public function averageBillAmount()
    {
        if (empty($this->billing_event_count)) {
            return 0;
        }

        return round($this->total_amount_billed / $this->billing_event_count, '2');
    }

    /**
     * @author  Will
     * @todo    make this more performant
     * @return int
     */
    public function numberOfAccounts()
    {
        return $this->connectedUserAccounts('active')->count();
    }

    /**
     * @author  Will
     *
     * @return int
     */
    public function totalCompetitorsAdded() {

        $account_ids = $this->activeUserAccounts()->stringifyField('account_id');

        $STH = $this->DBH->prepare("
            SELECT COUNT(*) as count
            FROM user_accounts
            WHERE competitor_of
            IN ($account_ids)
            AND track_type != 'orphan'
        ");

        return $STH->fetch()->count;
    }

    /**
     * @author Will
     *
     * @return int
     */
    public function totalKeywordsAdded() {

        $account_ids = $this->activeUserAccounts()->stringifyField('account_id');

        $STH = $this->DBH->prepare("
            SELECT COUNT(*) as count
            FROM user_accounts_keywords
            WHERE account_id
            IN ($account_ids)
        ");

        return $STH->fetch()->count;
    }

    /**
     * @author  Will
     *
     * @return int
     */
    public function totalDomainsAdded() {
        $account_ids = $this->activeUserAccounts()->stringifyField('account_id');

        $STH = $this->DBH->prepare("
            SELECT COUNT(*) as count
            FROM user_accounts_domains
            WHERE account_id
            IN ($account_ids)
        ");

        return $STH->fetch()->count;
    }

    /**
     * Parse the Chargify subscription to our Org object
     *
     * @author  Will
     *
     * @param $subscription
     *
     * @return $this
     */
    public function parseSubscription ($subscription) {

        $this->subscription_state  = $subscription->state;
        $this->trial_start_at      = strtotime($subscription->trial_started_at);
        $this->trial_end_at        = strtotime($subscription->trial_ended_at);
        $this->total_amount_billed = $subscription->total_revenue_in_cents / 100;
        $this->coupon_code         = $subscription->coupon_code;

        return $this;

    }

    /**
     * Updates all connected user accounts user_account keyword and domain limits
     * to the default limits
     *
     * @author  Will
     */
    protected function updateUserAccountLimits() {
        $user_accounts = $this->connectedUserAccounts();

        /** @var $user_account UserAccount */
        foreach ($user_accounts as $user_account) {
            $user_account->setLimits($this->plan());
        }

        $user_accounts->insertUpdateDB();

        return $this;
    }

    /**
     * @author  Will
     *
     * @return Tasks
     */
    public function tasks() {
        $tasks = new Tasks();

        foreach (Tasks::$organization_task_names as $name) {
            $task = new Task($name);
            $task->setType(Task::TYPE_ORGANIZATION);
            $tasks->add($task);
        }

        foreach($this->activeUserAccounts() as $user_account) {
            $tasks->merge($user_account->tasks());
        }

        return $tasks;
    }
}

class OrganizationException extends DBModelException {}
class OrganizationNotFoundException extends OrganizationException {}
