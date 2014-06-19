<?php

    /**
     * This script will guess the gender of the profile based on the first name
     *
     * @author  Will
     */

    /*
     * Config
     */
    $numberOfProfilesToEvaluate = 1000;
    //define('APPLICATION_ENV', 'development');

    use Gender\Gender;
    use Pinleague\CLI;

    include '../../bootstrap/bootstrap.php';

    Log::setLog(__FILE__);

    try {

        CLI::h1('Starting program');

        $engine = new Engine('Gender');

        $DBH = DatabaseInstance::DBO();

        CLI::write('Connected to database');

        CLI::write('Finding profiles to evaluate');
        $STH = $DBH->query(
            'select *
            from data_profiles_new
            where gender is NULL
            limit ' . $numberOfProfilesToEvaluate
        );

        if($STH->rowCount() ==0) {
            throw new Exception('No profiles left');
        }

        CLI::write('Updating profiles gender to PR for processing');
        $profiles = new Profiles();
        foreach ($STH->fetchAll() as $DBprofile) {
            $profile         = Profile::CreateFromDBData($DBprofile);
            $profile->gender = 'PR';

            $profiles->add($profile);
        }

        $profiles->saveModelsToDB();

        CLI::h2('Determining gender of ' . $STH->rowCount() . ' names');
        foreach ($profiles as $key => $profile) {
            $gender = new Gender;

            $name   = $profile->first_name;
            $result = $gender->get($name);

            switch ($result) {
                case Gender::IS_FEMALE:
                    $profile->gender = 'FE'; //female
                    break;

                case Gender::IS_MOSTLY_FEMALE:
                    $profile->gender = 'MF'; //mostly female
                    break;

                case Gender::IS_MALE:
                    $profile->gender = 'MA'; //male
                    break;

                case Gender::IS_MOSTLY_MALE:
                    $profile->gender = 'MM'; //mostly male
                    break;

                case Gender::IS_UNISEX_NAME:
                    $profile->gender = 'UN'; //unisex
                    break;

                case Gender::IS_A_COUPLE:
                    $profile->gender = 'BT'; //both
                    break;

                case Gender::NAME_NOT_FOUND:
                    $profile->gender = 'NF';
                    break;

                case Gender::ERROR_IN_NAME:
                default:

                    $profile->gender = 'ER';
                    break;
            }

            CLI::write("$profile->username is a $profile->gender");

        }

        CLI::h2('Updating profiles in the DB');
        $profiles->saveModelsToDB();
        CLI::write('Success');

        CLI::h2('Calculations finished');

        $meter = $engine->computeSpeed($numberOfProfilesToEvaluate);
        CLI::write("Evaluated $meter per second");

        CLI::seconds();

    }

    catch (EngineException $e) {

        CLI::alert($e->getMessage());
        CLI::stop();

    }
    catch (Exception $e) {

        CLI::alert($e->getMessage());
        Log::error($e);
        $engine->fail();
        CLI::stop();

    }
