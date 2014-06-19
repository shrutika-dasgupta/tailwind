<?php
/** This script is used for pulling pins to obtain the user_id
 *  and populate the data_pins_new
 *
 * @author : Yesh
 */

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use Pinleague\PinterestException;
use Pinleague\CLI;

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

        $numberOfCallsInBatch = 1000;

        $DBH = DatabaseInstance::DBO();
        CLI::write('Connected to Database');

        CLI::write(Log::info('Grabbing Pin_ids from missing_pin_data'));
        $pins_info = $DBH->query("
                                 SELECT pin_id
                                 FROM missing_pin_data
                                 WHERE completed = 0
                                 LIMIT $numberOfCallsInBatch")
                     ->fetchAll();

        foreach ($pins_info as $pins) {
            $pin_ids_array[] = $pins->pin_id;
        }

        $list_of_pin_ids = array_chunk($pin_ids_array, 10);

        Log::info('Getting public pin_info');
        foreach ($list_of_pin_ids as $pin_ids){
            var_dump($pin_ids);
            sleep(5);
            $resp = $pinterest->getPublicPinInformation($pin_ids);
            var_dump($resp);
            sleep(5);

            CLI::write(Log::info('Inserting data into the new table'));
            foreach ($resp as $key => $value) {
                if ($value != "" and isset($value->pinner)) {
                    $user_id = $value->pinner->id;
                    Log::debug("Inserting data into" . $key);
                    $STH = $DBH->prepare("UPDATE data_pins_new
                                          SET user_id = :user_id,
                                          timestamp = :timestamp
                                          WHERE pin_id = :pin_id");

                    $STH->execute(array(':pin_id'    => $key,
                                        ':user_id'   => $user_id,
                                        ':timestamp' => time()));
                }
            }

            $pin_ids_implode = implode(",", $pin_ids);

            CLI::write(Log::info('Set completed flag'));
            $STH = $DBH->query("UPDATE missing_pin_data
                                SET completed = 1
                                WHERE pin_id in ($pin_ids_implode)");
        }

        $engine->complete();

        CLI::write(Log::runtime(). 'total runtime');
        CLI::write(Log::memory().' peak memory usage');

        CLI::h1(Log::info('Complete'));
    }
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
