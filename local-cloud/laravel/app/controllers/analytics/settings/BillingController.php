<?php

namespace Analytics\Settings;

use View,
    Redirect,
    URL,
    Input,
    Mail,
    Config,
    Log;

use User,
    Plan,
    UserHistory,
    UserProperty,
    UserEmail;

use ChargifyStatement,
    ChargifySubscription,
    ChargifyCustomer,
    ChargifyQuantityBasedComponent,
    ChargifyConnector,
    ChargifyCoupon;

use Exception, ChargifyValidationException;

/**
 * Class BillingController
 *
 * @package Analytics\Settings
 */
class BillingController extends BaseController
{

    protected $layout = 'layouts.analytics';

    /**
     * /settings/billing/change-plan/{planSlug}
     *
     * @author  Will
     */
    public function changePlan($plan_slug)
    {

        $base_vars = $this->baseLegacyVariables();
        extract($base_vars);

        /**
         * @var $customer /User
         * @var $user     User
         */
        $user = $customer;

        $new_handle = filter_var($plan_slug, FILTER_SANITIZE_STRING);

        switch ($new_handle) {
            default:
                $new_plan_name = Plan::FREE_WITH_CC;
                $new_plan_id   = Plan::FREE_PLAN_ID;
                break;

            case 'basic-pinterest-analytics-plan':
                $new_plan_name = Plan::LITE;
                $new_plan_id   = Plan::LITE_PLAN_ID;
                break;

            case 'professional-pinterest-analytics-plan':
                $new_plan_name = Plan::PRO;
                $new_plan_id   = Plan::PRO_PLAN_ID;
                break;

            case 'agency-pinterest-analytics-plan':
                $new_plan_name = Plan::AGENCY;
                $new_plan_id   = Plan::AGENCY_PLAN_ID;
                break;

        }

        try {

            // Record old plan for tracking purposes
            $old_plan_name = $user->plan()->name;
            $old_plan_id   = $user->plan()->plan_id;

            // If this is a downgrade, send them to the survey before changing plans.
            if (($old_plan_id == 2 AND $new_plan_id == 1)
                || ($old_plan_id == 3 AND $new_plan_id == 2)
                || ($old_plan_id == 3 and $new_plan_id == 1)
            ) {
                return Redirect::to(URL::route('billing-downgrade-survey', array(
                    'old_plan' => $old_plan_id,
                    'new_plan' => $new_plan_id,
                )));
            }

            if (!(($old_plan_id == 1 AND $new_plan_id == 2)
                || ($old_plan_id == 1 AND $new_plan_id == 3)
                || ($old_plan_id == 2 AND $new_plan_id == 3)
            )) {
                throw new Exception('Unknown plan change');
            }

            $user->organization()->changePlan($new_plan_id);

            $user->recordEvent(
                UserHistory::UPGRADE,
                array(
                    'from_plan' => $old_plan_name,
                    'to_plan'   => $new_plan_name,
                )
            );

            return Redirect::to("/settings/billing?plan=$new_plan_id")
                   ->with('flash_message', 'Your plan has been updated');

        } catch (Exception $e) {
            Log::error($e);

            return Redirect::back()
                   ->with('flash_error', $e->getMessage());
        }

    }

    /**
     * Displays billing statements
     *
     * @author Daniel
     *
     * @return void
     */
    public function statements()
    {
        $base_vars = $this->baseLegacyVariables();
        extract($base_vars);

        if ($customer->is_admin == User::PERMISSIONS_VIEWER) {
            return Redirect::to('/settings/profile');
        }

        $statements = array();
        if ($customer->hasCreditCardOnFile()) {
            try {
                $subscription = new ChargifySubscription();
                $subscription = $subscription->getByCustomerID($customer->organization()->chargify_id);
            } catch (ChargifyValidationException $e) {}

            $subscription = $subscription[0];

            $statements = new ChargifyStatement();
            $statements = $statements->getBySubscriptionID($subscription->id);
        }

        $this->layout->main_content = View::make(
            'analytics.pages.settings.billing.statements',
            array(
                'statements'          => $statements,
                'settings_navigation' => $this->buildSettingsNavigation('billing'),
                'billing_navigation'  => $this->buildNavigation('statements'),
            )
        );
    }

