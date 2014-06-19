<?php
/**
 * Finding the pin information from repinned boards
 * from map_traffic_pins_boards
 *
 * @author yesh
 */

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use Pinleague\CLI;

Log::setLog(__FILE__);

$number_of_pins = 40;

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {

        throw new EngineException('Engine is running');

    } else {
        $engine->start();
        CLI::write(Log::info('Engine started'));

        $DBH = DatabaseInstance::DBO();
        CLI::write('Connected to Database');

        $repin_tree_pins = new statusRepinsTree();

        CLI::write(Log::debug('Get pins from the queue'));
        $all_pins_to_pull = $repin_tree_pins->fetchPinToPullBoards($number_of_pins);

        if (empty($all_pins_to_pull)){
            CLI::alert(Log::notice('No more pins to pull boards for | Sleep 60'));
            sleep(60);
            $engine->complete();
            exit;
        }

        $pins_to_pull = $all_pins_to_pull['pins_to_pull'];
        $pin_as_source = $all_pins_to_pull['pins_as_source'];

        CLI::write(Log::info('Getting data from API'));

        $pinterest = Pinterest::getInstance();
        $pins_repins_data = array();

        $map_repin_boards = new mapRepinsBoardsPins();
        $pins_repins      = new PinsRepins();

        $more_data_to_get = true;

        $boards_data_from_api = array();
        $bkmark          = array();

        foreach($pins_to_pull as $pin){

            if(empty($pin->source_pin)){
                $wave = 0;
                while($more_data_to_get == true){
                    $wave ++;
                    CLI::write(Log::debug('Doing wave'. $wave . ' for ' . $pin->pin_id));

                    if(empty($bkmark)){
                        $board_data_from_api = $pinterest->getPinRepins($pin->origin_pin);
                    } else {
                        $board_data_from_api = $pinterest->getPinRepins($pin->origin_pin,
                                                        array('bookmark' => $bkmark));
                    }

                    $boards_data_from_api[] = $board_data_from_api;

                    if(isset($board_data_from_api['bookmark'])){
                        $bkmark = $board_data_from_api['bookmark'];
                    } else {
                        $more_data_to_get = false;
                    }
                }
            } else {
                $wave = 0;
                while($more_data_to_get == true){
                    $wave ++;
                    CLI::write(Log::debug('Doing wave '. $wave . ' for ' . $pin->pin_id));

                    if(empty($bkmark)){
                        $board_data_from_api = $pinterest->getPinRepins($pin->source_pin);
                    } else {
                        $board_data_from_api = $pinterest->getPinRepins($pin->source_pin,
                                                        array('bookmark' => $bkmark));
                    }

                    $boards_data_from_api[] = $board_data_from_api;

                    if(isset($board_data_from_api['bookmark'])){
                        $bkmark = $board_data_from_api['bookmark'];
                    } else {
                        $more_data_to_get = false;
                    }
                }
            }
        $more_data_to_get = true;
        $bkmark = array();
        }

        CLI::write(Log::debug('Adding found pins to map_repin_board_pin table'));
        foreach($boards_data_from_api as $boards_from_api){

            foreach($boards_from_api['data'] as $boards_data){

                $map_repin_board = new mapRepinBoardPin();
                $pins_repin       = new PinsRepin($boards_data['id']);

                $map_repin_board->board_id = $boards_data['id'];

                if (empty($pin->origin_pin)){
                    $map_repin_board->origin_pin = $pin->pin_id;
                } else {
                    $map_repin_board->origin_pin = $pin->origin_pin;
                }

                if (empty($pin->origin_pin)){
                    $map_repin_board->parent_pin = $pin->pin_id;
                } else {
                    $map_repin_board->parent_pin = $pin->parent_pin;
                }
                $map_repin_board->flag       = 0;


                $pins_repin->loadAPIData($boards_data);

                $pins_repins->add($pins_repin);
                $map_repin_boards->add($map_repin_board);
            }
        Log::debug('Number of map_repin_boards:' . count($map_repin_boards));
        }

        CLI::write(Log::info('Save map_repin_board_pin collection to DB'));

        try{

            $map_repin_boards->insertUpdateDB($dont_update_these_columns = array('flag'));

        } catch (CollectionException $e){

            CLI::alert(Log::notice('No repins to save to DB'));

        }

        CLI::write('Save pins repins');
        try{

            $pins_repins->insertUpdateDB();

        } catch (CollectionException $e){

            CLI::alert(Log::notice('No pins to save to pinsrepins'));

        }


        // Setting the inital pins source id in order to start tracking
        //  its own repins along side repins from the origin pin
        if(!empty($pin_as_source)){
            foreach($pin_as_source as $pin_id){
                $STH = $DBH->prepare("UPDATE status_repin_tree
                                  SET source_pin = :pin_id
                                  WHERE pin_id = :pin_id");
                $STH->execute(array(":pin_id" => $pin_id));
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
