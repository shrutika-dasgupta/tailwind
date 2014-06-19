<?php namespace Analytics;

use
    Config,
    Exception,
    Input,
    Log,
    Pinleague\MailchimpWrapper,
    Redirect,
    RequiredVariableException,
    Request,
    Session,
    UserHistory,
    User,
    UserProperty,
    View;

/**
 * Class AuthController
 *
 * @package Analytics
 */
class AuthController extends BaseController
{

    protected $layout = 'layouts.signin';

    /**
     * /login/auto/{email}/{key}
     *
     * @author  Will
     *
     * @param $email
     * @param $key
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function autoLogin($email, $key)
    {
        Session::flush();
        Session::clear();

        $user = User::autoLogin($email, $key);

        $user->incrementUserProperty(
          UserProperty::LOGIN_COUNT,
          1
        );
        $user->recordEvent(
             $event = UserHistory::LOGIN,
             $parameters = array(
                 'logged_in_to' => 'profile',
                 'autologin' => true
             )
        );

        return Redirect::to('/profile');
    }

    /**
     * Logs in the user
     *
     * @author  Will
     */
    public function processLogin()
    {
        Session::flush();
        Session::clear();

        try {

            $redirect = false;
            $attempt  = 0;

            if (!all_in_array($_POST, 'email', 'password')) {
                throw new RequiredVariableException('Email and Password required');
            }

            $email    = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            $password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);
            $redirect = filter_var($_POST['redirect_to'], FILTER_SANITIZE_STRING);
            $attempt  = filter_var($_POST['attempts'], FILTER_SANITIZE_NUMBER_INT);


            if ($customer = User::login($email, $password)) {

                $customer->last_seen_ip = Request::getClientIp();
                $customer->insertUpdateDB();

                if ($customer->type == User::DELETED) {

                    $customer->recordEvent(
                             UserHistory::LOGIN_DEACTIVATED
                    );

                    return Redirect::to('/login')
                                   ->with('flash_error', 'Your account has been deactivated');


                } else if ($customer->type == User::PENDING) {

                    $customer->type = User::ACCEPTED;
                    $customer->saveToDB();

                    $uid = $customer->cust_id;
                    $rid = md5($uid . date("Y-m-d"));


                    $customer->recordEvent(
                             UserHistory::ACCEPT_COLLABORATOR
                    );

                    $customer->incrementUserProperty(UserProperty::LOGIN_COUNT,1);

                    return Redirect::to("/password-reset?uid=$uid&rid=$rid");

                }

                if (empty($redirect) OR substr($redirect, 0, 5) == 'login' OR $redirect == '/') {
                    $redirect = 'profile';
                }

                $customer->incrementUserProperty(UserProperty::LOGIN_COUNT,1);

                $customer->recordEvent(
                         UserHistory::LOGIN,
                         $parameters = array(
                             'logged_in_to' => $redirect
                         )
                );

                return Redirect::to('/' . $redirect);


            } else {

                $error_message = 'Your username/password combination was incorrect.';
                $attempt++;

               if($attempt >2) {
                   /*
                    * If there are more than 3 attempts to login, they are probably in struggle city
                    * so we want to send them an email to help them log in.
                    */
                   $user = User::findByEmail($email);

                   if ($user) {

                       $reset_url = url('password-reset/form', array($user->cust_id, md5($user->cust_id . date("Y-m-d"))));

                       $email = \Pinleague\Email::instance(UserHistory::HELP_LOGGING_IN_EMAIL_SENT);
                       $email->subject('Getting back into Tailwind');
                       $email->body(
                             'help_logging_in',
                             array(
                                  'first_name'          => $user->first_name,
                                  'autologin_link'      => $user->getAutoLoginLink(),
                                  'password_reset_link' => $reset_url,
                             )
                       );
                       $email->to($user);
                       $email->send();

                       Log::info('Sent email with help logging in');

                       $error_message = 'Your username/password combination was incorrect. Please check your email for help logging in.';

                   } else {

                       Log::notice('Could not find '.$email.' in our database when they tried to log in.');
                       $error_message = "Are you sure that $email is the email address you signed up with?";

                   }

                   $attempt = 0;
               }

                return Redirect::to('/login')
                               ->with('flash_error', $error_message)
                               ->withInput()
                               ->with('attempts', $attempt)
                               ->with('redirect_to', $redirect);
            }
        }

