<?php namespace Presenters\Dashboard;

use View,
    Str,
    Log;

/**
 * Class Widget
 *
 * @package Presenters\Dashboard
 */
class ListeningWidget extends Widget implements WidgetInterface
{
    /**
     * The widget should return a string of HTML
     *
     * @return string
     */
    public function render()
    {

        $this->setViewName('discover_stats');

        $vars = array(
            'pins' => $this->user_account->profile()->getDBPins()->limit(21)
        );

        return View::make($this->viewPath(), $vars);
    }


}