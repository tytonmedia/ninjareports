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
Route::get('/', 'HomeController@show')->name('welcome.show');
Route::get('/home', 'HomeController@show')->name('home.show');

// CronController Routes
Route::prefix('cron')->group(function () {
    Route::get('run', 'CronController@run')->name('cron.run');
    Route::get('report/{id}', 'CronController@report')->name('cron.report');
});

Route::group(['middleware' => 'auth'], function () {

    // AccountsController Routes
    Route::prefix('accounts')->group(function () {
        Route::get('/', 'AccountsController@index')->name('accounts.index');
        Route::get('connect', 'AccountsController@connect')->name('accounts.connect');
        Route::get('settings/{type}', 'AccountsController@setting')->name('accounts.setting');
        Route::get('sync/{type}/adaccounts', 'AccountsController@sync')->name('accounts.sync.adaccounts');
        Route::get('delete/{id}', 'AccountsController@delete')->name('account.delete');
        Route::get('addelete/{id}', 'AccountsController@addelete')->name('adaccount.delete');
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
        Route::get('testreport/{account_type}', 'ReportsController@testreport')->name('reports.testreport');
        Route::get('create', 'ReportsController@create')->name('reports.create');
        Route::post('store', 'ReportsController@store')->name('reports.store');
        Route::get('edit/{id}', 'ReportsController@edit')->name('reports.edit');
        Route::post('{id}/pause/{status}', 'ReportsController@status')->name('reports.status');
        Route::post('update/{id}', 'ReportsController@update')->name('reports.update');
        Route::get('delete/{id}', 'ReportsController@destroy')->name('reports.delete');
        Route::get('{type}/adaccounts', 'ReportsController@ad_accounts')->name('reports.adaccounts');
        Route::get('{type}/properties/{account}', 'ReportsController@properties')->name('reports.properties');
        Route::get('{type}/properties/{account}/profiles/{property}', 'ReportsController@profiles')->name('reports.profiles');

    });
});
