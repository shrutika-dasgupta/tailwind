<?php

if (App::environment() == 'production' OR 1) {
    ini_set('display_errors', 'off');
    error_reporting(0);
} else {

    ini_set('display_errors', 'on');
    error_reporting(E_ALL);
}

/**
 * Class BaseController
 */
class BaseController extends Controller {

    /**
     * @var View | string | NULL
     */
    protected $layout;

    /**
	 * Setup the layout used by the controller.
	 *
	 * @return void
	 */
	protected function setupLayout()
	{
		if ( ! is_null($this->layout))
		{
			$this->layout = View::make($this->layout);
		}
	}

}