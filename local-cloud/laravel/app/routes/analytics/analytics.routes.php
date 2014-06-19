<?php
/**
|--------------------------------------------------------------------------
| Routes outside the "Auth Bubble"
|--------------------------------------------------------------------------
|
| These routes will redirect to the "dashboard" if the user is logged in
| said another way - only logged out users can get to these routes
|
| @see AnalyticsGuest filter
 */

Route::group(array(
                  'domain'    => ROUTE_PREFIX . 'analytics.tailwindapp.' . ROUTE_TLD,
                  'before'    => 'AnalyticsGuest',
                  'namespace' => 'Analytics'
             ),
    function () {
        Route::get('/login',
                   array(
                        'as'   => 'login',
                        'uses' => 'AuthController@showLogin'
                   )
        );
    }
);

/**
|--------------------------------------------------------------------------
| Unfiltered Routes
|--------------------------------------------------------------------------
| These routes work regardless of auth status
|
*/

Route::group(
    array(
         'domain' => ROUTE_PREFIX . 'analytics.tailwindapp.' . ROUTE_TLD,
         'namespace' => 'Analytics'
    ),
    function () {
        /*
         * The login check has to be outside the auth filter so that
         * we can log out the current user when trying to log in. Otherwise the old
         * logged in user doesn't get logged out
         */
        Route::any('/login/check', 'AuthController@processLogin');

        /*
        |--------------------------------------------------------------------------
        | Thank You Page
        |--------------------------------------------------------------------------
        */
        Route::get('/thank-you', 'ThankYouController@showThanks');

        /*
        |--------------------------------------------------------------------------
        | Signup Form
        |--------------------------------------------------------------------------
        */
        Route::get('/signup', 'SignupController@showSignupForm');
        Route::post('/signup/process', 'SignupController@processSignupForm');
        Route::post('/signup/free','SignupController@createFreeAccount');

        /*
        |--------------------------------------------------------------------------
        | Logins
        |--------------------------------------------------------------------------
        */
        Route::get('/login/auto/{email}/{key}', 'AuthController@autoLogin');

        /*
        |--------------------------------------------------------------------------
        | Password Reset
        |--------------------------------------------------------------------------
        */
        Route::get('/password-reset', 'AuthController@showPasswordResetDefault');
        Route::get('/password-reset/{result}', 'AuthController@showPasswordReset');


        Route::post('/password-reset/check', 'AuthController@processPasswordResetEmail');
        Route::get('/password-reset/form/{uid}/{rid}', 'AuthController@showPasswordResetForm');
        Route::post('/password-reset/process', 'AuthController@processPasswordReset');

        /*
        |--------------------------------------------------------------------------
        | Accept Invitation
        |--------------------------------------------------------------------------
        */
        Route::get('/invitation/', 'AuthController@showAcceptInviteDefault');
        Route::get('/invitation/{accept}', 'AuthController@showAcceptInviteError');
        Route::get('/invitation/{accept}/{token}', 'AuthController@showAcceptInvite');

        /*
        |--------------------------------------------------------------------------
        | Legacy Redirect
        |--------------------------------------------------------------------------
        */
        Route::any('/{page}.php',function($page) {
            return Redirect::to('/'.$page);
        });

        /*
        |--------------------------------------------------------------------------
        | AJAX Scripts
        |--------------------------------------------------------------------------
        */
        Route::get('/ajax/check-username/{username}', 'AjaxController@checkUsername');
    }
);

