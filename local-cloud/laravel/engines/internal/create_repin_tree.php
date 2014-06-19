<?php
/**
 * For a given origin pin, we go through the status_repin_tree
 * table and try to build out the repin tree from the given data.
 *
 *
 * @author yesh
 */

ini_set('memory_limit', '500M');

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use Pinleague\CLI;

Log::setLog(__FILE__);

$number_of_boards = 40;
$origin_pin = $argv[1];


try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {

        throw new EngineException('Engine is running');

    } else {
        $engine->start();
        CLI::write(Log::info('Engine started'));

        $pins_from_resp = array();

        $DBH = DatabaseInstance::DBO();
        CLI::write('Connected to Database');

        $STH = $DBH->prepare("SELECT *
                              FROM status_repin_tree
                              WHERE origin_pin = :origin_pin");
        $STH->execute(array(":origin_pin" => $origin_pin));

        $pins_from_db = $STH->fetchAll();

        $total_pins_to_traverse = count($pins_from_db);

        foreach($pins_from_db as $pin){
            if (isset($pin_hash[$pin->parent_pin])){
                $pin_hash[$pin->parent_pin][] = $pin;
            } else {
                $pin_hash[$pin->parent_pin] = array();
                $pin_hash[$pin->parent_pin][] = $pin;
            }
        }

        $pins_traversed                            = 0;
        $level                                     = 0;
        $parent_pins[$level]                       = array($origin_pin);
        $pin_hash[$origin_pin]['cumulative_count'] = count($pin_hash[$pin->origin_pin]);

        $count_pins_traversed  = 2;

        while ($total_pins_to_traverse > $count_pins_traversed) {
            foreach($parent_pins[$level] as $parent_pin){
                $level ++;

                $parent_repin_count  = count($pin_hash[$parent_pin]);
                $parent_pins[$level] = array();

                foreach($pin_hash[$parent_pin] as $pin){
                    $cumulative_count = $pin_hash[$parent_pin]['cumulative_count']
                                            + count($pin_hash[$pin->pin_id]);

                    echo 'level: ' . $level . ' | ' . ' Parent Pin: ' . $pin->parent_pin .
                           ' | Current Pin:' . $pin->pin_id . ' | Cumulative: ' . $cumulative_count . PHP_EOL;

                    $pin_hash[$pin->pin_id]['cumulative_count'] = $cumulative_count;
                    $count_pins_traversed += 1;

                    if(!empty($pin->pin_id)){
                        var_dump($pin->pin_id);
                        array_push($parent_pins[$level], $pin->pin_id);
                    }
                }
            }
        }

        $engine->complete();
        CLI::write(Log::info('Complete'));

        CLI::write(Log::runtime(). 'total runtime');
        CLI::write(Log::memory().' peak memory usage');

    }

} catch (EngineException $e) {

    CLI::alert($e->getMessage());
    CLI::stop();

} catch (PDOException $e) {

    CLI::alert($e->getMessage());
    $engine->fail();
    Log::error($e);
    CLI::stop();

} catch (Exception $e) {

    CLI::alert($e->getMessage());
    $engine->complete();
    Log::error($e);
    CLI::stop();
}

