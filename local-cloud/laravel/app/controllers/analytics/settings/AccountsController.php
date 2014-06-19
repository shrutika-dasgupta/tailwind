<?php namespace Analytics\Settings;

use
    ChargifyConnector,
    ChargifyCustomer,
    ChargifySubscription,
    ChargifyQuantityBasedComponent,
    ChargifyValidationException,
    Exception,
    Input,
    OrganizationException,
    Pinleague\Pinterest\PinterestProfileNotFoundException,
    Profile,
    Redirect,
    RequiredVariableException,
    Tasks,
    User,
    UserAccount,
    UserAccountsDomain,
    UserAccountsDomainException,
    UserEmailPreferences,
    UserHistory,
    UserIndustries,
    View;

/**
 * Class AccountsController
 *
 * @package Analytics\Settings
 */
class AccountsController extends BaseController
{

    /**
     * /settings/account/add
     *
     * @author  Will
     */
    public function add()
    {

        try {

            $legacy = $this->baseLegacyVariables();
            extract($legacy);

            /** @var $customer \User */
            /** @var  $user \User */
            $user = $customer;

            /*
             * If we didn't send all the variables, throw an exception
             */
            if (!all_in_array($_POST,
                              'account_name', 'username', 'domain', 'account_type', 'industry_id'
            )
            ) {
                throw new RequiredVariableException('Post variables not all sent');
            }

            $account_name = filter_var($_POST['account_name'], FILTER_SANITIZE_STRING);
            $username     = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
            $domain       = filter_var($_POST['domain'], FILTER_SANITIZE_STRING);
            $account_type = filter_var($_POST['account_type'], FILTER_SANITIZE_STRING);
            $industry_id  = filter_var($_POST['industry_id'], FILTER_SANITIZE_NUMBER_INT);


            /*
             * We want to load the username from Pinterest
             * this method isn't explict, but thats what it does
             * need to change that
             */

            $profile             = Profile::createViaApi($username);
            $profile->track_type = 'user';
            $profile->insertUpdateDB();

            /*
             * Lets make sure that we don't already have this account
             * attached to the organization
             */
            if ($user->organization()->hasUserAccount($profile->username)) {
                throw new OrganizationException('Account already in organization');
            }

            /*
             * User accounts are attached to the organziation
             * This also adds or updates to status_profiles
             */
            $user_account = $user->organization()->addUserAccount(
                                 $account_name,
                                 $profile,
                                 $account_type,
                                 $industry_id
            );

            /*
             * Create default preferences for email queue
             */
            $preferences = UserEmailPreferences::getDefault($user_account);
            $preferences->setPropertyOfAllModels('cust_id', $user->cust_id);
            $preferences->insertUpdateDB();

            $user->removeQueuedAutomatedEmails()
                 ->seedAutomatedEmails();

            $user->recordEvent(
                 UserHistory::ADD_ACCOUNT,
                 array(
                      '$username' => $profile->username
                 )
            );

            /*
             * User accounts have domains attached to them, normally
             */
            try {

                $user_account->addDomain($domain);

                $user->recordEvent(
                     UserHistory::ADD_ACCOUNT_DOMAIN,
                     array(
                          '$domain' => $domain
                     )
                );

            }
            catch (UserAccountsDomainException $e) {
                //No domain, probably didn't send anything
            }

            $user->organization()->addChargifyComponent();

            return Redirect::back()
                           ->with('flash_message', 'Account successfully added');


        }
        catch (PinterestProfileNotFoundException $e) {
            return Redirect::back()
                           ->with('flash_error', 'That profile could not be found');
        }
        catch (\UserAccountException $e) {
            return Redirect::back()
                           ->with('flash_error', $e->getMessage());
        }
        catch (Exception $e) {
            return Redirect::back()
                           ->with('flash_error', $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine());
        }

    }

