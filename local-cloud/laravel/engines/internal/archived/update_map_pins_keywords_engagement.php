<?php

/**
 *
 * @author  Alex
 */
error_reporting(E_ALL);
ini_set('display_errors', 'On');
ini_set('memory_limit', '5000M');
ini_set('max_execution_time', '5000');
include('classes/pinterest.php');
include('classes/pin.php');
include('classes/board.php');
// include('includes/connection.php');
include('includes/functions.php');
include("classes/crawl.php");



chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\CLI;



try {

    $date = 1386907560;
    $end_date_calc = $date;
    $begin_date_calc = strtotime("-1 minutes", $end_date_calc);
    $now = date('g:ia');
    CLI::h1('Starting tests (' . $now . ')');
    //$conn = DatabaseInstance::mysql_connect();
    $DBH = DatabaseInstance::DBO();
    CLI::write('Connected to database');
    $run_count = 0;


//    $pins = 1;
//    while($pins != 0){
//
//        $start = microtime(true);
//        $pins = 0;
//        $pin_array = array();
//        $acc = "select a.pin_id, a.repin_count, a.like_count, a.comment_count from
//        map_pins_keywords b left join data_pins_new a on b.pin_id = a.pin_id
//        where b.timestamp < 1387337880 limit 1000";
//        $acc_res = mysql_query($acc,$conn) or die(mysql_error());
//        while ($a = mysql_fetch_array($acc_res)) {
//            $pins = 1;
//
//            $pin_id = $a['pin_id'];
//
//            $pin_array["$pin_id"] = array();
//            $pin_array["$pin_id"]['pin_id'] = $pin_id;
//            $pin_array["$pin_id"]['repins'] = $a['repin_count'];
//            $pin_array["$pin_id"]['likes'] = $a['like_count'];
//            $pin_array["$pin_id"]['comments'] = $a['comment_count'];
//        }
//        $end = microtime(true);
//        CLI::write('Pull: ' . ($end - $start));
//
//
//        $start = microtime(true);
//        foreach($pin_array as $p){
//
//            $pin_id = $p['pin_id'];
//            $repins = $p['repins'];
//            $likes = $p['likes'];
//            $comments = $p['comments'];
//            $timenow = time();
//
//            $insert = "Update map_pins_keywords
//                    set repin_count = $repins
//                    , like_count = $likes
//                    , comment_count = $comments
//                    , timestamp = $timenow
//                    where pin_id = $pin_id";
//            $resu = mysql_query($insert, $conn);
//        }
//
//        $end = microtime(true);
//        CLI::write('Updates: ' . ($end - $start));
//
//        CLI::write($run_count*100);
//
//        $run_count++;
//
//    }


    while($begin_date_calc > 1366002000){
    //while($begin_date_calc > 1387260000){

        $start = microtime(true);

        $STM = $DBH->query("set sql_log_bin = 0;");

        $STM = $DBH->query("

              update map_pins_keywords a
              left join data_pins_new b
              on a.pin_id = b.pin_id
                set a.repin_count = b.repin_count
                , a.like_count = b.like_count
                , a.comment_count = b.comment_count
                , a.timestamp = unix_timestamp(now())
                , a.flag = 1
              where a.timestamp < $end_date_calc and a.timestamp >= $begin_date_calc;

        ");

        $STM = $DBH->query("set sql_log_bin = 1;");

        $end_date_calc = $begin_date_calc;
        $begin_date_calc = strtotime("-1 minutes", $begin_date_calc);

        $end = microtime(true);
        $run_count++;
        CLI::write($run_count . ': Completed for ' . date("Y-m-d H:i", $begin_date_calc) . " $begin_date_calc :: Took " . ($end - $start) . "seconds");
    }

    CLI::write('Complete!');
}

catch (Exception $e) {
    CLI::alert($e->getMessage());
    CLI::stop();
}
