<?php

/**
 * Class UserAccounts
 * 
 * @return UserAccount
 */
class UserAccounts extends DBCollection
{
    public
        $table = 'user_accounts',
        $columns =
        array(
            'account_id',
            'account_name',
            'org_id',
            'username',
            'user_id',
            'industry_id',
            'account_type',
            'track_type',
            'competitor_of',
            'chargify_id',
            'chargify_id_alt',
            'domain_limit',
            'keyword_limit',
            'access_token',
            'created_at',
            'last_update',
        ),
        $primary_keys = array('account_id');

    /**
     * Get a comma separated string of the user ids
     * @author  Will
     * @todo just use stringify
     */
    public function user_ids_string($seperator = ',')
    {
        $user_ids = '';
        foreach ($this->models as $user_account) {
            $user_ids .= $user_account->user_id . $seperator;
        }
        $user_ids = rtrim($user_ids, ',');

        return $user_ids;
    }

    /**
     * Get a comma separated string of the account ids
     * @author  Will
     * @todo just use stringify
     */
    public function account_ids_string($seperator = ',')
    {
        $user_ids = '';
        foreach ($this->models as $user_account) {
            $user_ids .= $user_account->account_id . $seperator;
        }
        $user_ids = rtrim($user_ids, ',');

        return $user_ids;
    }

    /**
     * Returns the profiles for the user accounts
     * Highly suggested that these come preloaded
     * @author  Will
     */
    public function profiles()
    {
        $profiles = new Profiles();

        foreach($this->models as $user_account) {
            /** @var $user_account UserAccount */
            $profiles->add($user_account->profile());
        }

        return $profiles;
    }

    /**
     * @return bool
     */
    public function hasDomain() {
        $accounts_with_domain = $this->copy()->filter(function(UserAccount $user_account) {
           if($user_account->domains()->count() == 0){
               return false;
           }
            return true;
        });

        if($accounts_with_domain->count() < 1) {
            return false;
        }
        return true;
    }
}