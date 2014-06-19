<?php
 
/**
 * Chargify statement class.
 * 
 * @see http://docs.chargify.com/api-statements
 * 
 * @author Daniel
 */
class ChargifyStatement
{
    private $connector;

    public function __construct($test_mode = false)
    {
        $this->connector = new ChargifyConnector($test_mode);
    }

    public function getByID($id, $format = 'XML')
    {
        return $this->connector->getStatementByID($id, $format);
    }

    public function getBySubscriptionID($subscription_id)
    {
        return $this->connector->getStatementsBySubscriptionID($subscription_id);
    }
}