        catch (Exception $e) {

            Log::error($e);

            ///Send to some sort of error page with an explanation
            return Redirect::to('/login')
                           ->with('flash_error', $e->getMessage())
                           ->withInput()
                           ->with('redirect_to', $redirect)
                           ->with('attempts', $attempt);
        }
    }

    /**
     * /logout
     *
     * @author  Will
     */
    public function processLogout()
    {
        $this->logged_in_customer->recordEvent(
           UserHistory::LOGOUT
        );

        Session::clear();
        Session::flush();

        return Redirect::to('login')
                       ->with('flash_alert', 'You have been logged out')
                       ->with('redirect_to', '');


    }

    /**
     * /password-reset
     *
     *
     * @author Alex
     */
    public function processPasswordReset()
    {
        try {

            /*
             * Check for post variables
             */
            if (!all_in_array($_POST, 'check', 'uid', 'password', 'confirm', 'rid')) {
                throw new RequiredVariableException('All Post Variables Not Set');
            }

            /*
             * filter post variables
             */
            $uid      = filter_var($_POST['uid']);
            $rid      = filter_var($_POST['rid']);
            $check    = filter_var($_POST['check']);
            $password = $_POST['password'];
            $confirm  = $_POST['confirm'];

            /*
             * check to see if this is a valid password reset request
             * and save the new password
             */
            if ((md5($uid . date("Y-m-d")) == $rid) && ($password == $confirm)) {
                $reset_user = User::find($uid);
                $email      = $reset_user->email;
                $invited_by = $reset_user->invited_by;

                $reset_user->setPassword($password);
                $reset_user->insertUpdateDB();

                $reset_user->recordEvent(
                           UserHistory::PASSWORD_RESET,
                           $parameters = array(
                               '$password_reset_email' => $reset_user->email
                           )
                );

                /*
                 * Check to see if we are setting a new password for an invited collaborator
                 * if so, we will automatically log them in and change their user type from
                 * 'pending' to 'accepted'.
                 */

                if ($invited_by > 0) {

                    $new_user = User::login($email, $password);

                    if ($new_user->type == User::PENDING) {

                        $new_user->type = User::ACCEPTED;
                        $new_user->insertUpdateDB();

                        $new_user->recordEvent(
                                 UserHistory::LOGIN,
                                 $parameters = array(
                                     'logged_in_to'=>'profile'
                                 ),
                                 'Initial Login - Invitation Accepted'
                        );

                        // Subscribe the user to the blog rss newsletter since we skipped this on signup.
                        try {
                            MailchimpWrapper::instance()->subscribeToList(
                                Config::get('mailchimp.BLOG_RSS_LIST_ID'),
                                $new_user
                            );
                        } catch (Exception $e) {}
                    }

                    return Redirect::to('/profile');

                } else {
                    /*
                     * If this is just a normal password reset request, we will send them back to
                     * the login page with an alert and ask them to try out their new password.
                     */
                    return Redirect::to('/login')
                                   ->with('flash_alert',
                                          'Your password has been reset!
                                          Please login using your new password')
                                   ->withInput(array('email' => $email));
                }
            } else {
                /*
                 * Redirect to error alert on login page if request was not a valid password reset
                 * request
                 */
                return Redirect::to('/login')
                               ->with('flash_error',
                                      'There was an error with your request, please check try again.')
                               ->withInput();
            }
        }
        catch (Exception $e) {
            ///Send to some sort of error page with an explanation
        }
    }

    /**
     * Processes password reset requests.
     *
     * @author Alex
     * @author Will
     * 
     * @return void
     */
    public function processPasswordResetEmail()
    {
        $email = filter_var(Input::get('email'), FILTER_SANITIZE_EMAIL);
        $user = User::findByEmail($email);

        if (!$user) {
            return Redirect::to('password-reset/no-email');
        }

        $reset_url = url('password-reset/form', array($user->cust_id, md5($user->cust_id . date("Y-m-d"))));

        $email = \Pinleague\Email::instance(UserHistory::PASSWORD_RESET_EMAIL_SENT);
        $email->subject('Tailwind Reset Password Request');
        $email->body(
            'password_reset',
            array(
                'first_name' => $user->first_name,
                'reset_url'  => $reset_url,
            )
        );
        $email->to($user);
        $email->send();

        return Redirect::to('password-reset/success');
    }

    /**
     * Shows the Invitation Acceptance page
     *
     * @author Alex
     */
    public function showAcceptInvite($action, $token)
    {
        /*
        * Check for error parameter first to prevent redirect loop
        */
        if (isset($action) && $action != "accept") {
            //do nothing

            /*
             * Otherwise, make sure there is an "accept" parameter and a token is provided
             */
        } else if (isset($action) && $action == "accept" && isset($token) && $token != "") {

            /*
             * Parse the token to get the cust_id and sub-token (md5 hash)
             *
             * provide token string anatomy:
             * token[0] = length of cust_id
             * token[1->4/5] = cust_id
             * token[5/6->end] = sub-token -> md5(email.password.temporary_key))
             */

            $id_length = (int)substr($token, 0, 1);
            $cust_id   = substr($token, 1, $id_length);
            $sub_token = substr($token, 1 + $id_length);

            /*
             * check to see if this user exists
             */
            $invited_user = User::find($cust_id);

            if ($invited_user) {

                $email         = $invited_user->email;
                $password      = $invited_user->password;
                $temporary_key = $invited_user->temporary_key;
                $first_name    = $invited_user->first_name;
                $type          = $invited_user->type;


                /*
                * Check to see whether invitation was already accepted in the past
                */
                if ($type == User::ACCEPTED) {
                    return Redirect::to('/login')
                                   ->with('flash_alert',
                                          'You\'ve already accepted your invitation!
                                          Login now!  If you can\'t remember your password,
                                          you can reset it below')
                                   ->withInput(array('email' => $email));

                    /*
                     * Otherwise, make sure their invitation is still pending and not "deleted"
                     */
                } else if ($type == User::PENDING) {

                    /*
                     * validate sub-token string and show password creation screen if valid
                     */
                    $authorized = false;
                    if ($sub_token == md5($email . $password . $temporary_key)) {

                        $authorized = true;
                        /*
                         * create necessary request id token for password_reset controller
                         * re-using this to not create a new controller just for invites
                         */
                        $rid = md5($cust_id . date('Y-m-d'));

                        $vars = array(
                            'authorized' => $authorized,
                            'rid'        => $rid,
                            'first_name' => $first_name,
                            'cust_id'    => $cust_id,
                        );

                        switch ($action) {
                            default:
                                $this->layout->main_content =
                                    View::make('analytics.pages.invitation.page_not_found', $vars);

                                break;

                            case'invite_not_found':
                            case'token_error':
                            case 'parameters_not_set':

                                $this->layout->main_content =
                                    View::make('analytics.pages.invitation.error', $vars);

                                break;

                            case 'accept':

                                $this->layout->main_content =
                                    View::make('analytics.pages.invitation.accept', $vars);

                                break;
                        }

                    } else {
                        /*
                         * if sub-token does not match, go to error page
                         */

                        return Redirect::to('/invitation/token_error');
                    }
                } else {
                    return Redirect::to('/login')
                                   ->with('flash_error',
                                          '<strong>Whoops!</strong> Looks like there was a bit of a hiccup
                                          with your invitation.  Please try contacting your invitor.')
                                   ->withInput(array('email' => $email));
                }

            } else {
                /*
                 * if invitation not found, go to error page
                 */
                return Redirect::to('/invitation/invite_not_found');
            }

        } else {
            /*
             * if no accept parameter or token provided, redirect to login page
             */
            return Redirect::to('/invitation/parameters_not_set');
        }


        /*
         * Show error notification if there was an issue with the invitation
         */
        if ($action == "invite_not_found" || $action == "token_error" || $action == "parameters_not_set") {

            $this->layout->main_content =
                View::make('analytics.pages.invitation.error');

        }
    }

    /**
     * Shows the Invitation Acceptance page
     *
     * @author Alex
     */
    public function showAcceptInviteDefault()
    {
        return $this->showAcceptInviteError('', '');
    }

    /**
     * Shows the Invitation Acceptance page
     *
     * @author Alex
     */
    public function showAcceptInviteError($action)
    {
        return $this->showAcceptInvite($action, '');
    }

    /**
     * Shows the login page
     *
     * @author  Will
     */
    public function showLogin()
    {
        $vars = array(
            'email'       => '',
            'alert'       => '',
            'redirect_to' => '',
            'attempts'    => 0,
        );

        if (Session::has('redirect_to')) {

            $vars['redirect_to'] = Session::get('redirect_to');
        }

        if (Session::has('attempts')) {
            $vars['attempts'] = Session::get('attempts');
        }

        if (Session::has('_old_input')) {
            $input         = Session::get('_old_input');
            $vars['email'] = $input['email'];
        }

        if (Session::has('flash_error')) {
            $error         = Session::get('flash_error');
            $vars['alert'] = View::make('shared.components.alert',
                                        array(
                                             'type'    => 'error',
                                             'message' => $error
                                        ));
        }

        if (Session::has('flash_alert')) {
            $vars['alert'] = View::make('shared.components.alert',
                                        array(
                                             'type'    => 'alert',
                                             'message' => Session::get('flash_alert')
                                        )
            );
        }

        if (Session::has('flash_message')) {
            $vars['alert'] = View::make('shared.components.alert',
                                        array(
                                             'type'    => 'alert',
                                             'message' => Session::get('flash_message')
                                        )
            );
        }

        $this->layout->main_content = View::make('analytics.pages.signin', $vars);


    }

    /**
     * @author  Alex
     */
    public function showPasswordReset($result)
    {

        if ($result == "success") {
            $vars = array(
                'message' => 1
            );

            $this->layout->main_content = View::make('analytics.pages.password_reset', $vars);
        } else if ($result == "no-email") {
            $vars = array(
                'message' => 2
            );

            $this->layout->main_content = View::make('analytics.pages.password_reset', $vars);
        } else {
            $vars = array(
                'reset' => 1
            );

            $this->layout->main_content = View::make('analytics.pages.password_reset', $vars);
        }

    }

    /**
     * @author  Alex
     */
    public function showPasswordResetDefault()
    {
        $this->showPasswordReset('');
    }

    /**
     * @author  Alex
     */
    public function showPasswordResetForm($uid, $rid)
    {
        $vars = array(
            'uid' => $uid,
            'rid' => $rid
        );

        $this->layout->main_content = View::make('analytics.pages.password_reset', $vars);
    }

    public function showPasswordResetFormDefault()
    {
        return $this->showPasswordResetForm('', '');
    }

}