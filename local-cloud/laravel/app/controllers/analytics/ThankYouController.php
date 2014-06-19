<?php namespace Analytics;

use
    ChargifySubscription,
    Exception,
    Log,
    Plan,
    Redirect,
    User,
    UserHistory,
    View;


/**
 * Controller for actions related to the signup flow and process
 *
 * @author  Will
 *
 */
class ThankYouController extends BaseController
{

    protected $layout = 'layouts.analytics';

    /**
     * Construct
     *
     * @author  Will
     */
    public function __construct()
    {

        parent::__construct();

        Log::setLog(__FILE__, 'Reporting', 'thanks_controller');
    }

    /**
     * /thank-you
     *
     * Processes signup coming from chargify with GET params:
     *  id -> chargify subscription id (NOT customer id!!)
     *  ref -> chargify customer reference (this SHOULD be the user's cust_id in our database)
     *  plan -> will be either 'free', 'lite' or 'pro' (this is only used for google analytics
     *          conversion tracking).
     *
     * @author  Alex
     *
     */
    public function showThanks()
    {

        $vars = $this->baseLegacyVariables();

        try {

            /*
             * If the subscription id is not set and it's not valid, we are going to have
             * a bad time
             *
             */
            if (!all_in_array($_GET, 'id', 'ref')) {
                throw new Exception('Error with chargify, not all fields sent');
            }

            $subscription_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

            if (!$subscription_id) {
                throw new Exception('Error with chargify, id wasnt sent');
            }

            /*
             * If there was a reference passed, they already have a customer id
             * we just need to log it and change their account to their new plan
             *
             */
            if ($_GET['ref'] > 0) {

                $customer_id = filter_var($_GET['ref'], FILTER_SANITIZE_NUMBER_INT);

                $customer = User::find($customer_id);
                /*
                 * Go find the subscription
                 */

                $subscription = new ChargifySubscription();
                $subscription = $subscription->getByID($subscription_id);

                $customer->organization()->chargify_id = $subscription->customer->id;
                $customer->organization()->insertUpdateDB();

                /*
                 * Change the plan based on the subscription product id
                 *
                 * I'm using a switch case here instead of searching in the DB because we might make
                 * plans that both use the same chargify plan id (custom plans with the same chargify
                 * plan id. This just works for the "typical" case
                 *
                 *
                 */
                switch ($subscription->product->id) {

                    default:
                        /*
                        * throw an error, same plan @todo
                        */

                        $new_plan    = Plan::FREE_WITH_CC;
                        $new_plan_id = Plan::FREE_PLAN_ID;

                        $customer->organization()->changePlan($new_plan_id);
                        break;

                    /*
                     * Lite
                     *
                     */
                    case 3319111:
                        $new_plan    = Plan::LITE;
                        $new_plan_id = Plan::LITE_PLAN_ID;

                        $customer->organization()->changePlan(
                                 $new_plan_id,
                                 'dont prorate',
                                 'ignore chargify'
                        );
                        break;

                    /*
                     * Pro
                     *
                     */
                    case 3319112:
                        $new_plan    = Plan::PRO;
                        $new_plan_id = Plan::PRO_PLAN_ID;

                        $customer->organization()->changePlan(
                                 $new_plan_id,
                                 'dont prorate',
                                 'ignore chargify'
                        );
                        break;

                }

                /*
                 * Record the history
                 */
                $customer->recordEvent(
                    UserHistory::UPGRADE,
                        $parameters = array(
                            'from_plan' => Plan::FREE_NO_CC,
                            'to_plan' => $new_plan
                        )
                );

                $customer->recordEvent(
                         UserHistory::TRIAL_START,
                         $parameters = array(
                             'plan' => $new_plan
                         )
                );

                /*
                 * Now we show the thank you page and redirect with Javascript
                 * I'm not really sure why we do this, but it's how it was
                 * {AT THE BOTTOM OF THIS PAGE}
                 *
                 */

            } else {
                /*
                 * New signup straight from the public pricing page
                 *
                 * @todo will need to make sure we track conversions for TRADA
                 * when customers complete the signup page.
                 */
                return Redirect::to("/signup?id=$subscription_id");

            }
        }

        catch (Exception $e) {
            /*
             * Log the error
             *
             */
            error_log($e->getMessage());

            /*
             * Send them away!
             *
             */
            $error = urlencode($e->getMessage());

            // echo var_dump($error);
            return Redirect::to("/upgrade?e=$error");

        }

        /*
         * SHOW THANK YOU PAGE
         * This is only seen when its an upgrade from FREE to paid after adding a chargify ID
         *
         */
        $new_plan = $customer->organization()->plan;

        $js_redirect = "
        <script>
            jQuery(document).ready(function () {
                setTimeout('location.href=\"/profile?upgrade=' + '$new_plan\"', 5000);
            });
        </script>";

        $pre_nav_vars = array_merge($vars,
                                    array(
                                         'js_redirect' => $js_redirect
                                    ));


        $this->layout->head            = $this->buildInclude('head');
        $this->layout->loading_overlay = View::make(
                                             'analytics.components.pre_nav.loading_overlay_thank_you',
                                             $pre_nav_vars
        );
        $this->layout->main_content    = '';
    }

}