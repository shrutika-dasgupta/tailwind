<?php namespace Composers\Analytics\Components;

use
    View;

/**
 * Class AnalyticsComposer
 *
 * @package Layouts
 */
class AdminDropdownComposer
{

    /**
     * When layout is created, this method is run. Useful for setting defaults,
     * creating assets etc
     *
     * @param $view
     */
    public function create(View $view)
    {
        if (!\Session::has('admin_dropdown_list')) {

            $DBH = \DatabaseInstance::DBO();
            $STH = $DBH->query("
                SELECT users.cust_id, user_accounts.username,user_organizations.plan, users.org_id
                FROM user_accounts
                LEFT JOIN user_organizations
                ON user_organizations.org_id = user_accounts.org_id
                LEFT JOIN users
                ON user_organizations.org_id = users.org_id
                WHERE user_accounts.username IS NOT NULL
                AND user_accounts.track_type != 'orphan'
                AND user_accounts.competitor_of = 0
                AND user_accounts.username !=''
                ORDER BY user_accounts.username ASC, users.cust_id ASC
        ");

            \Session::set('admin_dropdown_list',$STH->fetchAll());

        }

        $view->accounts = \Session::get('admin_dropdown_list');

        /** @var \User $user */
        $user = $view->user;
        /** @var \UserAccount $active_account */
        $active_account = $view->active_account;

        $view->cust_id = $user->cust_id;
        $view->account_id = $active_account->account_id;
        $view->org_id = $user->org_id;
        $view->plan_id = $user->organization()->plan;
        $view->chargify_id = $user->organization()->chargify_id;
        $view->account_type = $active_account->account_type;
        $view->account_track_type = $active_account->track_type;
        $view->domains = $active_account->domains();
        $view->username = $active_account->username;
        $view->user_id = $active_account->user_id;
        $view->competitors = $user->organization()->totalCompetitorsAdded();
        $view->has_ga = $active_account->hasGoogleAnalytics('STRING');

    }

    /**
     * This fires when the view is rendered
     *
     * @param View $view
     *
     * @author  Will
     */
    public function compose(View $view)
    {
        //do nothing
    }
}