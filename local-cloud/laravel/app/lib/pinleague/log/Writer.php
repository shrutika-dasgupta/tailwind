<?php namespace Pinleague\Log;

use Illuminate\Events\Dispatcher;
use Pinleague\Log\Logger as MonologLogger;

/**
 * Class Writer
 *
 * @package Pinleague\Log
 * @author  Will
 */
class Writer extends \Illuminate\Log\Writer {

    /**
     * All of the error levels.
     *
     * @var array
     */
    protected $levels = array(
        'runtime',
        'memory',
        'debug',
        'info',
        'notice',
        'warning',
        'error',
        'critical',
        'alert',
        'emergency',
    );

    /**
     * Create a new log writer instance.
     *
     * @param \Monolog\Logger|\Pinleague\Log\Logger $monolog
     * @param  \Illuminate\Events\Dispatcher        $dispatcher
     *
     * @return \Pinleague\Log\Writer
     */
    public function __construct(MonologLogger $monolog, Dispatcher $dispatcher = null)
    {
        $this->monolog = $monolog;

        if (isset($dispatcher))
        {
            $this->dispatcher = $dispatcher;
        }
    }

    /**
     * Parse the string level into a Monolog constant.
     *
     * @param  string  $level
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    protected function parseLevel($level)
    {
        switch ($level) {
            case 'debug':
                return MonologLogger::DEBUG;

            case 'info':
                return MonologLogger::INFO;

            case 'notice':
                return MonologLogger::NOTICE;

            case 'warning':
                return MonologLogger::WARNING;

            case 'error':
                return MonologLogger::ERROR;

            case 'critical':
                return MonologLogger::CRITICAL;

            case 'alert':
                return MonologLogger::ALERT;

            case 'emergency':
                return MonologLogger::EMERGENCY;

            case 'runtime':
                return MonologLogger::RUNTIME;

            case 'memory':
                return MonoLogLogger::MEMORY;

            default:
                throw new \InvalidArgumentException("Invalid log level.");
        }
    }
}