    /**
     * Downloads a specific billing statement as PDF.
     *
     * @param int $id
     *
     * @return void
     */
    public function statement($id)
    {
        extract($this->baseLegacyVariables());

        if (!$customer->hasCreditCardOnFile()) {
            return Redirect::to('/settings/billing/statements');
        }

        if ($customer->is_admin == User::PERMISSIONS_VIEWER) {
            return Redirect::to('/settings/profile');
        }

        try {
            $subscription = new ChargifySubscription();
            $subscription = $subscription->getByCustomerID($customer->organization()->chargify_id);
        } catch (ChargifyValidationException $e) {}

        $subscription = $subscription[0];

        $statement = new ChargifyStatement();
        $statement = $statement->getByID($id);

        // Prevent access to other customer's statements.
        if ($statement->subscription_id != $subscription->id) {
            return Redirect::to('/settings/billing/statements');
        }

        $statement_pdf = new ChargifyStatement();
        $statement_pdf = $statement_pdf->getByID($id, 'PDF');

        $date = !empty($statement->closed_at) ? date('F-j-Y', strtotime($statement->closed_at)) : 'Current';
        $filename = "Tailwind-Statement-$date.pdf";
        
        header('Content-type: application/octet-stream');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        echo $statement_pdf;
        
        $customer->recordEvent(UserHistory::DOWNLOAD_BILLING_STATEMENT);
    }

