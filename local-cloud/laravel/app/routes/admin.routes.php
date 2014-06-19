<?php

/*
 * Admin Routes
 * before the auth wall
 */
Route::group(array('domain' => ROUTE_PREFIX . 'admin.tailwindapp.' . ROUTE_TLD, 'before' => 'AdminGuest'), function () {

    Route::get('/', 'Admin\AuthController@showLogin');

    Route::get('/login',
        array('as'   => 'admin-login',
              'uses' => 'Admin\AuthController@showLogin')
    );

    Route::post('/login', 'Admin\AuthController@processLogin');

});

Route::group(array('domain' => ROUTE_PREFIX . 'admin.tailwindapp.' . ROUTE_TLD), function () {

    Route::get('/adp', 'Admin\adp\AdpController@runAdp');

});


/*
 * Admin Routes
 * Behind the auth wall
 */
Route::group(array('domain' => ROUTE_PREFIX . 'admin.tailwindapp.' . ROUTE_TLD, 'before' => 'AdminAuth'), function () {

    Route::get('/logout',
        array('as'   => 'admin-logout',
              'uses' => 'Admin\AuthController@processLogout'
        ));

    Route::get('/dashboard',
        array('as'   => 'admin-dashboard',
              'uses' => 'Admin\DashboardController@showIndex'
        ));

    /*
     * Pinterest Controller
     * @see documentation on controllers vs direct routing
     */
    Route::controller('/pinterest', 'Admin\PinterestController');

    /*
     * Customer Controller
     */
    Route::get('/customers', 'Admin\CustomerController@getIndex');
    Route::get('/customer/{customer_id}', 'Admin\CustomerController@showCustomerProfile');
    Route::get('/customer/{customer_id}/history', 'Admin\CustomerController@getHistory');
    Route::post('/customer/{customer_id}/edit', 'Admin\CustomerController@edit');
    Route::get('/customer/{customer_id}/features', 'Admin\CustomerController@getFeatures');
    Route::get('/customer/{customer_id}/feature/{feature_id}/enable', 'Admin\CustomerController@enableFeature');
    Route::get('/customer/{customer_id}/feature/{feature_id}/disable', 'Admin\CustomerController@disableFeature');
    Route::get('/customer/{customer_id}/feature/{feature_id}/reset', 'Admin\CustomerController@resetFeature');
    Route::get('/customer/{customer_id}/feature/{feature_id}/edit', 'Admin\CustomerController@editFeature');
    Route::get('/customer/{customer_id}/emails/off', 'Admin\CustomerController@turnOffEmails');
    Route::get('/customer/{customer_id}/plan/edit', 'Admin\CustomerController@changePlan');

    Route::get('/customers/leads','Admin\CustomerController@getleads');

    Route::get('/org/{org_id}/', 'Admin\OrganizationController@showOrgProfile');
    Route::get('/org/{org_id}/features', 'Admin\OrganizationController@getFeatures');
    Route::get('/org/{org_id}/feature/{feature_id}/enable', 'Admin\OrganizationController@enableFeature');
    Route::get('/org/{org_id}/feature/{feature_id}/disable', 'Admin\OrganizationController@disableFeature');
    Route::get('/org/{org_id}/feature/{feature_id}/reset', 'Admin\OrganizationController@resetFeature');
    Route::get('/org/{org_id}/feature/{feature_id}/edit', 'Admin\OrganizationController@editFeature');
    Route::get('/org/{org_id}/plan/edit', 'Admin\OrganizationController@changePlan');

    Route::get('/plan/{plan_id}/','Admin\PlanController@getFeatures');
    Route::get('/plan/{plan_id}/features', 'Admin\PlanController@getFeatures');
    Route::get('/plan/{plan_id}/feature/{feature_id}/enable', 'Admin\PlanController@enableFeature');
    Route::get('/plan/{plan_id}/feature/{feature_id}/disable', 'Admin\PlanController@disableFeature');
    Route::get('/plan/{plan_id}/feature/{feature_id}/reset', 'Admin\PlanController@resetFeature');
    Route::get('/plan/{plan_id}/feature/{feature_id}/edit', 'Admin\PlanController@editFeature');


    Route::any('/publisher/auth/status','Admin\PublisherController@getAuthStatus');


    /*
     * Engines Controller
     */
    Route::controller('/engines', 'Admin\EnginesController');

    /*
     * Calcs Controller
     */
    Route::controller('/calcs', 'Admin\CalcsController');

    /**
     * Email Controller
     */
    Route::get('email/preview/{id}', 'Admin\EmailController@showPreview');
    Route::get('email/preview/{id}/send', 'Admin\EmailController@sendPreview');
    Route::get('email/preview/{id}/resend', 'Admin\EmailController@resend');
    Route::get('email/queue', 'Admin\EmailController@getQueue');
    Route::get('email/cancel/{id}', 'Admin\EmailController@cancelEmailSend');
    Route::get('email/delete/{id}', 'Admin\EmailController@deleteFromQueue');
    Route::get('email/requeue/{id}', 'Admin\EmailController@requeueEmail');
    Route::get('email/queue/{filter}',
        array('as'   => 'queue',
              'uses' => 'Admin\EmailController@showQueue'
        ));


    /*
     * Demo Account Functions
     */
    Route::get('/demo/new', 'Admin\DemoController@showDemoSignup');
    //Route::get('/demo/new', function(){die('foo');});
    Route::get('/demo/summary', 'Admin\DemoController@showDemoSummary');
    Route::post('/demo/new/create', 'Admin\DemoController@addDemoAccount');

    /*
     * Ajax Scripts
     */
    Route::get('/ajax/check-username/{username}', 'Analytics\AjaxController@checkUsername');

    /**
     * Super hacky way to catch all routes that start with /docs and show the docs HTML
     * We did it this way so that we could use the same auth system in the admin dashboard
     * for the docs.
     *
     * @author  Yesh
     * @author  Will
     */
    Route::any('/docs/{path?}', function(){
        $uri = parse_url(Request::path());
        $path = app_path(). '/../'. $uri['path'];

        if (file_exists($path)){
            $response = Response::make(file_get_contents($path));

            if (substr($path,-3) == 'css') {
                $response->header('Content-type','text/css');
            }

            if (substr($path,-2) == 'js') {
                $response->header('Content-type','text/javascript');
            }

            return $response;

        } elseif (file_exists($path . '/index.html')) {
           return file_get_contents($path . '/index.html');
        }

        return '404';
    })->where('path', '.+');


});