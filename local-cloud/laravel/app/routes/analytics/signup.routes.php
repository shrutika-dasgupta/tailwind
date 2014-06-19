<?php
/*
|--------------------------------------------------------------------------
| Unfiltered Routes
|--------------------------------------------------------------------------
| These routes work regardless of auth status
|
 */
Route::group(array(
                  'namespace' => 'Analytics',
                  'domain'    => ROUTE_PREFIX . 'analytics.tailwindapp.' . ROUTE_TLD,
                  'prefix'    => 'signup'
             ),
    function () {

        Route::get('/resend-email-confirmation/',
                   array(
                        'as'   => 'resend-confirmation',
                        'uses' => 'SignupController@resendEmailConfirmation'
                   )
        );
        Route::get('/confirm/{email}/{key}/{token}',
                   array(
                        'as'   => 'confirm-email',
                        'uses' => 'SignupController@confirmEmail'
                   )
        );

        Route::any('/demo/create',
            array(
                 'as'=>'process-demo-signup',
                 'uses'=>'SignupController@createDemoAccount'
            )
        );

    }
);
/*
|--------------------------------------------------------------------------
| Auth filtered Routes
|--------------------------------------------------------------------------
| These routes only work if you are logged in
|
 */
Route::group(array(
                  'namespace' => 'Analytics',
                  'domain'    => ROUTE_PREFIX . 'analytics.tailwindapp.' . ROUTE_TLD,
                  'prefix'    => 'signup',
                  'before' => 'AnalyticsAuth'
             ),
    function () {


    }
);



