<?php namespace Composers\Layouts;

use View;

/**
 * Class AnalyticsComposer
 *
 * @package Layouts
 */
class AnalyticsComposer
{

    /**
     * When layout is created, this method is run. Useful for setting defaults,
     * creating assets etc
     *
     * @param $view
     */
    public function create(View $view)
    {
        /*
         * Set defaults for the layout variables that
         * are optional
         */
        $view->title           = '';
        $view->description     = '';
        $view->author          = '';
        $view->body_id         = '';
        $view->top_bar_alert   = '';
        $view->loading_overlay = '';
        $view->side_navigation = '';
        $view->top_navigation  = '';
        $view->alert           = '';
        $view->last_calculated = '';
        $view->trada           = '';

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
        if ($view->assets instanceof \Assets) {
            //compile them
        }

    }
}