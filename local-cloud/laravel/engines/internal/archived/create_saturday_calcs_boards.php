<?php
ini_set("set_time_limit", "1000000");
/**
 * Alerts when things happen
 * sends via email
 *
 * @author  Will
 */

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use Pinleague\CLI;

try {

    $sat_date = '1383368400';
    $sun_date = '1383454800';
    $mon_date = '1383544800';

    $now = date('g:ia');
    CLI::h1('Starting tests (' . $now . ')');

    $DBH = DatabaseInstance::DBO();
    CLI::write('Connected to database');

    CLI::write('Finding saturday');
    $STH = $DBH->query("
            select * from calcs_board_history
            where date = $sat_date
            limit 200000, 250000
    ");

    CLI::write('Finding monday');
    $STM = $DBH->query("
        select * from calcs_board_history
        where date = $mon_date
        limit 200000, 250000
    ");

CLI::write('Adding to calcs');
    $sat_calcs = array();
    foreach ($STH->fetchAll() as $sat_calc) {
        $sat_calcs[$sat_calc->board_id] = $sat_calc;
    }

    unset($STH);

    $sunday_calcs = new CalcBoardHistories();
    foreach ($STM->fetchAll() as $mon_calcData) {

        if(!isset($sat_calcs[$mon_calcData->board_id])) {
            continue;
        }

        $weekend_calcs = new CalcBoardHistories();

        $mon_calc = new CalcBoardHistory();
        $mon_calc->loadDBData($mon_calcData);
        $weekend_calcs->add($mon_calc);

        $sat_calc = new CalcBoardHistory();
        $sat_calc->loadDBData($sat_calcs[$mon_calc->board_id]);
        $weekend_calcs->add($sat_calc);

        $sunday_calc           = new CalcBoardHistory();
        $sunday_calc->board_id = $mon_calc->board_id;
        $sunday_calc->date     = $sun_date;
        $sunday_calc->user_id  = $mon_calc->user_id;

        CLI::write('Calculating ' . $mon_calc->board_id);

        $sunday_calc->followers                = $weekend_calcs->average('followers');
        $sunday_calc->pins                     = $weekend_calcs->average('pins');
        $sunday_calc->repins                   = $weekend_calcs->average('repins');
        $sunday_calc->likes                    = $weekend_calcs->average('likes');
        $sunday_calc->comments                 = $weekend_calcs->average('comments');
        $sunday_calc->pins_atleast_one_comment = $weekend_calcs->average('pins_atleast_one_comment');
        $sunday_calc->pins_atleast_one_engage  = $weekend_calcs->average('pins_atleast_one_engage');
        $sunday_calc->pins_atleast_one_like    = $weekend_calcs->average('pins_atleast_one_like');
        $sunday_calc->pins_atleast_one_repin   = $weekend_calcs->average('pins_atleast_one_repin');
        $sunday_calc->timestamp                = time();

        $sunday_calcs->add($sunday_calc);
        unset($sat_calcs[$mon_calc->board_id]);

    }

    CLI::end($sunday_calcs->count());
    $sunday_calcs->insertIgnoreDB();
}

catch (Exception $e) {
    CLI::alert($e->getMessage());
    CLI::stop();
}
