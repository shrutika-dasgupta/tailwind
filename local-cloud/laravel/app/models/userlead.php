<?php

use UAParser\UAParser;

class UserLead extends PDODatabaseModel
{
    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */

    public
        $id,
        $username,
        $user_agent,
        $ip,
        $timestamp,
        $__count;
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
    protected $_parsed_user_agent = false;

    /*
    |--------------------------------------------------------------------------
    | Instance Methods
    |--------------------------------------------------------------------------
    */

    /**
     * @author  Will
     * @return bool|\UAParser\Result\ResultInterface|\UAParser\ResultInterface
     */
    protected function getParsedUA()
    {
        if ($this->_parsed_user_agent) {
            return $this->_parsed_user_agent;
        }
        $uaParser = new UAParser();

        return $this->_parsed_user_agent = $uaParser->parse($this->user_agent);
    }
}

/*
|--------------------------------------------------------------------------
| Exceptions
|--------------------------------------------------------------------------
*/

class UserLeadException extends DBModelException {}
