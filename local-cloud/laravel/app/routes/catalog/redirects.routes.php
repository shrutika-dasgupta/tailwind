<?php
Route::group(array('domain' => ROUTE_PREFIX . 'www.tailwindapp.' . ROUTE_TLD), function () {
    /*
    |--------------------------------------------------------------------------
    | Redirects
    |--------------------------------------------------------------------------
    */
    Route::get('/login', function () {
        return Redirect::to('http://' . ROUTE_PREFIX . 'analytics.tailwindapp.' . ROUTE_TLD . '/login', 301);
    });
    Route::get('/blog', function () {
        return Redirect::to('http://blog.tailwindapp.com', 301);
    });
    Route::get('/jobs', function () {
        return Redirect::to('/about/#hiring', 301);
    });
    Route::get('/about/careers', function () {
        return Redirect::to('/about/#hiring', 301);
    });
    Route::get('/faq', function () {
        return Redirect::to('/pricing#faq', 301);
    });

});