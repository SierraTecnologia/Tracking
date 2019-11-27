<?php

namespace Tracking;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Collection;

class TrackingProvider extends ServiceProvider
{
    public static $providers = [
        \Tracking\Providers\TrackingEventServiceProvider::class,
        \Tracking\Providers\TrackingServiceProvider::class,
        \Tracking\Providers\TrackingRouteProvider::class,

        \Audit\AuditProvider::class,
    ];

    /**
     * Alias the services in the boot.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'../resources/views' => base_path('resources/views/vendor/tracking'),
        ], 'SierraTecnologia Tracking');
    }

    /**
     * Register the services.
     */
    public function register()
    {
        $this->setProviders();

        // View namespace
        $this->loadViewsFrom(__DIR__.'../resources/views', 'tracking');

        $this->loadMigrationsFrom(__DIR__.'/Migrations');

        // // Configs
        // $this->app->config->set('Tracking.modules.Tracking', include(__DIR__.'/config.php'));

        /*
        |--------------------------------------------------------------------------
        | Register the Commands
        |--------------------------------------------------------------------------
        */

        $this->commands([]);
    }

    private function setProviders()
    {
        (new Collection(self::$providers))->map(function ($provider) {
            $this->app->register($provider);
        });
    }

}
