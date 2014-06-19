<?php namespace Pinleague\Log;

use Monolog,
    Monolog\Handler\StreamHandler,
    Monolog\Formatter\LineFormatter,
    Pinleague\CLI;

/**
 * Class Log
 *
 * @package Pinleague
 *
 * @author  Will
 * @author  Yesh
 */
class Log extends \Illuminate\Support\Facades\Log
{

    /**
     * The filename of the log we'd like to write to
     *
     * @var $filename string
     */
    static $filename = 'log';
    static $instance_id = false;
    static $print_in_cli = false;

    /**
     * This will set the log to the name of the file so it is separate from the rest
     * of the scripts
     *
     * @author   Yesh
     * @author   Will
     *
     * @param        $filename
     * @param string $channel
     *
     * @param bool   $specific_name
     *
     * @example  Log::setLog(__FILE__);
     */
    public static function setLog($filename,
                                  $channel = 'Tailwind',
                                  $specific_name = false)
    {
        if ($channel == 'CLI') {
            self::$print_in_cli = true;
        }

        /**
         * Generate a unique id for each logged instance so that we can search
         * a particular run of a script. If there was an error, this makes it easier to see the
         * full picture of *just* that script
         */
        self::$instance_id = self::generateUniqueId();

        $log = parent::getMonolog($channel);

        if($specific_name === false){
            $filename = basename($filename, ".php");
        } else {
            $filename = $specific_name;
        }

        $filename = str_replace('-','_',$filename);

        self::$filename = $filename;

        $stream =
            new StreamHandler(
                storage_path() . '/logs/' . $filename . ".beaverlog", 0, 'debug'
            );

        $stream->setFormatter(
               new LineFormatter(
                   "[%datetime%] %process_id%.%user%:$filename.%log_id%.%level_name%:%runtime%:%memory%: %message% %context% \n"
               )
        );

        $log->popHandler();
        $log->pushHandler($stream);

    }

    /**
     * Adds a log record at the DEBUG level.
     *
     * @param string  $message The log message
     * @param array   $context The log context
     * @return Boolean Whether the record has been processed
     * @static
     */
    public static function debug($message, $context = array()){

        $message = self::parseMessage($message,$context);

        if (self::$print_in_cli) {
            CLI::debug($message);
        }

        if(parent::debug($message,$context)) {
            return $message;
        }

        return false;
    }

    /**
     * Adds a log record at the INFO level.
     *
     * @param string  $message The log message
     * @param array   $context The log context
     * @return Boolean Whether the record has been processed
     * @static
     */
    public static function info($message, $context = array()){

        $message = self::parseMessage($message,$context);

        if (self::$print_in_cli) {
            CLI::write($message);
        }

        if(parent::info($message,$context)) {
            return $message;
        }

        return false;
    }

    /**
     * Adds a log record at the NOTICE level.
     *
     * @param string  $message The log message
     * @param array   $context The log context
     * @return Boolean Whether the record has been processed
     * @static
     */
    public static function notice($message, $context = array()){

        $message = self::parseMessage($message,$context);

        if (self::$print_in_cli) {
            CLI::notice($message);
        }

        if(parent::notice($message,$context)) {
            return $message;
        }

        return false;
    }

    /**
     * Adds a log record at the WARNING level.
     *
     * @param string  $message The log message
     * @param array   $context The log context
     * @return Boolean Whether the record has been processed
     * @static
     */
    public static function warning($message, $context = array()){

        $message = self::parseMessage($message,$context);

        if (self::$print_in_cli) {
            CLI::write($message);
        }

        if(parent::warning($message,$context)) {
            return $message;
        }

        return false;
    }

    /**
     * Adds a log record at the ERROR level.
     *
     * @param string  $message The log message
     * @param array   $context The log context
     * @return Boolean Whether the record has been processed
     * @static
     */
    public static function error($message, $context = array()){

        $message = self::parseMessage($message,$context);

        if (self::$print_in_cli) {
            CLI::alert($message);
        }

        if(parent::error($message,$context)) {
            return $message;
        }

        return false;
    }

