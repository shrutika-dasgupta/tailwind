<?php

use Pinleague\CLI;

/**
 * We need to "Seed" the mixpanle project with actions that will be imported because of some
 * shit going on with the way Mixpanel imports things.
 *
 *
 * @author  Will
 */

chdir(__DIR__);
include '../../../bootstrap/bootstrap.php';


set_time_limit(0);
ini_set('memory_limit', '500M');

$now = date('g:ia');
CLI::h1('Starting import (' . $now . ')');

$DBH = DatabaseInstance::DBO();
CLI::write('Connected to database');

$DBH = DatabaseInstance::DBO();

CLI::write('Creating Mixpanel instance');
$mixpanel = Mixpanel::getInstance(Config::get('mixpanel.TOKEN'));

//Run all imported events first as will so they can be imported

$user = User::find(1748);

CLI::write('Seeding event type :' . UserHistory::SIGNUP);
$parameters = array(
    'plan'     => Plan::PRO,
    'imported' => true,
);

try {

$user->recordEvent(
     UserHistory::SIGNUP,
     $parameters
);
}
catch (Exception $e) {
    die($e->getTraceAsString());
}

CLI::write('Seeding event type :' . UserHistory::UPGRADE);
$parameters = array(
    'from_plan' => Plan::FREE_NO_CC,
    'to_plan'   => Plan::PRO,
    'imported'  => true,
);

$user->recordEvent(
     UserHistory::UPGRADE,
     $parameters
);

CLI::write('Seeding event type :' . UserHistory::TRIAL_START);

$user->recordEvent(
     UserHistory::TRIAL_START,
     $parameters = array(
         'imported' => true,
         'plan'     => Plan::FREE_WITH_CC
     )
);

CLI::write('Seeding event type :' . UserHistory::TRIAL_END);

$user->recordEvent(
     UserHistory::TRIAL_END,
     $parameters = array(
         'imported' => true
     )
);

CLI::write('Seeding event type :' . UserHistory::CANCELLED_SUBSCRIPTION);

$user->recordEvent(
     UserHistory::CANCELLED_SUBSCRIPTION,
     $parameters = array(
         'imported' => true
     )
);

CLI::write('Seeding event type :' . UserHistory::DOWNGRADE);

$parameters = array(
    'to_plan'   => Plan::FREE_WITH_CC,
    'from_plan' => Plan::PRO,
    'imported'  => true
);
$user->recordEvent(
     UserHistory::DOWNGRADE,
     $parameters
);

CLI::write('Seeding event type :' . UserHistory::TRIAL_CONVERTED);

$parameters = array(
    'imported' => true
);
$user->recordEvent(
     UserHistory::TRIAL_CONVERTED,
     $parameters
);

CLI::write('Seeding event type :' . UserHistory::BILLED);

$parameters = array(
    'billing_event_count'            => 0,
    'days_since_first_billing_event' => '0',
    'total_amount_billed'            => '0.00',
    'imported'                       => true
);

$user->recordEvent(
     UserHistory::BILLED,
     $parameters
);

CLI::write('Seeding event type :' . UserHistory::BILLING_FAILED);
$parameters = array(
    'imported' => true
);

$user->recordEvent(
     UserHistory::BILLING_FAILED,
     $parameters
);

CLI::write('Seeding event type :' . UserHistory::REFUNDED);

$parameters = array(
    'total_amount_refunded' => -5,
    'imported'              => true
);

$user->recordEvent(
     UserHistory::REFUNDED,
     $parameters
);

CLI::write('Seeding event type :' . UserHistory::TRIAL_STOP);

$parameters = array(
    'to_plan'   => Plan::FREE_WITH_CC,
    'from_plan' => Plan::LITE,
    'imported'  => true
);

$user->recordEvent(
     UserHistory::TRIAL_STOP,
     $parameters
);

CLI::write('Seeding event type :' . UserHistory::TRIAL_RESTARTED);

$parameters = array(
    'to_plan'   => Plan::PRO,
    'from_plan' => Plan::FREE_WITH_CC,
    'imported'  => true
);

