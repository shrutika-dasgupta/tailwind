<?php namespace API;

use
    BaseController,
    Config,
    Exception,
    Input,
    Log,
    Organization,
    PHPMailer,
    Pinleague\MailchimpWrapper,
    Response,
    User,
    UserProperty,
    UserHistory,
    UserNotFoundException;

/**
 * Class WebhookController
 * For third party services that want to send us info about stuff happening on their end
 *
 * @package API
 */
class WebhookController extends BaseController
{
    /**
     * POST /chargify
     *
     * @notes
     *           This really needs to be secured. As it stands right now, we just straight
     *           accept anything that is sent to us. It's ok because it's just for some internal reporting
     *           on KM. If it ever becomes something more (storage of billing history etc) we should tighten
     *           it up. Well we should tighten it up anyway.
     *
     * @author   Will
     */
    public function parseChargify()
    {
        Log::setLog(__FILE__,'Tailwind API','Chargify_Webhook');

        try {

            /*
             * Basic sanity test
             */
            if (!all_in_array($_POST, 'id', 'event', 'payload')) {
                throw new Exception('Not all parameters were posted :( ');
            }

            $webhook_id = $_POST['id'];
            $event      = $_POST['event'];
            $payload    = $_POST['payload'];

            /*
             * We need to test if we've seen this webhook before
             * chargify will resend these if we don't respond immediately, so there
             * is potential that this isn't the first time we've seen this webhook. We
             * differentiate by the webhook id
             */

            /**
             * @todo create webhook
             */

            switch ($event) {
                default:
                    return '';
                    break;

                case 'signup_success':

                    //do nothing
                    return '';

                    break;

                case 'signup_failure':

                    $reference = $payload['subscription']['customer']['reference'];
                    $email     = $payload['subscription']['customer']['email'];

                    if ($reference != '') {
                        try {
                            $user = User::find($reference);

                            $user->recordEvent(
                                 UserHistory::FAILED_SUBSCRIPTION_SIGNUP
                            );

                        }
                        catch (Exception $e) {

                            return '';

                        }
                    }

                    // @todo send email offering help regardless

                    break;

                case 'renewal_success':

                    //do nothing
                    return '';

                    break;

                case'renewal_failure':

                    //do nothing
                    return '';

                    break;

                case 'payment_success':

                    $chargify_customer_id = $payload['subscription']['customer']['id'];
                    $billed               = $payload['transaction']['amount_in_cents'] / 100;
                    $time                 = strtotime($payload['transaction']['created_at']);
                    $total_amount_billed  = $payload['subscription']['total_revenue_in_cents'] / 100;

                    Log::debug('Payment Success webhook received', $payload);
                    /*
                     * The chargify customer id is stored at the organization level
                     * We can find the organization, but we can't necessarily drill that down
                     * to one person's customer account.
                     *
                     * Our options are to every customer or just the first person we find in the
                     * organization.
                     *
                     * I think it would mess up the total revenue numbers to record payments for
                     * each user since it would double/triple up. Going to go with just the first
                     * customer in the organization
                     */
                    $organization = Organization::findByChargifyID($chargify_customer_id);

                    $user  = $organization->billingUser();
                    $email = $user->email;

                    if(empty($total_amount_billed)){
                        Log::error('Total Amount Billed Not Received', $payload);
                    } else {
                        if (empty($organization->first_billing_event_at)) {

                            /*
                             * We only count this as a "converted trial" if it's within 7 days
                             * of the trial ending
                             */
                            if ($organization->daysSinceTrialEnded() < 7) {

                                $user->recordEvent(
                                     UserHistory::TRIAL_CONVERTED
                                );

                                $organization->trial_converted_at = time();
                            }


                            $organization->trial_converted_at     = $time;
                            $organization->total_amount_billed    = $total_amount_billed;
                            $organization->last_billing_amount    = $total_amount_billed;
                            $organization->last_billing_event_at  = $time;
                            $organization->first_billing_event_at = $time;
                            $days                                 = 0;

                        } else {

                            $organization->last_billing_amount   = $total_amount_billed;
                            $organization->last_billing_event_at = $time;
                            $organization->total_amount_billed   += $total_amount_billed;

                            $seconds_since = $time - $organization->first_billing_event_at;
                            $days          = round($seconds_since / (60 * 60 * 24));

                        }


                        $organization->billing_event_count++;
                        $organization->insertUpdateDB();

                        $user_history = new UserHistory($user->cust_id);
                        $user_history->recordBilling(
                                     $billed,
                                     $parameters = array(
                                         'billing_event_count'            => $organization->billing_event_count,
                                         'days_since_first_billing_event' => $days,
                                         'total_amount_billed'            => $total_amount_billed
                                     )
                        );
                    }

                    return '';

                    break;

                case 'payment_failure':

                    $chargify_customer_id = $payload['subscription']['customer']['id'];

                    $organization = Organization::findByChargifyID($chargify_customer_id);

                    $user = $organization->billingUser();

                    $user->recordEvent(
                         UserHistory::BILLING_FAILED
                    );

                    Log::notice('Payment Failure Webhook Received', $payload);

                    return '';


                    break;

                case 'billing_date_change':

                    //do nothing
                    return '';

                    break;

                case 'subscription_state_change':

                    $chargify_customer_id   = $payload['subscription']['customer']['id'];
                    $previous_state         = $payload['subscription']['previous_state'];
                    $new_subscription_state = $payload['subscription']['state'];

                    Log::notice('Subscription State Change Webhook Received', $payload);

                    $organization = Organization::findByChargifyID($chargify_customer_id);
                    $user         = $organization->billingUser();

                    if($organization->subscription_state != $previous_state){
                        Log::warning(
                           'The subscription state changed and what Tailwind has recorded as ' .
                           "subscription state (" . $organization->subscription_state . ') is not' .
                           "what chargify ($previous_state) has."
                        );
                    }

                    $organization->subscription_state = $new_subscription_state;
                    $organization->insertUpdateDB();

                    $user->recordEvent(
                         UserHistory::SUBSCRIPTION_STATE_CHANGE,
                         $parameters = array(
                             'previous_subscription_state' => $previous_state,
                             'new_subscription_state'      => $new_subscription_state
                         )
                    );

                    if ($previous_state == 'trialing' AND $new_subscription_state = 'active') {
                        $user->recordAction(
                             UserHistory::TRIAL_END,
                             'The trial ended'
                        );
                    }

                    return '';

                    break;

                case 'subscription_product_change':

                    //do nothing
                    return '';

                    break;

                case 'expiring_card':
                    $chargify_customer_id = $payload['subscription']['customer']['id'];
                    $organization         = Organization::findByChargifyID($chargify_customer_id);
                    $user                 = $organization->billingUser();

                    $events = $user->findUserHistoryEvents(
                                   UserHistory::CARD_SOON_TO_EXPIRE,
                                   $timerange = '30 days ago'
                    );

                    $user->recordEvent(
                         UserHistory::CARD_SOON_TO_EXPIRE,
                         $parameters = array(
                             'number_of_warnings' => $events->count()
                         )
                    );

                    return '';

                    break;

                case 'customer_update':

                    //do nothing
                    return '';

                    break;

                case 'component_allocation_change':

                    //do nothing
                    return '';

                    break;

                case 'upgrade_downgrade_success':

                    //do nothing
                    return '';

                    break;

                case 'upgrade_downgrade_failure':

                    //do nothing
                    return '';

                    break;

                case 'subscription_card_update':

                    $chargify_customer_id = $payload['subscription']['customer']['id'];
                    $organization         = Organization::findByChargifyID($chargify_customer_id);
                    $user                 = $organization->billingUser();

                    $user->recordEvent(
                         UserHistory::CARD_UPDATE
                    );

                    break;
            }


        }
        catch (Exception $e) {
            error_log($e->getMessage());

            return Response::json(array('success' => false), 500);
        }

    }

