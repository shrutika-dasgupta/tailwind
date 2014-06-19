<?php namespace Analytics\Settings;

use
    ChargifyCustomer,
    ChargifyConnector,
    ChargifySubscription,
    ChargifyQuantityBasedComponent,
    ChargifyValidationException,
    Exception,
    Input,
    Plan,
    Redirect,
    Request,
    RequiredVariableException,
    Response,
    Session,
    UserHistory,
    User,
    View;

/**
 * Class ProfileController
 *
 * @package Analytics\Settings
 */
class ProfileController extends BaseController
{


    /**
     * /settings/profile/edit
     *
     * @author Will
     */
    public function edit()
    {

        $legacy = $this->baseLegacyVariables();
        extract($legacy);

        /**
         * @var $customer /User
         * @var $user User
         */
        $user = $customer;
        try {

            if (!all_in_array($_POST,
                'first_name', 'last_name', 'org_name', 'org_type'
            )
            ) {
                throw new RequiredVariableException('Post variables not all sent');
            }

            $first_name = filter_var($_POST['first_name'], FILTER_SANITIZE_STRING);
            $last_name  = filter_var($_POST['last_name'], FILTER_SANITIZE_STRING);
            $org_name   = filter_var($_POST['org_name'], FILTER_SANITIZE_STRING);
            $org_type   = filter_var($_POST['org_type'], FILTER_SANITIZE_STRING);


            $old_org_type = $user->organization()->org_type;
            $old_org_name = $user->organization()->org_name;

            $user->organization()->org_type = $org_type;
            $user->organization()->org_name = $org_name;
            $user->organization()->saveToDB();

            if($old_org_name != $org_name) {

                $user->recordEvent(
                    UserHistory::UPDATE_ORGANIZATION_NAME
                );
            }

            if($old_org_type != $org_type) {
                $user->recordEvent(
                    UserHistory::UPDATE_ORGANIZATION_TYPE
                );
            }

            $old_name = $user->getName();

            $user->first_name = $first_name;
            $user->last_name  = $last_name;
            $user->insertUpdateDB();


            if($old_name != $user->getName()) {
               $user->recordEvent(
                   UserHistory::UPDATE_CUSTOMER_NAME,
                       $parameters = array(),
                  "Updated name from $old_name to " . $user->getName()
               );
            }

            $message = 'updated';

            return Redirect::back()
                   ->with('flash_message', $message);

        }
        catch (Exception $e) {
            return Redirect::back()
                   ->with('flash_error', $e->getMessage());
        }
    }

