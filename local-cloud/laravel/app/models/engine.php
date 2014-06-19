<?php

/**
 * Engines help us track when the last time a script ran
 * In some cases, they also help use avoid collusion when scripts are run
 * via cron.
 *
 * @author  Will
 */
class Engine extends PDODatabaseModel
{
    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */
    /**
     * When the script is running. Sometimes the engine gets stuck at running and needs
     * to be reset. It is not an actual representation of the pid or anything.
     */
    const RUNNING = 'Running';
    /**
     * When we want to momentarily pause the script. Will throw an engine exception when
     * the "running()" method is called so the script will not continue.
     */
    const PAUSED = 'Paused';
    /**
     * When the script goes to completion with no errors.
     */
    const COMPLETE = 'Complete';
    /**
     * Should be used when the script fails. Eventually, we should not rerun scripts that fail
     * and we should alert the error. Right now, the script just restarts.
     */
    const FAILED = 'Failed';
    /**
     * The first time the script is run, it creates an engine. Useful for debugging the first time
     * a script is run
     */
    const CREATED = 'Created';
    /**
     * When the script is running fine, but has nothing to do. Not the same as a failure, but should
     * not count as a completed script.
     */
    const IDLE = 'Idle';

    /*
    |--------------------------------------------------------------------------
    | Schema table
    |--------------------------------------------------------------------------
    */
    /**
     * The columns in the table
     *
     * @var array
     */
    public $columns = array(
        'engine',
        'status',
        'longest_run_time',
        'average_run_time',
        'runs',
        'timestamp'
    ), $table = 'status_engines';

    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    /**
     * So we can check if a script is runnning longer than it ever has before
     * (ie it probably died)
     *
     * @var int
     */
    public $longest_run_time = null;
    /**
     * The average time the script takes
     * Calculated by multiplying the given number (unless 0) by the number of runs
     * and adding current time, then dividing again
     *
     * @var int
     */
    public $average_run_time = 0, $runs = 0;
    /**
     * The name of the engine
     * should be the name of the script that is running
     *
     * @var string
     */
    public $name, $engine;
    /**
     * Is the script currently running?
     *
     * @var string
     */
    public $status = null;
    /**
     * The last time the script was run
     *
     * @var int
     */
    public $timestamp;
    /**
     * @var float microtime
     */
    protected $start_time;

    /*
    |--------------------------------------------------------------------------
    | Construct
    |--------------------------------------------------------------------------
    */

