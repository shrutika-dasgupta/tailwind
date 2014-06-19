<?php

use Pinleague\Pinterest;
use Pinleague\PinterestException;

/**
 * Collection of Status Domains
 *
 * @author Yesh
 */
class StatusDomains extends DBCollection
{
    public $table = 'status_domains';

    public $columns = array(
        'domain',
        'last_pulled',
        'last_calced',
        'calculate_influencers_footprint',
        'pins_per_day',
        'track_type',
        'timestamp'
    );

    public $primary_keys = array('domain');

    public $domain;
    public $last_pulled;
    public $last_calced;
    public $pins_per_day;
    public $track_type;
    public $timestamp;

    /**
     * @param     $flag Name of the column
     * @param int $limit
     *
     * @return array
     *
     * @author Yesh
     */
    public static function fetch($flag, $limit = 150) {

        $DBH = DatabaseInstance::DBO();

        $STH = $DBH->query("SELECT domain
                              FROM status_domains
                              WHERE $flag = 0 AND
                              track_type = 'user'
                              LIMIT $limit");
        return $STH->fetchAll();

        if (empty($results)) {

            $STH = $DBH->query("SELECT domain
                                  FROM status_domains
                                  WHERE $flag = 0 AND
                                  track_type = 'competitor'
                                  LIMIT $limit");
            return $STH->fetchAll();

        }

        return $results;
    }


    /**
     * @author  Alex
     *
     * Fetch a collection of status_domains from a stringified list of domains
     *
     * @param string $domains_stringify
     *
     * @return StatusDomains Collection
     *
     */
    public static function fetchFromList($domains_stringify) {

        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->query(
                         "SELECT domain, pins_per_day
                          FROM status_domains
                          WHERE domain IN ($domains_stringify)"
        );

        $status_domains_db = $STH->fetchAll();

        /*
         * Create a new StatusDomains collection to return
         */
        $status_domains = new StatusDomains();
        foreach ($status_domains_db as $status_domain_db) {
            $status_domain = new StatusDomain();
            $status_domain->domain = $status_domain_db->domain;
            $status_domain->pins_per_day = $status_domain_db->pins_per_day;
            $status_domains->add($status_domain, true);
        }

        return $status_domains;

    }


    /**
     * @author  Alex
     *
     * Update pins_per_day value for set of status_domain models
     *
     *          This method should only be used when we've already checked a
     *          calculated pins_per_day, compared it to the current value and
     *          determined that it now higher than a previous value.
     */
    public function updatePinsPerDay() {

        foreach($this->getModels() as $status_domain) {
            $STH = $this->DBH->prepare(
                "UPDATE status_domains
                SET pins_per_day = :pins_per_day
                WHERE domain = :domain"
            );

            $STH->execute(
                array(
                     ":pins_per_day" => $status_domain->pins_per_day,
                     ":domain"       => $status_domain->domain
                )
            );
        }
    }

    /**
     * @author Yesh
     * @param array $dont_update_these_columns
     * @return $this
     */
    public function insertUpdateDB($dont_update_these_columns = array())
    {
        array_push($dont_update_these_columns, 'user_id', 'email');
        $append = "ON DUPLICATE KEY UPDATE ";

        foreach ($this->columns as $column) {
            if (!in_array($column, $dont_update_these_columns)) {
                $append .= "$column = VALUES($column),";
            }
        }

        if (!in_array('track_type', $dont_update_these_columns)) {
            $append .=
                "track_type=IF(VALUES(track_type)='user',
                'user',IF(VALUES(track_type)='competitor', 'competitor', track_type))";
        }

        return $this->saveModelsToDB('INSERT INTO', $append);
    }
}

class statusDomainsException extends DBModelException {}
