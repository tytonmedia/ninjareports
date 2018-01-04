<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
 */
Route::get('/', 'WelcomeController@show')->name('welcome.show');
Route::get('/home', 'HomeController@show')->name('home.show');

Route::group(['middleware' => 'auth'], function () {

    // AccountsController Routes
    Route::prefix('accounts')->group(function () {
        Route::get('/', 'AccountsController@index')->name('accounts.index');
        Route::get('connect', 'AccountsController@connect')->name('accounts.connect');
    });

    // ConnectController Routes
    Route::prefix('connect')->group(function () {
        Route::get('test', 'ConnectController@test')->name('connect.test');
        Route::get('facebook', 'ConnectController@facebook')->name('connect.facebook');
        Route::get('facebook/callback', 'ConnectController@facebookCallback')->name('connect.facebook.callback');
        Route::get('analytics', 'ConnectController@analytics')->name('connect.analytics');
        Route::get('analytics/callback', 'ConnectController@analyticsCallback')->name('connect.analytics.callback');
        Route::get('adwords', 'ConnectController@adwords')->name('connect.adwords');
        Route::get('adwords/callback', 'ConnectController@adwordsCallback')->name('connect.adwords.callback');
    });

    // ReportsController Routes
    Route::prefix('reports')->group(function () {
        Route::get('/', 'ReportsController@index')->name('reports.index');
        Route::get('create', 'ReportsController@create')->name('reports.create');
        Route::post('store', 'ReportsController@store')->name('reports.store');
    });
});
