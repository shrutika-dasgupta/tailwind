<?php

namespace Catalog;

use
    Cookie,
    Exception,
    Input,
    NoPinsFoundException,
    Pinleague\Pinterest\PinterestProfileNotFoundException,
    Profile,
    ProfileNotFoundException,
    Redirect,
    RequiredVariableException,
    User,
    View;

/**
 * Class SignupController
 *
 * @package Catalog
 */
class SignupController extends BaseController
{
    protected $layout = 'layouts.public';

    /**
     * @author  Will
     *
     * @param $username
     */
    public function showAdvice($username)
    {

        $this->buildLayoutDefaults('signup');
        $this->layout->navigation = View::make('catalog.components.signup_navigation');
        $this->layout->pre_body_close .= View::make('catalog.pre_body_close.signup_free');

        try {

            if (!$username) {
                throw new RequiredVariableException('Username is required');
            }

            $username = urldecode($username);

            $profile = Profile::findInDB($username);


            $pins   = $profile->getDBPins();
            $boards = $profile->getDBBoards();

            /*
             * If the pins we have in the DB were pulled more than 12 hours ago
             * we want to repull 250 so we have a better sample
             */
            if ($profile->latestPinPulledMoreThan('12 hours ago') OR $pins->count() == 0) {
                $pins = $profile->getLatestApiPins(250);

                if ($pins->count() > 0) {
                    $pins->setPropertyOfAllModels('track_type', 'free');
                    $pins->insertUpdateDB();
                } else {
                    return Redirect::to('/')
                                   ->with('signup_error', true)
                                   ->with('username', $username)
                                   ->with('message', "<strong>$username</strong> doesn't have any pins yet! Try pinning something to get started.");
                }
            }

            if ($profile->latestBoardPulledMoreThan('12 hours ago') OR $boards->count() == 0) {

                $boards = $profile->getAPIBoards();
                $boards->setPropertyOfAllModels('track_type', 'free');
                $boards->setPropertyOfAllModels('user_id', $profile->user_id);
                $boards->insertUpdateDB();
            }

            /*
             * If we don't have 250 pins, we want to fill up the pins from the domain
             */
            if ($count = $pins->count() < 250) {

                $pins_to_add = 250 - $count;

                while ($pins_to_add > 0) {
                    foreach ($pins->toArray() as $repeatedPin) {
                        $pins->add($repeatedPin);
                        $pins_to_add--;
                    }
                }

            } elseif ($count > 250) {
                $pins = array_slice($pins->toArray(), 0, 250);
            }

            /*
             * Create an array of pins for the background
             */
            $vars                = array();
            $vars['post_domain'] = 'http://' . ROUTE_PREFIX . 'analytics.tailwindapp.' . ROUTE_TLD;

            $pins         = $pins->toArray();
            $vars['pins'] = array(
                array(array_slice($pins, 0, 11), array_slice($pins, 11, 11)),
                array(array_slice($pins, 22, 11), array_slice($pins, 33, 11)),
                array(array_slice($pins, 44, 11), array_slice($pins, 55, 11)),
                array(array_slice($pins, 66, 11), array_slice($pins, 77, 11)),
                array(array_slice($pins, 88, 11), array_slice($pins, 99, 11)),
                array(array_slice($pins, 110, 11), array_slice($pins, 121, 11)),
                array(array_slice($pins, 132, 11), array_slice($pins, 143, 11)),
            );

            /*
             * We'll need this for some of the advice
             */
            $seconds_since_account_created = time() - $profile->created_at;

            /*
             * Get the advice for pins
             */
            $days                 = ((($seconds_since_account_created / 60) / 60) / 24);
            $vars['pins_per_day'] = $pins_per_day = number_format($profile->pin_count / $days, 2);
            $vars['pin_count']    = number_format($profile->pin_count, 0);

            if ($pins_per_day > 1) {

                $vars['pins_advice'] = View::make('catalog.signup.advice.pins_good', $vars);
            } else {

                $vars['pins_advice'] = View::make('catalog.signup/advice/pins_bad', $vars);
            }


            /*
             * Get the advice for boards
             */
            $vars['num_boards']     = number_format($profile->board_count, 0);
            $vars['num_categories'] = $boards->numberOfCategories();
            $vars['top_category']   = $boards->topCategory('ignore no category');

            switch ($boards->score()) {
                case 'bad':

                    $vars['boards_advice'] = View::make('catalog.signup/advice/boards_bad', $vars);
                    break;

                case 'good':

                    $vars['boards_advice'] = View::make('catalog.signup/advice/boards_good', $vars);
                    break;
            }

            /*
             * Calculate the number of followers per week
             */
            $weeks                      = (((($seconds_since_account_created / 60) / 60) / 24) / 7);
            $vars['followers_per_week'] = $followers_per_week = ceil($profile->follower_count / $weeks);

            $vars['followers'] = number_format($profile->follower_count, 0);

            /*
             * Get the advice for followers
             */
            if ($followers_per_week < 10) { //bad
                $vars['followers_advice'] = View::make('catalog.signup/advice/followers_bad', $vars);
            } elseif ($followers_per_week > 50) {

                $vars['followers_advice'] = View::make('catalog.signup/advice/followers_great', $vars);
            } else {

                $vars['followers_advice'] = View::make('catalog.signup/advice/followers_good', $vars);
            }

            /*
             * Other template variables
             */
            $vars['name']              = $profile->getName();
            $vars['username']          = $username;
            $vars['profile_image_url'] = $profile->getImageUrl(75);
            if (Input::has('source')) {
                $vars['source'] = Input::get('source');
            } else {
                $vars['source'] = Cookie::get('source', '');
            }

            /*
             * Show the page
             */

            $this->layout->main_content = View::make('catalog.signup.free', $vars);


        }
        catch (RequiredVariableException $e) {
            /*
             * @todo send them to a place explaining why it didn't work
             */
            ppx($e);

        }

        catch (ProfileNotFoundException $e) {
            /*
             * @todo send them to a place explaining why it didn't work
             */
            ppx($e);
        }

    }

