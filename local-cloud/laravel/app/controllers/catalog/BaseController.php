<?php

namespace Catalog;

use DatabaseInstance,
    Session,
    View;

class BaseController extends \BaseController
{

    /**
     * @var PDO
     */
    protected $DBH;

    public function __construct()
    {

        $this->DBH = DatabaseInstance::DBO();
    }

    /**
     * Build the call to action for use on the page
     *
     * @author  Will
     * @author  Alex
     *
     * @param      $page
     * @param bool $variation
     *
     * @return \Illuminate\View\View
     */
    public function buildCallToAction($page, $variation = false)
    {

        $username       = '';
        $message        = '';
        $cta_help_class = '';
        if (Session::has('signup_error')) {
            $username       = Session::get('username');
            $message        = Session::get('message');
            $cta_help_class = 'error';
        }
        /*
          * Call to actions
          */
        $cta_vars = array(
            'username'       => $username,
            'cta_help_class' => $cta_help_class,
            'message'        => $message,
            'page_name'      => $page
        );

        $cta      = View::make('catalog/components/call_to_action', $cta_vars);



        return $cta;
    }

    /**
     * @author  Will
     *
     * @param        $page
     * @param string $action
     */
    public function buildLayoutDefaults($page, $action = 'index')
    {
        $this->layout->head_append = View::make('catalog/heads/' . $page);
        if (file_exists(app_path() . 'views/catalog/pre_body_close/' . $page . '.' . $action . '.php')) {
            $this->layout->pre_body_close = View::make("catalog/pre_body_close/$page.$action");
        }

        $cta = $this->buildCallToAction($page);

        $pop_up_vars = array(
            'call_to_action' => $cta
        );

        $this->layout->pop_up_cta = View::make('catalog/components/pop_up_cta', $pop_up_vars);

    }

    /**
     * Setup the layout used by the controller.
     *
     * @return void
     */
    protected function setupLayout()
    {
        if (!is_null($this->layout)) {
            /*
             * Create the layout object
             */
            $this->layout = View::make($this->layout);
            $this->layout->segmentio_write_key  = \Config::get('segmentio.WRITE_KEY');

            /*
             * Set some blank defaults
             */
            $this->layout->top_head       = '';
            $this->layout->head_append    = '';
            $this->layout->page_name      = '';
            $this->layout->pre_body_close = '';

            /*
             * Define the footer and side_nav defaults
             */
            $vars                     = array(
                'year' => date('Y')
            );
            $this->layout->footer     = View::make('catalog/components/footer', $vars);
            $this->layout->navigation = View::make('catalog/components/navigation');

        }
    }
}