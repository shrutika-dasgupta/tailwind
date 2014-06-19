<?php namespace Pinleague\Log;

use Monolog\Handler\StreamHandler;
use Monolog\Logger as Monologger;

/**
 * Tailwind Log extension
 *
 * @author Will
 */
class Logger extends Monologger
{

    /**
     * Bastadization of the logger to record numerical values
     * yayyy!
     */
    const RUNTIME = 50;
    const MEMORY = 75;

    protected static $levels = array(
        50  => 'RUNTIME',
        75  => 'MEMORY',
        100 => 'DEBUG',
        200 => 'INFO',
        250 => 'NOTICE',
        300 => 'WARNING',
        400 => 'ERROR',
        500 => 'CRITICAL',
        550 => 'ALERT',
        600 => 'EMERGENCY',
    );

    /**
     * Adds a log record.
     *
     * @param  integer $level   The logging level
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addRecord($level, $message, array $context = array())
    {
        if (!$this->handlers) {
            $this->pushHandler(new StreamHandler('php://stderr', static::DEBUG));
        }

        if (!static::$timezone) {
            static::$timezone = new \DateTimeZone(date_default_timezone_get() ?: 'UTC');
        }

        $runtime       = array_get($context, '_runtime', 0);
        $memory_usage  = array_get($context, '_memory', 0);
        $log_id        = array_get($context, '_log_id', 0);
        $process_id    = array_get($context, '_process_id', 0);
        $last_modified = array_get($context, '_last_modified', date("Y-d-m H:i:s ", strtotime('January 1,1969')));
        $user          = array_get($context, '_user', 'unknown');

        unset($context['_runtime']);
        unset($context['_memory']);
        unset($context['_log_id']);
        unset($context['_process_id']);
        unset($context['_last_modified']);
        unset($context['_user']);

        $record = array(
            'message'    => (string)$message,
            'context'    => $context,
            'level'      => $level,
            'level_name' => static::getLevelName($level),
            'channel'    => $this->name,
            'datetime'   => \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true)), static::$timezone)->setTimezone(static::$timezone),
            'extra'      => array(),
            'runtime'    => $runtime,
            'memory'     => $memory_usage,
            'log_id'     => $log_id,
            'process_id' => $process_id,
            'last_mod'   => $last_modified,
            'user'       => $user
        );

        // check if any handler will handle this message
        $handlerKey = null;
        foreach ($this->handlers as $key => $handler) {
            if ($handler->isHandling($record)) {
                $handlerKey = $key;
                break;
            }
        }
        // none found
        if (null === $handlerKey) {
            return false;
        }

        // found at least one, process message and dispatch it
        foreach ($this->processors as $processor) {
            $record = call_user_func($processor, $record);
        }
        while (isset($this->handlers[$handlerKey]) &&
            false === $this->handlers[$handlerKey]->handle($record)) {
            $handlerKey++;
        }

        return true;
    }
}
