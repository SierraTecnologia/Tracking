<?php

declare(strict_types=1);

namespace Tracking;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Tracking\Console\PruneCommand;
use Tracking\Services\TrackingService;

class TrackingProvider extends ServiceProvider
{
    public string $packageName = 'tracking';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tracking.php', 'tracking');

        $this->app->singleton('tracking', fn ($app) => new TrackingService(
            dispatcher: $app->bound(Dispatcher::class) ? $app->make(Dispatcher::class) : null,
        ));

        $this->app->alias('tracking', TrackingService::class);
    }

    public function boot(): void
    {
        if (config('tracking.migrations', true)) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/tracking.php');

        $this->publishes([
            __DIR__.'/../config/tracking.php' => config_path('tracking.php'),
        ], 'tracking-config');

        $this->publishes([
            __DIR__.'/../resources/js/tracker.js' => public_path('js/tracker.js'),
        ], ['tracking-assets', 'tracking']);

        if ($this->app->runningInConsole()) {
            $this->commands([PruneCommand::class]);
        }
    }
}
