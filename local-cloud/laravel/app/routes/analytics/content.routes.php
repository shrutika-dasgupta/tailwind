<?php

Route::group(
    array(
        'before'    => 'AnalyticsAuth',
        'namespace' => 'Analytics',
        'domain'    => ROUTE_PREFIX . 'analytics.tailwindapp.' . ROUTE_TLD,
        'prefix'    => 'content'
    ),
    function() {
        Route::get('/', array('as' => 'content', 'uses' => 'ContentController@index'));
        Route::any('/entry/{entry_id}/flag', array('as' => 'content-flag-entry', 'uses' => 'ContentController@flagEntry'));
        Route::get('/{query}/{page}/{num?}', array('as' => 'content-paginate', 'uses' => 'ContentController@paginate'));
    }
);