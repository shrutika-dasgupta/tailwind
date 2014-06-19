<?php

Route::group(
     array(
          'before'    => 'AnalyticsAuth',
          'namespace' => 'Analytics',
          'domain'    => ROUTE_PREFIX . 'analytics.tailwindapp.' . ROUTE_TLD,
          'prefix'    => 'discover'
     ),
     function () {
         Route::get('/topic-bar', array('as' => 'discover-topic-bar', 'uses' => 'DiscoverController@buildTopicBarSourceData'));

         Route::any('/add-topic', array('as' => 'discover-add-topic', 'uses' => 'DiscoverController@addTopic'));
         Route::delete('/remove-topic/{topic?}', array('as' => 'discover-remove-topic', 'uses' => 'DiscoverController@removeTopic'));

         Route::get('/tags', array('as' => 'discover-tags', 'uses' => 'DiscoverController@tags'));
         Route::post('/add-tag', array('as' => 'discover-add-tag', 'uses' => 'DiscoverController@addTag'));
         Route::delete('/remove-tag/{name?}', array('as' => 'discover-remove-tag', 'uses' => 'DiscoverController@removeTag'));

         Route::get('/wordcloud/{query}', array('as' => 'discover-wordcloud-snapshot', 'uses' => 'DiscoverController@buildKeywordsWordcloudSnapshot'));
         Route::get('/top-pinners/{query}', array('as' => 'discover-top-pinners-snapshot', 'uses' => 'DiscoverController@buildTopPinnersSnapshot'));

         Route::get('/', array('as' => 'discover', 'uses' => 'DiscoverController@index'));
         Route::get('/insights/{query?}', array('as' => 'discover-insights', 'uses' => 'DiscoverController@insights'));
         Route::get('/{type}/{query?}', array('as' => 'discover-feed', 'uses' => 'DiscoverController@feed'));
     }
);