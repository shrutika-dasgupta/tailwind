<?php

/*
|--------------------------------------------------------------------------
| Register The Laravel Class Loader
|--------------------------------------------------------------------------
|
| In addition to using Composer, you may use the Laravel class loader to
| load your controllers and models. This is useful for keeping all of
| your classes in the "global" namespace without Composer updating.
|
*/
ClassLoader::addDirectories(array(

    app_path().'/collections',
	app_path().'/database/seeds',
    app_path().'/models',

));

/*
|--------------------------------------------------------------------------
| Application Error Logger
|--------------------------------------------------------------------------
|
| Here we will configure the error logger setup for the application which
| is built on top of the wonderful Monolog library. By default we will
| build a rotating log file setup which creates a new file each day.
|
*/

$logFile = 'log-'.php_sapi_name().'.beaverlog';

Log::useFiles(storage_path().'/logs/'.$logFile);

/*
 * Hacky hack hacks
 * We do this so the log format is the same everywhere
 */
Log::setLog(false,'Tailwind','log-'.php_sapi_name());

/*
|--------------------------------------------------------------------------
| Application Error Handler
|--------------------------------------------------------------------------
|
| Here you may handle any errors that occur in your application, including
| logging them or displaying custom views for specific errors. You may
| even register several error handlers to handle different types of
| exceptions. If nothing is returned, the default error view is
| shown, which includes a detailed stack trace during debug.
|
*/

App::error(function(Exception $exception, $code)
{
	Log::error($exception);
});

/*
|--------------------------------------------------------------------------
| HTTP Exceptions
|--------------------------------------------------------------------------
*/
App::error(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $exception, $code) {

    $path = parse_url(
        $uri = array_get($_SERVER, 'REQUEST_URI', '/'),
        PHP_URL_PATH
    );

    /*
     * We are getting a lot of junk from pinreach's spammy tactics
     * So we want to filter these out and return a 410 (Gone) response
     * If it came redirected to /pinreach (that means it came from pinreach.com)
     * and is 404'ing we are sending a 410 and a sorry page.
     */

    if (substr($path, 0, 9) === '/pinreach') {

        Log::debug('Pinreach spammy link shown 410 HTTP GONE response', $_SERVER);

        $content = 'Looking for analytics on your Pinterest account? Check out <a href="http://www.tailwindapp.com?ref=410-gone">Tailwind</a>.';

        return Response::make($content, 410);

    }

    Log::warning($exception, $_SERVER);

    $content = View::make(
                   'layouts.http_error',
                   array(
                        'navigation'=>'',
                        'main_content' => View::make('catalog.404', array('url' => $path)),
                        'footer'=>''
                   )
    );

    $response = Response::make($content, 404);

    return $response;

});

App::error(function (PDOException $exception, $code) {
    //handle all alerts and stuff here
    Log::error($exception);

});

App::fatal(function($exception)
{
    Log::critical($exception);
});


App::after(function($request, $response)
{
    if (in_array(Request::getClientIp(),Config::get('blocked.ips'))) {
        Log::debug('Blocked ip address. Sleeping so they get frustrated');
        sleep(31);
    }
});


/*
|--------------------------------------------------------------------------
| Segment.io integration
|--------------------------------------------------------------------------
*/

$segment_io_log_path = storage_path().'/segment.io/';
if(!file_exists($segment_io_log_path)) {
    mkdir($segment_io_log_path);
}

Analytics::init(
         Config::get('segmentio.WRITE_KEY'),
         array(
              "consumer"       => "fork_curl",
              "max_queue_size" => 10000,
              "batch_size"     => 100,
              //"consumer" => "file",
              //"filename" => $segment_io_log_path."/events.log",
              "on_error" => function ($code, $message) {
                      Log::error("Segment.io analytics error: [$code] $message");
                  }
         )
);
/*
|--------------------------------------------------------------------------
| Maintenance Mode Handler
|--------------------------------------------------------------------------
|
| The "down" Artisan command gives you the ability to put an application
| into maintenance mode. Here, you will define what is displayed back
| to the user if maintenace mode is in effect for this application.
|
*/

App::down(function()
{
	return Response::make("Be right back!", 503);
});

/*
|--------------------------------------------------------------------------
| Require The Filters File
|--------------------------------------------------------------------------
|
| Next we will load the filters file for the application. This gives us
| a nice separate location to store our route and application filter
| definitions instead of putting them all in the main routes file.
|
*/
require app_path().'/filters.php';
/*
|--------------------------------------------------------------------------
| Require view composers and namespaces
|--------------------------------------------------------------------------
| I feel obligated to write something here because the docblocks above
| have something written. Honestly for some reason I hate the way the
| comments are written above. They sound kind of douchey.
|
*/
require app_path().'/views/composers.php';
require app_path().'/views/namespaces.php';
