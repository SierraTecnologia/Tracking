<?php

namespace Tracking;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Collection;
use Spatie\LaravelAnalytics\LaravelAnalyticsFacade;
use Spatie\LaravelAnalytics\LaravelAnalyticsServiceProvider;
use Sitec\Laracogs\LaracogsProvider;
use Laravel\Dusk\DuskServiceProvider;
use Barryvdh\Debugbar\ServiceProvider as DebugService;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Schema;
use Route;

class TrackingProvider extends ServiceProvider
{
    public static $aliasProviders = [
        'Horizon' => \Laravel\Horizon\Horizon::class,

        /*
         * Log and Monitoring 
         */
        'Sentry' => \Sentry\Laravel\Facade::class,
        'Debugbar' => \Barryvdh\Debugbar\Facade::class,
    ];

    public static $providers = [
        \Tracking\Providers\TrackingRouteProvider::class,

        \Audit\AuditProvider::class,

        /**
         * Externos
         */
        \Aschmelyun\Larametrics\LarametricsServiceProvider::class,
        \Laravel\Horizon\HorizonServiceProvider::class,
    ];

    /**
     * Alias the services in the boot.
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        $this->publishes([
            $this->getResourcesPath('views') => base_path('resources/views/vendor/tracking'),
        ], 'tracking');
        
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tinker-tool');

        $this->app->booted(function () {
            $this->routes();
        });
    }

    /**
     * Register the tool's routes.
     *
     * @return void
     */
    protected function routes()
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware(['nova', Authorize::class])
                ->prefix('nova-vendor/beyondcode/tinker-tool')
                ->group(__DIR__.'/Routes/api.php');
    }

    /**
     * Register the services.
     */
    public function register()
    {
        $this->setProviders();

        // View namespace
        $this->loadViewsFrom($this->getResourcesPath('views'), 'tracking');

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

    /**
     * Configs Paths
     */
    private function getResourcesPath($folder)
    {
        return __DIR__.'/../resources/'.$folder;
    }

    private function getPublishesPath($folder)
    {
        return __DIR__.'/../publishes/'.$folder;
    }

    private function getDistPath($folder)
    {
        return __DIR__.'/../dist/'.$folder;
    }

    /**
     * Load Alias and Providers
     */
    private function setProviders()
    {
        $this->setDependencesAlias();
        (new Collection(self::$providers))->map(function ($provider) {
            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        });
    }
    private function setDependencesAlias()
    {
        $loader = AliasLoader::getInstance();
        (new Collection(self::$aliasProviders))->map(function ($class, $alias) use ($loader) {
            $loader->alias($alias, $class);
        });
    }
}