/*
|--------------------------------------------------------------------------
| Routes inside the "Auth Bubble"
|--------------------------------------------------------------------------
|
| These routes will redirect to the "login" if the user is not logged in
| said another way - only logged in users can get to these routes
|
| @see AnalyticsAuth filter
*/
Route::group(
    array('domain' => ROUTE_PREFIX . 'analytics.tailwindapp.' . ROUTE_TLD,
          'before' => 'AnalyticsAuth'),
    function () {

        /*
        |--------------------------------------------------------------------------
        | Logout
        |--------------------------------------------------------------------------
        */
        Route::get('/logout',
            array('as'   => 'logout',
                  'uses' => 'Analytics\AuthController@processLogout')
        );


        /*
        |--------------------------------------------------------------------------
        | Dashboard Controller
        |--------------------------------------------------------------------------
        */
        Route::get('/',
                   array(
                        'as' => 'dashboard',
                        'uses' => 'Analytics\DashboardController@showSingleAccountDashboard'
                   ));



        /*
        |--------------------------------------------------------------------------
        | Boards Controller
        |--------------------------------------------------------------------------
        */

        Route::post('/board/create','Analytics\Api\PublisherController@createBoard');

        Route::get('/boards', 'Analytics\BoardsController@showBoardsDefault');
        Route::get(
             '/boards/{range}',
                 'Analytics\BoardsController@showBoards'
        );

        Route::get(
             '/boards/{range}/{start_date}/{end_date}',
                 'Analytics\BoardsController@showBoards'
        );
        Route::get(
            '/boards/{start_date}/{end_date}/export/{type}',
            'Analytics\BoardsController@downloadBoards'
        );

        /*
        |--------------------------------------------------------------------------
        | Website Controller
        |--------------------------------------------------------------------------
        */
        Route::get('/website', 'Analytics\WebsiteController@showWebsiteDefault');
        Route::get(
            '/website/{startDate}/{endDate}',
            'Analytics\WebsiteController@showWebsite'
        );

        Route::get(
            '/website/{startDate}/{endDate}/export/{type}',
            'Analytics\WebsiteController@downloadWebsite'
        );
        Route::post('/website/add', 'Analytics\WebsiteController@addDomain');




        /*
        |--------------------------------------------------------------------------
        | Categories
        |--------------------------------------------------------------------------
        */

        Route::get('/categories',array('as'=>'categories','uses'=> 'Analytics\CategoriesController@showCategories'));
        Route::get('/categories/export/{type}', 'Analytics\CategoriesController@downloadCategories');

        /*
        |--------------------------------------------------------------------------
        |  Days + times
        |--------------------------------------------------------------------------
        */
        Route::get('/days-and-times',array('as'=>'peak-days','uses'=> 'Analytics\DaysTimesController@showPeakDaysAndTimes'));



        Route::group(array('namespace' => 'Analytics' ), function()
        {
            Route::get('/demo/{plan}','SignupController@toggleDemo');
            /*
            |--------------------------------------------------------------------------
            | Profile Controller
            |--------------------------------------------------------------------------
            */
            Route::get(
                 '/profile',
                 array(
                      'as'=>'profile',
                      'uses'=>'ProfileController@showProfileDefault'
                 )
            );
            Route::get('/profile/new',
                        array(
                             'as' => 'dashboardnew',
                             'uses' => 'ProfileController@showProfileDefault'
                        )
            );

            Route::get('/profile/demo/new',
                array(
                     'as' => 'dashboarddemo',
                     'uses' => 'ProfileController@showProfileDefault'
                )
            );

            Route::get(
                 '/profile/{range}',
                 'ProfileController@showProfile'
            );

            Route::get(
                 '/profile/{range}/{start_date}/{end_date}',
                 'ProfileController@showProfile'
            );
            Route::get(
                 '/profile/{startDate}/{endDate}/export/{type}',
                 'ProfileController@downloadProfile'
            );

            /*
            |--------------------------------------------------------------------------
            | Influencers
            |--------------------------------------------------------------------------
            */
            Route::get(
                 '/top-repinners',
                 array(
                      'as'   => 'top-repinners',
                      'uses' => 'InfluencersController@showTopRepinnersDefault'
                 )
            );

            Route::get(
                 '/most-valuable-pinners',
                 array(
                      'as'   => 'most-valuable-pinners',
                      'uses' => 'InfluencersController@showMostValuablePinners'
                 )
            );

            Route::get(
                 '/followers/newest',
                 array(
                      'as' => 'newest-followers',
                      'uses' => 'InfluencersController@showNewestFollowers'
                 )
            );

            Route::get(
                 '/followers/influential',
                 array(
                      'as'=>'influential-followers',
                      'uses'=> 'InfluencersController@showInfluentialFollowers'
                 )
            );
            Route::get(
                 '/followers/export/{type}',
                 'InfluencersController@downloadInfluentialFollowers'
            );

            Route::get(
                 '/domain-pinners',
                     array(
                          'as'=>'domain-pinners',
                          'uses'=> 'InfluencersController@showTopPinnersDefault'
                     )
            );
            Route::get(
                 '/domain-pinners/{range}',
                 'InfluencersController@showTopPinners'
            );
            Route::get(
                 '/domain-pinners/{range}/export/{type}',
                 'InfluencersController@downloadTopPinners'
            );


            /*
             * Have /brand-pinners do the same thing as /domain-pinners.
             */
            Route::get(
                 '/brand-pinners',
                     array(
                          'as'=>'domain-pinners',
                          'uses'=> 'InfluencersController@showTopPinnersDefault'
                     )
            );
            Route::get(
                 '/brand-pinners/{range}',
                     'InfluencersController@showTopPinners'
            );
            Route::get(
                 '/brand-pinners/{range}/export/{type}',
                     'InfluencersController@downloadTopPinners'
            );



            Route::get(
                 '/top-repinners/{range}',
                 'InfluencersController@showTopRepinners'
            );
            Route::get(
                 '/top-repinners/{range}/export/{type}',
                 'InfluencersController@downloadTopRepinners'
            );

            Route::get(
                 '/most-valuable-pinners/export/{type}',
                 'InfluencersController@downloadMostValuablePinners'
            );

            /*
            |--------------------------------------------------------------------------
            | Pins
            |--------------------------------------------------------------------------
            */
            Route::get(
                 '/pins/owned',
                 array(
                      'as'=> 'owned-pins',
                      'uses' => 'PinsController@showOwnedPinsDefault'
                 )
            );
            Route::get('/pins/owned/board/{board_id_piece}', 'PinsController@showOwnedBoardPins');

            Route::get(
                 '/pins/owned/trending',
                 array(
                      'as'=> 'owned-trending-pins',
                      'uses' => 'PinsController@showTrendingOwnedPinsDefault'
                 )
            );
            Route::get('/pins/owned/trending/{start_date}/{end_date}', 'PinsController@showTrendingOwnedPins');

            Route::get(
                 '/pins/domain/trending',
                 array(
                      'as' => 'trending-images',
                      'uses' => 'TrendingPinsController@showTrendingPinsDefault'
                 )
            );
            Route::get(
                 '/pins/domain/trending/{range}',
                 'TrendingPinsController@showTrendingPins'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Geo
        |--------------------------------------------------------------------------
        */

        Route::group(array('namespace' => 'Analytics', 'prefix' => 'geo'), function()
        {
            Route::get('/city/timezone', array(
                'as'   => 'geo-city-timezone',
                'uses' => 'GeoController@getCityTimezone',
            ));
            Route::get('/cities', array(
                'as'   => 'geo-cities',
                'uses' => 'GeoController@getCities',
            ));
        });

        /*
        |--------------------------------------------------------------------------
        | Competitor Controller
        |--------------------------------------------------------------------------
        */

        Route::get('/competitors/benchmarks',array('as'=>'profile-competitor-benchmarks','uses'=> 'Analytics\CompetitorController@showBenchmarksDefault'));
        Route::get(
             '/competitors/benchmarks/{range}',
                 'Analytics\CompetitorController@showBenchmarks'
        );

        Route::get(
             '/competitors/benchmarks/{range}/{start_date}/{end_date}',
                 'Analytics\CompetitorController@showBenchmarks'
        );
        Route::get(
            '/competitors/benchmarks/{startDate}/{endDate}/export/{type}',
            'Analytics\CompetitorController@downloadBenchmarks'
        );

        /*
        |--------------------------------------------------------------------------
        | Profile Settings
        |--------------------------------------------------------------------------
        */
        Route::group(array('namespace' => 'Analytics\Settings', 'prefix' => 'settings'), function()
        {
            Route::get('/', array('as' => 'settings-index', 'uses' => 'ProfileController@show'));
            Route::get('/profile', array(
                'as'   => 'settings-profile',
                'uses' => 'ProfileController@show',
            ));
            Route::post('/profile/edit', array(
                'as'   => 'settings-profile-edit',
                'uses' => 'ProfileController@edit',
            ));
            Route::post('/profile/timezone/update', array(
                'as'   => 'settings-profile-update-timezone',
                'uses' => 'ProfileController@updateTimezone',
            ));
            Route::post('/profile/location/update', array(
                'as'   => 'settings-profile-update-location',
                'uses' => 'ProfileController@updateLocation',
            ));
        });

        /*
        |--------------------------------------------------------------------------
        | Account Settings
        |--------------------------------------------------------------------------
        */
        Route::get('/settings/accounts', 'Analytics\Settings\AccountsController@show');
        Route::post('/settings/account/edit', 'Analytics\Settings\AccountsController@edit');
        Route::post('/settings/account/add', 'Analytics\Settings\AccountsController@add');
        Route::any(
            '/settings/account/{account_id}/remove',
            'Analytics\Settings\AccountsController@remove'
        );

        /*
        |--------------------------------------------------------------------------
        | Competitors Settings
        |--------------------------------------------------------------------------
        */
        Route::get('/settings/competitors', 'Analytics\Settings\CompetitorsController@show');
        Route::post('/settings/competitor/add', 'Analytics\Settings\CompetitorsController@add');
        Route::get(
            '/settings/competitor/{id}/remove',
            'Analytics\Settings\CompetitorsController@remove'
        );

        /*
        |--------------------------------------------------------------------------
        | Collaborator Settings
        |--------------------------------------------------------------------------
        */
        Route::group(array('namespace' => 'Analytics\Settings', 'prefix' => 'settings/collaborators'), function()
        {
            Route::get('/', array('as' => 'collaborators', 'uses' => 'CollaboratorsController@show'));
            Route::post('/invite', array(
                'as'   => 'collaborators-invite',
                'uses' => 'CollaboratorsController@invite',
            ));
            Route::post('/remove', array(
                'as'   => 'collaborators-remove',
                'uses' => 'CollaboratorsController@remove',
            ));
            Route::post('/edit', array(
                'as'   => 'collaborators-edit',
                'uses' => 'CollaboratorsController@edit',
            ));
        });

        /*
        |--------------------------------------------------------------------------
        | Google Analytics
        |--------------------------------------------------------------------------
        */
        Route::get('/settings/google-analytics', 'Analytics\Settings\GAController@show');
        Route::post('/settings/google-analytics/edit', 'Analytics\Settings\GAController@edit');
        Route::any('/settings/google-analytics/sync', 'Analytics\Settings\GAController@sync');
        Route::post('/settings/google-analytics/resync', 'Analytics\Settings\GAController@resync');
        Route::post('/settings/google-analytics/select-profile', 'Analytics\Settings\GAController@selectProfile');

        /*
        |--------------------------------------------------------------------------
        | Email Settings
        |--------------------------------------------------------------------------
        */
        Route::get('/settings/notifications', 'Analytics\Settings\NotificationsController@showNotifications');
        Route::post('/settings/notifications/update', 'Analytics\Settings\NotificationsController@editNotifications');

        /*
        |--------------------------------------------------------------------------
        | Billing Settings
        |--------------------------------------------------------------------------
        */
        Route::group(array('namespace' => 'Analytics\Settings', 'prefix' => 'settings/billing'), function()
        {
            Route::get('/', array('as' => 'billing', 'uses' => 'BillingController@subscription'));
            Route::get('/statements', array(
                'as'   => 'billing-statements',
                'uses' => 'BillingController@statements',
            ));
            Route::get('/statement/{id}', array(
                'as'   => 'billing-statement',
                'uses' => 'BillingController@statement',
            ));
            Route::get('/change-plan/{plan_slug}', array(
                'as'   => 'billing-change-plan',
                'uses' => 'BillingController@changePlan',
            ));
            Route::get('/downgrade-survey', array(
                'as'   => 'billing-downgrade-survey',
                'uses' => 'BillingController@downgradeSurvey',
            ));
            Route::post('/downgrade', array(
                'as'   => 'billing-downgrade',
                'uses' => 'BillingController@downgrade',
            ));
            Route::get('/cancel-downgrade', array(
                'as'   => 'billing-cancel-downgrade',
                'uses' => 'BillingController@cancelDowngrade',
            ));
            Route::get('/downgrade-confirmation', array(
                'as'   => 'billing-downgrade-confirmation',
                'uses' => 'BillingController@downgradeConfirmation',
            ));
        });

        Route::get('/upgrade', 'Analytics\Settings\BillingController@showUpgrade');

        /*
        |--------------------------------------------------------------------------
        | Static Pages
        |--------------------------------------------------------------------------
        */
        Route::get('/faq', 'Analytics\StaticController@showFAQ');

        /*
        |--------------------------------------------------------------------------
        | AJAX Scripts
        |--------------------------------------------------------------------------
        */
        Route::get('/ajax/check-image-processing', 'Analytics\AjaxController@checkImageProcessing');
        Route::get('/ajax/check-new-user-data', 'Analytics\AjaxController@checkNewUserData');
        Route::post('/ajax/get-pin-history', 'Analytics\AjaxController@getPinHistory');
        Route::get('/ajax/get-category-boards/{category}/{username}', 'Analytics\AjaxController@getCategoryBoards');
        Route::any('/v1/user/history/record/event', array('as' => 'api-record-event', 'uses' => 'Analytics\AjaxController@recordEvent'));
        Route::any('/upgrade/modal/{report}','Analytics\AjaxController@showUpgradeModal');



        /*
        |--------------------------------------------------------------------------
        | API Test Scripts
        |--------------------------------------------------------------------------
        */
        Route::get('/testing/call-test', 'Analytics\TestController@showApiTest');

        /*
        |--------------------------------------------------------------------------
        |
        |--------------------------------------------------------------------------
        */
        Route::group(array(
                          'namespace' => 'Analytics'
                     ),
            function () {
                Route::get('/settings/tasks',
                           array(
                                'as'   => 'tasks',
                                'uses' => 'Settings\AccountsController@showTasks'
                           )
                );

                Route::get('/settings/profiles/refresh',
                    array(
                         'as' => 'refresh-profiles',
                         'uses'=> 'Settings\AccountsController@refreshProfilesData'
                    )
                );


            }
        );
    }
);

/**
 * External Pinterest URLs.
 */
Route::group(
    array('domain' => '//pinterest.com'),
    function() {
        Route::get('/pin/{id}', array('as' => 'pinterest-pin'));
        Route::get('/pin/create/button', array('as' => 'pinterest-pin-it'));
    }
);

Route::any('/admin/search','Analytics\AjaxController@searchUsers');
Route::any('/admin/switch','Analytics\AjaxController@switchAccounts');
