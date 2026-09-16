<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tracking\Http\Controllers\TrackingAssetController;
use Tracking\Http\Controllers\TrackingEventController;

if (config('tracking.script.enabled', true)) {
    Route::get((string) config('tracking.script.route', '/js/tracker.js'), [TrackingAssetController::class, 'script'])
        ->name('tracking.script');

    foreach ((array) config('tracking.script.aliases', []) as $indice => $alias) {
        Route::get((string) $alias, [TrackingAssetController::class, 'script'])->name('tracking.script.alias.'.$indice);
    }
}

if (config('tracking.routes.enabled', true)) {
    $caminho = (string) config('tracking.routes.events_path', 'leads/{uuid}/eventos');

    Route::prefix((string) config('tracking.routes.prefix', 'api/v1'))
        ->middleware((array) config('tracking.routes.middleware', []))
        ->group(function () use ($caminho): void {
            Route::post($caminho, [TrackingEventController::class, 'store'])->name('tracking.events.store');
            Route::options($caminho, fn () => response()->noContent(204))->name('tracking.events.options');
        });
}
