<?php

/**
 * DataEngines Bootstrap
 *
 * @author  Will
 */

/*
   * Define the start time of the application
   */
$time = microtime(true);
define('START_TIME', $time);

/*
 * Define the root path to make includes easier
 */
$path = realpath(dirname(__FILE__) . '/../');
define('ROOT_PATH', $path . '/');


include ROOT_PATH . 'bootstrap/autoload.php';
include ROOT_PATH . 'bootstrap/debug.php';
include ROOT_PATH . 'bootstrap/helpers.php';

$app = require_once ROOT_PATH . 'bootstrap/start.php';
$app->boot();

define('APPLICATION_ENV', App::environment());

include ROOT_PATH . 'bootstrap/databaseinstance.php';

/*
 * Set CST timezone
 */
date_default_timezone_set('America/Chicago');


