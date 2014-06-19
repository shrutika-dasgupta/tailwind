<?php

Route::group(
    array(
         'domain' => ROUTE_PREFIX . 'api.tailwindapp.' . ROUTE_TLD,
    ), function () {

    /*
    |--------------------------------------------------------------------------
    | In-app + Mobile API
    |--------------------------------------------------------------------------
    */
    Route::get('/login', 'API\AuthController@processLogin');

    //Route::any('/v1/user/history/record/event', array('as' => 'api-record-event', 'uses' => 'API\UserHistoryController@recordEvent'));

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    */
    Route::any('/chargify', 'API\WebhookController@parseChargify');
    Route::any('/mailgun', 'API\WebhookController@parseMailgun');
    Route::any('/bookmarklet', 'API\WebhookController@parseBookmarklet');
    Route::any('/mailchimp', 'API\WebhookController@parseMailchimp');
});
