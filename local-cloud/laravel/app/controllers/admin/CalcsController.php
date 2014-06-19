<?php

namespace Admin;

date_default_timezone_set('America/Chicago');

use Redirect,
    View;

use DatabaseInstance;

use Engines,
    Engine;

use willwashburn\table;

class CalcsController extends BaseController
{

    protected $layout = 'layouts.admin';

    /**
     * /calcs/status
     *
     * @author  Alex
     */
    public function getStatus()
    {

        $conn = DatabaseInstance::mysql_connect();

        $calcs_table = new table();
        $calcs_table->setId('calcs');
        $calcs_table->striped()->bordered()->condensed();


//        print "<table border=1 cellspacing=0 cellpadding=5>
//                    <tr>
//                        <td>Calcs Engine</td>
//                        <td>Calcs</td>
//                        <td>Completed</td>
//                        <td>Left to Start</td>
//                        <td>Left to Finish</td>
//                    </tr>";
        $t = time();
        $timestamp = mktime(0,0,0,date("n",$t),date("j",$t),date("Y",$t));



        /*
         * Profiles
         */
        $engine = "Profiles";

        $total_calcs = 0;
        $acc = "select count(*) as calcs from status_profiles where track_type!='orphan' and track_type!='not_found'";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $total_calcs = $a['calcs'];
        }

        $total_completed = 0;
        $acc = "select count(*) as calcs from calcs_profile_history where date = $timestamp";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $total_completed = $a['calcs'];
        }



        $left_to_start = 0;
        $acc = "select count(*) as calcs from status_profiles where last_calced < $timestamp and track_type!='orphan' and track_type!='not_found'";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $left_to_start = $a['calcs'];
        }

        $acc = "select min(timestamp) as min, max(timestamp) as max from calcs_profile_history where date = '$timestamp'";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $max = $a['max'];
            $min = $a['min'];
        }

        $left_to_finish = $total_calcs - $total_completed;


        $calcs_table->addRow(
            array(
                 'Engine'         => $engine,
                 'Total Queue'    => number_format($total_calcs),
                 'Completed'      => number_format($total_completed),
                 'Left to Start'  => number_format($left_to_start),
                 'Left to Finish' => number_format($left_to_finish),
                 'Earliest Start' => date("m/d g:ia", $min),
                 'Latest Start'   => date("m/d g:ia", $max)
            ));




        /*
         * Boards
         */
        $engine = "Boards";

        $total_calcs = 0;
        $acc = "select count(*) as calcs from status_boards where track_type!='orphan'";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $total_calcs = $a['calcs'];
        }

        $total_completed = 0;
        $acc = "select count(*) as calcs from calcs_board_history where date = '$timestamp'";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $total_completed = $a['calcs'];
        }

        $left_to_start = 0;
        $acc = "select count(*) as calcs from status_boards where last_calced < '$timestamp' and track_type!='orphan'";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $left_to_start = $a['calcs'];
        }