    /**
     * /settings/account/edit
     *
     * @author Will
     */
    public function edit()
    {
        try {
            $user = $this->logged_in_customer;


            if (!all_in_array($_POST,
                              'account_id', 'account_name', 'username', 'domain',
                              'account_type', 'industry_id'
            )
            ) {
                throw new RequiredVariableException('Post variables not all sent');
            }

            $account_id   = filter_var($_POST['account_id'], FILTER_SANITIZE_NUMBER_INT);
            $account_name = filter_var($_POST['account_name'], FILTER_SANITIZE_STRING);
            $username     = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
            $domain       = filter_var($_POST['domain'], FILTER_SANITIZE_STRING);
            $account_type = filter_var(Input::get('account_type'), FILTER_SANITIZE_STRING);
            $industry_id  = filter_var($_POST['industry_id'], FILTER_SANITIZE_NUMBER_INT);


            $message = 'updated-account&';

            $user_account = UserAccount::find($account_id);

            /*
             * If the account name changes, we want to update it
             */
            if ($account_name != $user_account->account_name) {

                $user->recordEvent(
                     UserHistory::UPDATE_ACCOUNT_NAME,
                     $parameters = array(),
                     "Update account name from $user_account->account_name to $account_name"
                );

                $message .= '&account_name=1';
            }

            /*
             * If account type changes, we want to update it
             */
            if ($account_type != $user_account->account_type) {

                $user->recordEvent(
                     UserHistory::UPDATE_ACCOUNT_TYPE,
                     $parameters = array(
                         '$old_account_type' => $user_account->account_type,
                         '$new_account_type' => $account_type,
                     ),
                     "Update account type from $user_account->account_type to $account_type"
                );

                $message .= '&account_type=1';
            }

            /*
             * If industry changes, we want to update it
             */
            if ($industry_id != $user_account->industry_id && $industry_id != '') {

                $user->recordEvent(
                     UserHistory::UPDATE_ACCOUNT_INDUSTRY,
                     $parameters = array(
                         '$old_account_industry' => $user_account->industry_id,
                         '$new_account_industry' => $industry_id
                     ),
                     "Update account industry from $user_account->industry_id " .
                     " to $industry_id"
                );

                $message .= '&account_industry=1';
            }

            /*
             * If the username changes, we want to add a new
             * //orphan the account
             * //create new account if there is no orphaned one
             */
            if ($username != $user_account->username) {
                $old_username = $user_account->username;

                $profile             = Profile::createViaApi($username);
                $profile->track_type = 'user';
                $profile->saveToDB("INSERT INTO", 'append update string');

                $new_user_account = UserAccount::create($profile,
                                                        $user_account->org_id,
                                                        $user_account->track_type
                );

                $new_user_account->addDomain($domain);

                /*
                 * Remove the old preferences
                 * and remove the emails currently on the queue
                 */
                $user->removeEmailPreferences($user_account)
                    ->removeQueuedAutomatedEmails();
                /*
                * Create default preferences for email queue
                */
                $preferences = UserEmailPreferences::getDefault($new_user_account);
                $preferences->setPropertyOfAllModels('cust_id', $user->cust_id);
                $preferences->insertUpdateDB();

                $user->seedAutomatedEmails();

                /*
                 * Orphan the old account
                 */
                /** @var UserAccount $user_account */
                $user_account->changeTrackType('orphan');
                $user_account->insertUpdateDB();



                /*
                 * Delete the email preferences for the orphaned account
                 */
                $user->recordEvent(
                     UserHistory::UPDATE_ACCOUNT_USERNAME,
                     $parameters = array(
                         '$old_username' => $old_username,
                         '$new_username' => $username
                     ),
                     "Change account name from $old_username to $username"
                );

                $message .= '&username=1';

                /*
                 * Set the user account as the new one we just made
                 */
                $user_account = $new_user_account;

            }

            /*
             * Actually make the changes / updates
             */
            $user_account->account_name = $account_name;
            $user_account->account_type = $account_type;
            $user_account->industry_id  = $industry_id;
            $user_account->insertUpdateDB();

            /*
             * If the domain changes, make sure we replace the domain on the account
             */
            $domain     = new UserAccountsDomain($domain);
            $old_domain = $user_account->mainDomain()->domain;

            if ($old_domain != $domain->domain) {

                $user_account->replaceDomain($domain->domain);

                $user->recordEvent(
                    UserHistory::UPDATE_ACCOUNT_DOMAIN,
                        $parameters = array(
                            '$old_domain' => $old_domain,
                            '$new_domain' => $domain->domain
                        ),
                     "Change from $old_domain to $domain->domain"
                );
            }

            return Redirect::back()
                           ->with('flash_message', 'Account updated');


        }
        catch (PinterestProfileNotFoundException $e) {
            return Redirect::back()
                           ->with('flash_error', "Oh no! $username could not be found on Pinterest. Are you sure thats the name you want to use?")
                           ->withInput();
        }
        catch (Exception $e) {
            return Redirect::back()
                           ->with('flash_error', $e->getMessage());
        }


    }

