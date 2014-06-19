<?php namespace Composers\Analytics\Components;

use
    View;

/**
 * Class AnalyticsComposer
 *
 * @package Layouts
 */
class DemoBarComposer
{

    /**
     * When layout is created, this method is run. Useful for setting defaults,
     * creating assets etc
     *
     * @param $view
     */
    public function create(View $view)
    {
        $view->lite_toggle_class = '';
        $view->pro_toggle_class = 'active';

        switch($view->plan) {
            case 'Lite':
                $view->lite_toggle_class = 'active';
                $view->pro_toggle_class = '';

                break;
            default:
                ///nothing
                break;
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

    }
}