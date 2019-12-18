<?php

Route::group(['middleware' => ['web']], function () {  
    // Route::group(['middleware' => ['siravel-analytics']], function () {

    Route::prefix('tracking')->group(function () {
        Route::group(['as' => 'tracking.'], function () {

            Route::get('analytics', 'AnalyticsController@index')->name('analytics');
        });

        Route::group(['namespace' => 'Metrics'], function () {
            Route::group(['as' => 'larametrics::'], function () {

                // dashboard routes
                Route::get('/metrics', [
                    'as' => 'metrics.index',
                    'uses' => 'MetricsController@index',
                ]);

                // logs routes
                Route::get('/metrics/logs', [
                    'as' => 'logs.index',
                    'uses' => 'LogController@index',
                ]);

                Route::get('/metrics/logs/{log}', [
                    'as' => 'logs.show',
                    'uses' => 'LogController@show',
                ]);

                // models routes
                Route::get('/metrics/models', [
                    'as' => 'models.index',
                    'uses' => 'ModelController@index',
                ]);

                Route::get('/metrics/models/{model}', [
                    'as' => 'models.show',
                    'uses' => 'ModelController@show',
                ]);

                Route::get('/metrics/models/{model}/revert', [
                    'as' => 'models.revert',
                    'uses' => 'ModelController@revert',
                ]);

                // performance routes
                Route::get('/metrics/performance', [
                    'as' => 'performance.index',
                    'uses' => 'PerformanceController@index',
                ]);

                // request routes
                Route::get('/metrics/requests', [
                    'as' => 'requests.index',
                    'uses' => 'RequestController@index',
                ]);

                Route::get('/metrics/requests/{request}', [
                    'as' => 'requests.show',
                    'uses' => 'RequestController@show',
                ]);

                // notifications routes
                Route::get('/metrics/notifications', [
                    'as' => 'notifications.index',
                    'uses' => 'NotificationController@index',
                ]);

                Route::post('/metrics/notifications/edit', [
                    'as' => 'notifications.update',
                    'uses' => 'NotificationController@update',
                ]);

            });
        });
    });
});