    /**
     * /settings/account/remove
     *
     * @author  Will
     */
    public function remove($account_id)
    {
        try {

            $legacy = $this->baseLegacyVariables();
            extract($legacy);

            /** @var $customer \User */
            $user = $customer;

            $account_id = filter_var($account_id, FILTER_SANITIZE_NUMBER_INT);

            $user_account = UserAccount::find($account_id);

            /*
             * Make sure the track types are right for the pins, boards and domains
             * status tables which is what this magical function does
             */
            $user_account->changeTrackType('orphan');
            $user_account->insertUpdateDB();

            /*
             * Remove the old preferences
             * and remove the emails currently on the queue
             */
            $user->removeEmailPreferences($user_account)
                 ->removeQueuedAutomatedEmails()
                 ->seedAutomatedEmails();

            $user->recordEvent(
                UserHistory::REMOVE_ACCOUNT,
                    $parameters = array(
                        '$username' => $user_account->username
                    ),
                 'Removed account with username:' . $user_account->username .
                 '(' . $user_account->user_id . ')'
            );

            $user->organization()->removeChargifyComponent();

            return Redirect::back()
                           ->with('flash_message', 'Account removed');

        }
        catch (PinterestProfileNotFoundException $e) {
            return Redirect::back()
                           ->with('flash_error', 'That profile could not be found');
        }
        catch (Exception $e) {
            return Redirect::back()
                           ->with('flash_error', $e->getMessage());
        }

    }

    /**
     * /settings/accounts
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
         * Redirect to profile settings if user does not have permissions to see this page.
         */
        if ($customer->is_admin == User::PERMISSIONS_VIEWER) {
            return Redirect::to('/settings/profile');
        }

        /*
         * Copying in legacy code...
         */
        $account_tabs         = array();
        $account_tabs_content = array();
        $account_counter      = 0;

        $industry_select = "";

        foreach (UserIndustries::all() as $ind) {
            $industry_select .= "
<option value='" . $ind->industry_id . "'>" . $ind . "</option>";
        }

