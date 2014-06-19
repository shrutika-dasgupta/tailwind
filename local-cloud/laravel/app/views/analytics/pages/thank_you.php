<?php ini_set('display_errors', 'off');
error_reporting(0);
/**
 *
 * @author      Alex
 * @author      Will
 *
 *
 *
 *              I THINK WE DONT USE THIS PAGE - <3 WILL
 *
 *
 *
 *
 * This page is where successful chargify returns are sent
 *
 * We expect chargify to send
 *
 * /thankyou.php?
 *      id={CHARGIFY_SUBSCRIPTION_ID}
 *      &ref={EXISTING_CUSTOMER_ID}
 *
 * If the ref is empty, they are a new account
 * If the id is empty, that shit's a mistake
 *
 */


try {

    /*
     * If the subscription id is not set and it's not valid, we are going to have
     * a bad time
     */
    if (!all_in_array($_GET, 'id', 'ref')) {
        throw new Exception('Error with chargify, not a fields sent');
    }

    $subscription_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

    if (!$subscription_id) {
        throw new Exception('Error with chargify, id wasnt sent');
    }

    /*
     * If there was a reference passed, they already have a customer id
     * we just need to log it and change their account to their new plan
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
        $customer->organization()->saveToDB();

        /*
         * Change the plan based on the subscription product id
         *
         * I'm using a switch case here instead of searching in the DB because we might make
         * plans that both use the same chargify plan id (custom plans with the same chargify
         * plan id. This just works for the "typical" case
         *
         */
        switch ($subscription->product->id) {

            default:
                $customer->organization()->changePlan(1);
                break;

            /*
             * Lite
             */
            case 3319111:
                $customer->organization()->changePlan(2,'dont prorate');
                break;

            /*
             * Pro
             */
            case 3319112:
                $customer->organization()->changePlan(3,'dont prorate');
                break;

        }

        /*
         * Now we show the thank you page and redirect with Javascript
         * I'm not really sure why we do this, but it's how it was
         * {AT THE BOTTOM OF THIS PAGE}
         */

    } else {
        /*
         * New signup straight from the public pricing page
         *
         * @todo will need to make sure we track conversions for TRADA
         * when customers complete the signup page.
         *
         */

        header("location: signup.php?id=$subscription_id");
        exit;
    }
}

catch (Exception $e) {
    /*
     * Log the error
     */
    error_log($e->getMessage());

    /*
     * Send them away!
     */
    $error = urlencode($e->getMessage());
    // echo var_dump($error);
    header('location:/upgrade.php?e=' . $error);
    exit;
}

/*
 * SHOW THANK YOU PAGE
 * This is only seen when its an upgrade from FREE to paid after adding a chargify ID
 */
$page = "Thank You";
?>

<script>
    jQuery(document).ready(function () {
        setTimeout('location.href="/profile?upgrade=' + '<?= $new_plan; ?>"', 5000);
    });
</script>
