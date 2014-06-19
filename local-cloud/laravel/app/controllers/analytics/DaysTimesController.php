<?php

namespace Analytics;

use View,
    Log,
    Redirect,
    UserHistory;

/**
 * Class PublishingController
 *
 * @package Analytics
 */
class DaysTimesController extends BaseController
{
    protected $layout = 'layouts.analytics';

    /**
     * Construct
     *
     * @author  Will
     */
    public function __construct()
    {

        parent::__construct();

        Log::setLog(__FILE__, 'Reporting', 'Publishing_Report');
    }

    public function showPeakDaysAndTimes()
    {
        $vars                       = $this->baseLegacyVariables();

        /*
         * Redirect to upgrade page if feature not available
         */
        extract($vars);
        if(!$customer->hasFeature('nav_day_time')){
            return Redirect::to("/upgrade?ref=day_time&plan=" . $customer->plan()->plan_id . "");
        }

        $this->layout_defaults['page'] = 'days_time';
        $this->layout_defaults['top_nav_title'] = 'Peak Days & Times';
        $this->layout->top_navigation = $this->buildTopNavigation();

        $this->layout->side_navigation = $this->buildSideNavigation('day_time');

        $this->layout->body_id = 'day-time';
        $vars['nav_day_time_class'] .= ' active';
        $vars['report_url'] = 'days-and-times';

        $this->layout->sub_navigation = View::make('analytics.components.sub_nav.optimize', $vars);

        $this->layout->main_content = View::make(
            'analytics.pages.day_time',
            array_merge($vars, $this->active_user_account->profile()->getPeakDaysAndTimesData())
        );

        $this->logged_in_customer->recordEvent(
                                 UserHistory::VIEW_REPORT,
                                 $parameters = array(
                                     'report' => 'peak days & times'
                                 )
        );

    }
}