    /**
     * /mailgun
     *
     *
     * @notes
     *           This really needs to be secured. As it stands right now, we just straight
     *           accept anything that is sent to us. It's ok because it's just for some internal reporting
     *           on KM. If it ever becomes something more (storage of billing history etc) we should tighten
     *           it up. Well we should tighten it up anyway.
     *
     * @author   Will
     */
    public function parseMailgun()
    {
        Log::setLog(__FILE__,'Tailwind API','Mailgun_Webhook');

        try {

            /*
            * Basic sanity test
            */
            if (!all_in_array($_POST, 'recipient', 'event', 'token')) {
                throw new Exception('Not all parameters were posted :( ');
            }
            Log::debug('Mailgun hit the webhook',$_POST);

            $event     = $_POST['event'];
            $recipient = $_POST['recipient'];


            if (!$user = User::findByEmail($recipient)) {
                throw new UserNotFoundException("$recipient isn't in our database.");
            }

            Log::debug('Found user by email: '.$recipient,$user);

            switch ($event) {
                default:
                    throw new Exception('That event is not one we care about');
                    break;

                case 'unsubscribed':

                    $parameters = array(
                        'ip'          => $_POST['ip'],
                        'country'     => $_POST['country'],
                        'region'      => $_POST['region'],
                        'city'        => $_POST['city'],
                        'user-agent'  => $_POST['user-agent'],
                        'device-type' => $_POST['device-type'],
                        'client-type' => $_POST['client-type'],
                        'client-name' => $_POST['client-name'],
                        'client-os'   => $_POST['client-os'],
                        'tag'         => $_POST['tag'],
                        'timestamp'   => $_POST['timestamp']
                    );

                    $user->recordEvent(
                         UserHistory::EMAIL_UNSUBSCRIBE,
                         $parameters
                    );
                    Log::debug('Recorded user history',$parameters);

                    $user->incrementUserProperty(UserProperty::UNSUBSCRIBED_COUNT,1);
                    $user->setUserProperty(UserProperty::LAST_UNSUBSCRIBED_AT,time());

                    break;

                case 'opened':

                    $parameters = array(
                        'ip'          => $_POST['ip'],
                        'country'     => $_POST['country'],
                        'region'      => $_POST['region'],
                        'city'        => $_POST['city'],
                        'user-agent'  => $_POST['user-agent'],
                        'device-type' => $_POST['device-type'],
                        'client-type' => $_POST['client-type'],
                        'client-name' => $_POST['client-name'],
                        'client-os'   => $_POST['client-os'],
                        'tag'         => $_POST['tag'],
                        'timestamp'   => $_POST['timestamp']
                    );

                    $user->incrementUserProperty(UserProperty::EMAIL_OPENS,1);
                    $user->setUserProperty(UserProperty::LAST_EMAIL_OPEN_AT,time());

                    $user->recordEvent(
                         UserHistory::EMAIL_OPEN,
                         $parameters
                    );

                    Log::debug('Recorded user history',$parameters);

                    break;

                case 'complained':

                    $parameters = array(
                        'tag'       => $_POST['tag'],
                        'timestamp' => $_POST['timestamp'],
                    );

                    $user->incrementUserProperty(UserProperty::EMAIL_COMPLAINTS,1);
                    $user->setUserProperty(UserProperty::LAST_COMPLAINT_AT,time());

                    $user->recordEvent(
                         UserHistory::EMAIL_COMPLAIN,
                         $parameters
                    );


                    Log::debug('Recorded user history',$parameters);

                    break;

                case 'bounced':

                    $parameters = array(
                        'code'         => $_POST['code'],
                        'error'        => $_POST['error'],
                        'notification' => $_POST['notification'],
                        'tag'          => $_POST['tag'],
                        'timestamp'    => $_POST['timestamp'],
                    );

                    /**
                     * Update user table email confirmed to bounced
                     */

                    $user->incrementUserProperty(UserProperty::EMAIL_BOUNCES,1);
                    $user->setUserProperty(UserProperty::LAST_BOUNCE_AT,time());

                    $user->recordEvent(
                         UserHistory::EMAIL_BOUNCE,
                         $parameters
                    );

                    $user->email_status = User::EMAIL_BOUNCED;
                    $user->insertUpdateDB();

                    Log::debug('Recorded user history',$parameters);

                    break;

                case 'clicked':

                    $parameters = array(
                        'ip'          => $_POST['ip'],
                        'country'     => $_POST['country'],
                        'region'      => $_POST['region'],
                        'city'        => $_POST['city'],
                        'user-agent'  => $_POST['user-agent'],
                        'device-type' => $_POST['device-type'],
                        'client-type' => $_POST['client-type'],
                        'client-name' => $_POST['client-name'],
                        'client-os'   => $_POST['client-os'],
                        'tag'         => $_POST['tag'],
                        'timestamp'   => $_POST['timestamp'],
                        'url'         => filter_var($_POST['url'], FILTER_SANITIZE_STRING)
                    );

                    $user->incrementUserProperty(UserProperty::EMAIL_CLICKS,1);
                    $user->setUserProperty(UserProperty::LAST_EMAIL_CLICK_AT,time());

                    $user->recordEvent(
                         UserHistory::EMAIL_CLICK,
                         $parameters
                    );

                    Log::debug('Recorded user history',$parameters);

                    break;

                case 'dropped':

                    $parameters = array(
                        'code'        => $_POST['code'],
                        'description' => $_POST['description'],
                        'reason'      => $_POST['reason'],
                        'tag'         => $_POST['tag'],
                        'timestamp'   => $_POST['timestamp'],
                    );

                    $user->incrementUserProperty(UserProperty::EMAIL_DROPS,1);
                    $user->setUserProperty(UserProperty::LAST_EMAIL_DROP_AT,time());

                    $user->recordEvent(
                         UserHistory::EMAIL_DROPPED,
                         $parameters
                    );

                    Log::debug('Recorded user history',$parameters);

                    break;
            }

        }

        catch (UserNotFoundException $e) {
            Log::warning($e);
            return Response::json(array('success'=>false));
        }

        catch (Exception $e) {

            Log::error($e);

            return Response::json(array('success' => false), 500);
        }
    }

