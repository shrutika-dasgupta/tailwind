<?php namespace Analytics;

use
    Config,
    ChargifyCustomer,
    ChargifySubscription,
    Crypt,
    Exception,
    Input,
    Log,
    Mail,
    Pinleague\Pinterest\PinterestProfileNotFoundException,
    Plan,
    Profile,
    Redirect,
    RequiredVariableException,
    Session,
    User,
    UserHistory,
    UserAlreadyExistsException,
    URL,
    VariableNotValidException,
    View;

/**
 * Controller for actions related to the signup flow and process
 *
 * @author  Will
 *
 */
class SignupController extends BaseController
{

    protected $layout = 'layouts.signin';

    /**
     * Construct
     * Used to set the logs for all signup actions
     *
     * @author  Will
     */
    public function __construct()
    {
        parent::__construct();

        Log::setLog(__FILE__, 'Reporting', 'signup');
    }

    /**
     * POST /signup/create
     *
     * After people on the catalog site enter their email and password, they are directed
     * here for processing
     *
     * @author  Will
     */
    public function createFreeAccount($type = 'free')
    {
        /*
         * We're using a honeypot to knock off the lowest level spam.
         * Basically, if this is  anything but " " we ignore the request
         * since some simple spam bots just fill out every form item
         */
        $honey_pot  = Input::get('name');
        $honey_pot2 = Input::get('email_address');

        if ($honey_pot !== '' || $honey_pot2 !== '') {

            Log::warning('The honey pot was full during a signup',
                         array('honey' => $honey_pot, 'honey2' => $honey_pot2)
            );

            return Redirect::to('/');
        }

        $email    = filter_var(Input::get('email'), FILTER_SANITIZE_EMAIL);
        $source   = filter_var(Input::get('source'), FILTER_SANITIZE_STRING);
        $password = filter_var(Input::get('password',random_string(5)), FILTER_SANITIZE_STRING);
        $username = filter_var(Input::get('username'), FILTER_SANITIZE_STRING);

        if (
            empty($email) OR
            empty($password) OR
            empty($username)
        ) {
            return Redirect::back()
                           ->withInput()
                           ->with('flash_error',
                                  'You need to fill out everything');
        }

        try {

            $user = User::create(
                        $email,
                        $password,
                        $username,
                        $organization = false,
                        $add_user_account = true,
                        $first_name = false,
                        $last_name = false,
                        $admin = 'S',
                        $track_type = $type,
                        $invited_by_cust_id = 0,
                        $source
            );
            Log::info('Signed up a new user',$user);

            $user->setLogin($password);
            Log::debug('Logged in new signup');

            $user->recordEvent(
                 UserHistory::SIGNUP,
                 $parameters = array(
                     'plan'     => Plan::FREE_NO_CC,
                     'username' => $username
                 )
            );

            /**
             * If this is a demo signup, then we'll send it to a slightly different URL
             * (/profile/demo/new) so we can track these conversions separately in Google Analytics.
             */
            if ($track_type == User::TRACK_TYPE_INCOMPLETE) {
                return Redirect::route('dashboarddemo')
                               ->with(
                               'new_signup',
                                   'free'
                    );
            }

            /**
             * Sent to (/profile/new) so we can track free account conversions in Google Analytics.
             */
            return Redirect::route('dashboardnew')
                           ->with(
                           'new_signup',
                           'free'
                );

        }
        catch (UserAlreadyExistsException $e) {

            $user = User::findByEmail($email);

            $user->recordEvent(
                 UserHistory::SIGNED_UP_AGAIN,
                 array(
                      'plan'      => Plan::FREE_NO_CC,
                      '$username' => $username
                 )
            );

            return Redirect::to('login')
                           ->with(
                           'flash_message',
                           "The email '$email' already has a Tailwind account. Try logging in?"
                )
                           ->withInput();
        }
    }