    /**
     * /settings/profile
     * /settings
     *
     * @author  Will
     * @author  Alex
     */
    public function show()
    {
        /*
         * Extracting these varibles bring them into scope.
         * This is hacky . We know.
         */
        $legacy = $this->baseLegacyVariables();
        extract($legacy);

        /*
         * Copying in legacy code...
         */
        if (!$customer->hasCreditCardOnFile()) {

            $has_chargify            = false;
            $cust_product            = "Free Starter Account";
            $cust_subscription_state = "active";
            $cust_next_assessment_at = "N/A";
            $cust_masked_credit_card = "";;
            $cust_components          = "";
            $cust_component_quantity  = 0;
            $cust_update_billing_link = "";
            $state_style              = '';
            $expiration_month         = '';
            $expiration_year          = '';


        } else {

            $has_chargify   = true;
            $product_family = 297652;

            $cust_obj = new ChargifyCustomer(null, false);
            try {
                $cust_obj->id = $cust_chargify_id;
                $this_cust    = $cust_obj->getByID();
            }
            catch (ChargifyValidationException $cve) {
                //echo $cve->getMessage();
            }


            $subscription = new ChargifySubscription(null, false);

            try {
                $sub = $subscription->getByCustomerID($this_cust->id);
            }
            catch (ChargifyValidationException $cve) {
                //echo $cve->getMessage();
            }


            if ($sub[0]->state == "trialing") {
                $state_style = "label-warning";
            } else if ($sub[0]->state == "active") {
                $state_style = "label-success";
            } else if ($sub[0]->state == "past_due" || $sub[0]->state == "canceled") {
                $state_style = "label-important";
            }

            $this_sub = $sub[0];

            /*
           * Create the token necessary to create the
           * secure "update billing info" link
           * for each customer
           */
            $token = sha1('update_payment--'.$sub[0]->id.'--QRx_0FzJtvqT30dAQFd9');


            $base_price = number_format($this_sub->product_price_in_cents / 100, 0);

            $components = new ChargifyQuantityBasedComponent();
            //$components->allocated_quantity = 1;
            //$components->update($subscription->id,20537);
            $comps = $components->getAll($this_sub->id, 20537);


            $connector = new ChargifyConnector();
            //$comps = json_decode($connector->retrieveAllMeteredComponentsByProductFamily(297652,".json"));


            /*
             * get appropriate component
             * details and pricing
             */
            switch ($cust_plan) {
                case 1:
                case 2:
                    $comp_number = 20540;
                    break;
                case 3:
                    $comp_number = 20537;
                    break;
                case 4:
                    $comp_number = 20537;
                    break;
                default:
                    //
                    $comp_number = 20540;
                    break;
            }

            $cust_product            = $sub[0]->product->name;
            $cust_subscription_state = $sub[0]->state;
            $cust_next_assessment_at = date('m/d/Y', strtotime($sub[0]->next_assessment_at));
            $cust_masked_credit_card = $sub[0]->credit_card->masked_card_number;
            $cust_components         = $components->getAll($this_sub->id, $comp_number);
            $cust_component_quantity = $cust_components->allocated_quantity;

            if ($cust_component_quantity > 0) {
                $cust_component_detail_link = "<a target='_blank' class='label label-info pull-right'
                           href='settings.php?tab=Management'> See Extra Account Details → </a>";
            } else {
                $cust_component_detail_link = "";
            }


        }


        /*
            * handle expired credit card
            */
        if ($customer->hasCreditCardOnFile()) {
            $expiration_timestamp = strtotime($sub[0]->credit_card->expiration_year . "-" . ($sub[0]->credit_card->expiration_month + 1) . "-01");
            $expiration_month = $sub[0]->credit_card->expiration_month;
            $expiration_year = $sub[0]->credit_card->expiration_year;
        } else {
            $expiration_timestamp = INF;
        }
        $expiration_class            = "";
        $expiration_style            = "";
        $billing_update_label_class  = "label-info";
        $billing_update_button_class = "btn-info";
        $expired_status              = "";
        $billing_tab_icon            = "";
        $billing_tab_class           = "";
        $billing_tooltip             = "";
        $past_due_amount             = "";
        if (isset($sub)) {

            if ($sub[0]->state == "past_due") {
                $billing_tab_icon            = "";
                $billing_tab_class           = "expired-card";
                $billing_tooltip             = "data-toggle='tooltip' data-container='body' data-placement='bottom' data-original-title='There was be a problem processing your payment.  Please verify your billing details!'";
                $billing_update_label_class  = "label-important";
                $billing_update_button_class = "btn-danger";
                $past_due_class              = "text-error";
                $past_due_label              = "<span class='label label-important pull-right'>Past Due</span>";
                $past_due_amount             = "<div class='row'>
                                        <div class='span2'>
                                            <strong>Current Balance:</strong>
                                        </div>
                                        <div class='span3'>
                                            <strong class='text-error'>$" . number_format(($sub[0]->balance_in_cents / 100), 2) . "</strong>
                                            $past_due_label
                                        </div>
                                    </div>";
            }
            if (time() > $expiration_timestamp) {
                $expiration_class            = "text-error";
                $expiration_style            = "font-weight:bold;";
                $billing_update_label_class  = "label-important";
                $billing_update_button_class = "btn-danger";
                $expired_status              = "<span class='label label-important'>Expired Credit Card</span>";
                $billing_tab_icon            = "";
                $billing_tab_class           = "expired-card";
                $billing_tooltip             = "data-toggle='tooltip' data-container='body' data-placement='bottom' data-original-title='Your credit card may have expired.  Please verify your billing details!'";
            }


            $cust_update_billing_link = "<a target='_blank' class='label $billing_update_label_class pull-right'
                           href='https://tailwind.chargify.com/update_payment/" . $sub[0]->id . "/" . substr($token, 0, 12) . "'>
                               Update Billing Info →
                            </a>";

        }

        $vars = array(
            'profile_navigation'       => $this->buildSettingsNavigation('profile'),
            'cust_org_type_display'    => ucfirst($legacy['cust_org_type']),
            'cust_product'             => $cust_product,
            'state_style'              => $state_style,
            'cust_subscription_state'  => $cust_subscription_state,
            'expired_status'           => $expired_status,
            'cust_component_quantity'  => $cust_component_quantity,
            'has_chargify'             => $customer->hasCreditCardOnFile(),
            'cust_next_assessment_at'  => $cust_next_assessment_at,
            'cust_masked_credit_card'  => $cust_masked_credit_card,
            'expiration_style'         => $expiration_style,
            'expiration_class'         => $expiration_class,
            'expiration_month'         => $expiration_month,
            'expiration_year'          => $expiration_year,
            'cust_update_billing_link' => $cust_update_billing_link,
            'past_due_amount'          => $past_due_amount
        );

        $merged = array_merge($vars, $legacy);

        $this->layout->main_content = View::make('analytics.pages.settings.profile', $merged);

    }

