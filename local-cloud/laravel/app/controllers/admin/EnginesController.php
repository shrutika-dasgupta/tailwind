<?php namespace Admin;

use Redirect,
    Input,
    View,
    Config,
    Auth,
    DatabaseInstance;

use Engines;

use willwashburn\table,
    HipChat\HipChat;

/**
 * Class EnginesController
 *
 * @package Admin
 */
class EnginesController extends BaseController
{
    protected $layout = 'layouts.admin';

    /**
     * /engines/all-clear
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function getAllClear()
    {
        $hip_chat = new HipChat(Config::get('hipchat.rooms.engineering.API_TOKEN'));

        $hip_chat->message_room(
                 Config::get('hipchat.rooms.engineering.ID'),
                 'Engine Beaver',
                 Auth::user()->name . " checked the engines report, everything is running smoothly.",
                 $notify = false,
                 HipChat::COLOR_GREEN
        );

        return Redirect::to('/engines/status');
    }

    /**
     * /engines/reset
     *
     * @author  Will
     * @return \Illuminate\Http\RedirectResponse
     */
    public function getReset()
    {
        $DBH      = DatabaseInstance::DBO();
        $hip_chat = new HipChat(Config::get('hipchat.rooms.engineering.API_TOKEN'));

        if (isset($_GET['engine'])) {
            $engine = filter_var(urldecode(Input::get('engine')), FILTER_SANITIZE_STRING);

            $STH = $DBH->prepare("
               update status_engines set status = 'Complete' where engine = :engine
            ");

            $STH->execute(
                array(
                     ':engine' => $engine
                )
            );

            $message_body = Auth::user()->name . " reset $engine status to complete";

            $hip_chat->message_room(
                     Config::get('hipchat.rooms.engineering.ID'),
                     'Engine Beaver',
                     $message_body,
                     $notify = false,
                     HipChat::COLOR_GREEN
            );

            return Redirect::back();
        }

        $DBH->query("update status_engines set status = 'Complete' where status = 'Running' ");

        return Redirect::back();
    }

    /**
     * /engines/pause?engine={engine}
     *
     * @author  Will
     * @return \Illuminate\Http\RedirectResponse
     */
    public function getPause()
    {
        $DBH      = DatabaseInstance::DBO();
        $hip_chat = new HipChat(Config::get('hipchat.rooms.engineering.API_TOKEN'));

        if (isset($_GET['engine'])) {
            $engine = filter_var(urldecode(Input::get('engine')), FILTER_SANITIZE_STRING);

            $STH = $DBH->prepare("
               update status_engines set status = 'Paused' where engine = :engine
            ");

            $STH->execute(
                array(
                     ':engine' => $engine
                )
            );

            $message_body = Auth::user()->name . " paused $engine.";

            $hip_chat->message_room(
                     Config::get('hipchat.rooms.engineering.ID'),
                     'Engine Beaver',
                     $message_body,
                     $notify = false,
                     HipChat::COLOR_GRAY
            );

            return Redirect::back();
        }

        $DBH->query("update status_engines set status = 'Complete' where status = 'Running' ");

        return Redirect::back();
    }
    /**
     * /engines/status
     *
     * @author  Will
     */
    public function getStatus()
    {
        $engines      = Engines::fetch();
        $engine_table = new table();
        $engine_table->setId('engines');
        $engine_table->striped()->bordered()->condensed();

        foreach ($engines as $engine) {

            $difference = time() - $engine->timestamp;

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
            $reset = '';

            if ($engine->status != 'Paused') {
                $reset = '<a class="btn pull-right btn-mini" href="/engines/pause/?engine=' . urlencode($engine->engine) . '"><i class="icon-pause"></i></a>';

            }

            if ($engine->status != 'Complete') {
                $reset .= '<a class="btn btn-warning btn-mini" href="/engines/reset/?engine=' . urlencode($engine->engine) . '">Reset</a>';
            }

            $engine_table->addRow(
                         array(
                              'Engine'           => $engine->engine,
                              'Longest Run Time' => number_format($engine->longest_run_time) . ' seconds',
                              'Average Run Time' => number_format($engine->average_run_time) . ' seconds',
                              'Runs'             => number_format($engine->runs),
                              'Last run'         => $time_status,
                              'Status'           => $engine->status,
                              ''                 => $reset
                         ));
        }

        $vars = array(
            'engines_table'  => $engine_table->render(),
            'execution_time' => '',
        );

        $this->layout->main_content = View::make('admin.engines', $vars);
    }

}
