<?php

    chdir(__DIR__);
    use Pinleague\CLI;

    include '../../../../bootstrap/bootstrap.php';

    $DBH = DatabaseInstance::DBO();

    $DBH->query("update status_engines set status = 'Complete' where status = 'Running' ");

    CLI::write('Updated `status_engines` and reset all running engines to complete');
