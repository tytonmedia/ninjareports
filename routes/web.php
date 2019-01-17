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
    Route::get('send-report','ReportSenderController@run');
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
    Route::prefix('connect')->name('connect.')->group(function () {
        Route::get('test', 'ConnectController@test')->name('test');
        Route::get('facebook', 'ConnectController@facebook')->name('facebook');
        Route::get('facebook/callback', 'ConnectController@facebookCallback')->name('facebook.callback');
        Route::get('analytics', 'ConnectController@analytics')->name('analytics');
        Route::get('analytics/callback', 'ConnectController@analyticsCallback')->name('analytics.callback');
        Route::get('adwords', 'ConnectController@adwords')->name('adwords');
        Route::get('adwords/callback', 'ConnectController@adwordsCallback')->name('adwords.callback');
        Route::get('stripe', 'ConnectController@stripe')->name('stripe');
        Route::get('stripe/callback', 'ConnectController@stripeCallback')->name('stripe.callback');
        Route::get('google-search', 'ConnectController@googleSearch')->name('google.search');
        Route::get('google-search/callback', 'ConnectController@googleSearchCallback')->name('google.search.callback');
    });

    // ReportsController Routes
    Route::prefix('reports')->group(function () {
        Route::get('/', 'ReportsController@index')->name('reports.index');
        // new report page
        Route::get('main', 'ReportsController@reports_index')->name('reports.main');
        // new report page end
        Route::get('create', 'ReportsController@create')->name('reports.create');
        Route::post('store', 'ReportsController@store')->name('reports.store');
        Route::get('edit/{id}', 'ReportsController@edit')->name('reports.edit');
        Route::post('{id}/pause/{status}', 'ReportsController@status')->name('reports.status');
        Route::post('update/{id}', 'ReportsController@update')->name('reports.update');
        Route::get('delete/{id}', 'ReportsController@destroy')->name('reports.delete');
        Route::get('{type}/adaccounts', 'ReportsController@ad_accounts')->name('reports.adaccounts');
        Route::get('{type}/properties/{account}', 'ReportsController@properties')->name('reports.properties');
        Route::get('{type}/properties/{account}/profiles/{property}', 'ReportsController@profiles')->name('reports.profiles');
        Route::get('tester/{account_type}', 'ReportsController@tester')->name('reports.tester');
        // new report page
        Route::get('choose-template','ReportsController@template')->name('reports.chooseTemplate');
        Route::get('settings/{slug}','ReportsController@settings')->name('reports.templateSettings');
        Route::post('settings/{slug}/store','ReportsController@settingsStore')->name('reports.settingsStore');
        Route::get('settings/{slug}/{id}','ReportsController@editSettings')->name('reports.editSettings');
        Route::post('settings/{slug}/{id}/update','ReportsController@updateSettings')->name('reports.updateSettings');
        Route::get('{type}/account_properties/{account}', 'ReportsController@getProperties')->name('reports.getProperties');
        Route::get('{type}/account_properties/{account}/profiles/{property}', 'ReportsController@getProfiles')->name('reports.getProfiles');
        Route::post('{id}/status/{status}', 'ReportsController@postStatus')->name('reports.postStatus');
        Route::get('remove/{id}', 'ReportsController@remove')->name('reports.remove');
        // new report page end
    });

    Route::get('user/me/integrations','Api\UserController@getIntegrations');
});

Route::get('send-report-test',function(){
    $report = \App\Models\NinjaReport::with('template','accounts.ad_account.account','user')
            ->whereHas('template',function ($query) {
                $query->where('slug','seo-report');
            })
            ->first();
    (new \App\Services\Report\ReportSender)->send($report);
});

Route::get('report-data-test',function () {
    $accounts = [
            'google_analytics' => [
                'profile_id' => '',
                'access_token' => ''
            ],
            'google_search_console'=> [
                'access_token' => '',
                'site_url' => ''
            ]
        ];
    $emailData = app('App\Services\Report\TemplateData\SEOReportData')
         ->setAccounts($accounts)
         ->generate('2018-12-01','2018-12-31')
         ->get('email');

    dd($emailData);
});

Route::get('chart-test',function () {
    $url = (new \App\Services\ChartService)->getDonutChartImageUrl([['Men',10],['Women',30]]);
    echo '<img src="' . $url . '">';
});
