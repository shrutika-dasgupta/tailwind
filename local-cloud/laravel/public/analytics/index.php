<?php
/**
 *
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylorotwell@gmail.com>
 */

/*
|--------------------------------------------------------------------------
| Include Tailwind bootstrap files
|--------------------------------------------------------------------------
|
| There are some files we need that won't be included in the autoloaders
| (that perhaps should be) and this was the easiest way to do it. One day
| maybe we'll clean all this up and make it all delightful (read below for
| an explanation of that) but... this just needs to get done.
|
*/
require __DIR__ . '/../../bootstrap/config_set.php';
require __DIR__ . '/../../bootstrap/databaseinstance.php';
require __DIR__ . '/../../bootstrap/helpers.php';
require __DIR__ . '/../../bootstrap/debug.php';
require __DIR__ . '/../../bootstrap/general_exceptions.php';

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader
| for our application. We just need to utilize it! We'll require it
| into the script here so that we do not have to worry about the
| loading of any our classes "manually". Feels great to relax.
|
*/

require __DIR__.'/../../bootstrap/autoload.php';

/*
|--------------------------------------------------------------------------
| Turn On The Lights
|--------------------------------------------------------------------------
|
| We need to illuminate PHP development, so let's turn on the lights.
| This bootstraps the framework and gets it ready for use, then it
| will load up this application so that we can run it and send
| the responses back to the browser and delight these users.
|
*/

$app = require_once __DIR__.'/../../bootstrap/start.php';

session_start();
/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can simply call the run method,
| which will execute the request and send the response back to
| the client's browser allowing them to enjoy the creative
| and wonderful application we have whipped up for them.
|
*/

$app->run();