    /**
     * POST /signup/demo/create
     *
     * After people on the catalog site enter their email and password, they are directed
     * here for processing
     *
     * @author  Will
     */
    public function createDemoAccount()
    {
        Session::set('incomplete_signup',true);

        $beaver_facts = Config::get('facts.beaver_facts');
        $fact_key     = array_rand($beaver_facts);
        $fact         = $beaver_facts[$fact_key];

        Mail::send(
            array(
                 'html' => 'shared.emails.html.demo_signup',
                 'text' => 'shared.emails.plaintext.demo_signup'
            ),
            array(
                 'name'  => Input::get('name_2'),
                 'email' => Input::get('email'),
                 'username'=>Input::get('username'),
                 'company'=>Input::get('company'),
                 'domain'=>Input::get('domain'),
                 'type'=>Input::Get('type'),
                 'fact'           => $fact,
            ),
                function ($message)
                {
                    $message->from('bd+downgradebot@tailwindapp.com','Demo Signup Bot');
                    $message->to('bd@tailwindapp.com', 'Business Development Team');
                    $message->bcc('will@tailwindapp.com');
                    $message->subject("Demo Signup! ");
                }
        );


        return $this->createFreeAccount(User::TRACK_TYPE_INCOMPLETE);
    }

    /**
     * /signup/process
     *
     * @author  Alex
     * @author  Will
     */
    public function processSignupForm()
    {

        $chargify_id = 'error';

        try {

            /*
             * If we didn't send all the variables, throw an exception
             */

            if (!all_in_array($_POST,
                              'email', 'password', 'confirm_password', 'username', 'site_address',
                              'org_name', 'org_type', 'first_name', 'last_name', 'chargify_customer_id',
                              'product', 'timezone'
            )
            ) {
                throw new RequiredVariableException('Not all fields were sent', 6);
            }

            $email            = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            $password         = filter_var($_POST['password'], FILTER_SANITIZE_STRING);
            $confirm_password = filter_var($_POST['confirm_password'], FILTER_SANITIZE_STRING);
            $username         = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
            $domain           = filter_var($_POST['site_address'], FILTER_SANITIZE_STRING);
            $org_name         = filter_var($_POST['org_name'], FILTER_SANITIZE_STRING);
            $org_type         = filter_var($_POST['org_type'], FILTER_SANITIZE_STRING);
            $first_name       = filter_var($_POST['first_name'], FILTER_SANITIZE_STRING);
            $last_name        = filter_var($_POST['last_name'], FILTER_SANITIZE_STRING);
            $chargify_id      = filter_var($_POST['chargify_customer_id'], FILTER_SANITIZE_NUMBER_INT);
            $product          = filter_var($_POST['product'], FILTER_SANITIZE_STRING);
            $timezone         = filter_var($_POST['timezone'], FILTER_SANITIZE_STRING);

            /*
             * Validate some of the data coming through
             */
            if ($password != $confirm_password) {
                throw new VariableNotValidException('Passwords do not match', 4);
            }

            if (strlen($password) < 7) {
                throw new VariableNotValidException('Password not long enough', 3);
            }

            try {
                /*
                 * Find the profile
                 */
                $profile             = Profile::createViaApi($username);
                $profile->track_type = "user";


                $profile->insertUpdateDB();

                $user = User::create(
                            $email,
                            $password,
                            $username,
                            $org_id = false,
                            $add_user_account = false,
                            $f_name = false,
                            $l_name = false,
                            $is_admin = 'S',
                            $type = 'user'
                );

                $user->first_name = ucfirst($first_name);
                $user->last_name  = ucfirst($last_name);
                $user->insertUpdateDB();

                $user->organization()->org_type    = $org_type;
                $user->organization()->org_name    = $org_name;
                $user->organization()->chargify_id = $chargify_id;

                $subscription = $user->organization()->subscription('force update');

                /*
                 * Now that we have the subscription object back from chargify,
                 * update the subscription data in our DB
                 */
                $user->organization()->subscription_state = $subscription->state;
                $user->organization()->trial_start_at  = $subscription->trial_started_at;
                $user->organization()->trial_end_at = $subscription->trial_ended_at;
                $user->organization()->coupon_code = $subscription->coupon_code;

                /*
                * Change the plan based on the subscription product id
                *
                * I'm using a switch case here instead of searching in the DB because we might make
                * plans that both use the same chargify plan id (custom plans with the same chargify
                * plan id. This just works for the "typical" case
                *
                */
                try {

                    switch ($subscription->product->id) {

                        default:
                            $user->organization()->plan = Plan::FREE_PLAN_ID;
                            $plan                       = Plan::FREE_WITH_CC;
                            break;

                        /*
                         * Lite
                         */
                        case 3319111:
                            $user->organization()->plan = Plan::LITE_PLAN_ID;
                            $plan                       = Plan::LITE;
                            break;

                        /*
                         * Pro
                         */
                        case 3319112:
                            $user->organization()->plan = Plan::PRO_PLAN_ID;
                            $plan                       = Plan::PRO;
                            break;

                    }
                }
                catch (Exception $e) {
                    echo $e->getMessage();
                    exit;
                }

                $user->organization()->insertUpdateDB();

                $user->recordEvent(
                     UserHistory::SIGNUP,
                     $parameters = array(
                         'plan'      => $plan,
                         '$username' => $username
                     )
                );

                $user->recordEvent(
                     UserHistory::TRIAL_START,
                     array('plan' => $plan)

                );

                /*
                 * Add the user account
                 */
                $user_accoount =
                    $user->organization()->addUserAccount($org_name, $profile, $org_type, 0);

                $user->recordAction(
                     UserHistory::ADD_ACCOUNT,
                     'Add user account for ' . $user_accoount->username
                );

                /*
                 * Add the domain
                 */
                /** @var $user_account \UserAccount */
                $user_accoount->addDomain($domain);

                $user->recordAction(
                     UserHistory::ADD_ACCOUNT_DOMAIN,
                     $parameters = array(
                         '$domain' => $domain->domain
                     )
                );

                session_start();
                $user->setLogin($password);

                $key = $user->createTemporaryKey();

                return Redirect::route('dashboard');

            }
            catch (UserAlreadyExistsException $e) {

                $user = User::findByEmail($email);

                /*
                 *  Need to find a better way to keep track of this, especially which plan they are
                 * signing up for
                 *
                $user->record_hiistory(
                     UserHistory::SIGNED_UP_AGAIN,
                     "Tried to sign up with username:$username, but already had an account");
                    */

                throw new Exception('User already exists', 5);

            }
            catch (PinterestProfileNotFoundException $e) {

                throw new Exception('Profile not found', 7);
            }

        }
        catch (Exception $e) {

            Log::error($e);
            /*
             * 0 - General Exception
             * 1 - Invalid Email
             * 2 -
             * 3 - Password not long enough
             * 4 - Passwords don't match
             * 5 - User already exists
             * 6 - All fields weren't filled out
             * 7 - Pinterest profile not found
             * 8 -
             * 9 -
             */

            return Redirect::to('/signup?id=' . $chargify_id)
                           ->withInput()
                           ->with('flash_error', $e->getMessage());

        }

    }

