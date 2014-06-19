<?php

    namespace Admin;
    use \Auth;
    use \Redirect;
    use \Input;

    class AuthController extends BaseController
    {

        /*
         * The layout that should be used for responses.
         */
        protected $layout = 'layouts.admin-signin';

        /**
         * Shows the home page
         * @author  Will
         */
        public function showLogin()
        {

        }

        /**
         * Process Login
         * @author  Will
         */
        public function processLogin() {

            $user = array(
                'email' => Input::get('email'),
                'password' => Input::get('password'),
            );

            if (Auth::attempt($user)) {
                return Redirect::to('/customers');
            }

            // authentication failure! lets go back to the login page
            return Redirect::route('/login')
                ->with('flash_error', 'Your username/password combination was incorrect.')
                ->withInput();

        }

        /**
         * Logout
         * @author  Will
         */
        public function processLogout() {
            Auth::logout();

            return Redirect::to('/login')
                ->with('flash_notice', 'You are successfully logged out.');
        }
    }