    /**
     * @author  Will
     */
    public function storeLead()
    {
        try {

            if (!isset($_GET['username'])) {
                throw new RequiredVariableException('Username is required');
            }

            if ($_GET['username'] == "") {
                throw new RequiredVariableException('Username is empty', 1);
            }

            $username = filter_var($_GET['username'], FILTER_SANITIZE_STRING);

            if (isset($_GET['source'])) {
                $source        = $_GET['source'];
                $source_string = "?source=" . $source;
            }


            /*
             * We want to have a somewhat recent pull of profile data
             * So if we have anything from the past 12 hours, we just use what we have in the
             * database
             */
            try {

                $profile = Profile::findInDB($username);

                if ($profile->wasPulledMoreThan('12 hours ago')) {

                    $profile->updateViaApi()->insertUpdateDB();

                }

            }
            catch (ProfileNotFoundException $e) {

                $profile = Profile::createViaApi($username);
                $profile->insertUpdateDB();

            }

            /*
             * if the last time we ran profile calcs was more than 12 hours ago
             * we set off the engine to calculate their profile history
             * we'll also be fetching
             *
             */
            if ($profile->historyCalculatedMoreThan('12 hours ago')) {

                shell_exec('php ' . base_path() . "/engines/calculations/calculate_profile_history.php $profile->user_id > /dev/null 2>&1 &");
            }


            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

            $STH = $this->DBH->prepare(
                             'INSERT INTO user_leads
                              SET
                               username = :username,
                               user_agent = :user_agent,
                               timestamp = :time,
                               ip = :ip'
            );

            $STH->execute(array(
                               ':username'   => $profile->username,
                               ':user_agent' => $user_agent,
                               ':time'       => time(),
                               ':ip'         => ip()

                          ));

            return Redirect::to('/signup/free/' . urlencode($username) . $source_string);

        }
        catch (PinterestProfileNotFoundException $e) {

            return Redirect::to('/')
                           ->with('signup_error', true)
                           ->with('username', $username)
                           ->with('message', 'That username could not be found on Pinterest');

        }
        catch (Exception $e) {
            return Redirect::to('/')
                           ->with('signup_error', true)
                           ->with('username', $username)
                           ->with('message', $e->getMessage());
        }

    }


    /**
     * POST /signup/demo/process
     * @author  Will
     *
     */
    public function processDemo() {





        /** @var User $user */
        $user = User::find(1748);
        User::autoLogin($user->email,$user->temporary_key);





    }


}