<?php namespace Presenters\Dashboard;

/**
 * Class Widget
 *
 * @package Presenters\Dashboard
 */
interface  WidgetInterface {

    /**
     * Every widget needs to be able to be rendered
     *
     * @return string
     */
    public function render();

    /**
     * Every widget should optionally be able to set the name of the view it's going
     * to use to be able to be rendered
     *
     * @param $file_name
     *
     * @return self
     */
    public function setViewName($file_name);

    /**
     * If we want to use the smaller template, we should be able to use this
     * method to do so
     *
     * @return self
     */
    public function setSmall();

}