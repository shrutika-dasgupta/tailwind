<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::get('/', function()
{
	return View::make('hello');
});

Route::get('hi', function()
{
	return View::make('helloTest');
});


Route::any('users',function()
{
	return 'Users!';
});

Route::any('authors','TestController@get_index');

Route::any('restaurants','RestTestController@get_restro_list');

Route::any('display','RestTestController@display_list');

Route::any('getData','RestTestController@get_Data');