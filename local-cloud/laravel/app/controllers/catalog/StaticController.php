<?php

namespace Catalog;
use View,
    Response,
    Redirect,
    Cookie;

define('USER_COUNT', '17,000');
define('AGENCY_COUNT', '300');

class StaticController extends BaseController
{

    /*
     * The layout that should be used for responses.
     */
    protected $layout = 'layouts.public';

    /**
     * @author  Alex
     * @author  Will
     */
    public function showAbout()
    {
        $this->buildLayoutDefaults('about');

        $this->layout->page_name    = 'about';
        $this->layout->main_content = View::make('catalog/about');
    }

    /**
     * @author  Alex
     * @author  Will
     */
    public function showAgencies()
    {
        $this->buildLayoutDefaults('agencies');

        $this->layout->page_name    = 'agencies';
        $this->layout->main_content = View::make('catalog/agencies');
    }

    /**
     * @author  Will
     * @author  Alex
     */
    public function showFeatures()
    {
        $this->buildLayoutDefaults('features');

        $vars = array(
            'call_to_action' => $this->buildCallToAction('features')
        );

        $this->layout->page_name    = 'features';
        $this->layout->top_head     = View::make('catalog.heads.features_experiment');
        $this->layout->main_content = View::make('catalog/features', $vars);
    }

    /**
     * @author  Will
     * @author  Alex
     */
    public function showFeaturesB()
    {
        $this->buildLayoutDefaults('features');

        $vars = array(
            'call_to_action' => $this->buildCallToAction('features')
        );

        $this->layout->page_name    = 'features';
        $this->layout->main_content = View::make('catalog/features_b', $vars);
    }

    /**
     * @author  Will
     * @author  Alex
     */
    public function showFeaturesC()
    {
        $this->buildLayoutDefaults('features');

        $vars = array(
            'call_to_action' => $this->buildCallToAction('features')
        );

        $this->layout->page_name    = 'features';
        $this->layout->main_content = View::make('catalog/features_c', $vars);
    }

    /**
     * @author  Will
     * @author  Alex
     */
    public function showIndex()
    {

        $this->buildLayoutDefaults('index');

        $this->layout->page_name    = 'index';
        $this->layout->top_head     = View::make('catalog.heads.landing_experiment');
        $this->layout->main_content = View::make('catalog/index', array(
            'call_to_action' => $this->buildCallToAction('index'),
            'page_name'      => 'index'
        ));
    }

    /**
     * Display an alternate version of the main landing page for A/B testing.
     *
     * @author Janell
     */
    public function showIndexA()
    {
        $this->buildLayoutDefaults('index');

        $this->layout->page_name    = 'index';
        $this->layout->top_head     = View::make('catalog.heads.landing_experiment');
        $this->layout->main_content = View::make('catalog/index_a', array(
            'call_to_action' => $this->buildCallToAction('index'),
            'page_name'      => 'index'
        ));
    }

    /**
     * Display an alternate version of the main landing page for A/B testing.
     *
     * @author Janell
     */
    public function showIndexB()
    {
        $this->buildLayoutDefaults('index');

        $this->layout->page_name    = 'index';
        $this->layout->top_head     = View::make('catalog.heads.landing_experiment');
        $this->layout->main_content = View::make('catalog/index_b', array(
            'call_to_action' => $this->buildCallToAction('index'),
            'page_name'      => 'index'
        ));
    }

    /**
     * Display an alternate version of the main landing page for A/B testing.
     *
     * @author Janell
     */
    public function showIndexC()
    {
        $this->buildLayoutDefaults('index');

        $this->layout->page_name    = 'index';
        $this->layout->top_head     = View::make('catalog.heads.landing_experiment');
        $this->layout->main_content = View::make('catalog/index_c', array(
            'call_to_action' => $this->buildCallToAction('index'),
            'page_name'      => 'index'
        ));
    }

    /**
     * /pinreach
     *
     * @author  Will
     * @author  Alex
     */
    public function showPinreach()
    {
        $cookie = Cookie::forever('source', 'pinreach');
        $this->buildLayoutDefaults('index');

        $vars                       = array(
            'call_to_action' => $this->buildCallToAction('index', 'pinreach'),
            'page_name'      => 'index'
        );

        $this->layout->head_append = View::make('catalog/heads/pinreach');
        $this->layout->page_name    = 'index';
        $this->layout->main_content = View::make('catalog.pinreach', $vars);

        return Response::make($this->layout)->withCookie($cookie);
    }

    /**
     *
     * /pinreach/welcome
     *
     * @author  Will
     * @author  Alex
     */
    public function showPinreachWelcome()
    {
        $cookie = Cookie::forever('source', 'pinreach');
        $this->buildLayoutDefaults('index');

        $vars                       = array(
            'call_to_action' => $this->buildCallToAction('index', 'pinreach_welcome'),
            'page_name'      => 'index'
        );
        $this->layout->page_name    = 'index';
        $this->layout->main_content = View::make('catalog.pinreach_welcome', $vars);

        return Response::make($this->layout)->withCookie($cookie);
    }

    /**
     * @author  Will
     * @author  Alex
     */
    public function showPricing()
    {
        $this->buildLayoutDefaults('pricing');

        $this->layout->top_head       = View::make('catalog.heads.pricing_experiment');
        $this->layout->pre_body_close = View::make('catalog.pre_body_close.pricing_fixed');

        $pricing_vars = array(
            'feature_grid' => View::make('catalog.components.feature_grid_n')
        );

        $this->layout->page_name    = 'pricing';
        $this->layout->main_content = View::make('catalog.pricing_n2', $pricing_vars);
    }

    /**
     * @author  Alex
     */
    public function showNewPricing()
    {
        $this->buildLayoutDefaults('pricing');

        $this->layout->top_head       = View::make('catalog.heads.pricing_experiment');
        $this->layout->pre_body_close = View::make('catalog.pre_body_close.pricing_fixed');

        $pricing_vars = array(
            'feature_grid' => View::make('catalog.components.feature_grid_n')
        );

        $this->layout->page_name    = 'pricing';
        $this->layout->main_content = View::make('catalog.pricing_n', $pricing_vars);
    }

    /**
     * @author Alex
     */
    public function showPricingPackages()
    {
        $this->buildLayoutDefaults('pricing');

        $this->layout->top_head       = View::make('catalog.heads.pricing_experiment');
        $this->layout->pre_body_close = View::make('catalog.pre_body_close.pricing_fixed');

        $pricing_vars = array(
            'feature_grid' => View::make('catalog.components.feature_grid')
        );

        $this->layout->page_name    = 'pricing';
        $this->layout->main_content = View::make('catalog.pricing_a', $pricing_vars);

    }

    /**
     * @author Alex
     */
    public function showPricingPlans()
    {
        $this->buildLayoutDefaults('pricing');

        $this->layout->top_head       = View::make('catalog.heads.pricing_experiment');
        $this->layout->pre_body_close = View::make('catalog.pre_body_close.pricing_fixed');

        $pricing_vars = array(
            'feature_grid'    => View::make('catalog.components.feature_grid')
        );

        $this->layout->page_name    = 'pricing';
        $this->layout->main_content = View::make('catalog.pricing_b', $pricing_vars);

    }

    /**
     * @author  Will
     */
    public function showPrivacy()
    {
        $this->buildLayoutDefaults('about');
        $this->layout->main_content = View::make('catalog/privacy');
    }

    /**
     * @author  Will
     */
    public function showTerms()
    {
        $this->buildLayoutDefaults('about');
        $this->layout->main_content = View::make('catalog/terms');
    }


}