    /**
     * Displays subscription and general billing info.
     *
     * @author Yesh
     * 
     * @return void
     */
    public function subscription()
    {
        $base_vars = $this->baseLegacyVariables();
        extract($base_vars);

        if ($customer->is_admin == User::PERMISSIONS_VIEWER) {
            return Redirect::to('/settings/profile');
        }

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
            $expiration_timestamp     = INF;

        } else {

            $has_chargify   = true;
            $product_family = 297652;

            $cust_obj = new ChargifyCustomer(null, false);
            try {
                $cust_obj->id = $cust_obj->id = $customer->organization()->chargify_id;
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
             * Handle card expired notice (special case when it's set for december of a year)
             */
            $expiration_month = $sub[0]->credit_card->expiration_month;
            $expiration_year = $sub[0]->credit_card->expiration_year;
            if($expiration_month == 12){
                $expiration_month = 0;
                $expiration_year++;
            }
            $expiration_timestamp = strtotime($expiration_year . "-" . ($expiration_month + 1) . "-01");

            $base_price = number_format($this_sub->product_price_in_cents / 100, 0);

            $components = new ChargifyQuantityBasedComponent();
            //$components->allocated_quantity = 1;
            //$components->update($subscription->id,20537);
            $comps = $components->getAll($this_sub->id, 20537);

            $coupon = new ChargifyCoupon();
            if(!empty($sub[0]->coupon_code)){
                $cust_coupon_details = $coupon->getByCode(297652, $sub[0]->coupon_code);
                $cust_coupon             = $sub[0]->coupon_code;
                $cust_coupon_description = $cust_coupon_details->description;
                $cust_coupon_amount      = $cust_coupon_details->amount_in_cents / 100;
                $cust_coupon_percent     = $cust_coupon_details->percentage;

                /*
                 * Deal with pinreach coupon specifically
                 */
//                if(strtolower($cust_coupon) == "newhome"
//                    && (((time() - strtotime($sub[0]->trial_ended_at))/60/60/24) < 93)){
//                    if($cust_plan == 2){
//                        $cust_coupon_amount = 19.33;
//                    } else if($cust_plan == 3){
//                        $cust_coupon_amount = 66;
//                    } else {
//                        $cust_coupon_amount = 0;
//                    }
//                } else if (strtolower($cust_coupon) == "newhome") {
//                    $cust_coupon_amount = 0;
//                }
            } else {
                $cust_coupon_amount = 0;
            }



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
            $comp_details            = json_decode($connector
                                                   ->retrieveComponentByComponentId($product_family, $comp_number, ".json"));


            function getComponentPriceBreakdown($price_ranges = array(), $quantity)
            {

                $comp_total_price = 0;
                $counter          = 0;
                $breakdown        = array();

                foreach ($price_ranges as $range) {

                    if ($quantity > $range->ending_quantity) {
                        $breakdown[$counter]['price'] = $range->unit_price * ($range->ending_quantity - $range->starting_quantity + 1);
                        $breakdown[$counter]['print'] = "(" . ($range->ending_quantity - $range->starting_quantity + 1) . " × $" . number_format($range->unit_price, 0) . ")";
                    } else if ($quantity > $range->starting_quantity - 1) {
                        $breakdown[$counter]['price'] = $range->unit_price * ($quantity - $range->starting_quantity + 1);
                        $breakdown[$counter]['print'] = "(" . ($quantity - $range->starting_quantity + 1) . " × $" . number_format($range->unit_price, 0) . ")";
                    }

                    if ($range->starting_quantity == $range->ending_quantity) {
                        $breakdown[$counter]['grid'] = addOrdinalNumberSuffix($range->starting_quantity) . " Extra Account: $" . number_format($range->unit_price, 0);
                    } else {
                        $breakdown[$counter]['grid'] = addOrdinalNumberSuffix($range->starting_quantity) . " - " . addOrdinalNumberSuffix($range->ending_quantity) . " Extra Accounts: $" . number_format($range->unit_price, 0);
                    }

                    $counter++;
                }

                return $breakdown;
            }

            function addOrdinalNumberSuffix($num)
            {
                if (!in_array(($num % 100), array(11, 12, 13))) {
                    switch ($num % 10) {
                        // Handle 1st, 2nd, 3rd
                        case 1:
                            return $num . 'st';
                        case 2:
                            return $num . 'nd';
                        case 3:
                            return $num . 'rd';
                    }
                }

                return $num . 'th';
            }

            function getComponentsRecurringPrice($price_ranges = array(), $quantity)
            {

                $comp_total_price = 0;

                foreach ($price_ranges as $range) {

                    if ($quantity > $range->ending_quantity) {
                        $comp_total_price += $range->unit_price * ($range->ending_quantity - $range->starting_quantity + 1);
                    } else if ($quantity >= $range->starting_quantity - 1) {
                        $comp_total_price += $range->unit_price * ($quantity - $range->starting_quantity + 1);
                    }

                }

                return $comp_total_price;
            }

            $component_total_price     = getComponentsRecurringPrice($comp_details->component->prices, $cust_component_quantity);
            $new_component_total_price = getComponentsRecurringPrice($comp_details->component->prices, $cust_component_quantity + 1);
            $current_monthly_total     = number_format($component_total_price + $base_price - $cust_coupon_amount, 0);
            $new_monthly_total         = number_format($new_component_total_price + $base_price - $cust_coupon_amount, 0);
            $comp_breakdown            = getComponentPriceBreakdown($comp_details->component->prices, $cust_component_quantity);

            /*
             * See if there's a percentage associated with the coupon
             */
            if(!empty($cust_coupon_percent)){
                $cust_coupon_amount = ((int)$cust_coupon_percent/100) * $current_monthly_total;
                $current_monthly_total     = number_format($component_total_price + $base_price - $cust_coupon_amount, 0);
                $new_monthly_total         = number_format($new_component_total_price + $base_price - $cust_coupon_amount, 0);
            }

        }

        $view_vars = array(
            'settings_navigation'     => $this->buildSettingsNavigation('billing'),
            'billing_navigation'      => $this->buildNavigation('subscription'),
            'sub'                     => $sub,
            'expiration_timestamp'    => $expiration_timestamp,
            'cust_product'            => $cust_product,
            'base_price'              => $base_price,
            'comp_breakdown'          => $comp_breakdown,
            'current_monthly_total'   => $current_monthly_total,
            'new_monthly_total'       => $new_monthly_total,
            'state_style'             => $state_style,
            'cust_coupon'             => $cust_coupon,
            'cust_coupon_description' => $cust_coupon_description,
            'cust_coupon_amount'      => $cust_coupon_amount
        );

        $this->layout->main_content = View::make(
            'analytics.pages.settings.billing.subscription',
            array_merge($view_vars, $base_vars)
        );
    }

    /**
     * /upgrade
     *
     * @author  Yesh
     * @author  Alex
     */
    public function showUpgrade()
    {

        $this->layout_defaults['head']['upgrade'] = 1;
        $this->layout->head                       = $this->buildInclude('head');


        $this->logged_in_customer->incrementUserProperty(UserProperty::VIEW_REPORT.'upgrade',1);
        $this->logged_in_customer->setUserProperty(UserProperty::LAST_VIEWED_REPORT_AT.'upgrade',time());

        $click_from = array_get($_GET, 'ref');

        $this->logged_in_customer->recordEvent(
                                 UserHistory::VIEW_APP_UPGRADE,
                                 array(
                                      'Clicked from' => $click_from
                                 )
        );

        $vars                       = $this->baseLegacyVariables();
        $vars['alert']              = $this->generateAlertBox();

        $trial_end_date = $this->logged_in_customer->trialEndDate();
        $trial_days_left = 0;
        $pretty_trial_end_date = '';

        if ($trial_end_date > time()) {
            try {
                $timezone = new \DateTimeZone($this->logged_in_customer->getTimezone());

                $trial_end_datetime = new \DateTime();
                $trial_end_datetime->setTimestamp($trial_end_date);
                $trial_end_datetime->setTimezone($timezone);

                $trial_days_left = $trial_end_datetime->diff(new \DateTime('now', $timezone))->days;

                $pretty_trial_end_date = $trial_end_datetime->format('F j, Y @ g:i A');
            } catch (Exception $e) {}
        }

        $vars['trial_days_left']       = $trial_days_left;
        $vars['pretty_trial_end_date'] = $pretty_trial_end_date;

        $this->layout->main_content = View::make('analytics.pages.upgrade', $vars);
        $this->layout->pre_body_close .= View::make('analytics.components.pre_body_close.upgrade');
    }

    /**
     * Displays a survey after a user has downgraded their plan.
     *
     * @author Janell
     *
     * @route /settings/billing/downgrade-survey
     *
     * @return void
     */
    public function downgradeSurvey()
    {
        $plan_names = array(
            Plan::FREE_PLAN_ID   => 'Free',
            Plan::LITE_PLAN_ID   => Plan::LITE,
            Plan::PRO_PLAN_ID    => Plan::PRO,
            Plan::AGENCY_PLAN_ID => Plan::AGENCY,
        );

        $old_plan_id   = Input::get('old_plan');
        $new_plan_id   = Input::get('new_plan');
        $old_plan_name = $plan_names[$old_plan_id];
        $new_plan_name = $plan_names[$new_plan_id];

        $this->layout->main_content = View::make(
            'analytics.pages.settings.billing.downgrade_survey',
            array(
                'settings_navigation' => $this->buildSettingsNavigation('billing'),
                'old_plan_id'         => $old_plan_id,
                'new_plan_id'         => $new_plan_id,
                'old_plan_name'       => $old_plan_name,
                'new_plan_name'       => $new_plan_name,
            )
        );
    }

    /**
     * Processes a downgrade survey, downgrades the user's plan, and sends an email to the team to
     * notify them of the downgrade and the user's answers.
     *
     * @author Janell
     *
     * @route /settings/billing/downgrade
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function downgrade()
    {
        $user = $this->logged_in_customer;

        $reason      = Input::get('reason');
        $reason_text = Input::get('reason_text', '');
        $old_plan_id = Input::get('old_plan');
        $new_plan_id = Input::get('new_plan');

        $plan_names = array(
            Plan::FREE_PLAN_ID   => Plan::FREE_WITH_CC,
            Plan::LITE_PLAN_ID   => Plan::LITE,
            Plan::PRO_PLAN_ID    => Plan::PRO,
            Plan::AGENCY_PLAN_ID => Plan::AGENCY,
        );

        $old_plan_name = $plan_names[$old_plan_id];
        $new_plan_name = $plan_names[$new_plan_id];

        $beaver_facts = Config::get('facts.beaver_facts');
        $fact_key     = array_rand($beaver_facts);
        $fact         = $beaver_facts[$fact_key];

        try {
            $user->organization()->changePlan($new_plan_id);

            $user->recordEvent(
                UserHistory::DOWNGRADE,
                array(
                    'from_plan' => $old_plan_name,
                    'to_plan'   => $new_plan_name,
                    'reason'    => $reason,
                    'answer'    => $reason_text,
                )
            );

            // Send a confirmation email to the user.
            $email = \Pinleague\Email::instance();
            $email->subject('Confirmation: Your Tailwind Plan has been Downgraded');
            $email->body('downgrade_confirmation', array(
                'first_name'     => $user->first_name,
                'old_plan_name'  => $old_plan_name,
                'new_plan_name'  => ($new_plan_name == Plan::FREE_WITH_CC) ? 'Free' : $new_plan_name,
            ));
            $email->to($user);
            $email->send();

            // Send a downgrade notification to the team.
            Mail::send(
                array(
                    'html' => 'shared.emails.html.downgrade_notification',
                    'text' => 'shared.emails.plaintext.downgrade_notification'
                ),
                array(
                    'customer_name'  => $user->getName(),
                    'customer_email' => $user->email,
                    'old_plan_name'  => $old_plan_name,
                    'new_plan_name'  => $new_plan_name,
                    'cust_id'        => $user->cust_id,
                    'org_id'         => $user->org_id,
                    'chargify_id'    => $user->organization()->chargify_id,
                    'signup_date'    => date('c', $user->organization()->signupDate()),
                    'trial_start'    => date('c', $user->organization()->trial_start_at),
                    'trial_end'      => date('c', $user->organization()->trial_end_at),
                    'billed'         => $user->organization()->billing_event_count,
                    'revenue'        => "$" . $user->organization()->total_amount_billed,
                    'reason'         => $reason,
                    'reason_text'    => $reason_text,
                    'fact'           => $fact,
                ),
                function ($message) use ($user)
                {
                    $message->from('bd+downgradebot@tailwindapp.com','Downgrade Bot');
                    $message->to('bd@tailwindapp.com', 'Business Development Team');
                    $message->bcc('will@tailwindapp.com');
                    $message->subject("Downgrade Alert | {$user->getName()} (cust_id:$user->cust_id)");
                }
            );
        } catch (Exception $e) {
            Log::error($e);
        }

        return Redirect::to(URL::route('billing-downgrade-confirmation', array(
            'plan' => $new_plan_id,
        )));
    }

    /**
     * Records user history when a user decides to keep their current plan after seeing the
     * downgrade survey.
     *
     * @author Janell
     *
     * @route /settings/billing/cancel-downgrade
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancelDowngrade()
    {
        $user = $this->logged_in_customer;

        $old_plan_id = Input::get('old_plan');
        $new_plan_id = Input::get('new_plan');

        $plan_names = array(
            Plan::FREE_PLAN_ID   => Plan::FREE_WITH_CC,
            Plan::LITE_PLAN_ID   => Plan::LITE,
            Plan::PRO_PLAN_ID    => Plan::PRO,
            Plan::AGENCY_PLAN_ID => Plan::AGENCY,
        );

        $old_plan_name = $plan_names[$old_plan_id];
        $new_plan_name = $plan_names[$new_plan_id];

        $user->recordEvent(
            UserHistory::CANCEL_DOWNGRADE,
            array(
                'from_plan' => $old_plan_name,
                'to_plan'   => $new_plan_name,
            )
        );

        return Redirect::to(URL::route('billing'))
            ->with('flash_message', 'Thank you for keeping your current plan!');
    }

    /**
     * Displays a downgrade confirmation page.
     *
     * @author Janell
     *
     * @route /settings/billing/downgrade-confirmation
     *
     * @return void
     */
    public function downgradeConfirmation()
    {
        $plan_names = array(
            Plan::FREE_PLAN_ID   => 'Free',
            Plan::LITE_PLAN_ID   => Plan::LITE,
            Plan::PRO_PLAN_ID    => Plan::PRO,
            Plan::AGENCY_PLAN_ID => Plan::AGENCY,
        );

        $new_plan_id   = Input::get('plan');
        $new_plan_name = $plan_names[$new_plan_id];

        $this->layout->main_content = View::make(
            'analytics.pages.settings.billing.downgrade_confirmation',
            array(
                'settings_navigation' => $this->buildSettingsNavigation('billing'),
                'new_plan_id'         => $new_plan_id,
                'new_plan_name'       => $new_plan_name,
            )
        );
    }

    /**
     * Builds the billing navigation.
     *
     * @param string $tab
     *
     * @return string
     */
    protected function buildNavigation($tab)
    {
        return View::make(
            'analytics.pages.settings.billing.navigation',
            array('tab' => $tab)
        );
    }
}
