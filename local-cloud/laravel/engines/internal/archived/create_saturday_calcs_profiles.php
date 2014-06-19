<?php

chdir(__DIR__);
include '../../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use Pinleague\CLI;

try {

    $sat_date = '1393048800'; //early date
    $sun_date = '1393135200'; //missing date
    $mon_date = '1393221600'; //after date

    $now = date('g:ia');
    CLI::h1('Starting tests (' . $now . ')');

    $DBH = DatabaseInstance::DBO();
    CLI::write('Connected to database');

    $STH = $DBH->query("
            select * from calcs_profile_history
            where date = $sat_date
    ");

    $STM = $DBH->query("
        select * from calcs_profile_history
        where date = $mon_date
    ");


    $sat_calcs = array();
    foreach ($STH->fetchAll() as $sat_calc) {
        $sat_calcs[$sat_calc->user_id] = $sat_calc;
    }

    $sunday_calcs = new  CalcProfileHistories();
    foreach ($STM->fetchAll() as $mon_calcData) {

        if (!isset($sat_calcs[$mon_calcData->user_id])) {
            continue;
        }

        $weekend_calcs = new CalcProfileHistories();

        $mon_calc = new CalcProfileHistory();
        $mon_calc->loadDBData($mon_calcData);
        $weekend_calcs->add($mon_calc);

        $sat_calc = new CalcProfileHistory();
        $sat_calc->loadDBData($sat_calcs[$mon_calc->user_id]);
        $weekend_calcs->add($sat_calc);

        $sunday_calc          = new CalcProfileHistory();
        $sunday_calc->user_id = $mon_calc->user_id;
        $sunday_calc->date    = $sun_date;

        CLI::write('Calculating ' . $mon_calc->user_id);

        $sunday_calc->follower_count           = $weekend_calcs->average('follower_count');
        $sunday_calc->following_count          = $weekend_calcs->average('following_count');
        $sunday_calc->reach                    = $weekend_calcs->average('reach');
        $sunday_calc->board_count              = $weekend_calcs->average('board_count');
        $sunday_calc->pin_count                = $weekend_calcs->average('pin_count');
        $sunday_calc->repin_count              = $weekend_calcs->average('repin_count');
        $sunday_calc->like_count               = $weekend_calcs->average('like_count');
        $sunday_calc->comment_count            = $weekend_calcs->average('comment_count');
        $sunday_calc->pins_atleast_one_comment = $weekend_calcs->average('pins_atleast_one_comment');
        $sunday_calc->pins_atleast_one_engage  = $weekend_calcs->average('pins_atleast_one_engage');
        $sunday_calc->pins_atleast_one_like    = $weekend_calcs->average('pins_atleast_one_like');
        $sunday_calc->pins_atleast_one_repin   = $weekend_calcs->average('pins_atleast_one_repin');
        $sunday_calc->timestamp                = time();

        //  $sunday_calc->saveToDB();
        $sunday_calcs->add($sunday_calc);

    }

    $sunday_calcs->insertIgnoreDB();
    CLI::end($sunday_calcs->count());
}

catch (Exception $e) {
    CLI::alert($e->getMessage());
    CLI::stop();
}
