<?php

/*
* Catalog Website Routes
*/
Route::group(array('domain' => ROUTE_PREFIX . 'www.tailwindapp.' . ROUTE_TLD), function () {

    Route::get('/', 'Catalog\StaticController@showIndex');
    Route::get('/a', 'Catalog\StaticController@showIndexA');
    Route::get('/b', 'Catalog\StaticController@showIndexB');
    Route::get('/c', 'Catalog\StaticController@showIndexC');

    Route::get('/about', 'Catalog\StaticController@showAbout');
    Route::get('/about/privacy', 'Catalog\StaticController@showPrivacy');
    Route::get('/about/terms', 'Catalog\StaticController@showTerms');

    Route::get('/agencies', 'Catalog\StaticController@showAgencies');

    Route::get('/features', 'Catalog\StaticController@showFeatures');
    Route::get('/features/pinterest-marketing', 'Catalog\StaticController@showFeaturesB');
    Route::get('/features/pinterest-tools', 'Catalog\StaticController@showFeaturesC');

    Route::get('/pricing', 'Catalog\StaticController@showPricing');
    Route::get('/pricing/packages', 'Catalog\StaticController@showNewPricing');
    Route::get('/pricing/plans', 'Catalog\StaticController@showPricingPlans');

    /*
    |--------------------------------------------------------------------------
    | Landing pages
    |--------------------------------------------------------------------------
    */
    Route::get('/pinreach', 'Catalog\StaticController@showPinreach');
    Route::get('/pinreach/welcome', 'Catalog\StaticController@showPinreachWelcome');

});