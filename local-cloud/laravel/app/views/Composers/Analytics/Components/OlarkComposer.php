<?php namespace Composers\Analytics\Components;

use
    View;

/**
 * Class AnalyticsComposer
 *
 * @package Layouts
 */
class OlarkComposer
{

    /**
     * When layout is created, this method is run. Useful for setting defaults,
     * creating assets etc
     *
     * @param $view
     */
    public function create(View $view)
    {
        if (empty($view->active_user_account) || empty($view->user)) {
            $view->cust_first_name = '';
            $view->cust_last_name  = '';
            $view->cust_email      = '';
            $view->cust_org_id     = '';
            $view->cust_account_id = '';
            $view->cust_username   = '';
            $view->cust_plan_id    = '';
        } else {
            $view->cust_first_name = $view->user->first_name;
            $view->cust_last_name  = $view->user->last_name;
            $view->cust_email      = $view->user->email;
            $view->cust_org_id     = $view->user->org_id;
            $view->cust_account_id = $view->active_user_account->account_id;
            $view->cust_username   = $view->active_user_account->username;
            $view->cust_plan_id    = $view->user->organization()->plan;
        }


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