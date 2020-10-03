<?php

// @tgodo para corrigir bug
// Route::group(
    // ['middleware' => ['web', 'admin']], function () {   @todo
        // Route::group(['middleware' => ['siravel-analytics']], function () {

                    Route::group(
                        ['as' => 'rica.tracking.'],
                        function () {
                            Route::prefix('tracking')->group(
                                function () {
                                    Route::get('analytics', 'Admin\AnalyticsController@index')->name('analytics');
                                }
                            );
                        }
                    );
