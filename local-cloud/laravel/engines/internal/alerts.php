<?php

use Pinleague\Pinterest,
    Pinleague\CLI,
    HipChat\HipChat,
    Carbon\Carbon;

/**
 * Alerts when things happen
 * sends via email
 *
 * @author  Will
 *
 * @example
 * php alerts.php notice
 * php alerts.php warning
 * php alerts.php alert
 *
 * Current setup
 * warning -> calcs server
 * error -> fe server
 * alert -> cat server
 *
 */

/*
 * Note:: you can't call anything $config before the bootstrap file
 * because laravel overwrites it - hence the tw_ prefix
 */

$tw_config = array(
    'alert levels' => array(
        'warning' => array(
            'time'             => array('30 minutes ago', '1 hour ago'),
            'excluded_engines' => array(
                'calculations-traffic.php',
                'calculations-keywords_word_cloud.php',
                'legacy-analytics.php',
                'api_pulls-keyword_user.php',
                'api_pulls-keyword_search_boards.php',
                'api_pulls-keyword_sc.php',
                'test',
                'api_pulls-board_followers_user.php',
                'api_pulls-board_pins_user.php'
            )
        ),
        'error'   => array(
            'time'             => array('2 hours ago', '3 hours ago'),
            'excluded_engines' => array(
                'calculations-traffic.php',
                'api_pulls-keyword_user.php',
                'api_pulls-keyword_search_boards.php',
                'api_pulls-keyword_sc.php',
                'test'
            )
        ),
        'alert'   => array(
            'time'             => array('8 hours ago', '9 hours ago'),
            'excluded_engines' => array('test')
        )
    )
);


/*
 * Since we're running this from the CLI, we're changing into this directory so
 * the relative path works as we'd expect here. Otherwise things get hairy
 */
chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

/*
 * We want to record logs to individual files, so we change the log settings here
 * Otherwise they would go to the general daily log
 */
Log::setLog(__FILE__);

