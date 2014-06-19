<?php
/**
 * @author  Will
 */

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\CLI;

try {

    $now = date('g:ia');
    CLI::h1('Starting tests (' . $now . ')');

    $DBH = DatabaseInstance::DBO();
    CLI::write('Connected to database');


    $users = $DBH->query('select * from users');

    foreach ($users->fetchAll() as $user) {

        $validator = Validator::make(
                              array('email' => $user->email),
                              array('email' => 'email')
        );

        if ($validator->fails()) {
            CLI::alert($user->cust_id.' - '.$user->email.' '.$validator->messages()->first());
        }

    }


}
catch (Exception $e) {
    var_dump($e);
    die();

}