    /**
     *
     */
    public function resendEmailConfirmation() {

        $this->logged_in_customer->sendConfirmationEmail();
        return Redirect::back()->with('flash_message','Confirmation email sent! Please check your email');
    }


    /**
     * /signup/confirm/{email}/{key}/{token}
     * Uses the auto login
     *
     * @author  Will
     *
     * @param $email    string The email address of the user
     * @param $key      string The auto login key
     * @param $token    string An encrypted timestamp.
     *
     * @throws \Exception
     * @return \Illuminate\Http\RedirectResponse
     */
    public function confirmEmail($email, $key,$token)
    {

        if (empty($email) OR empty($key) OR empty($token)) {
            throw new Exception('Validation failed from missing parameters');
        }

        $timestamp = Crypt::decrypt($token);

        if ($timestamp < strtotime('7 day ago')) {
            throw new Exception('Validation has expired');
        }

        Session::flush();
        Session::clear();

        $user = User::autoLogin($email, $key);

        if(!$user) {
            return Redirect::to('/login')
                ->with('flash_error',
                       'The link was invalid. Please contact'.
                       ' help@tailwindapp.com to troubleshoot.'
                );
        }

        $user->email_status = User::EMAIL_CONFIRMED;
        $user->insertUpdateDB();

        $user->recordEvent(
          UserHistory::CONFIRM_EMAIL
        );

        $user->recordEvent(
             $event = UserHistory::LOGIN,
             $parameters = array(
                 'logged_in_to' => '/',
                 'autologin' => true
             )
        );

        return Redirect::to('/')->with('flash_message','Email address confirmed!');
    }



