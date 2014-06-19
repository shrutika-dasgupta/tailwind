<?php
use Pinleague\CLI;

/**
 * Takes history in our user history table and imports it to Mixpanel
 *
 * @example
 *
 * php user_history_to_mixpanel.php 0
 * php user_history_to_mixpanel.php 1
 * (this will do rows 10,000 -> 20,000)
 *
 * @author  Will
 */

chdir(__DIR__);
set_time_limit(0);
ini_set('memory_limit', '910M');
include '../../../bootstrap/bootstrap.php';

$multiplier = array_get($argv,1,0);

$starting_record= 10000 * $multiplier;
$upper_limit = 10000;

try {

    $now = date('g:ia');
    CLI::h1('Starting import (' . $now . ')');

    $DBH = DatabaseInstance::DBO();
    CLI::write('Connected to database');

    $DBH = DatabaseInstance::DBO();

    CLI::write('Creating Mixpanel instance');
    $mixpanel = Mixpanel::getInstance(Config::get('mixpanel.TOKEN'));

    CLI::write('Finding all user history events');
    $STH = $DBH->query("
                select
                mixpanel_import.id,
                mixpanel_import.type as mx_type,
                mixpanel_import.description,
                mixpanel_import.timestamp as mx_tm,
                mixpanel_import.*
                from mixpanel_import
                limit $starting_record, $upper_limit
            ");


    foreach ($STH->fetchAll() as $row) {

        try {

        CLI::h2('ID: '.$row->id);
        CLI::write('Cust id'.$row->cust_id);
        CLI::write('Event: '.$row->mx_type);

        $user_history = new UserHistory($row->cust_id);
        $user_history->timestamp = $row->mx_tm;
        $user_history->type = $row->mx_type;

        switch ($row->mx_type) {

            default:
                CLI::alert('No action associated with '.$row->type);

                break;


            case 'End of trial':
                ///not sending to mixpanel
                break;
            case 'Upgraded plan':
                $parameters = array();
                switch($row->description) {

                    default:

                        $parameters['to_plan']   = Plan::PRO;
                        $parameters['from_plan'] = Plan::FREE_NO_CC;

                        if (!empty($row->description)) {
                            if (substr($row->description, 0, 1) == '{') {
                                $parameters = (array) json_decode($row->description);
                            }
                        } else {
                            CLI::alert('Bad json '.__LINE__);
                        }

                        break;

                    case 'Change from Free (1) to Lite (2)':
                        $parameters['to_plan']   = Plan::LITE;
                        $parameters['from_plan'] = Plan::FREE_NO_CC;

                        break;

                    case 'Change from Free (1) to Pro (3)':
                        $parameters['to_plan']   = Plan::LITE;
                        $parameters['from_plan'] = Plan::FREE_NO_CC;

                        break;
                };

                $parameters['imported'] = true;

                $user_history->record(
                             UserHistory::UPGRADE,
                             $parameters,
                             $description = false,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                break;

            case 'Downgraded plan':
                $parameters = array();
                switch($row->description) {

                    default:

                        $parameters['to_plan']   = Plan::FREE_WITH_CC;
                        $parameters['from_plan'] = Plan::PRO;

                        if (!empty($row->description)) {
                            if (substr($row->description, 0, 1) == '{') {
                                $parameters = (array) json_decode($row->description);
                            }
                        } else {
                            CLI::alert('Bad json '.__LINE__);
                        }

                        break;

                    case 'Change from Basic (2) to Free-with-credit-card (1)':
                        $parameters['to_plan']   = Plan::FREE_WITH_CC;
                        $parameters['from_plan'] = Plan::LITE;

                        break;

                    case 'Change from Pro (3) to Free-with-credit-card (1)':
                        $parameters['to_plan']   = Plan::FREE_WITH_CC;
                        $parameters['from_plan'] = Plan::PRO;

                        break;
                };

                $parameters['imported'] = true;

                $user_history->record(
                             UserHistory::DOWNGRADE,
                             $parameters,
                             $description = false,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                break;

            case 'Add user account':
            case 'Attempted to login to deactivated account':
            case 'Remove collaborator':
            case 'Remove user account':
            case 'Update user account name':
            case 'Update user account username':
            case 'Updated customer name':
            case 'Was sent email':

                $user_history->record(
                             $row->type,
                            array('imported'=>true),
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                break;

            case 'Add user account domain':

                if ($row->description != 'Added a blank domain') {

                    $user_history->record(
                                 UserHistory::ADD_ACCOUNT_DOMAIN,
                                 array('imported'=>true),
                                 $description = false,
                                 $send_to_mixpanel = true,
                                 $store_in_db = false,
                                 $time = $row->timestamp
                    );

                }

                break;

            case 'Added a competitor':

                $user_history->record(
                             UserHistory::ADD_COMPETITOR,
                             array('imported'=>true),
                             $description = false,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );
                break;

            case 'Calculated User Profile History':
                //do nothing, not needed
                break;

            case 'Cancelled chargify subscription':

                $user_history->record(
                             UserHistory::CANCELLED_SUBSCRIPTION,
                             $parameters = array(
                                 'imported' => true
                             ),
                             $description = false,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );
                break;

            case 'Convert a trial to paid':
            case 'Credit card expiring soon':
            case 'Logout':
            case 'Payment failed':
            case 'Reset password':
            case 'Reset password email sent':
            case 'Subscription state changed':
            case 'Update notification settings':
            case 'Update user account domain':
            case 'Updated organization name':
            case 'Updated organization type':


                $parameters = array();
                if (!empty($row->description)) {
                    if (substr($row->description, 0, 1) == '{') {
                        $parameters = (array)json_decode($row->description);
                    }
                }
                $parameters['imported'] = true;

                $user_history->record(
                             $row->type,
                             $parameters,
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                break;

            case 'Invite collaborator':

                //they all had role V
                $user_history->record(
                             $row->type,
                             $parameters = array(
                                 'role'     => 'V',
                                 'imported' => true,
                             ),
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                break;

            case 'Login':
            case 'login':

                if ($row->description == 'Initial Login - Invitation Accepted') {
                    $parameters = array(
                        'accepting invitation' => true
                    );
                } else {
                    $page = trim(str_replace('Login to', '', $row->description));
                    $page = trim(str_replace('.php', '', $page));

                    $parameters = array(
                        'login_to' => $page
                    );
                }

                $parameters['imported'] = true;

                $user_history->record(
                             UserHistory::LOGIN,
                             $parameters,
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );
                break;

            case 'Opt into daily profile summary':
            case 'Opt into domain alerts':
            case 'Opt into monthly profile summary':
            case 'Opt into profile alerts':
            case 'Opt into weekly profile summary':
            case 'Opt out of daily profile summary':
            case 'Opt out of domain alerts':
            case 'Opt out of monthly profile summary':
            case 'Opt out of profile alerts':
            case 'Opt out of weekly profile summary':
                //do nothing

                break;

            case 'Refunded':

                $parameters = (array) json_decode($row->description);

                $parameters['imported'] = true;

                $user_history->record(
                             $row->type,
                             $parameters,
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                $mixpanel->people->trackCharge(
                                       $user_history->cust_id,
                                       $parameters['total_amount_refunded'],
                                       $row->timestamp
                );

                break;

            case 'add_organization':
            case 'create_organization':
            case 'Create an organization':
                //do nothing, this is not an event, its an action
                break;

            case 'Autologin':
                $parameters = array(
                    'login_to'  => $page,
                    'autologin' => true
                );

                $parameters['imported'] = true;

                $user_history->record(
                             UserHistory::LOGIN,
                             $parameters,
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );
                break;

            case 'Removed a competitor':
                $user_history->record(
                             UserHistory::REMOVE_COMPETITOR,
                             array('imported' => true),
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                break;

            case 'Sign up for a pro account':
                break;



            case 'Sign up for free account':
                break;

            case 'Start a 14 day trial':
            case 'Start trial':

                $parameters = array();
                if (!empty($row->description)) {
                    if (substr($row->description, 0, 1) == '{') {
                        $parameters = (array) json_decode($row->description);
                    }
                }

                $parameters['imported'] = true;

                $user_history->record(
                             UserHistory::TRIAL_START,
                             $parameters,
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                break;

            /*
             * ------------------------
Starting import (1:57pm)
------------------------
Connected to database
Creating Mixpanel instance
Finding possible types

ID: 39141
---------
Cust id1421
Event: Subscription billed
! => Undefined index: total_amount_billed | Line 404
             */

            case 'Subscription billed':

                $parameters = array();
                if (!empty($row->description)) {
                    if (substr($row->description, 0, 1) == '{') {
                        $parameters = (array) json_decode($row->description);
                    }
                    else {
                        $amount                            = trim(str_replace('Billed $', '', $row->description));
                        $parameters['total_amount_billed'] = $amount;
                    }
                }

                $parameters['imported'] = true;

                $user_history->record(
                             $row->type,
                             $parameters,
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                $mixpanel->people->trackCharge(
                                       $user_history->cust_id,
                                       $parameters['total_amount_billed'],
                                       $row->timestamp
                );

                break;

            case 'Synced Google Analytics':
                $parameters = array();
                if (!empty($row->description)) {
                    if (substr($row->description, 0, 1) == '{') {
                        $parameters = (array) json_decode($row->description);
                    }
                }
                $parameters['imported'] = true;

                $user_history->record(
                             UserHistory::SYNC_GOOGLE_ANALYTICS,
                             $parameters,
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                break;

            case 'Tried to sign up for a free account, but already had an account':
            case 'Tried to sign up for an account, but email address was already used':

                $parameters = array();
                if (!empty($row->description)) {
                    if (substr($row->description, 0, 1) == '{') {
                        $parameters = (array) json_decode($row->description);
                    }
                }

                $parameters['imported'] = true;

                $user_history->record(
                             UserHistory::SIGNED_UP_AGAIN,
                             $parameters,
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                break;

            case 'Update collaborator role':

                //they just all happen to be A

                $user_history->record(
                             UserHistory::UPDATE_COLLABORATOR_ROLE,
                             $parameters = array(
                                 'to_role'   => 'A',
                                 'from_role' => 'V',
                                 'imported'  => true,
                             ),
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                break;

            case 'Update user account industry':

                $pieces = explode('to', $row->description);

                $from_industry = trim(str_replace('Update account industry from', '', $pieces[0]));
                $to_industry   = trim($pieces[1]);

                $user_history->record(
                             UserHistory::UPDATE_ACCOUNT_INDUSTRY,
                             $parameters = array(
                                 'to_industry'   => $to_industry,
                                 'from_industry' => $from_industry,
                                 'imported'      => true,
                             ),
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                break;

            case 'Update user account type':
            case 'update_user_account_type':

                $pieces = explode('to', $row->description);

                $from_brand = trim(str_replace('Update account type from', '', $pieces[0]));
                $to_brand   = trim($pieces[1]);

                $user_history->record(
                             UserHistory::UPDATE_ACCOUNT_TYPE,
                             $parameters = array(
                                 'to_brand'   => $to_brand,
                                 'from_brand' => $from_brand,
                                 'imported'   => true,
                             ),
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );
                break;

            case 'add_account':

                $user_history->record(
                             UserHistory::ADD_ACCOUNT,
                             array('imported' => true),
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                break;

            case 'add_domain':

                $user_history->record(
                             UserHistory::ADD_ACCOUNT_DOMAIN,
                             array('imported' => true),
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                break;

            case 'add_user':

                $role = false;

                if (strpos($row->description, 'with role V') !== false) {
                    $role = 'V';
                } else if (strpos($row->description, 'with role A') !== false) {
                    $role = 'A';
                }

                if ($role) {

                    $user_history->record(
                                 UserHistory::INVITE_COLLABORATOR,
                                 array(
                                      'role'     => $role,
                                      'imported' => true,
                                 ),
                                 $row->description,
                                 $send_to_mixpanel = true,
                                 $store_in_db = false,
                                 $time = $row->timestamp
                    );

                }

                break;

            case 'create_user_account':
            case 'add_user_account':

                $user_history->record(
                             UserHistory::ADD_ACCOUNT,
                             array('imported' => true),
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                break;

            case 'change-account':
                $user_history->record(
                             UserHistory::UPDATE_ACCOUNT_NAME,
                             array('imported' => true),
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );
                break;

            case 'change_plan':

                /*
                 * I think we can ignore this because
                 * ->Free to lite/paid we get from new subscriptions (already in DB)
                 * ->Lite/Pro to anything else we can from subscription changes
                 *
                 * so...do nothing here
                 */

                break;

            case 'customer_update':
                $user_history->record(
                             UserHistory::UPDATE_CUSTOMER_NAME,
                             array('imported' => true),
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );
                break;

            case 'organization_update':
            case 'update_organization':

                $user_history->record(
                             UserHistory::UPDATE_ORGANIZATION_NAME,
                             array('imported' => true),
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );
                break;

            case 'password_reset':

                $event = false;

                if (strpos($row->description, 'New password saved for') !== false) {
                    $event = UserHistory::PASSWORD_RESET;
                } else if (strpos($row->description, 'requested password reset email') !== false) {
                    $event = UserHistory::PASSWORD_RESET_EMAIL_SENT;
                }

                if ($event) {

                    $user_history->record(
                                 $event,
                                 array('imported' => true),
                                 $row->description,
                                 $send_to_mixpanel = true,
                                 $store_in_db = false,
                                 $time = $row->timestamp
                    );
                }
                break;

            case 'remove_account':

                $user_history->record(
                             UserHistory::REMOVE_ACCOUNT,
                             array('imported' => true),
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                break;

            case 'remove_user':

                $user_history->record(
                             UserHistory::REMOVE_COLLABORATOR,
                             array('imported' => true),
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                break;

            case 'remove_user_account':

                $user_history->record(
                             UserHistory::REMOVE_ACCOUNT,
                             array('imported' => true),
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                break;

            case 'signup':
            case 'Sign up for an account':

                $parameters = array();
                if (!empty($row->description)) {
                    if (substr($row->description, 0, 1) == '{') {
                        $parameters = (array)json_decode($row->description);
                    }
                }
                $parameters['imported'] = true;

                $user_history->record(
                             UserHistory::SIGNUP,
                             $parameters,
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                break;

                //Believe we have back dated all of these, so no issues here
                //Do nothing

                break;

            case 'signup-again':
            case 'signup_again':

                $user_history->record(
                             UserHistory::SIGNED_UP_AGAIN,
                             array('imported' => true),
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                break;

            case 'update_email_notifications':

                $user_history->record(
                             UserHistory::UPDATE_NOTIFICATIONS_SETTINGS,
                             array('imported' => true),
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                break;

            case 'update_self':

                $user_history->record(
                             UserHistory::UPDATE_CUSTOMER_NAME,
                             array('imported' => true),
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                break;

            case 'update_user':

                if (strpos($row->description, 'Change role') !== false) {

                    if (strpos($row->description, 'to A') !== false) {
                        $role      = 'V';
                        $from_role = 'A';
                    } else {
                        $role      = 'A';
                        $from_role = 'V';
                    }

                    $user_history->record(
                                 UserHistory::UPDATE_COLLABORATOR_ROLE,
                                 array(
                                      'to_role'   => $role,
                                      'from_role' => $from_role
                                 ),
                                 $row->description,
                                 $send_to_mixpanel = true,
                                 $store_in_db = false,
                                 $time = $row->timestamp
                    );


                } else if (strpos($row->description, 'Updated name') !== false) {
                    $user_history->record(
                                 UserHistory::UPDATE_CUSTOMER_NAME,
                                 array('imported' => true),
                                 $row->description,
                                 $send_to_mixpanel = true,
                                 $store_in_db = false,
                                 $time = $row->timestamp
                    );
                }

                break;


            case 'update_user_account_domain':

                $user_history->record(
                             UserHistory::UPDATE_ACCOUNT_DOMAIN,
                             array('imported' => true),
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                break;

            case 'update_user_account_industry':

                $user_history->record(
                             UserHistory::UPDATE_ACCOUNT_INDUSTRY,
                             array('imported' => true),
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );

                break;

            case 'update_user_account_name':

                $user_history->record(
                             UserHistory::UPDATE_ACCOUNT_NAME,
                             array('imported' => true),
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );
                break;

            case 'update_user_account_username':
                $user_history->record(
                             UserHistory::UPDATE_ACCOUNT_USERNAME,
                             array('imported' => true),
                             $row->description,
                             $send_to_mixpanel = true,
                             $store_in_db = false,
                             $time = $row->timestamp
                );
                break;

        }

        }
        catch (Exception $e) {
            CLI::alert($e->getMessage());
            exit();
        }
    }
}
catch (Exception $e) {
    CLI::alert($e->getMessage() . ' | Line ' . $e->getLine());
}
