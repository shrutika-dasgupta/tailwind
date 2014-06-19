<?php namespace Analytics;

use
    Lang,
    Log,
    UserHistory,
    UserProperty,
    View;

/**
 * Class StaticController
 *
 * @package Analytics
 */
class StaticController extends BaseController
{
    protected $layout = 'layouts.analytics';

    /**
     * Shows the FAQ page
     *
     * @author  Alex
     * @author  Will
     * @author  Janell
     */
    public function showFAQ()
    {
        Log::setLog(__FILE__, 'Static', 'FAQ');

        $this->layout_defaults['page'] = 'FAQ';
        $this->layout_defaults['top_nav_title'] = 'FAQ';
        $this->layout->top_navigation = $this->buildTopNavigation();
        $this->layout->side_navigation = $this->buildSideNavigation('');

        $this->logged_in_customer->incrementUserProperty(UserProperty::VIEW_REPORT.'FAQ',1);
        $this->logged_in_customer->setUserProperty(UserProperty::LAST_VIEWED_REPORT_AT.'FAQ',time());

        $this->logged_in_customer->recordEvent(
                                 UserHistory::VIEW_REPORT,
                                 $parameters = array(
                                     'report' => 'FAQ'
                                 )
        );

        $this->layout->main_content = View::make('analytics.pages.faq', array(
            'faq_sections' => Lang::get('faq.sections'),
            'questions'    => Lang::get('faq.questions'),
            'answers'      => Lang::get('faq.answers'),
        ));
    }

}
