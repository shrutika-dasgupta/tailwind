<?php
chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use Pinleague\CLI;

$pin_id_limit = 10;

Log::setLog(__FILE__);

try {
    CLI::h1('Starting Program');
    $engine  = new Engine(__FILE__);
    $started = microtime(true);

    if ($engine->running()) {

        throw new EngineException('Engine is running');

    } else {

        $pinterest = new Pinterest();
        $pin_ids   = array();

        $engine->start();
        CLI::write(Log::info('Engine started'));

        $DBH = DatabaseInstance::DBO();
        CLI::write(Log::debug('Connected to Database'));

        CLI::write(Log::info('Grabbing Pin_ids'));
        $pins_info = $DBH->query("
                                 SELECT pin_id
                                 FROM data_pins_new
                                 WHERE last_pulled = 0
                                 and pin_id > 10000000000
                                 LIMIT $pin_id_limit")
                     ->fetchAll();

        if (empty($pins_info)){
            CLI::alert(Log::notice('No more pins to pull | Sleep 20'));
            sleep(20);
            $engine->complete();
            exit;
        }

        foreach ($pins_info as $pins) {
            $pin_ids[] = $pins->pin_id;
            $STH       = $DBH->prepare("UPDATE data_pins_new
                                SET last_pulled = :current_time
                                WHERE pin_id = :pin_id");
            $STH->execute(array(":current_time" => time(), ":pin_id" => $pins->pin_id));
        }

        CLI::write(Log::info('Calling Public method', $pins_info));

        $resp = $pinterest->getPublicPinInformation($pin_ids);

        CLI::write(Log::info('Inserting data into the new table', $resp));
        foreach ($resp as $key => $value) {
            if (!empty($value)) {

                /*
                 * Check to make sure the board slug is not empty before inserting data into the
                 * map_traffic_pins_boards table.
                 */
                if(!empty($value->board->url)){
                    $board_slug = trim($value->board->url, '/');
                    $STH = $DBH->prepare("
                                         INSERT IGNORE INTO map_traffic_pins_boards (pin_id,
                                                                     board_slug,
                                                                     timestamp)
                                         VALUES (:pin_id,
                                                 :board_id,
                                                 :timestamp)");

                    $STH->execute(array(':pin_id'    => $key,
                                        ':board_id'  => $board_slug,
                                        ':timestamp' => time()));
                }
            }
        }
    }


    $engine->complete();
    CLI::yay(Log::info('Engine completed'));

    CLI::write(Log::runtime(). 'total runtime');
    CLI::write(Log::memory().' peak memory usage');
}
catch (EngineException $e) {

    CLI::alert($e->getMessage());
    CLI::stop();

} catch (PDOException $e) {

    CLI::alert($e->getMessage());
    $engine->fail();
    Log::error($e);
    CLI::stop();

} catch (Exception $e) {

    CLI::alert($e->getMessage());
    $engine->fail();
    Log::error($e);
    CLI::stop();
}