    /**
     * /signup?id={subscription_id}
     *
     * @author  Alex
     * @author  Will
     *
     */
    public function showSignupForm()
    {
        //testing id : 3679596

        try {

            if (!isset($_GET['id'])) {
                throw new RequiredVariableException('There was no chargify id sent. Something went terribly wrong');
            }

            $chargify_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

            $subscription = new ChargifySubscription(null, false);

            $subscription_new = $subscription->getByID($chargify_id);

            $customer_id = $subscription_new->customer->{'id'};


            $customer     = new ChargifyCustomer(null, false);
            $customer->id = $customer_id;
            $customer_x   = $customer->getByID();


            $vars = array(
                'email'                              => $customer_x->email,
                'username'                           => '',
                'site_address'                       => '',
                'org_name'                           => $customer_x->organization,
                'brand_checked'                      => 'checked',
                'agency_checked'                     => '',
                'agency_on_behalf_of_client_checked' => '',
                'non_profit_checked'                 => '',
                'personal_checked'                   => '',
                'other_checked'                      => '',
                'firstname'                          => $customer_x->first_name,
                'lastname'                           => $customer_x->last_name,
                'chargify_id'                        => $chargify_id,
                'chargify_customer_id'               => $customer_id,
                'include_mbsy'                       => true,
                'alert'                              => $this->generateAlertBox(),
            );

            if (Session::has('_old_input')) {
                $old_inputs = Session::get('_old_input');
                $vars       = array_merge($vars, $old_inputs);
            }

            $this->layout->main_content = View::make('analytics.pages.signup', $vars);

        }
        catch (Exception $e) {

            return $this->generateAlertBox();
        }

    }

    /**
     * /thank-you
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
                 *
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
                        $customer->organization()->changePlan(1);
                        break;

                    /*
                     * Lite
                     *
                     */
                    case 3319111:
                        $customer->organization()->changePlan(2, 'dont prorate');
                        break;

                    /*
                     * Pro
                     *
                     */
                    case 3319112:
                        $customer->organization()->changePlan(3, 'dont prorate');
                        break;

                }

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
                 *
                 *
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
        $page     = "Thank You";
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


    /**
     * GET
     *
     * @param $plan
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleDemo($plan)
    {
        $follow = Input::get('follow', '/');

        $url = parse_url(URL::previous(), PHP_URL_PATH);

        switch ($plan) {
            case 'pro':
                Session::set('demo_enabled', Plan::PRO);
                break;
            case 'lite':
                Session::set('demo_enabled', Plan::LITE);

                /*
                 * If we are switching to a lite plan on a pro
                 * only feature page, we want to send it back as pro with
                 * a message that says nut-uh uh like dennis from Jurrassic
                 * Park.
                 */

                if (in_array($url,
                             array(
                                  '/competitors/benchmarks',
                                  '/days-and-times',
                                  '/categories',
                                  '/most-valuable-pinners',
                                  '/brand-pinners',
                                  '/top-repinners',
                                  '/followers/influential',
                             )
                    )
                    OR strpos($url, '/domain/benchmarks') !== false
                    OR strpos($url, '/domain/trending-images') !== false
                    OR strpos($url, '/domain/most-visits') !== false
                    OR strpos($url, '/domain/most-clicked') !== false
                    OR strpos($url, '/domain/most-revenue') !== false
                    OR strpos($url, '/domain/most-transactions') !== false
                    OR strpos($url, '/domain/most-repinned') !== false
                    OR strpos($url, '/domain/most-liked') !== false
                    OR strpos($url, '/domain/most-commented') !== false

                ) {
                    Session::set('demo_enabled', PLan::PRO);

                    return Redirect::back()->with('flash_error', 'You can only view this report with a Pro level account');
                }

                break;
            case 'upgrade':
                Session::set('demo_enabled', false);
                $follow = '/upgrade';
                break;
            case 'finish-signup':
            case 'claim-dashboard':
                Session::set('demo_enabled', false);
                Session::set('signup_incomplete', false);

                $this->parent_customer->setPassword(Input::get('password', random_string(5)));
                $this->parent_customer->type = User::TRACK_TYPE_FREE;
                $this->parent_customer->insertUpdateDB();

                $this->parent_customer->recordEvent('Claim dashboard from demo');
                $this->parent_customer->sendConfirmationEmail();

                if ($plan == 'finish-signup') {
                    $follow = '/upgrade';
                } else {
                    $follow = '/';
                }

                break;

            default:
                Session::set('demo_enabled', false);
                break;
        }

        if ($this->parent_customer) {
            $customer = $this->parent_customer;
        } else {
            $customer = $this->logged_in_customer;
        }
        
        $customer->recordEvent(UserHistory::TOGGLE_DEMO,['plan'=>$plan]);

        if (strpos($url, '/settings') !== false OR strpos($url, '/upgrade') !== false) {
            return Redirect::to('/');
        }

        if ($follow == 'back') {
            return Redirect::back();
        }


        return Redirect::to($follow);
    }

}