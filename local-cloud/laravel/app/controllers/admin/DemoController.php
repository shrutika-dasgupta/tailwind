<?php namespace Admin;

date_default_timezone_set('America/Chicago');

use
    Exception,
    Pinleague\Pinterest\PinterestProfileNotFoundException,
    Profile,
    Plan,
    Redirect,
    Session,
    User,
    Users,
    UserAlreadyExistsException,
    UserHistory,
    View,
    willwashburn\table;

/**
 * Class DemoController
 *
 * @package Admin
 */
class DemoController extends BaseController
{

    protected $layout = 'layouts.admin';

    /**
     * /demo/new/create
     *
     * @author  Alex
     * @author  Will
     */
    public function addDemoAccount()
    {

        try {

            /*
             * If we didn't send all the variables, throw an exception
             */

            if (!all_in_array($_POST,
                              'email', 'password', 'confirm_password', 'username', 'site_address',
                              'org_name', 'org_type', 'first_name', 'last_name', 'timezone'
            )
            ) {
                die('not all variables sent');
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
            $timezone         = filter_var($_POST['timezone'], FILTER_SANITIZE_STRING);

            /*
             * Validate some of the data coming through
             */
            if ($password != $confirm_password) {
                die('Passwords do not match');
            }

            if (strlen($password) < 7) {
                die('Password not long enough');
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
                            false,
                            false,
                            ucfirst($first_name),
                            ucfirst($last_name),
                            'A',
                            'DEMO',
                            0,
                            'DEMO'
                );

                $user->organization()->org_type    = $org_type;
                $user->organization()->org_name    = $org_name;
                $user->organization()->chargify_id = "";

                /*
                *
                * Make the plan a pro account automatically
                *
                */
                $user->organization()->plan = 3;
                $plan                       = 'Pro';

                $user->organization()->insertUpdateDB();

                $user->recordEvent(
                     $event = UserHistory::SIGNUP,
                     $parameters = array(
                         'plan'     => Plan::PRO,
                         'demo'     => true,
                         'username' => $username
                     ),
                     $description = "Signed up DEMO account with username:$username ($plan plan)"
                );

                /*
                 * Add the user account
                 */
                $user_accoount = $user->organization()
                                      ->addUserAccount(
                                      $org_name,
                                      $profile,
                                      $org_type,
                                      $industry_id = 0
                                  );
                $user->recordAction(
                     $type = UserHistory::ADD_ACCOUNT,
                     "Demo account: $username"
                );

                /*
                 * Add the domain
                 */
                /** @var $user_account \UserAccount */
                $user_accoount->addDomain($domain);
                $user->recordAction(
                     $type = UserHistory::ADD_ACCOUNT_DOMAIN,
                     "Added $domain"
                );

                $key = $user->createTemporaryKey();

                return Redirect::to('http://admin.tailwindapp.com/demo/summary');

            }
            catch (UserAlreadyExistsException $e) {

                die('user already exists');
                throw new Exception('User already exists', 5);

            }
            catch (PinterestProfileNotFoundException $e) {

                die('profile not found');
                throw new Exception('Profile not found', 7);
            }

        }
        catch (Exception $e) {

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
            echo $e->getMessage();
            exit;
//            return Redirect::to('/demo/new')
//                   ->withInput()
//                   ->with('flash_error', $e->getMessage());

        }


    }

    /**
     * /demo/new
     *
     * @author  Alex
     */
    public function showDemoSignup()
    {

        $vars = array(
            'email'                              => '',
            'username'                           => '',
            'site_address'                       => '',
            'org_name'                           => '',
            'brand_checked'                      => '',
            'agency_checked'                     => '',
            'agency_on_behalf_of_client_checked' => '',
            'non_profit_checked'                 => '',
            'personal_checked'                   => '',
            'other_checked'                      => '',
            'firstname'                          => '',
            'lastname'                           => '',
            'chargify_id'                        => '',
            'chargify_customer_id'               => '',
            'alert'                              => '',
        );

        if (Session::has('_old_input')) {
            $old_inputs = Session::get('_old_input');
            $vars       = array_merge($vars, $old_inputs);
        }

        $this->layout->main_content = View::make('admin.demo_signup', $vars);

    }

    /**
     * /demo/new
     *
     * @author  Alex
     */
    public function showDemoSummary()
    {

        $table = new table();
        $table->setId('demo');

        $customers = Users::all(
                          array(
                               array(
                                   'preload' => 'organization',
                                   'include' => 'plan'
                               )

                          ),
                              1000,0,'and users.type = \'DEMO\''
        );


        foreach ($customers as $customer) {

            try {

                if (!empty($customer->source) && $customer->source == "DEMO") {

                    //ppx($customer);

                    /** @var $customer User */
                    if ($customer->temporary_key == '') {
                        $customer->temporary_key = $customer->createTemporaryKey();
                    }

                    try {
                        $main_account = $customer->organization()->primaryAccount()->username;
                    }
                    catch (\Exception $e) {
                        $main_account = 'Not found';
                    }

                    $time_ago = number_format(((time() - $customer->timestamp) / 60 / 60 / 24), 2);

                    if ($time_ago < 1) {
                        $created_at       = "<span class='label label-success'>" . $time_ago * 24 . " hours ago</span>";
                        $refresh_disabled = "";
                    } else if ($time_ago < 7 && $time_ago >= 1) {
                        $created_at       = "<span class='label label-info'>$time_ago days ago</span>";
                        $refresh_disabled = "disabled";
                    } else if ($time_ago < 14 && $time_ago >= 7) {
                        $created_at       = "<span class='label label-warning'>$time_ago days ago</span>";
                        $refresh_disabled = "disabled";
                    } else {
                        $created_at       = "<span class='label label-important'>$time_ago days ago</span>";
                        $refresh_disabled = "disabled";
                    }

                    $table->addRow(
                          array(
                               'cust id'           =>
                                   '<a href="/customer/' .
                                   $customer->cust_id .
                                   '/history">' .
                                   $customer->cust_id . '</a>',
                               'org id'            => $customer->organization()->org_id,
                               'Organization'      => $customer->organization()->org_name,
                               'Plan'              => $customer->organization()->plan()->name,
                               'Pinterest Account' => $main_account,
                               'email'             => $customer->email,
                               'name'              => $customer->getName(),
                               'created'           => $created_at,
                               'login'             =>
                                   '<a href="http://analytics.tailwindapp.com/login/auto/' .
                                   $customer->email . '/' . $customer->temporary_key .
                                   '" target="_blank">Auto-Login</a>',
                               'refresh'           =>
                                   '<a href="http://analytics.tailwindapp.com/profile?refresh=1"
                                     target="_blank">
                                    <span class="btn btn-mini ' . $refresh_disabled . ' btn-success">Refresh Data</span></a>'
                          )
                    );
                }
            }
            catch (\Exception $e) {
                //do nothing
            }
        }

        $vars = array(
            'customer_table' => $table->render(),
            'execution_time' => ''
        );

        $this->layout->main_content = View::make('admin.demo_customer_table', $vars);

    }
}
