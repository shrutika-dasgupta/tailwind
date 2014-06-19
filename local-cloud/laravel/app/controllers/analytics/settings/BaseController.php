<?php namespace Analytics\Settings;

use
    Log,
    Redirect,
    User,
    View;

/**
 * Class BaseController
 *
 * @package Analytics\Settings
 */
class BaseController extends \Analytics\BaseController
{

    protected $layout = 'layouts.analytics';

    /**
     * @author  Will
     */
    public function __construct()
    {
        parent::__construct();
        $this->layout_defaults['top_nav_title'] = 'Settings';

        Log::setLog(__FILE__,'Reporting','Settings');


       $is_demo = $this->isDemo();

        $this->beforeFilter(function() use ($is_demo){
            if ($is_demo){
                return Redirect::back()->with('flash_error','You cannot access that page in a demo.');
            }
        });

    }

    /**
     * @author  Will
     *
     * @param $tab
     *
     * @return string
     */
    protected function buildSettingsNavigation($tab)
    {
        $legacy = $this->baseLegacyVariables();
        extract($legacy);

        /** @var  $customer User */

        $vars = array(
            'profile_class'            => '',
            'account_class'            => '',
            'competitors_class'        => '',
            'collaborators_class'      => '',
            'analytics_class'          => '',
            'billing_class'            => '',
            'collaborators_link_class' => ($customer->maxAllowed('num_users') > 0 ? '' : 'inactive'),
            'collaborators_link'       => '',
            'has_chargify'             => $customer->hasCreditCardOnFile(),
//            'has_chargify'             => true,
            'billing_class'            => '',
            'billing_link_class'       => '',
            'billing_tooltip'          => '',
            'billing_tab_icon'         => '',
            'show_upgrade'             => true,
        );

        /*
         * Set the tab that is active
         */
        $vars[$tab . '_class'] = 'active';

        if ($customer->is_admin == User::PERMISSIONS_VIEWER) {
            return View::make('analytics.pages.settings.navigation_viewer', $vars);
        }

        return View::make('analytics.pages.settings.navigation', $vars);
    }
}
