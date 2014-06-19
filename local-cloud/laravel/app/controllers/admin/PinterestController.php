<?php

namespace Admin;

use   View;

use DatabaseInstance,
    StatusProfile,
    StatusProfileFollower,
    StatusProfilePin,
    StatusDomain;

use Engines,
    Engine,
    Profile;
use willwashburn\table;

class PinterestController extends BaseController
{

    /*
     * The layout that should be used for responses.
     */
    protected $layout = 'layouts.admin';

    /**
     * /pinterest/calls
     * Shows a graph of the last 72 hours of Pinterest Data
     */
    public function getCalls()
    {
        $DBH  = DatabaseInstance::DBO();
        $STH  = $DBH->query("select datetime, method, sum(calls) as calls from status_api_calls where client_id = 0 OR client_id = 1431615 group by datetime, method order by datetime DESC, method desc limit 1000");
        $data = $STH->fetchAll();

        $data = array_reverse($data);

        if (isset($_GET['v'])) {
            unset($data[71]);
        }

        $methods = array();
        foreach ($data as $d) {
            $method = $d->method;

            if ($method == '') {
                $method = 'Unknown';
            }

            if (!isset($methods["$method"])) {
                $methods["$method"] = array();
            }

            $methods["$method"]['method'] = $method;
        }

        $calls = array();
        foreach ($data as $d) {
            $method = $d->method;
            $date   = $d->datetime;
            $count  = $d->calls;

            if (!isset($calls["$date"])) {
                $calls["$date"] = array();
            }

            if (!isset($calls["$date"]['total'])) {
                $calls["$date"]['total'] = 0;
            }

            $calls["$date"]['total'] += $count;
            $calls["$date"]["$method"]             = array();
            $calls["$date"]["$method"]['calls']    = $count;
            $calls["$date"]["$method"]['method']   = $method;
            $calls["$date"]["$method"]['datetime'] = $date;
        }

        $STH  = $DBH->query("select datetime, method, calls from status_api_calls where client_id = 1436524 group by datetime, method order by datetime DESC, method desc limit 1000");
        $data = $STH->fetchAll();

        $data = array_reverse($data);

        if (isset($_GET['v'])) {
            unset($data[71]);
        }

        $methods_alt = array();
        foreach ($data as $d) {
            $method = $d->method;

            if ($method == '') {
                $method = 'Unknown';
            }

            if (!isset($methods["$method"])) {
                $methods_alt["$method"] = array();
            }

            $methods_alt["$method"]['method'] = $method;
        }

        $calls_alt = array();
        foreach ($data as $d) {
            $method = $d->method;
            $date   = $d->datetime;
            $count  = $d->calls;

            if (!isset($calls_alt["$date"])) {
                $calls_alt["$date"] = array();
            }

            if (!isset($calls_alt["$date"]['total'])) {
                $calls_alt["$date"]['total'] = 0;
            }

            $calls_alt["$date"]['total'] += $count;
            $calls_alt["$date"]["$method"]             = array();
            $calls_alt["$date"]["$method"]['calls']    = $count;
            $calls_alt["$date"]["$method"]['method']   = $method;
            $calls_alt["$date"]["$method"]['datetime'] = $date;
        }

        $vars = array(
            'calls'   => $calls,
            'methods' => $methods,
            'calls_alt' => $calls_alt,
            'methods_alt' => $methods_alt
        );

        $this->layout->main_content = View::make('admin/pinterest_calls', $vars);

    }

