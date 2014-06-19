<?php

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

    $STH = $DBH->query("
            select * from calcs_domain_history
            where date = $sat_date
    ");

    $STM = $DBH->query("
        select * from calcs_domain_history
        where date = $mon_date
    ");


    $sat_calcs = array();
    foreach ($STH->fetchAll() as $sat_calc) {
        $sat_calcs[$sat_calc->domain] = $sat_calc;
    }

    $sunday_calcs = new  CalcDomainHistories();
    foreach ($STM->fetchAll() as $mon_calcData) {

        if (!isset($sat_calcs[$mon_calcData->domain])) {
            continue;
        }

        $weekend_calcs = new CalcDomainHistories();

        $mon_calc = new CalcDomainHistory();
        $mon_calc->loadDBData($mon_calcData);
        $weekend_calcs->add($mon_calc);

        $sat_calc = new CalcDomainHistory();
        $sat_calc->loadDBData($sat_calcs[$mon_calc->domain]);
        $weekend_calcs->add($sat_calc);

        $sunday_calc         = new CalcDomainHistory();
        $sunday_calc->domain = $mon_calc->domain;
        $sunday_calc->date   = $sun_date;
        CLI::write('Calculating '.$mon_calc->domain);

        //$sunday_calc->domain_mentions       = $weekend_calcs->average('domain_mentions');
        //$sunday_calc->repin_count           = $weekend_calcs->average('repin_count');
        //$sunday_calc->like_count            = $weekend_calcs->average('like_count');
       // $sunday_calc->comment_count         = $weekend_calcs->average('comment_count');
        $sunday_calc->unique_domain_pinners = $weekend_calcs->average('unique_domain_pinners');
       // $sunday_calc->domain_reach          = $weekend_calcs->average('domain_reach');
       // $sunday_calc->domain_impressions    = $weekend_calcs->average('domain_impressions');
        $sunday_calc->timestamp             = time();

        $sunday_calcs->add($sunday_calc);

    }

    CLI::end($sunday_calcs->count());
    $sunday_calcs->insertIgnoreDB();
}

catch (Exception $e) {
    CLI::alert($e->getMessage());
    CLI::stop();
}