$user->recordEvent(
     UserHistory::TRIAL_RESTARTED,
     $parameters
);
/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/
CLI::write('Seeding event type'.UserHistory::ADD_ACCOUNT);
$parameters = array(
    'imported'  => true
);

$user->recordEvent(
     UserHistory::ADD_ACCOUNT,
     $parameters
);
CLI::write('Seeding event type'.UserHistory::ADD_ACCOUNT);
$parameters = array(
    'imported'  => true
);

$user->recordEvent(
     UserHistory::ADD_ACCOUNT,
     $parameters
);
CLI::write('Seeding event type'.UserHistory::LOGIN_DEACTIVATED);
$parameters = array(
    'imported'  => true
);

$user->recordEvent(
     UserHistory::LOGIN_DEACTIVATED,
     $parameters
);
CLI::write('Seeding event type'.UserHistory::REMOVE_COLLABORATOR);
$parameters = array(
    'imported'  => true
);

$user->recordEvent(
     UserHistory::REMOVE_COLLABORATOR,
     $parameters
);
CLI::write('Seeding event type'.UserHistory::REMOVE_ACCOUNT);
$parameters = array(
    'imported'  => true
);

$user->recordEvent(
     UserHistory::REMOVE_COLLABORATOR,
     $parameters
);
CLI::write('Seeding event type'.UserHistory::UPDATE_ACCOUNT_NAME);
$parameters = array(
    'imported'  => true
);

$user->recordEvent(
     UserHistory::UPDATE_ACCOUNT_NAME,
     $parameters
);
CLI::write('Seeding event type'.UserHistory::UPDATE_ACCOUNT_USERNAME);
$parameters = array(
    'imported'  => true
);

$user->recordEvent(
     UserHistory::UPDATE_ACCOUNT_USERNAME,
     $parameters
);
CLI::write('Seeding event type'.UserHistory::UPDATE_CUSTOMER_NAME);
$parameters = array(
    'imported'  => true
);

$user->recordEvent(
     UserHistory::UPDATE_CUSTOMER_NAME,
     $parameters
);
CLI::write('Seeding event type'.UserHistory::EMAIL_SEND);
$parameters = array(
    'imported'  => true
);

$user->recordEvent(
     UserHistory::EMAIL_SEND,
     $parameters
);
CLI::write('Seeding event type'.UserHistory::ADD_ACCOUNT_DOMAIN);
$parameters = array(
    'imported'  => true
);

$user->recordEvent(
     UserHistory::ADD_ACCOUNT_DOMAIN,
     $parameters
);
CLI::write('Seeding event type'.UserHistory::ADD_COMPETITOR);
$parameters = array(
    'imported'  => true
);

$user->recordEvent(
     UserHistory::ADD_COMPETITOR,
     $parameters
);
CLI::write('Seeding event type'.UserHistory::CARD_SOON_TO_EXPIRE);
$parameters = array(
    'imported'  => true,
    'number_of_warnings' => 0
);
$user->recordEvent(
     UserHistory::CARD_SOON_TO_EXPIRE,
     $parameters
);
CLI::write('Seeding event type'.UserHistory::LOGOUT);
$parameters = array(
    'imported'  => true,
);
$user->recordEvent(
     UserHistory::LOGOUT,
     $parameters
);
CLI::write('Seeding event type'.UserHistory::PASSWORD_RESET);
$parameters = array(
    'imported'  => true,
    '$password_reset_email'=>'will+3@willigant.com'
);

$user->recordEvent(
     UserHistory::PASSWORD_RESET,
     $parameters
);
CLI::write('Seeding event type'.UserHistory::PASSWORD_RESET_EMAIL_SENT);
$parameters = array(
    'imported'  => true,
);

