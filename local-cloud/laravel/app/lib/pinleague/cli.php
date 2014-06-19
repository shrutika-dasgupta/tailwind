<?php

/**
 * Wrapper class for CLI functions
 *
 * @author  Will
 */

namespace Pinleague;

use \Colors\Color;

/**
 * Class CLI
 *
 * @package Pinleague
 */
class CLI
{

    /**
     * Prints the time since the script started
     *
     * @author  Will
     */
    public static function seconds($msg = '')
    {
        $c = new Color();

        $now  = microtime(true);
        $time = $now - START_TIME;

        echo $c("$time seconds have passed $msg")->yellow() . PHP_EOL;
    }

    /**
     * @param bool $time
     */
    public static function date($time = false) {
        if(!$time) {
            $time = time();
        }

        self::write(date('g:ia D M d Y',$time));
    }

    /**
     * Alert a message to the CLI
     *
     * @param $msg
     *
     * @author  Will
     */
    public static function alert($msg)
    {

        $c = new Color();
        echo $c('! => ' . $msg)->red()->bold() . PHP_EOL;

    }

    /**
     * Happy Alert
     *
     * @author  Will
     */
    public static function yay($msg)
    {
        $c = new Color();
        echo $c(':D => ' . $msg)->green()->bold() . PHP_EOL;
    }

    /**
     * Header ouput, show some style
     *
     * @param $msg
     *
     * @author  Will
     */
    public static function h1($msg)
    {
        $bars = self::createFrame($msg, '-');

        $c = new Color();
        echo PHP_EOL;
        echo $c($bars)->cyan() . PHP_EOL;
        echo $c($msg)->blue()->bold() . PHP_EOL;
        echo $c($bars)->cyan() . PHP_EOL;

    }

    /**
     * Header ouput, show some style
     *
     * @param $msg
     *
     * @author  Will
     */
    public static function h2($msg)
    {

        $bars = self::createFrame($msg, '-');

        $c = new Color();
        echo PHP_EOL;
        echo $c($msg)->cyan()->bold() . PHP_EOL;
        echo $c($bars)->cyan() . PHP_EOL;

    }

    /**
     * Header ouput, show some style
     *
     * @param $msg
     *
     * @author  Will
     */
    public static function h3($msg)
    {

        $c = new Color();
        echo PHP_EOL;
        echo '### ' . $c($msg)->cyan()->bold() . ' ###' . PHP_EOL;

    }

    /**
     * Normal Write
     *
     * @param $msg
     *
     * @author  Will
     */
    public static function write($msg)
    {
        $c = new Color();
        echo $c($msg) . PHP_EOL;

    }

    /**
     * @param $msg
     */
    public static function warning($msg)
    {
        $c = new Color();
        echo $c($msg)->yellow() . PHP_EOL;
    }

    /**
     * @param $msg
     */
    public static function notice($msg)
    {
        $c = new Color();
        echo $c($msg)->blue() . PHP_EOL;
    }

    /**
     * @param $msg
     */
    public static function debug($msg)
    {
        $c = new Color();
        echo $c($msg) . PHP_EOL;
    }

    /**
     * Graceful exits
     *
     * @author Will
     */
    public static function stop($msg = 'Exiting program -- later brah')
    {
        $c = new Color();
        echo $c(':|  => ' . $msg)->magenta->italic() . PHP_EOL;
        exit;

    }

    /**
     * Happy endings (AMIRITE)
     *
     * @author  Will
     */
    public static function end($msg = 'Woo! We finished everything successfully')
    {
        $msg = ':) => ' . $msg;

        $V  = self::createFrame($msg, 'V');
        $up = self::createFrame($msg, '^');

        $c = new Color();
        echo PHP_EOL;
        echo $c($V)->green()->bold() . PHP_EOL;
        echo $c($msg)->green()->bold() . PHP_EOL;
        echo $c($up)->green()->bold() . PHP_EOL;
        echo PHP_EOL;
        exit;
    }

    /**
     * @author  Will
     *
     * @param $seconds
     */
    public static function sleep($seconds)
    {

        $total = $seconds;

        for ($xx = 1; $xx <= $seconds; $seconds--) {
            print '! => Sleep: ' . $seconds . " seconds \r";
            sleep(1);
        }
        print '! => Slept: ' . $total . " seconds";
        echo PHP_EOL;
    }

    /**
     * @param $string
     * @param $character
     *
     * @return string
     */
    protected static function createFrame($string, $character)
    {

        $characters = strlen($string);
        $frame      = str_repeat($character, $characters);

        return $frame;

    }

}