    /**
     * @author   Will
     *
     * @param bool|string $file_path __FILE__ constant should be used
     *
     * @internal param string $engine
     */
    public function __construct($file_path = false)
    {
        parent::__construct();
        if ($file_path) {

            $this->determineEngine($file_path);
            $this->start_time = microtime(true);

            $this->status = $this->status();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Static Methods
    |--------------------------------------------------------------------------
    */

    /**
     * @author      Yesh
     *              Alex
     *
     * @param bool $method
     *
     * @param bool $client_id
     *
     * @return mixed
     * @depreciated should be in Pinterest class
     * @see         Pinterest totalCallsRecorded
     */
    public static function current_call_rate($client_id = false, $method = false)
    {
        $DBH      = DatabaseInstance::DBO();
        if(!$client_id){
            $where_clause = "";

        } else {
            if(!$method) {
                $where_clause = "client_id = $client_id
                             AND ";
            } else {
                $where_clause = "method = '$method'
                             AND client_id = $client_id
                             AND ";
            }

        }

        $api_rate = $DBH->query("SELECT SUM(calls) calls
                                        FROM status_api_calls
                                        WHERE
                                        $where_clause
                                        datetime =
                                        (SELECT datetime
                                        FROM status_api_calls
                                        ORDER BY datetime DESC
                                        LIMIT 1)")
                        ->fetchAll();

        return $api_rate[0]->calls;
    }

    /**
     * @author      Will
     *
     * @param      $hours_to_show
     *
     * @param bool $include_current_hour
     *
     * @param bool $client_id
     *
     * @return array of ints
     *
     * @depreciated should be in Pinterest class
     * @see         Pinterest totalCallsRecorded
     */
    public static function totalPinterestCalls($hours_to_show, $include_current_hour = false, $client_id = false)
    {
        $DBH       = DatabaseInstance::DBO();

        $where_clause = "";
        if ($client_id) {
            $where_clause = "WHERE client_id = $client_id or client_id = 0";
        }

        $api_rates = $DBH->query("SELECT datetime,SUM(calls) calls
                                        FROM status_api_calls
                                        $where_clause
                                        GROUP BY datetime
                                        ORDER BY datetime DESC
                                        LIMIT $hours_to_show")
                         ->fetchAll();

        $calls = array();
        foreach ($api_rates as $rate) {
            $calls[] = $rate->calls;
        }

        if (!$include_current_hour) {
            unset($calls[0]);
        }

        return $calls;
    }

    /**
     * @author  Yesh
     *
     * @param $var
     * @param $engine_name
     */
    public static function var_engine($var, $engine_name)
    {
        $engine = new Engine($engine_name);
        var_dump($var);
        $engine->complete();
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Instance Methods
    |--------------------------------------------------------------------------
    */

    /**
     * @author  Will
     *
     * @return $this
     */
    public function complete()
    {
        $now    = microtime(true);
        $length = $now - $this->start_time;

        /*
         * Calculate longest run_time
         */
        if (is_null($this->longest_run_time)) {

            $this->longest_run_time = $length;

        } else if ($length > $this->longest_run_time) {

            $this->longest_run_time = $length;
        }

        /*
         * Calculate average run time
         */
        if (is_null($this->runs) OR $this->runs == 0) {
            $this->runs             = 1;
            $this->average_run_time = $length;
        } else {

            $total = $this->average_run_time * $this->runs;
            $total += $length;
            $this->runs++;

            $this->average_run_time = $total / $this->runs;
        }

        $this->update(self::COMPLETE);

        return $this;
    }

    /**
     * For getting x/second stuff
     *
     * @author  Will
     *
     * @param $batchAmount
     *
     * @return float
     */
    public function computeSpeed($batchAmount)
    {
        $now  = microtime(true);
        $time = $now - $this->start_time;

        return ($batchAmount / $time);
    }

    /**
     * @author Yesh
     *
     * @return $timestamp
     */
    public function engineTimestamp($engine_name)
    {

        $STH = $this->DBH->prepare("SELECT timestamp
                                    FROM status_engines
                                    WHERE engine = :engine");
        $STH->execute(array(":engine" => $engine_name));

        $last_run = $STH->fetchAll();

        return $last_run[0]->timestamp;
    }

    /**
     * @author  Will
     *
     * @return $this
     */
    public function fail()
    {
        $this->updateStatus(self::FAILED);

        return $this;
    }

    /**
     * @author  Will
     * @return $this
     */
    public function idle()
    {
        $this->updateStatus(self::IDLE, $update_timestamp = true);

        return $this;
    }

    /**
     * @author  Will
     * @return $this
     */
    public function pause()
    {
        $this->updateStatus(self::PAUSED);

        return $this;
    }

    /**
     * Check if this dataengine is running
     *
     * @author Will
     * @throws EngineException
     * @returns bool
     */
    public function running()
    {
        if ($this->status() === self::PAUSED) {
            Log::notice('Engine paused | Sleep 60');
            sleep(60);

            throw new EngineException('This engine has been paused. Change status to run');
        }
        if ($this->status() === self::RUNNING) {
            return true;
        }

        return false;
    }

    /**
     * @author  Will
     */
    public function start()
    {
        $this->updateStatus(self::RUNNING);
    }

    /**
     * @author  Will
     *
     * @param bool $force_update
     *
     * @returns string Running || Completed || Failed
     */
    public function status($force_update = false)
    {
        if (!is_null($this->status) && !$force_update) {
            return $this->status;
        } else {
            $STH = $this->DBH->prepare("SELECT status,longest_run_time,average_run_time,runs FROM status_engines WHERE engine = :engine");

            $STH->execute(array(':engine' => $this->name));

            if ($STH->rowCount() == 0) {
                $ITH = $this->DBH->prepare('
                    INSERT IGNORE INTO status_engines
                    (engine,status,timestamp)
                    VALUES
                    (:engine,:status,:timestamp)'
                );

                $ITH->execute(
                    array(
                         ':engine'    => $this->name,
                         ':status'    => self::CREATED,
                         ':timestamp' => time()
                    )
                );

                return self::CREATED;
            } else {
                $results                = $STH->fetch();
                $this->longest_run_time = $results->longest_run_time;
                $this->average_run_time = $results->average_run_time;
                $this->runs             = $results->runs;

                return $this->status = $results->status;
            }
        }
    }

    /**
     * @author  Will
     *
     * @param $status
     *
     * @return $this
     */
    public function update($status)
    {
        $STH = $this->DBH->prepare("
            INSERT INTO status_engines
            (status, timestamp, engine, longest_run_time, average_run_time, runs)
            VALUES (:status, :timestamp, :engine, :longest_run_time,:average_run_time,:runs)
            ON DUPLICATE KEY UPDATE
            status = if(status='Paused',status,VALUES(status)),
            timestamp = if(status='Paused',timestamp,VALUES(timestamp)),
            longest_run_time = if(status='Paused',longest_run_time,VALUES(longest_run_time)),
            average_run_time = if(status='Paused',average_run_time,VALUES(average_run_time)),
            runs = if(status='Paused',runs,VALUES(runs))
        ");

        $STH->execute(
            array(
                 ':status'           => $status,
                 ':timestamp'        => time(),
                 ':engine'           => $this->name,
                 ':longest_run_time' => $this->longest_run_time,
                 ':average_run_time' => $this->average_run_time,
                 ':runs'             => $this->runs
            )
        );

        return $this;
    }

    /**
     * Update ONLY the status, not the average run times etc
     *
     * @author  Will
     *
     * @param      $status
     *
     * @param bool $update_timestamp
     *
     * @return $this
     */
    public function updateStatus($status, $update_timestamp = false)
    {

        $this->status = $status;

        $timestamp_sql   = '';
        $this->timestamp = time();

        if ($update_timestamp) {
            $timestamp_sql = ", timestamp = VALUES(timestamp)";
        }

        $STH = $this->DBH->prepare("
            INSERT INTO status_engines
            (status, timestamp, engine, longest_run_time, average_run_time, runs)
            VALUES (:status, :timestamp, :engine, :longest_run_time,:average_run_time,:runs)
            ON DUPLICATE KEY UPDATE
            status = if(status='Paused',status,VALUES(status)) $timestamp_sql
            ");

        $STH->execute(
            array(
                 ':status'           => $status,
                 ':timestamp'        => $this->timestamp,
                 ':engine'           => $this->name,
                 ':longest_run_time' => $this->longest_run_time,
                 ':average_run_time' => $this->average_run_time,
                 ':runs'             => $this->runs
            )
        );

        return $this;
    }

    /**
     * @author  Will
     *
     * @param $file_path
     *
     * @return string parent_directory-filename.php
     */
    protected function determineEngine($file_path)
    {
        return $this->engine = $this->name = basename(dirname($file_path)) . '-' . basename($file_path);
    }
}

/**
 * Class EngineException
 */
class EngineException extends DBModelException
{

    protected $sleep = false;

    /**
     * @author  Will
     *
     * @param string    $message
     * @param bool|int  $sleep
     * @param int       $code
     * @param Exception $previous
     */
    public function __construct(
        $message,
        $sleep = false,
        $code = 0,
        Exception $previous = null
    )
    {
        $this->sleep = $sleep;
        return parent::__construct($message, $code, $previous);

    }

    /**
     * @return bool|int
     */
    public function getSleep()
    {
        return $this->sleep;
    }

}