    /**
     * Parses bookmarklet data.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function parseBookmarklet()
    {
        $data = Input::json('items');

        if (empty($data)) {
            $response = Response::json(array('success' => false));
            $response->headers->set('Access-Control-Allow-Methods', 'POST');
            $response->headers->set('Access-Control-Allow-Origin', '*');
            return $response;
        }

        $mail          = new PHPMailer();
        $mail->Subject = 'Bookmarklet Data';
        $mail->Body    = print_r($data, true);
        $mail->AddAddress('dsposito@tailwindapp.com');
        $mail->Send();

        $response = Response::json(array('success' => true));
        $response->headers->set('Access-Control-Allow-Methods', 'POST');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        return $response;
    }

    /**
     * Parses MailChimp data.
     *
     * @author Janell
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function parseMailchimp()
    {
        Log::setLog(__FILE__, 'Tailwind API', 'MailChimp_Webhook');

        try {
            if (empty($_GET['key'])
                || $_GET['key'] != Config::get('mailchimp.WEBHOOK_KEY')
                || empty($_POST['type'])
            ) {
                throw new Exception('Missing required parameter');
            }

//            Log::debug('MailChimp hit the webhook', $_POST);
            Log::debug('MailChimp hit the webhook: ' . $_POST['type']);

            switch ($_POST['type']) {
                case 'subscribe':
                    $parameters = array(
                        'fired_at'   => $_POST['fired_at'],
                        'id'         => $_POST['data']['id'],
                        'list_id'    => $_POST['data']['list_id'],
                        'email'      => $_POST['data']['email'],
                        'email_type' => $_POST['data']['email_type'],
                        'ip_opt'     => $_POST['data']['ip_opt'],
                        'ip_signup'  => $_POST['data']['ip_signup'],
                    );

                    $user = User::findByEmail($parameters['email']);
                    if (empty($user)) {
                        throw new Exception('Could not find user by email');
                    }

//                    Log::debug('Found user by email: ' . $parameters['email'], $user);
                    Log::debug('Found user by email: ' . $parameters['email']);

                    MailchimpWrapper::saveSubscriptionPreference(
                        $parameters['list_id'],
                        $user,
                        true,
                        $parameters
                    );
                    break;
                case 'unsubscribe':
                    $parameters = array(
                        'fired_at'    => $_POST['fired_at'],
                        'action'      => $_POST['data']['action'],  // 'unsub' or 'delete'
                        'reason'      => $_POST['data']['reason'],  // 'manual' unless spam ('abuse')
                        'id'          => $_POST['data']['id'],
                        'list_id'     => $_POST['data']['list_id'],
                        'email'       => $_POST['data']['email'],
                        'email_type'  => $_POST['data']['email_type'],
                        'ip_opt'      => $_POST['data']['ip_opt'],
                        'campaign_id' => $_POST['data']['campaign_id'],
                    );

                    $user = User::findByEmail($parameters['email']);
                    if (empty($user)) {
                        throw new Exception('Could not find user by email');
                    }

//                    Log::debug('Found user by email: ' . $parameters['email'], $user);
                    Log::debug('Found user by email: ' . $parameters['email']);

                    MailchimpWrapper::saveSubscriptionPreference(
                        $parameters['list_id'],
                        $user,
                        false,
                        $parameters
                    );
                    break;
                case 'profile':
                    $parameters = array(
                        'fired_at'   => $_POST['fired_at'],
                        'id'         => $_POST['data']['id'],
                        'list_id'    => $_POST['data']['list_id'],
                        'email'      => $_POST['data']['email'],
                        'email_type' => $_POST['data']['email_type'],
                        'ip_opt'     => $_POST['data']['ip_opt'],
                    );

                    $user = User::findByEmail($parameters['email']);
                    if (empty($user)) {
                        throw new Exception('Could not find user by email');
                    }

//                    Log::debug('Found user by email: ' . $parameters['email'], $user);
                    Log::debug('Found user by email: ' . $parameters['email']);

                    $user->recordEvent(
                        UserHistory::MAILCHIMP_PROFILE_UPDATE,
                        $parameters
                    );

//                    Log::debug('Recorded user history', $parameters);
                    Log::debug('Recorded user history');
                    break;
                case 'upemail':  // 'profile' events are always sent at the same time as 'upemail' events
                case 'cleaned':
                case 'campaign':
                default:
                    throw new Exception('That event is not one we care about');
                    break;
            }
        } catch (Exception $e) {
//            Log::error($e);
            Log::error($e->getMessage());

            return Response::json(array('success' => false));
        }
    }
}