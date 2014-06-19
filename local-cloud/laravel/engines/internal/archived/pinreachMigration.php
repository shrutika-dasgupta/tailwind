<?php
/**
 * This script fetches the boards that pins were repinned to
 * and stores the data in data_pins_repins
 *
 * @author  Will
 */

/*
 * Config
 */
$numberOfProfilesToRun = 400;

use Pinleague\CLI;
use Pinleague\Pinterest;
use Pinleague\PinterestException;

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';
Config::set('app.debug', true);

try {

    CLI::h1('Starting program');

    $engine = new Engine(__FILE__);

    if ($engine->running()) {

        throw new EngineException('The engine is already running.');

    } else {

        //$engine->start();
        CLI::write('Engine started');

        $DBH = DatabaseInstance::DBO();

        $limit = $numberOfProfilesToRun;

        $STH = $DBH
               ->query("
                    select * from legacy_profiles_pinreach
                    where track_type != 'migrated' && track_type != 'fail' && track_type != 'existed'
                    limit $limit
                    ");


        $numberOfProfiles = $STH->rowCount();

        CLI::write("Found $numberOfProfiles profiles to migrate");
        CLI::seconds();

        $profiles = new Profiles();

        foreach ($STH->fetchAll() as $legacyProfile) {

            if (empty($legacyProfile->username)) {

            } else {

                CLI::h2("Migrating legacy profile '$legacyProfile->username'");

                $profile = new Profile();
                $profile->username = $legacyProfile->username;

                if ($profile->doesNotExistInOurDB()) {

                    CLI::write("$legacyProfile->username is new!");

                    CLI::write("Finding $legacyProfile->username on Pinterest");

                    try {

                        $profile->updateViaAPI();
                        $profiles->add($profile);
                        $status = 'migrated';
                        CLI::write("Added $legacyProfile->username to Profiles collection");

                    }
                    catch (PinterestException $e) {

                        CLI::alert("Hmm, Pinterest says $legacyProfile->username doesn't exist.");
                        $status = 'fail';

                    }


                } else {

                    CLI::alert($legacyProfile->username . ' is already in the DB');
                    $status = 'existed';

                }

            }

            CLI::write('Setting the profile as ' . $status);
            $STH = $DBH->prepare("update legacy_profiles_pinreach set track_type = :status where username = :username");
            $STH->execute(array(':username' => $legacyProfile->username, ':status' => $status));

            CLI::seconds();

        }

        try {
            CLI::h2('Writing legacy models to data_profiles_new');
            $profiles->saveModelsToDB();
            CLI::write('Profiles saved to new DB');
        }
        catch (Exception $e) {

            CLI::alert($e->getMessage());
            CLI::stop('Exiting because there is nothing to do. Womp womp.');

        }

    }

    $engine->complete();
    CLI::write('Engine completed');

    $now  = microtime(true);
    $time = $now - START_TIME;

    $profilesPerSecond = $numberOfProfiles / $time;

    CLI::write("Migrated $profilesPerSecond profiles/second");

    CLI::end();

}
catch (EngineException $e) {

    CLI::alert($e->getMessage());
    CLI::stop();

}
catch (Exception $e) {

    CLI::alert($e->getMessage());
    CLI::write('Default bump out');
    CLI::stop();

}