    /**
     * /pinterest/status
     *
     * @author  Will
     */
    public function getQueue()
    {


        $STH = $this->DBH->query(
                         'select api_call, track_type, count(*) as calls
                          from status_api_calls_queue
                          GROUP BY api_call,track_type
                          order by count(*) desc'
        );

        $table = new table();
        $table->striped()->bordered()->condensed();
        $chart_data = array();

        foreach ($STH->fetchAll() as $key => $value) {

            $oldest_call = $this->DBH->prepare(
                                     "select timestamp from status_api_calls_queue
                                      where api_call = :call
                                      AND track_type = :track_type
                                      order by timestamp asc
                                      LIMIT 1"
            );

            $oldest_call->execute(
                        array(
                             ':call'       => $value->api_call,
                             ':track_type' => $value->track_type
                        ));

            $oldest_call = $oldest_call->fetch();

            $table->addRow(array(
                                'Api Call'       => $value->api_call,
                                'track type'     => $value->track_type,
                                'Calls on queue' => number_format($value->calls),
                                'oldest call'    => relativeTime($oldest_call->timestamp)
                           ));

            $chart_data[$value->api_call] = $value->calls;

        }


        /*
         * Pulls Status - Profiles
         */
        $status_profile          = new StatusProfile();
        $status_profile_follower = new StatusProfileFollower();
        $status_profile_pin      = new StatusProfilePin();
        $status_domain           = new StatusDomain();

        $profile_count           = $status_profile->getActiveCount();
        $profiles_finished       = $status_profile->getLastPulledTodayCount();
        $profile_boards_finished = $status_profile->getLastPulledBoardsTodayCount();

        $profile_followers_count    = $status_profile_follower->getActiveCount();
        $profile_followers_finished = $status_profile_follower->getLastPulledTodayCount();

        $profile_pins_count    = $status_profile_pin->getActiveCount();
        $profile_pins_finished = $status_profile_pin->getLastPulledTodayCount();

        $domain_count = $status_domain->getActiveCount();
        $domain_finished = $status_domain->getLastPulledTodayCount();


        $pulls_table = new table();
        $pulls_table->striped()->bordered()->condensed();

        $overall_total_pulls = 0;
        $overall_finished_pulls = 0;
        $overall_unfinished_pulls = 0;

        for($i = 2; $i >= 0; $i--){

            $unfinished_profiles  = $profile_count[$i]->count - $profiles_finished[$i]->count;
            $unfinished_boards    = $profile_count[$i]->count - $profile_boards_finished[$i]->count;
            $unfinished_pins      = $profile_pins_count[$i]->count - $profile_pins_finished[$i]->count;
            $unfinished_followers = $profile_followers_count[$i]->count - $profile_followers_finished[$i]->count;
            $unfinished_domains   = $domain_count[$i]->count - $domain_finished[$i]->count;

            $total_profiles  = $profile_count[$i]->count;
            $total_boards    = $profile_count[$i]->count;
            $total_pins      = $profile_pins_count[$i]->count;
            $total_followers = $profile_followers_count[$i]->count;
            $total_domains   = $domain_count[$i]->count;

            $finished_profiles  = $profiles_finished[$i]->count;
            $finished_boards    = $profile_boards_finished[$i]->count;
            $finished_pins      = $profile_pins_finished[$i]->count;
            $finished_followers = $profile_followers_finished[$i]->count;
            $finished_domains   = $domain_finished[$i]->count;
            
            $overall_unfinished_pulls += ($unfinished_profiles + $unfinished_boards + $unfinished_pins + $unfinished_followers + $unfinished_domains);
            $overall_finished_pulls += ($finished_profiles + $finished_boards + $finished_pins + $finished_followers + $finished_domains);
            $overall_total_pulls += ($total_profiles + $total_boards + $total_pins + $total_followers + $total_domains);

            $pulls_table->addRow(
                        array(
                             'Type' => 'Profile',
                             'Track Type' => $profile_count[$i]->track_type,
                             'Total'      => $profile_count[$i]->count,
                             'Finished'   => $profiles_finished[$i]->count,
                             'Unfinished' => $unfinished_profiles,
                             'Percent to go' => number_format($unfinished_profiles/$total_profiles*100,2),
                             'class' => (number_format($unfinished_profiles/$total_profiles*100,2) > 50 ? 'danger' : (number_format($unfinished_profiles/$total_profiles*100,2) > 25 ? 'warning' : ''))
                        )
            );

            $pulls_table->addRow(
                        array(
                             'Type' => 'Profile Boards',
                             'Track Type' => $profile_count[$i]->track_type,
                             'Total'      => $profile_count[$i]->count,
                             'Finished'   => $profile_boards_finished[$i]->count,
                             'Unfinished' => $unfinished_boards,
                             'Percent to go' => number_format($unfinished_boards/$total_boards*100,2),
                             'class' => (($unfinished_boards/$total_boards*100) > 50 ? 'error' : ($unfinished_boards/$total_boards*100 > 25 ? 'warning' : ''))
                        )
            );

            $pulls_table->addRow(
                        array(
                             'Type' => 'Profile Pins',
                             'Track Type' => $profile_pins_count[$i]->track_type,
                             'Total'      => $profile_pins_count[$i]->count,
                             'Finished'   => $profile_pins_finished[$i]->count,
                             'Unfinished' => $unfinished_pins,
                             'Percent to go' => number_format($unfinished_pins/$total_pins*100,2),
                             'class' => (($unfinished_pins/$total_pins*100) > 50 ? 'error' : ($unfinished_pins/$total_pins*100 > 25 ? 'warning' : ''))
                        )
            );

            $pulls_table->addRow(
                        array(
                             'Type' => 'Profile Followers',
                             'Track Type' => $profile_followers_count[$i]->track_type,
                             'Total'      => $profile_followers_count[$i]->count,
                             'Finished'   => $profile_followers_finished[$i]->count,
                             'Unfinished' => $unfinished_followers,
                             'Percent to go' => number_format($unfinished_followers/$total_followers*100,2),
                             'class' => (($unfinished_followers/$total_followers*100) > 50 ? 'error' : ($unfinished_followers/$total_followers*100 > 25 ? 'warning' : ''))
                        )
            );

            $pulls_table->addRow(
                        array(
                             'Type' => 'Domains',
                             'Track Type' => $domain_count[$i]->track_type,
                             'Total'      => $domain_count[$i]->count,
                             'Finished'   => $domain_finished[$i]->count,
                             'Unfinished' => $unfinished_domains,
                             'Percent to go' => number_format($unfinished_domains/$total_domains*100,2),
                             'class' => ((($unfinished_domains/$total_domains*100) > 50) ? 'error' : (($unfinished_domains/$total_domains*100) > 25 ? 'warning' : ''))
                        )
            );
        }

        $pulls_table->addRow(
                    array(
                         'Type'       => '<strong>Total</strong>',
                         'Track Type' => '<strong>All</strong>',
                         'Total'      => "<strong>$overall_total_pulls</strong>",
                         'Finished'   => "<strong>$overall_finished_pulls</strong>",
                         'Unfinished' => "<strong>$overall_unfinished_pulls</strong>",
                         'Percent to go' =>
                             "<strong>"
                             . number_format($overall_unfinished_pulls/$overall_total_pulls*100,2)
                             . "</strong>",
                         'class' => 'info'
                    )
        );


        /*
         * Engine Status table
         */
        $engines      = Engines::fetch();
        $engine_table = new table();
        $engine_table->striped()->bordered()->condensed();

        foreach ($engines as $engine) {

            $difference = time() - $engine->timestamp;

            $reset = '<a class="btn btn-warning" href="/engines/reset/?engine=' . urlencode($engine->engine) . '">Reset</a>';
            if ($engine->status == 'Complete') {
                $reset = '';
            }

            $time_status = relativeTime($engine->timestamp);

            if ($difference < 0) {
                //its in the future!
                $time_status = '<span class="label label-success">Now</span>&nbsp;';
            } else if ($difference > 31563000) {
                $time_status = '<span class="label">' . $time_status . '</span>&nbsp;';
            } else if ($difference > 2700) {
                $time_status = '<span class="label label-important">' . $time_status . '</span>&nbsp;';
            } else if ($difference > 1500) {
                $time_status = '<span class="label label-warning">' . $time_status . '</span>&nbsp;';
            }

            $engine_table->addRow(
                         array(
                              'Engine'           => $engine->engine,
                              'Status'           => $engine->status,
                              'Longest Run Time' => number_format($engine->longest_run_time) . ' seconds',
                              'Last run'         => $time_status,
                              'action'           => $reset
                         ));
        }

        $vars = array(
            'calls_table'    => $table->render(),
            'pulls_table'    => $pulls_table->render(),
            'status_engines' => $engine_table->render()
        );

        $this->layout->main_content = View::make('admin/api_call_queue', $vars);
    }

    /**
     * /calcs/{username}
     *
     * @param $username
     *
     * @author  Will
     */
    public function showCalcs($username)
    {
        $profile = Profile::findInDB($username);
        $calcs   = $profile->getCalculations();

        $table = new table();
        $table->striped()->bordered()->condensed();

        /** @var $calc /CalcProfileHistory */
        foreach ($calcs as $calc) {
            $table->addRow(array(
                                'Date'            => date('D    m/d/Y', $calc->date),
                                'Follower Count'  => number_format($calc->follower_count),
                                'Following Count' => number_format($calc->following_count),
                                'Reach'           => number_format($calc->reach),
                                'Boards'          => number_format($calc->board_count),
                                'Pin Count'       => number_format($calc->pin_count),
                                'Repins'          => number_format($calc->repin_count),
                                'Calced at'       => date('g:ia', $calc->timestamp)
                           ));
        }

        $vars = array(
            'calcs_table' => $table->render(),
            'username'    => $username
        );

        $this->layout->main_content = View::make('admin.calcs', $vars);
    }

}



