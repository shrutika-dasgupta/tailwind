<?php

/**
 * @author  Will
 */
class UserLeads extends DBCollection
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */
    public
        $table = 'user_leads',
        $columns =
        array(
            'id',
            'username',
            'user_agent',
            'ip',
            'timestamp'
        ),
        $primary_keys = array('id');

    /*
    |--------------------------------------------------------------------------
    | Static Methods
    |--------------------------------------------------------------------------
    */
    /**
     * Fetches all
     * @return Users
     */
    public static function allByDistinctUsername()
    {
        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->query(
            "SELECT *,COUNT(*) as count FROM user_leads GROUP BY username ORDER BY count DESC;
             "
        );

        $user_leads = new UserLeads();

        foreach ($STH->fetchAll() as $userData) {
            $user_lead = new UserLead();
            $user_lead->loadDBData($userData);
            $user_lead->__count = $userData->count;

            $user_leads->add($user_lead);
        }

        return $user_leads;
    }
}

class UserLeadsException extends CollectionException {}
