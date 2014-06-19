<?php
/**
 * @author Alex
 * Date: 4/24/14 2:54 PM
 * 
 */


/*
 * Config
 */
use Pinleague\CLI;
use Pinleague\Pinterest;
use Pinleague\PinterestException;

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';
Config::set('app.debug', true);


    CLI::h1('Starting program');

    $start_date = 64000000000000000;
    $increment  =  1000000000000000;
    $end_date   = $start_date + $increment;
    $finish    = 578890452282439915;

    $total_time = 0;
    $chunks_completed = 0;

    while($start_date < $finish){

        $start_time = microtime(true);

        $DBH = DatabaseInstance::DBO();

        $STH = $DBH
            ->prepare(
                "update status_boards a
                inner join data_boards b
                on a.board_id = b.board_id
                set a.owner_user_id = b.owner_user_id
                where a.owner_user_id is null
                and b.owner_user_id is not null
                and a.board_id < :end_date
                and a.board_id >= :start_date"
            );

        $STH->execute(array(
                           ":start_date" => $start_date,
                           ":end_date"  => $end_date
                      ));

        $start_date += $increment;
        $end_date   += $increment;

        $end_time = microtime(true);
        $run_time = $end_time - $start_time;
        $total_time += $run_time;

        $chunks_completed++;
        $avg_chunk_time = number_format($total_time/$chunks_completed, 2);
        $chunks_to_go = ceil(($finish - $end_date)/$increment);
        $time_to_go   = $chunks_to_go * $avg_chunk_time;


        CLI::write("Finished up to " . $end_date . ".  (" . ($finish - $end_date) . " to go).  Runtime: $run_time.  Total time: $total_time");
        CLI::write("Chunks to go: " . $chunks_to_go . ". Avg. Seconds per chunk: " . $avg_chunk_time . ". Estimated Time Remaining: " . $time_to_go . " seconds (" . number_format(($time_to_go/60),1) . " minutes).");
        sleep(0.05);
    }