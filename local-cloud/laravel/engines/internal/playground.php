<?php
/**
 * Alerts when things happen
 * sends via email
 *
 * @author  Will
 */

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\CLI;
use Guzzle\Http\Client;
use Pinleague\Feed;


try {

    $user = User::find(1748);
    echo json_encode($user);

}
catch (Exception $e) {
    var_dump($e);
    die();

}