    /**
     * Adds a log record at the CRITICAL level.
     *
     * @param string  $message The log message
     * @param array   $context The log context
     * @return Boolean Whether the record has been processed
     * @static
     */
    public static function critical($message, $context = array()){

        $message = self::parseMessage($message,$context);

        if (self::$print_in_cli) {
            CLI::alert($message);
        }

        if(parent::critical($message,$context)) {
            return $message;
        }

        return false;
    }

    /**
     * Adds a log record at the ALERT level.
     *
     * @param string  $message The log message
     * @param array   $context The log context
     * @return Boolean Whether the record has been processed
     * @static
     */
    public static function alert($message, $context = array()){

        $message = self::parseMessage($message,$context);

        if (self::$print_in_cli) {
            CLI::alert($message);
        }

        if(parent::alert($message,$context,$context,$context)) {
            return $message;
        }

        return false;
    }

    /**
     * Adds a log record at the EMERGENCY level.
     *
     * @param string  $message The log message
     * @param array   $context The log context
     * @return Boolean Whether the record has been processed
     * @static
     */
    public static function emergency($message, $context = array()){

        $message = self::parseMessage($message,$context);

        if (self::$print_in_cli) {
            CLI::alert($message);
        }

        if(parent::emergency($message,$context)) {
            return $message;
        }

        return false;
    }

    /**
     * Logs
     *
     * @author   Will
     */
    public static function runtime($context = array())
    {
        self::addContext($context);
        $context['_runtime'] = self::getCurrentRuntime();

        if (self::$print_in_cli) {
            CLI::write('The runtime is: '.number_format($context['_runtime'],2));
        }

        $log = self::getMonolog();

        if ($log->addRecord(Logger::RUNTIME, ':)', $context)) {
            return $context['_runtime'];
        }

        return false;
    }

    /**
     * Logs peak memory. Intended to go at the end of scripts
     * For current memory, use debug (its included)
     *
     * @author   Will
     */
    public static function memory($context = array())
    {
        self::addContext($context);
        $context['_memory'] = memory_get_peak_usage()/1056784;;

        if (self::$print_in_cli) {
            CLI::write('The peak memory is: '.number_format($context['_memory'],2));
        }

        $log = self::getMonolog();

        if ($log->addRecord(Logger::MEMORY, ':)', $context)) {
            return $context['_memory'];
        }

        return false;
    }

    /**
     *
     * @author  Will
     *
     * @param $message
     * @param $context
     *
     * @return string
     */
    protected static function parseMessage($message, &$context)
    {
        if (is_object($context)) {
            $context = (array)$context;
        }
        self::addContext($context);

        if (is_string($message)) {
            return $message;
        }

        if ($message instanceof \Exception) {

            $context['class']       = get_class($message);
            $context['file']        = $message->getFile();
            $context['line']        = $message->getLine();
            $context['stack_trace'] = $message->getTraceAsString();
            $context['code']        = $message->getCode();

            return $message->getMessage();
        }

        return 'The message could not be parsed';

    }

    /**
     * @author  Will
     *
     * @param $context
     *
     * @return void
     */
    protected static function addContext(&$context)
    {
        /*
         * This is added data that is sent with each log to help us figure out whats going
         * on
         */
        $context['_runtime']       = self::getCurrentRuntime();
        $context['_memory']        = memory_get_usage() / 1056784;
        $context['_log_id']        = self::getInstanceId();
        $context['_process_id']    = getmypid();
        $context['_last_modified'] = date("Y-d-m H:i:s ", getlastmod());

        $process_user     = posix_getpwuid(posix_geteuid());
        $context['_user'] = $process_user['name'];
    }

    /**
     * @return mixed
     * @author  Will
     */
    protected static function getCurrentRuntime()
    {
        if (defined('LARAVEL_START')) {
            return $runtime = microtime(true) - LARAVEL_START;
        }

        return 0;
    }

    /**
     * @author  Will
     * @return string
     */
    protected static function generateUniqueId() {
        return uniqid();
    }

    /**
     * @author  Will
     * @return string
     */
    protected static function getInstanceId() {
        if(!self::$instance_id) {
            self::$instance_id = self::generateUniqueId();
        }

        return self::$instance_id;
    }


}
