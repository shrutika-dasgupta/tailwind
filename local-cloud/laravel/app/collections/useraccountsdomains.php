<?php

use Caches\DomainInfluencers;

class UserAccountsDomains extends DBCollection
{
    public
        $domain,
        $account_id;
    public
        $table = 'user_accounts_domains',
        $columns =
        array(
            'account_id',
            'domain'
        ),
        $primary_keys = array();

    /**
     * @author  Will
     * @return string
     */
    public function __toString()
    {
        return $this->domainsString(', ','');
    }

    /**
     * Get a comma separated string of the domains
     *
     * @author  Will
     */
    public function domainsString($seperator = ',', $encase_in = "'")
    {
        return $this->stringifyField('domain', $seperator, $encase_in);
    }

    /**
     * @author  Will
     *
     * @param $track_type
     *
     * @return $this
     */
    public function changeStatusDomainsTrackType($track_type)
    {
        $status_domains = $this->stringifyField('domain');

        if ($status_domains != '') {
            $params = array(
                ':status_track_type' => $track_type,
            );

            $STH = $this->DBH->prepare("
                  update status_domains
                  set track_type = :status_track_type
                  where domain in ($status_domains)
                ");
            $STH->execute($params);
        }

        return $this;
    }
    
    /**
     *
     * @author  Will
     *
     * @param $pins
     *
     * @return Pins
     */
    public function getRecentPins($pins)
    {

        $domains = $this->stringifyField('domain');

        $STH = $this->DBH->query("
                    select * from data_pins_new
                    where domain in ($domains)
                    order by created_at DESC
                    limit $pins
                ");

        $pins = new Pins();
        foreach ($STH->fetchAll() as $pinData) {
            $pins->add(Pin::createFromDBData($pinData));
        }

        return $pins;
    }

    /**
     * Gets the top influencers for the domains.
     *
     * @author Will
     *
     * @param integer $period
     * @param int     $limit
     *
     * @return \Caches\DomainInfluencers
     */
    public function topPinners($period = 0, $limit = 25)
    {
        $domains_csv = $this->stringifyField('domain');

        $domain_influencers = DB::select(
                 "SELECT *
             FROM cache_domain_influencers
             WHERE domain IN ($domains_csv)
             AND period = ?
             AND influencer_user_id != 0
             ORDER BY domain_mentions DESC
             LIMIT $limit",
                 array($period)
        );

        return DomainInfluencers::createFromDBData($domain_influencers,'Caches\DomainInfluencer');
    }
}