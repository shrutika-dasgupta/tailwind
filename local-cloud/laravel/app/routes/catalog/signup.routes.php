<?php
/*
|--------------------------------------------------------------------------
| Signup Routes
|--------------------------------------------------------------------------
*/
$signup_route_config = [
    'domain'    => ROUTE_PREFIX . 'www.tailwindapp.' . ROUTE_TLD,
    'namespace' => 'Catalog',
    'prefix'    => 'signup'
];

Route::group($signup_route_config, function () {

    Route::get('/free/{username}', 'SignupController@showAdvice');
    Route::any('/leads', 'SignupController@storeLead');

    Route::get('/demo','SignupController@showDemo');
    Route::post('/demo/process','SignupController@processDemo');

});