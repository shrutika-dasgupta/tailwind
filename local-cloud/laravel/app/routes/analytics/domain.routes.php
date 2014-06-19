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




/*
|--------------------------------------------------------------------------
| Domains
|--------------------------------------------------------------------------
*/
Route::group(
    array(
      'before'    => 'AnalyticsAuth',
      'namespace' => 'Analytics\Domain',
      'domain'    => ROUTE_PREFIX . 'analytics.tailwindapp.' . ROUTE_TLD,
      'prefix'    => 'domain'
    ),
    function()
    {
        Route::get('/topic-bar', array('as' => 'domain-topic-bar', 'uses' => 'BaseController@buildTopicBarSourceData'));

        Route::any('/add-domain', array('as' => 'domain-add', 'uses' => 'BaseController@addDomain'));
        Route::any('/add-topic', array('as' => 'domain-add-topic', 'uses' => 'BaseController@addTopic'));
        Route::delete('/remove-topic/{topic?}', array('as' => 'domain-remove-topic', 'uses' => 'BaseController@removeTopic'));

        Route::get('/tags', array('as' => 'domain-tags', 'uses' => 'BaseController@tags'));
        Route::post('/add-tag', array('as' => 'domain-add-tag', 'uses' => 'BaseController@addTag'));
        Route::delete('/remove-tag/{name?}', array('as' => 'domain-remove-tag', 'uses' => 'BaseController@removeTag'));

        Route::get('/wordcloud/{query}', array('as' => 'domain-wordcloud-snapshot', 'uses' => 'BaseController@buildKeywordsWordcloudSnapshot'));
        Route::get('/top-pinners/{query}', array('as' => 'domain-top-pinners-snapshot', 'uses' => 'BaseController@buildTopPinnersSnapshot'));

        /**
         * Traffic
         */
        Route::get('/traffic', array('as' => 'domain-traffic-default', 'uses' => 'TrafficController@showTrafficDefault'));
        Route::get('/traffic/{range}', array('as' => 'domain-traffic', 'uses' => 'TrafficController@showTraffic'));
        Route::get('/traffic/{range}/{start_date}/{end_date}',array('as' => 'domain-traffic-custom-date', 'uses' => 'TrafficController@showTraffic'));
        Route::get('/traffic/{startDate}/{endDate}/export/{type}',array('as' => 'domain-traffic-export', 'uses' => 'TrafficController@downloadTraffic'));

        /**
         * Trending Images
         */
        Route::get('/trending-images/{query?}', array('as' => 'domain-trending-images-default', 'uses' => 'TrendingImagesController@trendingImagesFeedDefault'));
        Route::get('/trending-images/{query}/{range?}', array('as' => 'domain-trending-images-range', 'uses' => 'TrendingImagesController@trendingImagesFeedDateRange'));
        Route::get('/trending-images/{query}/{start_date}/{end_date}', array('as' => 'domain-trending-images-custom-date', 'uses' => 'TrendingImagesController@trendingImagesFeedCustomDate'));

        /**
         * Benchmarks
         */
        Route::get('/benchmarks', array('as' => 'domain-competitor-benchmarks', 'uses' => 'BenchmarksController@showBenchmarksDefault'));
        Route::get(
             '/benchmarks/{range}', array('as' => 'domain-competitor-benchmarks-range', 'uses' => 'BenchmarksController@showBenchmarks')
        );
        Route::get(
             '/benchmarks/{range}/{start_date}/{end_date}', array('as' => 'domain-competitor-benchmarks-custom-date', 'uses' => 'BenchmarksController@showBenchmarks')
        );

        /**
         * Insights
         */
        Route::get('/', array('as' => 'domain', 'uses' => 'BaseController@index'));
        Route::get('/insights/{query?}', array('as' => 'domain-insights-default', 'uses' => 'InsightsController@insightsDefault'));
        Route::get('/insights/{query}/{start_date}/{end_date}', array('as' => 'domain-insights', 'uses' => 'InsightsController@insights'));

        /**
         * Feeds
         */
        Route::get('/{type}/{query?}', array('as' => 'domain-feed', 'uses' => 'FeedsController@feedDefault'));
        Route::get('/{type}/{query}/{start_date}/{end_date}', array('as' => 'domain-feed-custom', 'uses' => 'FeedsController@feed'));
    }
);

