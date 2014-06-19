<?php

class Engines extends DBCollection
{
    public $table = 'status_engines',
        $columns = array(
        'engine', 'status', 'longest_run_time', 'timestamp'
    );

    /**
     * @author  Will
     * 
     * @return Engines
     */
    public static function fetch()
    {
        $DBH = DatabaseInstance::DBO();

        $STH = $DBH->query(
            'select * from status_engines'
        );

        $engines = new self();
        foreach ($STH->fetchAll() as $engineData) {
            $engine = new Engine();
            $engine->loadDBData($engineData);
            $engines->add($engine);
        }

        return $engines;
    }
}