    /**
     * POST /settings/profile/timezone/update
     *
     * @expects timezone
     *
     * @author Janell
     */
    public function updateTimezone()
    {
        $timezone = Input::get('timezone');

        try {
            // Make sure we have a valid timezone.
            $date_timezone = new \DateTimeZone($timezone);
            if ($date_timezone === false) {
                throw new Exception();
            }
        } catch (Exception $e) {
            if (Request::ajax()) {
                return Response::json(array(
                    'success' => false,
                    'message' => 'Invalid timezone',
                ));
            }

            return Redirect::back()
                ->with('flash_error', 'Invalid timezone');
        }

        $user = $this->logged_in_customer;

        if ($user->timezone != $timezone) {
            $old_timezone = $user->timezone;

            $user->timezone = $timezone;
            $user->insertUpdateDB();

            $user->recordEvent(
                UserHistory::UPDATE_CUSTOMER_TIMEZONE,
                array(
                    'old' => $old_timezone,
                    'new' => $timezone,
                )
            );
        }

        if (Request::ajax()) {
            return Response::json(array(
                'success' => true,
                'message' => 'Your timezone has been updated.',
            ));
        }

        return Redirect::back()
            ->with('flash_message', 'Your timezone has been updated.');
    }

    /**
     * POST /settings/profile/location/update
     *
     * @expects
     *      city
     *      region
     *      country
     *      timezone
     *
     * @author Janell
     */
    public function updateLocation()
    {
        $city     = Input::get('city');
        $region   = Input::get('region');
        $country  = Input::get('country');
        $timezone = Input::get('timezone');

        if (empty($city)) {
            if (Request::ajax()) {
                return Response::json(array(
                    'success' => false,
                    'message' => 'Missing required parameter: city',
                ));
            }

            return Redirect::back()
                ->with('flash_error', 'Missing required parameter: city');
        }

        if (empty($region)) {
            if (Request::ajax()) {
                return Response::json(array(
                    'success' => false,
                    'message' => 'Missing required parameter: region',
                ));
            }

            return Redirect::back()
                ->with('flash_error', 'Missing required parameter: region');
        }

        if (empty($country)) {
            if (Request::ajax()) {
                return Response::json(array(
                    'success' => false,
                    'message' => 'Missing required parameter: country',
                ));
            }

            return Redirect::back()
                ->with('flash_error', 'Missing required parameter: country');
        }

        $user = $this->logged_in_customer;

        $updated_location = false;

        $old_location = array(
            'city'    => $user->city,
            'region'  => $user->region,
            'country' => $user->country,
        );

        if ($user->city != $city) {
            $user->city = $city;
            $updated_location = true;
        }

        if ($user->region != $region) {
            $user->region = $region;
            $updated_location = true;
        }

        if ($user->country != $country) {
            $user->country = $country;
            $updated_location = true;
        }

        if (!empty($timezone)) {
            try {
                // Make sure we have a valid timezone.
                $date_timezone = new \DateTimeZone($timezone);
                if ($date_timezone === false) {
                    throw new Exception();
                }
            } catch (Exception $e) {
                if (Request::ajax()) {
                    return Response::json(array(
                        'success' => false,
                        'message' => 'Invalid timezone',
                    ));
                }

                return Redirect::back()
                    ->with('flash_error', 'Invalid timezone');
            }

            if ($user->timezone != $timezone) {
                $old_timezone = $user->timezone;

                $user->timezone = $timezone;
                $updated_timezone = true;
            }
        }

        if ($updated_location || $updated_timezone) {
            $user->insertUpdateDB();

            if ($updated_location) {
                $user->recordEvent(
                    UserHistory::UPDATE_CUSTOMER_LOCATION,
                    array(
                        'old' => $old_location,
                        'new' => array(
                            'city'    => $user->city,
                            'region'  => $user->region,
                            'country' => $user->country,
                        ),
                    )
                );
            }

            if ($updated_timezone) {
                $user->recordEvent(
                    UserHistory::UPDATE_CUSTOMER_TIMEZONE,
                    array(
                        'old' => $old_timezone,
                        'new' => $timezone,
                    )
                );
            }
        }


        if (Request::ajax()) {
            return Response::json(array(
                'success' => true,
                'message' => 'Your location has been updated.',
            ));
        }

        return Redirect::back()
            ->with('flash_message', 'Your location has been updated.');
    }



}