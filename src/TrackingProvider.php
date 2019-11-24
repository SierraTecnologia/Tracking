<?php

namespace Tracking;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class TrackingProvider extends ServiceProvider
{
    public static $providers = [
        // \Tracking\Providers\TrackingEventServiceProvider::class,
        // \Tracking\Providers\TrackingServiceProvider::class,
        // \Tracking\Providers\TrackingRouteProvider::class,

        // \Tracking\TrackingProvider::class,
        // \Casa\CasaProvider::class,
    ];

    /**
     * Alias the services in the boot.
     */
    public function boot()
    {
        // $this->publishes([
        //     __DIR__.'/Publishes/resources/tools' => base_path('resources/tools'),
        //     __DIR__.'/Publishes/app/Services' => app_path('Services'),
        //     __DIR__.'/Publishes/public/js' => base_path('public/js'),
        //     __DIR__.'/Publishes/public/css' => base_path('public/css'),
        //     __DIR__.'/Publishes/public/img' => base_path('public/img'),
        //     __DIR__.'/Publishes/config' => base_path('config'),
        //     __DIR__.'/Publishes/routes' => base_path('routes'),
        //     __DIR__.'/Publishes/app/Controllers' => app_path('Http/Controllers/Tracking'),
        // ]);

        // $this->publishes([
        //     __DIR__.'../resources/views' => base_path('resources/views/vendor/Tracking'),
        // ], 'SierraTecnologia Tracking');
    }

    /**
     * Register the services.
     */
    public function register()
    {
        $this->setProviders();

        // // View namespace
        // $this->loadViewsFrom(__DIR__.'/Views', 'Tracking');

        // if (is_dir(base_path('resources/Tracking'))) {
        //     $this->app->view->addNamespace('Tracking-frontend', base_path('resources/Tracking'));
        // } else {
        //     $this->app->view->addNamespace('Tracking-frontend', __DIR__.'/Publishes/resources/Tracking');
        // }

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

    protected function setProviders()
    {
        collection(self::$providers)->map(function ($provider) {
            $this->app->register($provider);
        })

    }

}