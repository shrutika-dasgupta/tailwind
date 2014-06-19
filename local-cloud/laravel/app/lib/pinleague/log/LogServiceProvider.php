<?php namespace Pinleague\Log;

/**
 * Class LogServiceProvider
 *
 * @package Pinleague\Log
 * @author  Will
 */
class LogServiceProvider extends \Illuminate\Log\LogServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $logger = new Writer(
            new Logger($this->app['env']), $this->app['events']
        );

        $this->app->instance('log', $logger);

        // If the setup Closure has been bound in the container, we will resolve it
        // and pass in the logger instance. This allows this to defer all of the
        // logger class setup until the last possible second, improving speed.
        if (isset($this->app['log.setup']))
        {
            call_user_func($this->app['log.setup'], $logger);
        }
    }
}
