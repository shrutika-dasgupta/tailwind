<?php
/**Script to find user profiles not yet in our
 * database
 *
 * @author Yesh
 */

ini_set('memory_limit', '5000M');

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use Pinleague\PinterestException;
use Pinleague\CLI;

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

        $engine->start();
        $start = microtime(true);
        CLI::write('Engine started');

        $Pinterest              = Pinterest::getInstance();
        $Pinterest->batch_calls = true;
        $DBH                    = DatabaseInstance::DBO();

        CLI::write('Connected to Database');

        // Keep a check out for the API RATE
        //  would exit if the limit crosses 70000

        $api_rate = engine::current_call_rate();

        if ($api_rate > 70000) {
            $engine->complete();
            sleep(300);
            exit;
        }

        CLI::write('Getting Profile Ids');

        $user_ids = $DBH->query("SELECT user_id
                            FROM status_pull_profiles_queue
                            WHERE flag = 0
                            LIMIT 100")
                    ->fetchAll();

        $profiles      = new Profiles();
        $graphprofiles = new GraphProfiles();

        foreach ($user_ids as $user_id) {
            try {
                $Pinterest->getProfileFollowers($user_id->user_id);
            }
            catch (PinterestException $e) {
                CLI::alert($e);
            }
            $STH = $DBH->query("UPDATE status_pull_profiles_queue
                            SET flag = 1
                            WHERE user_id = $user_id->user_id
                            ");


        }
        CLI::write('Sending batch requests');

        $data = $Pinterest->sendBatchRequests();

        foreach ($data as $curl_key => $curl_result) {
            if (isset($curl_result->data)) {
                foreach ($curl_result->data as $profileData) {
                    $source       = $user_ids[$curl_key]->user_id;
                    $profile      = new Profile();
                    $graphprofile = new GraphProfile($source);
                    try{
                    $profile->loadAPIData($profileData);
                    $profile->timestamp = time();
                    } catch (ProfileException $e){
                        CLI::alert($e);
                        $engine->complete();
                    }
                    try{
                    $graphprofile->update($profileData);
                    $graphprofiles->add($graphprofile);
                    } catch (GraphProfileException $e){
                        CLI::alert($e);
                        $engine->complete();
                    }
                    $profiles->add($profile);
                }
            }
        }

        CLI::write('Sending data to Profiles table');
        $profiles->insertUpdateDB($ignore = array('gender'));

        CLI::write('Sending data to status table');
        $graphprofiles->insertUpdateDB($ignore = array('flag'));


        CLI::write('Completed');
        $engine->complete();
    CLI::write(Log::runtime(). 'total runtime');
    CLI::write(Log::memory().' peak memory usage');


}
catch (EngineException $e) {

    CLI::alert($e->getMessage());
    CLI::stop();
}
catch (Exception $e) {

    CLI::alert($e->getMessage());
    $engine->complete();
    CLI::stop();
}

