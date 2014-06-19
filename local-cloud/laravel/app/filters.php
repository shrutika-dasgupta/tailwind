<?php

/*
|--------------------------------------------------------------------------
| Application & Route Filters
|--------------------------------------------------------------------------
|
| Below you will find the "before" and "after" events for the application
| which may be used to do any work before or after a request into your
| application. Here you may also register your custom route filters.
|
*/

App::before(function ($request) {
    //
});


App::after(function ($request, $response) {
    //
});

/*
|--------------------------------------------------------------------------
| Authentication Filters
|--------------------------------------------------------------------------
|
| The following filters are used to verify that the user of the current
| session is logged into this application. The "basic" filter easily
| integrates HTTP Basic authentication for quick, simple checking.
|
*/

Route::filter('AdminAuth', function () {
    if (Auth::guest()) return Redirect::to('login');
});

Route::filter('AnalyticsAuth', function () {

    $user = User::getLoggedInUser();
    if ($user) {

        if($user->force_logout) {
            $user->force_logout = 0;
            $user->insertUpdateDB();

            Session::clear();
            Session::flush();

            return Redirect::to('login')
                           ->with('flash_alert', 'Oh no! You\'ve been logged out. Please log in again.')
                           ->with('redirect_to', '');
        }
    } else {

        return Redirect::to('login')
        ->with('redirect_to',Request::path());
    }
});

/*
|--------------------------------------------------------------------------
| Guest Filter
|--------------------------------------------------------------------------
|
| The "guest" filter is the counterpart of the authentication filters as
| it simply checks that the current user is not logged in. A redirect
| response will be issued if they are, which you may freely change.
|
*/

Route::filter('AdminGuest', function () {
    if (Auth::check()) return Redirect::to('dashboard');
});
Route::filter('AnalyticsGuest', function () {
    if (User::getLoggedInId()) {
        return Redirect::route('dashboard');
    }
});

/*
|--------------------------------------------------------------------------
| CSRF Protection Filter
|--------------------------------------------------------------------------
|
| The CSRF filter is responsible for protecting your application against
| cross-site request forgery attacks. If this special token in a user
| session does not match the one given in this request, we'll bail.
|
*/

Route::filter('csrf', function () {
    if (Session::token() != Input::get('_token')) {
        throw new Illuminate\Session\TokenMismatchException;
    }
});

/*
|--------------------------------------------------------------------------
| SSL Filter
|--------------------------------------------------------------------------
*/
Route::filter('ssl', function () {

    if( ! Request::secure())
    {
        return Redirect::secure(Request::path());
    }

});