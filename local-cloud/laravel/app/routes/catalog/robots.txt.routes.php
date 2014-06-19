<?php
Route::group(array('domain' => ROUTE_PREFIX . 'www.tailwindapp.' . ROUTE_TLD), function () {
    /*
    |--------------------------------------------------------------------------
    | Robots Files
    |--------------------------------------------------------------------------
    */
    Route::get('/robots.txt', function () {
        $host = parse_url(Request::url(), PHP_URL_HOST);

        if ($host === 'www.tailwindapp.com') {
            $content = View::make('catalog.robots');
        } else {
            $content =
                "User-agent: *\n".
                "Disallow: /";
        }
        $response = Response::make($content, 200);

        $response->header('Content-Type', 'text/plain');

        return $response;

    });

});