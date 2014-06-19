<?php
/**
 * Alerts when things happen
 * sends via email
 *
 * @author  Will
 */

chdir(__DIR__);
set_time_limit(0);
ini_set('memory_limit','500M');
include '../../../bootstrap/bootstrap.php';

use Pinleague\CLI;

try {
    $now = date('g:ia');
    CLI::h1('Starting import (' . $now . ')');

    $DBH = DatabaseInstance::DBO();
    CLI::write('Connected to database');

    $DBH = DatabaseInstance::DBO();

    CLI::write('Creating Mixpanel instance');
    $mixpanel = Mixpanel::getInstance(Config::get('mixpanel.TOKEN'));

    CLI::write('Script is commented out because we shouldnt be doing this');
    /*
     * Get signup date
     * ---------------
     * Get earliest created at date of that organization's user_accounts
     *              ->where customer invited by = 0
     *              ->also competitor_of = 0 or NULL
     *
     * or just the earliest date? how could an org account exist without the org?
     *

    $signups = csv_to_array(ROOT_PATH . 'chargify_signup_events_clean.csv');

    $signed_up_org_ids = array();

    foreach ($signups as $signup) {
        try {

            $plan = Plan::findByChargfiyID($signup['plan_id']);

            /*
             * If there is no customer reference, they must have come directly from the signup
             * page. So this should count as a signup
            if (empty($signup['customer_reference'])) {

                CLI::write('Direct signup');

                $organization = Organization::findByChargifyID($signup['customer_id']);
                $user         = $organization->billingUser();

                $parameters = array(
                    'plan'     => $plan->name,
                    'imported' => true,
                );

                $user->recordEvent(
                     UserHistory::SIGNUP,
                     $parameters,
                     $description = false,
                     $signup['timestamp']
                );

                CLI::write('sending post to mixpanel');


                /*
                 * we don't have to do the trial start
                 * because we do that with the subscription data
                 */

                /*
                 * Add this to the list to check later
                $signed_up_org_ids[$organization->org_id] = $organization->org_id;

            } else {

                if($plan->plan_id == 1) {
                    continue;
                }

                CLI::write('Upgrade');
                if (!$user = User::find($signup['customer_reference'])) {
                    throw new UserNotFoundException('No such user');
                }

                $parameters = array(
                    'from_plan' => Plan::FREE_NO_CC,
                    'to_plan'   => $plan->name,
                    'imported'  => true,
                );

                $user->recordEvent(
                     UserHistory::UPGRADE,
                     $parameters,
                     $description = false,
                     $signup['timestamp']
                );

            }

        }
        catch (Exception $e) {
            CLI::alert($e->getMessage());
            continue;
        }
    }

    $STH = $DBH->query("
        select user_organizations.*, user_account.*
        from user_organizations
        LEFT JOIN (
          select * from user_accounts order by created_at ASC
        ) as user_account
        on user_organizations.org_id = user_account.org_id
        group by user_organizations.org_id;
    ");

    foreach ($STH->fetchAll() as $org) {
        try {

            if (in_array($org->org_id, $signed_up_org_ids)) {
                throw new Exception('Already signed up');
            }

            CLI::write('Recording signup date for org_id: ' . $org->org_id);
            $organization = new Organization();
            $organization->loadDBData($org);


            $user = $organization->billingUser();

            $parameters = array(
                'plan' => Plan::FREE_NO_CC
            );

            $user->recordEvent(
                 UserHistory::SIGNUP,
                 $parameters,
                 $description = false,
                 $org->created_at
            );
        }
        catch (Exception $e) {
            CLI::alert($e->getMessage());
            continue;
        }

    }

    /** */

    /*
     * Trial Start
     * -----------
     * Add trial start to org table
     * Use subscription data
     *          -> ignore customer_reference = 0
     *          -> use trial started at
     *
     * Trial End
     * ---------------
     * use subscription data to update org table with trial
     * Add user history of trial end
     *
     * Cancelled at
     * ------------
     * Use subscription data
     *      ->if there is something there, add it
     *      ->canceled_at
     *
     * Coupon
     * -------
     * Use subscription data
     *   ->if coupon is not null / blank add the code to org table
     *
     *
     * Upgrades + downgrades
     * ---------------
     * use subscription data
     * if product_name != FOREVER FREE Pinterest Analytics then upgrade from free-no-credit-card
     * to product_name

    $subscriptions = csv_to_array(ROOT_PATH . 'subscriptions.csv');

    $break_point_reached = false;
    foreach ($subscriptions as $subscription) {

        $cust_id = $subscription['customer_reference'];

        if($cust_id == '4264') {
            $break_point_reached = true;
        }

        if(!$break_point_reached) {
            continue;
        }

        CLI::h2('Customer ref: ' . $cust_id);

        if (empty($cust_id)) {
            CLI::alert('No reference');
            continue;
        }

        CLI::write('Recording trial start');

        $user = User::find($cust_id);

        if ($user) {

            $timestamp = strtotime($subscription['created_at']);

            if ($product_id = $subscription['product_id'] != Config::get('chargify.free_account_product_id')) {

                if ($product_id == Config::get('chargify.lite_account_product_id')) {
                    $to_plan = Plan::LITE;
                } else {
                    $to_plan = Plan::PRO;
                }

                $user->recordEvent(
                     UserHistory::UPGRADE,
                     $parameters = array(
                         'imported'  => true,
                         'from_plan' => Plan::FREE_NO_CC,
                         'to_plan'   => $to_plan
                     ),
                     $description = false,
                     $timestamp
                );
            }

            $trial_start = $subscription['trial_started_at'];
            $trial_end   = $subscription['trial_ended_at'];

            if (!empty($trial_start)) {

                $trial_start = strtotime($trial_start);

                $user->recordEvent(
                     UserHistory::TRIAL_START,
                     $parameters = array(
                         'imported' => true,
                         'plan'     => Plan::FREE_WITH_CC
                     ),
                     $description = false,
                     $trial_start
                );
            }

            if (!empty($trial_end)) {

                $trial_end = strtotime($trial_end);

                $user->recordEvent(
                     UserHistory::TRIAL_END,
                     $parameters = array(),
                     'Imported trial end from Chargify data',
                     $trial_end
                );
            }

            $cancelled_time = $subscription['canceled_at'];
            if (!empty($cancelled_time)) {
                $cancelled_time = strtotime($cancelled_time);

                $user->recordEvent(
                     UserHistory::CANCELLED_SUBSCRIPTION,
                     $parameters = array(
                         'imported' => true
                     ),
                     $description = false,
                     $cancelled_time
                );
            }

            $coupon_code = $subscription['coupon'];
            if (!empty($coupon_code)) {
                $user->organization()->coupon_code = $coupon_code;
                $user->organization()->insertUpdateDB();

                ///set people parameter for mixpanel here
                $mixpanel->people->set(
                                 $user->cust_id,
                                 array('coupon' => $coupon_code)
                );
            }
        }
    }

    /** */

    /*
     * Upgrades / Downgrades
     * --------------------
     * use product change events
    $product_change_events = csv_to_array(ROOT_PATH . 'chargify_product_change_events_clean.csv');

    foreach ($product_change_events as $product_change) {
        $user = User::find($product_change['cust_id']);

        if ($user) {

            $from_plan_product_id = $product_change['From Plan'];
            $to_plan_product_id   = $product_change['To Plan'];
            $timestamp            = $product_change['timestamp'];
            $upgrade              = false;

            switch ($from_plan_product_id) {
                default:
                case Config::get('chargify.free_account_product_id'):
                    $from_plan = Plan::FREE_WITH_CC;
                    $upgrade   = true;
                    break;

                case Config::get('chargify.lite_account_product_id'):
                    $from_plan = Plan::LITE;
                    break;

                case Config::get('chargify.pro_account_product_id'):
                    $from_plan = Plan::PRO;
                    break;

                case Config::get('chargify.agency_account_product_id'):
                    $from_plan = Plan::AGENCY;
                    break;

            }

            switch ($to_plan_product_id) {

                default:
                case Config::get('chargify.free_account_product_id'):

                    $to_plan = Plan::FREE_WITH_CC;

                    break;

                case Config::get('chargify.lite_account_product_id'):
                    $to_plan = Plan::LITE;

                    if ($from_plan == Plan::FREE_WITH_CC) {
                        $upgrade = true;
                    }

                    break;

                case Config::get('chargify.pro_account_product_id'):
                    $to_plan = Plan::PRO;
                    $upgrade = true;
                    break;


                case Config::get('chargify.agency_account_product_id'):
                    $to_plan = Plan::AGENCY;
                    $upgrade = true;

                    break;
            }

            if ($upgrade) {
                $event = UserHistory::UPGRADE;

            } else {
                $event = UserHistory::DOWNGRADE;
            }

            $parameters = array(
                'to_plan'   => $to_plan,
                'from_plan' => $from_plan,
                'imported'  => true
            );
            $user->recordEvent(
                 $event,
                 $parameters,
                 $description = false,
                 $timestamp
            );
        }
    }
    /** */

    /*
     *
     * Billing Events
     * --------------
     *
     * add memo
     *
     * if type == payment {
     * use transactions data
     * if success = TRUE
     * where type is payment, add amount in cents as billed (Convert to 1$)
     *
     * if success = FALSE
     * add failed billing event
     * }
     *
     * if type == refund
     * add negative billing event
    $transactions = csv_to_array(ROOT_PATH . 'transactions-clean.csv');

    ///need to sort the transactions by date decending so they are chronological
    $dates = array();
    foreach ($transactions as $key => $row) {
        $dates[$key] = strtotime($row['created_at']);
    }
    array_multisort($dates, SORT_ASC, $transactions);

    foreach ($transactions as $transaction) {

        $user = User::find($transaction['cust_id']);

        if ($user) {

            $amount    = $transaction['amount_in_cents'] / 100;
            $timestamp = strtotime($transaction['created_at']);

            if ($transaction['type'] == 'payment') {
                if ($transaction['success'] == 'TRUE') {

                    if (empty($user->organization()->first_billing_event_at)) {

                        $parameters = array(
                            'imported' => true
                        );

                        /*
                         * check if timestamp is less than trial end date + 2 days (just to be safe)

                        if ($timestamp < ($user->organization()->trial_end_at + 172800)) {
                            $user->recordEvent(
                                 UserHistory::TRIAL_CONVERTED,
                                 $parameters,
                                 $description = false,
                                 $timestamp
                            );
                        }

                        $user->organization()->first_billing_event_at = $timestamp;
                        $days                                         = 0;

                    } else {

                        $seconds_since = $timestamp - $user->organization()->first_billing_event_at;
                        $days          = round($seconds_since / (60 * 60 * 24));

                    }

                    $user->organization()->billing_event_count++;
                    $user->organization()->insertUpdateDB();

                    $parameters = array(
                        'billing_event_count'            => $user->organization()->billing_event_count,
                        'days_since_first_billing_event' => $days,
                        'total_amount_billed'            => $amount,
                        'imported'                       => true
                    );

                    $user->recordEvent(
                         UserHistory::BILLED,
                         $parameters,
                             $description = false,
                         $timestamp
                    );

                }
                if ($transaction['success'] == 'FALSE') {

                    $parameters = array(
                        'imported' => true
                    );

                    $user->recordEvent(
                         UserHistory::BILLING_FAILED,
                         $parameters,
                             $description = false,
                         $timestamp
                    );

                }
            }

            if ($transaction['type'] == 'refund') {

                $parameters = array(
                    'total_amount_refunded' => -$amount,
                    'imported'              => true
                );

                $user->recordEvent(
                     UserHistory::REFUNDED,
                     $parameters,
                         $description = false,
                     $timestamp
                );

            }
        }
    }
    /*
    * Trial Converted
    * ---------------
    * After all history imported
    * If billed +- 860000 seconds of trial end, trial was converted
     * think we got this covered with billing events?
    *
    * Trial Stopped
    * -------------
    * After all history imported
    * Downgrade to free, before trial end date
    *
    * Trial Restarted
    * ---------------
    * After all history imported
    * Upgrade from free, before trial end date
    *

    $STH = $DBH->query(
               "
               select * from user_organizations
               "
    );

    foreach ($STH->fetchAll() as $organizationData) {

        $organization = new Organization();
        $organization->loadDBData($organizationData);

        try {

        $user = $organization->billingUser();


        $STH = $DBH->prepare(
                   "
                   select * from user_history
                   where cust_id = :cust_id
                   and type = :downgrade
                   and timestamp <= :trial_end
                   "
        );

        $STH->execute(
            array(
                 ':cust_id'   => $user->cust_id,
                 ':downgrade' => UserHistory::DOWNGRADE,
                 ':trial_end' => $organization->trial_end_at
            ));

        foreach ($STH->fetchAll() as $userHistoryData) {
            $user_history = new UserHistory($user->cust_id);
            $user_history->loadDBData($userHistoryData);

            $meta = json_decode($user_history->description);

            if ($meta['to_plan'] == Plan::FREE_WITH_CC) {

                $parameters = array(
                    'to_plan'   => $meta['to_plan'],
                    'from_plan' => $meta['from_plan'],
                    'imported'  => true
                );

                $user->recordEvent(
                     UserHistory::TRIAL_STOP,
                     $parameters,
                         $description = false,
                     $timestamp
                );

            }

        }

        $STH = $DBH->prepare(
                   "
                   select * from user_history
                   where cust_id = :cust_id
                   and type = :upgrade
                   and timestamp <= :trial_end
                   "
        );

        $STH->execute(
            array(
                 ':cust_id'   => $user->cust_id,
                 ':upgrade'   => UserHistory::UPGRADE,
                 ':trial_end' => $organization->trial_end_at
            ));

        foreach ($STH->fetchAll() as $userHistoryData) {
            $user_history = new UserHistory($user->cust_id);
            $user_history->loadDBData($userHistoryData);

            $meta = json_decode($user_history->description);

            if ($meta['from_plan'] == Plan::FREE_WITH_CC) {

                $parameters = array(
                    'to_plan'   => $meta['to_plan'],
                    'from_plan' => $meta['from_plan'],
                    'imported'  => true
                );

                $user->recordEvent(
                     UserHistory::TRIAL_RESTARTED,
                     $parameters,
                         $description = false,
                     $timestamp
                );

            }

        }

    }
        catch(Exception $e) {
            CLI::alert($e->getMessage());
        }
    }


    /*
 * People properties
 * -----------------
 * const PROPERTY_INDUSTRY                = 'industry';
const PROPERTY_ACCOUNT_TYPE            = 'type of account';
const PROPERTY_NUMBER_OF_ACCOUNTS      = 'number of accounts';
const PROPERTY_AVG_PINTEREST_FOLLOWERS = 'average number of Pinterest account followers';
const PROPERTY_AVG_PINTEREST_REPINS    = 'average number of Pinterest account repins';
const PROPERTY_AVG_PINTEREST_LIKES     = 'average number of Pinterest account likes';
const PROPERTY_AVG_PINTEREST_COMMENTS  = 'average number of Pinterest account comments';
 *
 *
    $STH = $DBH->query(
               'select * from user_organizations'
    );

    CLI::h1('Updating parameters for all '.$STH->rowCount().' organizations');
    $xx =0;

    foreach ($STH->fetchAll() as $orgData) {
        $organization = new Organization();
        $organization->loadDBData($orgData);

        CLI::h2("Finding properties for".$organization->org_name);
        $accounts_count = $organization->activeUserAccounts()->count();

        if ($accounts_count == 0) {
            continue;
        }

        try {

            $avg_followers = $organization->activeUserAccounts()->profiles()->average('follower_count');
            $avg_likes     = $organization->activeUserAccounts()->profiles()->average('like_count');
        }
        catch (Exception $e) {
            CLI::alert($e->getMessage());
            $avg_followers = '';
            $avg_likes = '';
        }

        try {
            $avg_comments  = $organization->activeUserAccounts()->profiles()->averageCommentCount();
            $avg_repins    = $organization->activeUserAccounts()->profiles()->averageRepinCount();
        }
        catch (Exception $e) {
            CLI::alert($e->getMessage());
            $avg_comments = '';
            $avg_repins = '';
        }

        $parameters = array(
            UserHistory::PROPERTY_ACCOUNT_TYPE            => $organization->org_type,
            UserHistory::PROPERTY_NUMBER_OF_ACCOUNTS      => $accounts_count,
            UserHistory::PROPERTY_AVG_PINTEREST_FOLLOWERS => $avg_followers,
            UserHistory::PROPERTY_AVG_PINTEREST_REPINS    => $avg_repins,
            UserHistory::PROPERTY_AVG_PINTEREST_LIKES     => $avg_likes,
            UserHistory::PROPERTY_AVG_PINTEREST_COMMENTS  => $avg_comments
        );

        foreach ($organization->users() as $user) {

            CLI::write('Updating parameters for '.$user->getName());
            CLI::h1($xx.' of '. $STH->rowCount());
            /*
             $mixpanel->people->set(
                             $user->cust_id,
                             $parameters

            );

        }
        $xx++;
    }



    /*/

}
catch (Exception $e) {
    var_dump($e);
    die();

}