//                $acc = "select min(timestamp) as min, max(timestamp) as max from calcs_board_history where date = '$timestamp'";
//                $acc_res = mysql_query($acc,$conn) or die(mysql_error());
//                while ($a = mysql_fetch_array($acc_res)) {
//                    $max = $a['max'];
//                    $min = $a['min'];
//                }

        $left_to_finish = $total_calcs - $total_completed;

        $calcs_table->addRow(
            array(
                 'Engine'         => $engine,
                 'Total Queue'    => number_format($total_calcs),
                 'Completed'      => number_format($total_completed),
                 'Left to Start'  => number_format($left_to_start),
                 'Left to Finish' => number_format($left_to_finish),
                 'Earliest Start' => date("m/d g:ia", $min),
                 'Latest Start'   => date("m/d g:ia", $max)
            ));




        /*
         * Domains
         */
        $engine = "Domains";

        $total_calcs = 0;
        $acc = "select count(*) as calcs from status_domains where track_type!='orphan'";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $total_calcs = $a['calcs'];
        }

        $total_completed = 0;
        $acc = "select count(*) as calcs from calcs_domain_history where date = '$timestamp'";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $total_completed = $a['calcs'];
        }

        $left_to_start = 0;
        $acc = "select count(*) as calcs from status_domains where last_calced < '$timestamp' and track_type!='orphan'";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $left_to_start = $a['calcs'];
        }

        $acc = "select min(timestamp) as min, max(timestamp) as max from calcs_domain_history where date = '$timestamp'";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $max = $a['max'];
            $min = $a['min'];
        }

        $left_to_finish = $total_calcs - $total_completed;

        $calcs_table->addRow(
            array(
                 'Engine'         => $engine,
                 'Total Queue'    => number_format($total_calcs),
                 'Completed'      => number_format($total_completed),
                 'Left to Start'  => number_format($left_to_start),
                 'Left to Finish' => number_format($left_to_finish),
                 'Earliest Start' => date("m/d g:ia", $min),
                 'Latest Start'   => date("m/d g:ia", $max)
            ));



        /*
         * Keywords
         */
        $engine = "Keywords";

        $total_calcs = 0;
        $acc = "select count(*) as calcs from status_keywords where track_type!='orphan' and track_type!='on-hold'";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $total_calcs = $a['calcs'];
        }

        $total_completed = 0;
        $acc = "select count(distinct keyword) as calcs from cache_keyword_pins where timestamp >= $timestamp";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $total_completed = $a['calcs'];
        }

        $left_to_start = 0;
        $acc = "select count(*) as calcs from status_keywords where last_calced < '$timestamp' and track_type!='orphan' and track_type!='on-hold'";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $left_to_start = $a['calcs'];
        }

        $acc = "select min(timestamp) as min, max(timestamp) as max from cache_keyword_pins";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $max = $a['max'];
            $min = $a['min'];
        }

        $left_to_finish = $total_calcs - $total_completed;

        $calcs_table->addRow(
            array(
                 'Engine'         => $engine,
                 'Total Queue'    => number_format($total_calcs),
                 'Completed'      => number_format($total_completed),
                 'Left to Start'  => number_format($left_to_start),
                 'Left to Finish' => number_format($left_to_finish),
                 'Earliest Start' => date("m/d g:ia", $min),
                 'Latest Start'   => date("m/d g:ia", $max)
            ));




        /*
         * Traffic
         */
        $engine = "Traffic";

        $total_calcs = 0;
        $acc = "select count(*)
            from status_traffic a
            left join user_organizations b
            on a.org_id = b.org_id
            where (b.plan = 3 or b.plan = 4)";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $total_calcs = $a['calcs'];
        }

        $total_completed = 0;
        $acc = "select count(*)
            from status_traffic a
            left join user_organizations b
            on a.org_id = b.org_id
            where (b.plan = 3 or b.plan = 4)
            and last_calced >= '$timestamp'";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $total_completed = $a['calcs'];
        }


        $left_to_start = 0;
        $acc = "select count(*)
            from status_traffic a
            left join user_organizations b
            on a.org_id = b.org_id
            where (b.plan = 3 or b.plan = 4)
            and last_calced < '$timestamp'";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $left_to_start = $a['calcs'];
        }

        $acc = "select min(last_calced) as min, max(last_calced) as max from status_traffic";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $max = $a['max'];
            $min = $a['min'];
        }

        $left_to_finish = $total_calcs - $total_completed;

        $calcs_table->addRow(
            array(
                 'Engine'         => $engine,
                 'Total Queue'    => number_format($total_calcs),
                 'Completed'      => number_format($total_completed),
                 'Left to Start'  => number_format($left_to_start),
                 'Left to Finish' => number_format($left_to_finish),
                 'Earliest Start' => date("m/d g:ia", $min),
                 'Latest Start'   => date("m/d g:ia", $max)
            ));


        /*
         * Engagement
         */
        $engine = "Engagement";

        $total_calcs = 0;
        $acc = "select count(*) as calcs from status_profiles where track_type!='free' and track_type!='orphan'";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $total_calcs = $a['calcs'];
        }

        $total_completed = 0;
        $acc = "select count(distinct user_id) as calcs from cache_engagement_influencers where date = '$timestamp'";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $total_completed = $a['calcs'];
        }

        $left_to_start = 0;
        $acc = "select count(*) as calcs from status_profiles where last_calced_engagement < '$timestamp' and track_type!='free' and track_type!='orphan'";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $left_to_start = $a['calcs'];
        }

//                $acc = "select min(timestamp) as min, max(timestamp) as max from calcs_engager_history where `date` = '$timestamp'";
//                $acc_res = mysql_query($acc,$conn) or die(mysql_error());
//                while ($a = mysql_fetch_array($acc_res)) {
//                    $max = $a['max'];
//                    $min = $a['min'];
//                }


        $left_to_finish = $total_calcs - $total_completed;

        $calcs_table->addRow(
            array(
                 'Engine'         => $engine,
                 'Total Queue'    => number_format($total_calcs),
                 'Completed'      => number_format($total_completed),
                 'Left to Start'  => number_format($left_to_start),
                 'Left to Finish' => number_format($left_to_finish),
                 'Earliest Start' => date("m/d g:ia", $min),
                 'Latest Start'   => date("m/d g:ia", $max)
            ));




        $users = array();
        $acc = "select user_id from calcs_profile_history where date = '$timestamp'";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $user_id = $a['user_id'];
            $users["$user_id"] = true;
        }

        $uncomplete_user_ids = "";
        $acc = "select user_id from status_profiles where track_type!='orphan' and track_type!='not_found'";
        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
        while ($a = mysql_fetch_array($acc_res)) {
            $user_id = $a['user_id'];
            if (!array_key_exists($user_id, $users)) {
                $uncomplete_user_ids .= "$user_id<br/>";
            } else {
            }
        }


        $vars = array(
            'calcs_table'         => $calcs_table->render(),
            'uncomplete_user_ids' => $uncomplete_user_ids
        );

        $this->layout->main_content = View::make('admin.calcs_status', $vars);
    }

}