$user->recordEvent(
     UserHistory::PASSWORD_RESET_EMAIL_SENT,
     $parameters
);
CLI::write('Seeding event type'.UserHistory::SUBSCRIPTION_STATE_CHANGE);
$parameters = array(
    'imported'  => true,
    'previous_subscription_state' =>'trialing',
    'new_subscription_state'=>'active'
);
$user->recordEvent(
     UserHistory::SUBSCRIPTION_STATE_CHANGE,
     $parameters
);
CLI::write('Seeding event type'.UserHistory::UPDATE_NOTIFICATIONS_SETTINGS);
$parameters = array(
    'imported'  => true,
    'daily_summary_enabled'=>false,
    'weekly_summary_enabled'=>false,
    'monthly_summary_enabled'=>false,
    'profile_alerts_enabled'=>true,
    'domain_alerts_enabled'=>true
);

$user->recordEvent(
     UserHistory::UPDATE_NOTIFICATIONS_SETTINGS,
     $parameters
);
CLI::write('Seeding event type'.UserHistory::UPDATE_ACCOUNT_DOMAIN);
$parameters = array(
    'imported'  => true,
);

$user->recordEvent(
     UserHistory::UPDATE_ACCOUNT_DOMAIN,
     $parameters
);
CLI::write('Seeding event type'.UserHistory::UPDATE_ORGANIZATION_NAME);
$parameters = array(
    'imported'  => true,
);

$user->recordEvent(
     UserHistory::UPDATE_ORGANIZATION_NAME,
     $parameters
);
CLI::write('Seeding event type'.UserHistory::UPDATE_ORGANIZATION_TYPE);
$parameters = array(
    'imported'  => true,
);
$user->recordEvent(
     UserHistory::UPDATE_ORGANIZATION_TYPE,
     $parameters
);
CLI::write('Seeding event type'.UserHistory::INVITE_COLLABORATOR);
$parameters = array(
    'role'     => 'V',
    'imported' => true,
);

$user->recordEvent(
     UserHistory::INVITE_COLLABORATOR,
     $parameters
);
CLI::write('Seeding event type'.UserHistory::LOGIN);
$parameters = array(
    'accepting invitation' => true,
    'login_to' => 'profile',
    'autologin' => true,
    'imported' => true,
);

$user->recordEvent(
     UserHistory::LOGIN,
     $parameters
);
CLI::write('Seeding event type'.UserHistory::REMOVE_COMPETITOR);
$parameters = array(
    'imported' => true,
);

$user->recordEvent(
     UserHistory::REMOVE_COMPETITOR,
     $parameters
);
CLI::write('Seeding event type'.UserHistory::SYNC_GOOGLE_ANALYTICS);
$parameters = array(
    'imported' => true,
);

$user->recordEvent(
     UserHistory::SYNC_GOOGLE_ANALYTICS,
     $parameters
);
CLI::write('Seeding event type' . UserHistory::SIGNED_UP_AGAIN);
$parameters = array(
    'imported'  => true,
    'plan'      => 'Free-no-credit-card',
    '$username' => 'willwashburn',
);

$user->recordEvent(
     UserHistory::SIGNED_UP_AGAIN,
     $parameters
);
CLI::write('Seeding event type' . UserHistory::UPDATE_COLLABORATOR_ROLE);
$parameters = array(
    'to_role'   => 'A',
    'from_role' => 'V',
    'imported'  => true,
);

$user->recordEvent(
     UserHistory::UPDATE_COLLABORATOR_ROLE,
     $parameters
);
CLI::write('Seeding event type' . UserHistory::UPDATE_ACCOUNT_INDUSTRY);
$parameters = array(
    'to_industry'   => '1',
    'from_industry' => '2',
    'imported'      => true,
);

$user->recordEvent(
     UserHistory::UPDATE_ACCOUNT_INDUSTRY,
     $parameters
);
CLI::write('Seeding event type' . UserHistory::UPDATE_ACCOUNT_TYPE);
$parameters = array(
    'to_brand'   => 'Brand',
    'from_brand' => 'Agency',
    'imported'   => true,
);

$user->recordEvent(
     UserHistory::UPDATE_ACCOUNT_TYPE,
     $parameters
);
CLI::write('Seeding event type' . UserHistory::ADD_ACCOUNT);
$parameters = array(
    'imported'   => true,
);

$user->recordEvent(
     UserHistory::ADD_ACCOUNT,
     $parameters
);