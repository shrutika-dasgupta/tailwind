<?php

namespace Content;

/*
 * Status topic model.
 *
 * @author Daniel
 */
class StatusTopic extends \PDODatabaseModel
{
    public $table = 'status_topics';

    public $columns = array(
        'id',
        'topic',
        'type',
        'curated',
        'last_pulled',
    );

    public $primary_keys = array('id');

    public $id;
    public $topic;
    public $type;
    public $curated;
    public $last_pulled;

    /**
     * Saves model data.
     *
     * @param string  $statement_type
     * @param boolean $append
     *
     * @return void
     */
    public function saveToDB($statement_type = 'INSERT IGNORE INTO', $append = false)
    {
        if (empty($this->type)) {
            if (preg_match('/^([0-9a-z-]+\.)?[0-9a-z-]+\.[a-z]{2,7}$/i', $this->topic)) {
                $this->type = 'domain';
            } else {
                $this->type = 'keyword';
            }
        }
        
        parent::saveToDB($statement_type, $append);
    }
}
