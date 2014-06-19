<?php

Route::group(
     array(
          'namespace' => 'Analytics',
          'domain'    => ROUTE_PREFIX . 'analytics.tailwindapp.' . ROUTE_TLD,
          'prefix'    => 'oauth',
          'before'    => 'ssl',
     ),
         function () {
             Route::get('/pinterest/response', 'OAuthController@pinterestHandshake');
         }
);