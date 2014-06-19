<?php
/**
 * Routes for the /publisher and publisher API endpoints.
 *
 * @author  Daniel
 * @author  Will
 * @author  Yesh
 * @author  Alex
 * @author  Janell
 */

/**
 * Unfiltered routes
 */
Route::get(
     '/assets/pin/{uploaded_pin_id}/',
     array(
          'as'   => 'publisher-public-uploaded-post',
          'uses' => 'Analytics\PublisherController@showUploadedPost'
     )
)    ->where('uploaded_pin_id', '[0-9]+');

/**
 * App Routes.
 */
Route::group(
    array(
        'domain'    => ROUTE_PREFIX . 'analytics.tailwindapp.' . ROUTE_TLD,
        'prefix'    => 'publisher',
        'namespace' => 'Analytics',
    ),
    function () {
        Route::post('/draft-posts', array(
            'as'   => 'publisher-draft-posts',
            'uses' => 'PublisherController@draftPosts'
        ));

        /**
         * Routes in this group REQUIRE authentication.
         */
        Route::group(
            array('before' => 'AnalyticsAuth',),
            function () {
                Route::get('/', array(
                    'as'   => 'publisher',
                    'uses' => 'PublisherController@posts',
                ));

                Route::get('/posts/{view?}/{layout?}', array(
                    'as'   => 'publisher-posts',
                    'uses' => 'PublisherController@posts',
                ));

                Route::get('/post/new', array(
                    'as'   => 'publisher-new-post',
                    'uses' => 'PublisherController@newPost',
                ));

                Route::post('/post/new-draft', array(
                    'as'   => 'publisher-new-draft-post',
                    'uses' => 'PublisherController@newDraftPost',
                ));

                Route::get('/post/new/upload', array(
                     'as'   => 'publisher-new-upload',
                     'uses' => 'PublisherController@showUploadPost'
                ));

                Route::post('/post/upload',array(
                     'as'   => 'publisher-process-upload',
                     'uses' => 'PublisherController@processUpload'
                ));

                Route::post('/post/create', array(
                    'as'   => 'publisher-create-post',
                    'uses' => 'PublisherController@createPost',
                ));

                Route::get('/post/{id}/edit', array(
                    'as'   => 'publisher-edit-post',
                    'uses' => 'PublisherController@editPost',
                ));

                Route::post('/post/update', array(
                    'as'   => 'publisher-update-post',
                    'uses' => 'PublisherController@updatePost',
                ));

                Route::post('/post/publish', array(
                    'as'   => 'publisher-publish-post',
                    'uses' => 'PublisherController@publishPost',
                ));

                Route::post('/post/approve', array(
                    'as'   => 'publisher-approve-post',
                    'uses' => 'PublisherController@approvePost',
                ));

                Route::post('/posts/order', array(
                    'as'   => 'publisher-order-posts',
                    'uses' => 'PublisherController@orderPosts',
                ));

                Route::post('/draft/delete', array(
                    'as'   => 'publisher-delete-draft',
                    'uses' => 'PublisherController@deleteDraft'
                ));

                Route::get('/schedule', array(
                    'as'   => 'publisher-schedule',
                    'uses' => 'PublisherController@schedule',
                ));

                Route::get('/tools', array(
                    'as'   => 'publisher-tools',
                    'uses' => 'PublisherController@tools',
                ));

                Route::get('/permissions', array(
                    'as'   => 'publisher-permissions',
                    'uses' => 'PublisherController@permissions',
                ));

                Route::post('/permissions/update', array(
                    'as'   => 'publisher-update-permissions',
                    'uses' => 'PublisherController@updatePermissions',
                ));
            }
        );
    }
);

/**
 * API Routes.
 */
Route::group(
    array(
        'domain'    => ROUTE_PREFIX . 'analytics.tailwindapp.' . ROUTE_TLD,
        'prefix'    => 'api',
        'namespace' => 'Analytics\Api',
        'before'    => 'AnalyticsAuth',
    ),
    function () {
        Route::get('/posts', array(
            'as'   => 'api-publisher-get-posts',
            'uses' => 'PublisherController@getPosts',
        ));

        Route::get('/post/{id}/', array(
            'as'   => 'api-publisher-get-post',
            'uses' => 'PublisherController@getPost',
        ));

        Route::post('/post/create', array(
            'as'   => 'api-publisher-create-post',
            'uses' => 'PublisherController@createPost',
        ));

        Route::get('/post/{id}/delete', array(
            'as'   => 'api-publisher-delete-post',
            'uses' => 'PublisherController@deletePost',
        ));

        Route::get('/time-slot/{id}', array(
            'as'   => 'api-publisher-get-time-slot',
            'uses' => 'PublisherController@getTimeSlot',
        ));

        Route::post('/time-slot/create', array(
            'as'   => 'api-publisher-create-time-slot',
            'uses' => 'PublisherController@createTimeSlot',
        ));

        Route::get('/time-slot/{id}/delete', array(
            'as'   => 'api-publisher-delete-time-slot',
            'uses' => 'PublisherController@deleteTimeSlot',
       ));
    }
);