        /** @var $cust_accounts array */
        foreach ($cust_accounts as $ac) {

            /** @var UserAccount $user_account */
            if ($this->logged_in_customer->hasFeature('pinterest_oauth_enabled')) {
                $user_account  = UserAccount::find($ac['account_id']);
                $oauth_connect = View::make('pages::settings.oauth_button', ['url' => $user_account->getPinterestOAuthLink()]);
            } else {
                $oauth_connect = '';
            }

            if ($ac['account_name'] == "") {
                $ac['account_name']       = "[Account Name]";
                $this_account_name_label  = "[Account Name]";
                $this_account_name_input  = "";
                $this_account_placeholder = "Enter an Account Name";
            } else {
                $this_account_name_label  = $ac['account_name'];
                $this_account_name_input  = $ac['account_name'];
                $this_account_placeholder = "";
            }
            /*
            * Set tab labels
            *
            * @authors Alex
            */
            if ($account_counter == 0) {

                $account_tabs[] = "
<li class='account-tab-label main-account span4 active'>
    <a href='#account_$account_counter' data-toggle='tab'>
        <span class='main-account-name'>$this_account_name_label</span>
        <span class='pull-right'><span class='label label-info'>Main Account</span></span>
    </a>
</li>";

                $account_tabs_content[] = "
<div class='tab-pane active' id='account_$account_counter'>
    <h2>$this_account_name_label</h2>";
            } else {
                $account_tabs[$account_counter] = "
    <li class='account-tab-label span4'>
        <a href='#account_$account_counter' data-toggle='tab'>
            <span class='account-name'>$this_account_name_label</span>
            <span class='pull-right'><i class='icon-arrow-right'></i></span>
        </a>
    </li>";

                $account_tabs_content[$account_counter] = "
    <div class='tab-pane' id='account_$account_counter'>
        <h2>" . $ac['account_name'] . "</h2>
        <form action='/settings/account/" . $ac['account_id'] . "/remove' method='POST'>
            <button class='btn remove-account btn-mini' type='submit'
                    onclick=\"confirm('Are you sure you want to delete this account?  All of your data and history will be lost');\">Delete</button>
        </form>";
            }


            /*
            * Adds form for each account to the tab content area
            *
            * @authors Alex
            */
            $account_tabs_content[$account_counter] .=

                "<form action='/settings/account/edit' method='POST' style='margin-left:20px'>
                    <input type='hidden' name='account_id' value='" . $ac['account_id'] . "'>
            <fieldset>"

                . "
                <div class=\"control-group\">
                <label class=\"control-label\" for=\"account_name\">
                <strong>Account Name / Handle:</strong>
                </label>
                <div class=\"controls\">
                <input class=\"input-large\" value=\"$this_account_name_input\"
                id=\"account_name\" type=\"text\"
                name='account_name' placeholder='$this_account_placeholder'
                required>
    </div>
</div>"

                . "
<div class='control-group inline-block'>
    <label class='control-label' for='username'><strong>Pinterest Username:</strong></label>
    <div class='controls'>
        <div class='input-prepend pull-left' style='margin-bottom:0px'>
                                        <span class='add-on'>
                                            <i class='icon-user'></i> pinterest.com/
                                        </span>
            <input style='width:200px;margin-left: -4px;'
                   value='" . $ac['username'] . "' id='username' type='text'
            name='username' placeholder='Username'
            pattern='^[a-zA-Z0-9-_]{1,20}$'
            title='Please include username only, which should
            consist of only letters and numbers (no special characters).
            Thanks!' required>
        </div>
        <div class='help-icon-form pull-left' style='margin:3px 0 0 5px;'>
            <a id='help-icon-link' class=''
               data-toggle='popover'
               data-container='body'
               data-original-title='Not sure how to find your username?'
               data-content='Your Pinterest Username is found in the URL
                                                of your Pinterest profile:
                                                <span class=\"muted\">http://pinterest.com/
                                                <strong style=\"color:#000\">username</strong>/</span>
                                                <br><img class=\"img-rounded\"
                                                src=\"/img/username-help.jpg\">'
               data-trigger='hover' data-placement='top'>
                <i id='header-icon' class='icon-help'></i>
            </a>
        </div>
    </div>
</div>

<div class='clearfix'></div>"

                . "
<div class=\"control-group inline-block\">
<label class=\"control-label\" for=\"domain\"><strong>Domain:</strong></label>
<div class=\"controls\" style='margin-bottom:0px'>
<div class='input-prepend pull-left' style='margin-bottom:0px'>
    <span class=\"add-on\">
    <i class=\"icon-earth\"></i> http://
    </span>
    <input class=\"input-large\" data-minlength='0' value=\"" . @$ac['domains'][0] . "\"
    id=\"domain\" type=\"text\" name='domain' placeholder='e.g. \"amazon.com\"'
    pattern='^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}$'>
</div>
<div class='help-icon-form pull-left' style='margin:3px 0 0 5px;'>
    <a id='help-icon-link-domain' class=''
       data-toggle='popover'
       data-container='body'
       data-original-title='What Domain would you like to track?'
       data-content='
                                            <strong>Instructions:</strong>
                                            <small>
                                            <ul>
                                                <li>Core domains only, no trailing slashes
                                                    <br>(e.g. ending in \".com\" or \".co.uk\")</li>
                                                <li>\"http://\" and \"www\" not required.</li>
                                                <li>Only domains / subdomains can be tracked.</li>
                                            </ul>
                                            </small>
                                            <strong>Examples:<br></strong>
                                            <small>
                                                <span class=\"text-success\"><strong>Trackable:</strong></span>
                                                etsy.com, macys.com, yoursite.tumblr.com
                                                <br><span class=\"text-error\"><strong>Not Trackable:</strong></span>
                                                etsy.com/shop/mystore, macys.com/mens-clothing
                                            </small>'
       data-trigger='hover' data-placement='top'>
        <i id='header-icon' class='icon-help'></i>
    </a>
</div>
</div>
</div>"

                    . "<div class=\"control-group inline-block\">
<label class=\"control-label\" for=\"account_type\">
<strong>Account Type:</strong>
</label>
<div class='controls'>
    <select class='input-xlarge' name='account_type'>
        <option selected='selected' value='" . $ac['account_type'] . "'>
        " . ucwords($ac['account_type']) . "</option>
        <option value='brand'>Brand</option>
        <option value='sub-brand'>Sub-Brand</option>
        <option value='agency client'>Agency Client</option>
        <option value='non-profit'>Non-Profit</option>
        <option value='personal'>Personal / Individual</option>
        <option value='other'>Other</option>
    </select>
</div>
</div>"

                    . "<div class=\"control-group inline-block\">
<label class=\"control-label\" for=\"industry_id\">
<strong>Industry:</strong>
</label>
<div class='controls'>
    <select class='input-xlarge' name='industry_id'>
        <option selected='selected' value='" . $ac['industry_id'] . "'>" . $ac['industry_name'] . "</option>
        " . $industry_select . "
    </select>
</div>

</div>"

                    . "
<div class=\"form-actions\">
<input value='" . $ac['username'] . "' id='username_check' type='hidden' name='username_check'>
<button type=\"submit\" class=\"btn btn-primary pull-right\"
onClick='return checkUsername();'>Save Changes</button>
</div>"

                    . "
</fieldset>
</form>
</div>";

            $account_counter++;
        }

        if (isset($_GET['add'])) {
            $simulate_click_add_account = "
$('.account-tab-label.add-account, .account-tab-label.add-account a').trigger('click');
setTimeout('$(\"input#account_name\").focus()', 500);

";
        }

        /** @var $cust_chargify_id int */
        if ($cust_chargify_id <= 1 || $cust_chargify_id == "" || !$cust_chargify_id) {

            $has_chargify            = false;
            $cust_product            = "Free Starter Account";
            $cust_subscription_state = "active";
            $cust_next_assessment_at = "N/A";
            $current_period_ends_at  = "N/A";
            $cust_masked_credit_card = "";;
            $cust_components                   = "";
            $cust_component_quantity           = 0;
            $cust_update_billing_link          = "";
            $add_account_disable               = "deactivated";
            $base_price                        = 0;
            $comp_breakdown                    = array();
            $current_monthly_total             = 0;
            $add_next_component_price          = 0;
            $add_next_component_price_prorated = 0;
            $component_total_price             = 0;
            $new_component_total_price         = 0;
            $current_monthly_total             = 0;
            $new_monthly_total                 = 0;
            $next_component_discount           = 0;
            $days_in_curr_bill_period          = 0;
            $days_left_curr_bill_period        = 0;
            $add_second_component_price        = 0;

        } else {

            /** @var $customer */
            if ($customer->plan()->plan_id == 1) {

                $add_account_disable = "deactivated";

            } else {
                $add_account_disable = "";
            }


            $has_chargify = true;


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
            /** @var $cust_plan */
            switch ($cust_plan) {
                case 1:
                    //20540
                    break;
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
            }

            $cust_product            = $sub[0]->product->name;
            $cust_subscription_state = $sub[0]->state;
            $cust_next_assessment_at = date('m/d/Y', strtotime($sub[0]->next_assessment_at));
            $cust_masked_credit_card = $sub[0]->credit_card->masked_card_number;
            $cust_components         = $components->getAll($this_sub->id, $comp_number);
            $cust_component_quantity = $cust_components->allocated_quantity;
            $comp_details            = json_decode($connector
                                                       ->retrieveComponentByComponentId($product_family, $comp_number, ".json"));

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

            function getNextComponentPrice($price_ranges = array(), $quantity)
            {

                $comp_price = false;

                foreach ($price_ranges as $range) {

                    if ($quantity < $range->ending_quantity
                        && $quantity >= $range->starting_quantity - 1
                    ) {
                        $comp_price = $range->unit_price;
                    }
                }

                return $comp_price;
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


            $add_next_component_price   = getNextComponentPrice($comp_details->component->prices, $cust_component_quantity);
            $add_second_component_price = getNextComponentPrice($comp_details->component->prices, $cust_component_quantity + 1);
            $component_total_price      = getComponentsRecurringPrice($comp_details->component->prices, $cust_component_quantity);
            $new_component_total_price  = getComponentsRecurringPrice($comp_details->component->prices, $cust_component_quantity + 1);


            $current_monthly_total = number_format($component_total_price + $base_price, 0);
            $new_monthly_total     = number_format($new_component_total_price + $base_price, 0);


            $comp_breakdown = getComponentPriceBreakdown($comp_details->component->prices, $cust_component_quantity);

            if (isset($comp_breakdown[0]['print'])) {
                $next_component_discount = "(Save $" . number_format($comp_breakdown[0]['price'] - $add_next_component_price, 0) . ")";
            } else {
                $next_component_discount = "";
            }


            /*
             * Calculate prorated charge for adding account
             */
            $days_left_curr_bill_period = floor((strtotime($this_sub->current_period_ends_at)
                                                    - time()) / 60 / 60 / 24);
            $days_in_curr_bill_period   = number_format((strtotime($this_sub->current_period_ends_at)
                                                                                                                                                                                                                                                                                                    - strtotime($this_sub->current_period_started_at)) / 60 / 60 / 24, 2);
            $current_period_ends_at     = date('m/d/Y', strtotime($this_sub->current_period_ends_at));

            if ($days_in_curr_bill_period < 28) {
                $days_in_curr_bill_period = 31;
            };

            $add_next_component_price_prorated = number_format(($days_left_curr_bill_period
                / $days_in_curr_bill_period
                * $add_next_component_price), 2);


        }

        /** @var  $cust_industry_id */
        /** @var  $cust_industry */

        $vars = array(
            'navigation'                        => $this->buildSettingsNavigation('account'),
            'alert'                             => $this->generateAlertBox(),
            'simulate_click_add_account'        => '',
            'is_able_to_add_account'            => false,
            'cust_accounts'                     => $cust_accounts,
            'industries'                        => UserIndustries::all(),
            'cust_industry_id'                  => $cust_industry_id,
            'cust_industry'                     => $cust_industry,
            'has_chargify'                      => $customer->hasCreditCardOnFile(),
            'comp_breakdown'                    => $comp_breakdown,
            'cust_component_quantity'           => $cust_component_quantity,
            'add_next_component_price'          => $add_next_component_price,
            'cust_product'                      => $cust_product,
            'base_price'                        => $base_price,
            'current_monthly_total'             => $current_monthly_total,
            'next_component_discount'           => $next_component_discount,
            'new_monthly_total'                 => $new_monthly_total,
            'current_period_ends_at'            => $current_period_ends_at,
            'add_next_component_price_prorated' => $add_next_component_price_prorated,
            'days_left_curr_bill_period'        => $days_left_curr_bill_period,
            'add_account_disable'               => $add_account_disable,
            'oauth_button' => $oauth_connect


        );

        $merged                     = array_merge($vars, $legacy);
        $this->layout->main_content = View::make('analytics.pages.settings.account', $merged);

    }


    /**
     * @url     GET /settings/tasks
     * @author  Will
     */
    public function showTasks()
    {

        if(! $this->logged_in_customer->hasFeature('tasks_list_enabled')) {
            return Redirect::to('/');
        }


        $vars = [
            'completeness_percentage' => $this->logged_in_customer->tasks()->percentComplete(),
            'tasks'                   => $this->logged_in_customer->tasks()
        ];


        $this->mainContent('settings::tasks', $vars);
    }


    /**
     * @url GET /settings/profiles/refresh
     *
     * @author  Will
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refreshProfilesData() {

        if(! $this->logged_in_customer->hasFeature('tasks_list_enabled')) {
            return Redirect::back();
        }

        foreach ($this->logged_in_customer->organization()->activeUserAccounts() as $user_account) {
            /** @var $user_account UserAccount */
            $user_account->profile()->updateViaAPI();
            $user_account->profile()->insertUpdateDB();
        }

        return Redirect::back();
    }

}