try {
    $now = date('g:ia');
    CLI::h1(Log::info('Checking (' . $now . ')'));

    $DBH = DatabaseInstance::DBO();
    CLI::write(Log::debug('Connected to database'));

    $hip_chat = new HipChat(Config::get('hipchat.rooms.engineering.API_TOKEN'));

    /*
     * Gets the alert level from the arguments added in the command line
     * via the global $argv variable
     */
    $alert_level = $argv[1];

    if (!array_key_exists($alert_level, $tw_config['alert levels'])) {
        throw new Exception($alert_level . ' is not a valid alert level');
    }

    $time   = strtotime($tw_config['alert levels'][$alert_level]['time'][0]);
    $oldest = strtotime($tw_config['alert levels'][$alert_level]['time'][1]);

    $excluded_engines = array_merge(
        array('internal-pinterest_alerts.php', 'intercom-update_users.php'),
        $tw_config['alert levels'][$alert_level]['excluded_engines']
    );

    $STH = $DBH->query("
            select * from status_engines
            where timestamp < $time
            and timestamp > $oldest
            and engine NOT IN ('" . implode("','", $excluded_engines) . "')
        ");

    if ($STH->rowCount() > 0) {

        CLI::alert('Some engines are not running');

        $engines = $STH->fetchAll();

        $list = '';

        foreach ($engines as $engine) {

            Log::error("$engine->engine is not running");

            CLI::write('...like ' . $engine->engine);

            $time     = date('g:ia', $engine->timestamp);
            $last_run = Carbon::createFromFormat('U', $engine->timestamp)->diffForHumans();

            $list .= $engine->engine
                . ' (' . $time . ' CST - '
                . $last_run . ')<br>';
        }

        $message_body =
            "The last time these engines ran was over "
            . $tw_config['alert levels'][$alert_level]['time'][0]
            . '<br>'
            . $list
            . '<br>'
            . '<a href="http://admin.tailwindapp.com/engines/status">View engines report</a>';

        $hip_chat->message_room(
                 Config::get('hipchat.rooms.engineering.ID'),
                 'Alert Beaver',
                 $message_body,
                 $notify = true,
                 HipChat::COLOR_RED
        );

        CLI::write(Log::notice("Sent hipchat notification about engines"));

        Mail::send('shared.emails.templates.blank',
                   array('main_body' => $message_body),

            function ($message) use ($user, $alert_level, $now) {

                $message->from('coredev+bot@tailwindapp.com', 'Engine Alert Beaver');
                $message->to('coredev@tailwindapp.com', 'Tailwind Core Development Team');

                $message->subject('Engine ' . ucfirst($alert_level) . ' | ' . $now);

            }
        );
        CLI::write(Log::notice('Sent an email about engines'));
    }

    /*
    |--------------------------------------------------------------------------
    | Map Pin Promotoers
    |--------------------------------------------------------------------------
    */
    try {

        $promoter_exists = $DBH->query("select * from map_pins_promoters");
        if ($promoter_exists->rowCount() > 0) {

            $hip_chat->message_room(
                     Config::get('hipchat.rooms.engineering.ID'),
                     'Alert Beaver',
                     'Found promotion data!',
                     $notify = false,
                     HipChat::COLOR_PURPLE
            );

            Mail::send('shared.emails.templates.blank',
                       array(
                            'main_body' =>
                                'Found promotion data'
                       ),

                function ($message) {

                    $message->from('coredev+promotion-bot@tailwindapp.com', 'Promotion Data Bot');
                    $message->to('coredev@tailwindapp.com', 'Tailwind Core Development Team');

                    $message->subject('Found data in map_pins_promoters');
                }
            );
        }
    }
    catch (Exception $e) {
        CLI::alert($e->getMessage());
    }

    /*
    |--------------------------------------------------------------------------
    | Unwanted Pinterest API Errors
    |--------------------------------------------------------------------------
    */
    try {
        if($alert_level == "warning"){
            $last_30_minutes = strtotime("-30 minutes", time());
            $errors = $DBH->query(
                          "select * from status_api_errors
                          where (code = 3 or code = 8)
                          AND api_pull != 'Keyword Search'
                          AND timestamp > $last_30_minutes"
            );

            if ($errors->rowCount() > 0) {

                $hip_chat->message_room(
                         Config::get('hipchat.rooms.engineering.ID'),
                             'Alert Beaver',
                             'Critical Pinterest API Errors Found! (rate limit exceeded or authorization failed).  Check the status_api_erros table.',
                             $notify = false,
                             HipChat::COLOR_RED
                );

                foreach($errors as $error){
                    $body .= "Error code: " . $error->code . " (" . $error->message . ") - From Call: " . $error->api_call . " || Object_id: " . $error->object_id . " || Time: " . $error->timestamp . "<br><br>";
                }

                Mail::send('shared.emails.templates.blank',
                    array(
                         'main_body' => $body
                    ),

                    function ($message) {

                        $message->from('coredev+api-error-bot@tailwindapp.com', 'Pinterest API Error Bot');
                        $message->to('coredev@tailwindapp.com', 'Tailwind Core Development Team');

                        $message->subject('Critical Pinterest API Errors!');

                    }
                );
            }
        }
    }
    catch (Exception $e) {
        CLI::alert($e->getMessage());
    }

    /*
    |--------------------------------------------------------------------------
    | Pinterest API limit calls
    |--------------------------------------------------------------------------
    */
    $pinterest_calls = Engine::totalPinterestCalls(2, 'include current hour', Config::get("pinterest.client_id"));
    $calls_last_hour = $pinterest_calls[1];
    $calls_this_hour = $pinterest_calls[0];

    $minutes_into_this_hour = date('i');

    CLI::write(Log::info("Calls this hour: $calls_this_hour"));
    CLI::write(Log::info("Calls last hour: $calls_last_hour"));

    if (($calls_last_hour > Pinterest::HIGH_THRESHOLD OR $calls_last_hour < 7000) AND $minutes_into_this_hour < 50) {

        if ($minutes_into_this_hour > 15
            && (
                ($calls_this_hour * (60 / $minutes_into_this_hour)) > Pinterest::HIGH_THRESHOLD
                || ($calls_this_hour * (60 / $minutes_into_this_hour)) < 7000
            )
        )
        {
            CLI::alert('Calls seem too high or low. Sending an email');

            $calls_last_hour = number_format($calls_last_hour);
            $calls_this_hour = number_format($calls_this_hour);

            CLI::write(Log::error("Calls last hour were $calls_last_hour"));

            $message_body = 'Pinterest calls last hour were: ' . $calls_last_hour .
                '<br>' .
                "As of $now calls this hour are: $calls_this_hour" .
                '<br>' .
                '<a href="http://admin.tailwindapp.com/pinterest/queue">Queue Report</a>' .
                '&nbsp; | &nbsp;' .
                '<a href="http://admin.tailwindapp.com/engines/status">Engines Report</a>' .
                '&nbsp; | &nbsp;' .
                '<a href="http://admin.tailwindapp.com/pinterest/calls">Api Calls Graph</a>';

            $hip_chat->message_room(
                     Config::get('hipchat.rooms.engineering.ID'),
                     'Alert Beaver',
                     $message_body,
                     $notify = true,
                     HipChat::COLOR_RED
            );

            CLI::write(Log::debug("Sent hipchat notification about engines"));

            Mail::send('shared.emails.templates.blank',
                       array(
                            'main_body' => $message_body
                       ),

                function ($message) use ($user, $alert_level, $now) {

                    $message->from('coredev+bot@tailwindapp.com', 'Engine Alert Beaver');
                    $message->to('coredev@tailwindapp.com', 'Tailwind Core Development Team');

                    $message->subject("Pinterest calls alert | $now");

                }

            );
            CLI::write(Log::debug('Sent an email about calls'));
        }
    }
}
catch (Exception $e) {
    CLI::stop(Log::error($e));
}
