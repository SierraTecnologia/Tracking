<?php

namespace Tracking;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Collection;
// use Sitec\Laracogs\LaracogsProvider;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Schema;
use Route;

class TrackingProvider extends ServiceProvider
{
    public static $aliasProviders = [
        'Horizon' => \Laravel\Horizon\Horizon::class,

        'LaravelAnalytics' => \Spatie\LaravelAnalytics\LaravelAnalyticsFacade::class,

        
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
        \Spatie\Analytics\AnalyticsServiceProvider::class,
        \Aschmelyun\Larametrics\LarametricsServiceProvider::class,
        \Laravel\Horizon\HorizonServiceProvider::class,
    ];

    /**
     * Alias the services in the boot.
     */
    public function boot()
    {
        // Register configs, migrations, etc
        $this->registerDirectories();

        // // Wire up model event callbacks even if request is not for admin.  Do this
        // // after the usingAdmin call so that the callbacks run after models are
        // // mutated by Decoy logic.  This is important, in particular, so the
        // // Validation observer can alter validation rules before the onValidation
        // // callback runs.
        // $this->app['events']->listen('eloquent.*',
        //     'Tracking\Observers\ModelCallbacks');
        // $this->app['events']->listen('tracking::model.*',
        //     'Tracking\Observers\ModelCallbacks');
        // // Log model change events after others in case they modified the record
        // // before being saved.
        // $this->app['events']->listen('eloquent.*',
        //     'Tracking\Observers\Changes');

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
        // Merge own configs into user configs 
        $this->mergeConfigFrom($this->getPublishesPath('config/tracking/analytics.php'), 'tracking.analytics');
        $this->mergeConfigFrom($this->getPublishesPath('config/tracking/conf.php'), 'tracking.conf');
        $this->mergeConfigFrom($this->getPublishesPath('config/horizon.php'), 'horizon');
        $this->mergeConfigFrom($this->getPublishesPath('config/larametrics.php'), 'larametrics');
        $this->mergeConfigFrom($this->getPublishesPath('config/stats.php'), 'stats');

        // Register external packages
        $this->setProviders();
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
     * Delegate events to Decoy observers
     *
     * @return void
     */
    protected function delegateAdminObservers()
    {
        $this->app['events']->listen('eloquent.saving:*',
            '\Tracking\Observers\Localize');
        $this->app['events']->listen('eloquent.saving:*',
            '\Tracking\Observers\Encoding@onSaving');
        $this->app['events']->listen('eloquent.saved:*',
            '\Tracking\Observers\ManyToManyChecklist');
        $this->app['events']->listen('eloquent.deleted:*',
            '\Tracking\Observers\Encoding@onDeleted');
        $this->app['events']->listen('tracking::model.validating:*',
            '\Tracking\Observers\ValidateExistingFiles@onValidating');
    }

    /**
     * Register middlewares
     *
     * @return void
     */
    protected function registerMiddlewares()
    {

        // Register middleware individually
        foreach ([
            'tracking.auth'          => \Tracking\Http\Middleware\Auth::class,
            'tracking.edit-redirect' => \Tracking\Http\Middleware\EditRedirect::class,
            'tracking.guest'         => \Tracking\Http\Middleware\Guest::class,
            'tracking.save-redirect' => \Tracking\Http\Middleware\SaveRedirect::class,
        ] as $key => $class) {
            $this->app['router']->aliasMiddleware($key, $class);
        }

        // This group is used by public tracking routes
        $this->app['router']->middlewareGroup('tracking.public', [
            'web',
        ]);

        // The is the starndard auth protected group
        $this->app['router']->middlewareGroup('tracking.protected', [
            'web',
            'tracking.auth',
            'tracking.save-redirect',
            'tracking.edit-redirect',
        ]);

        // Require a logged in admin session but no CSRF token
        $this->app['router']->middlewareGroup('tracking.protected_endpoint', [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Session\Middleware\StartSession::class,
            'tracking.auth',
        ]);

        // An open endpoint, like used by Zendcoder
        $this->app['router']->middlewareGroup('tracking.endpoint', [
            'api'
        ]);
    }
    /**
     * Register configs, migrations, etc
     *
     * @return void
     */
    public function registerDirectories()
    {
        // Publish config files
        $this->publishes([
            // Paths
            $this->getPublishesPath('config/tracking') => config_path('tracking'),
            // Files
            $this->getPublishesPath('config/horizon.php') => config_path('horizon.php'),
            $this->getPublishesPath('config/larametrics.php') => config_path('larametrics.php'),
            $this->getPublishesPath('config/slow-query-logger.php') => config_path('slow-query-logger.php'),
            $this->getPublishesPath('config/stats.php') => config_path('stats.php')
        ], 'sitec-config');

        // Publish tracking css and js to public directory
        $this->publishes([
            $this->getDistPath() => public_path('assets/tracking')
        ], 'sitec-assets');



        // Publish tracking css and js to public directory
        $this->publishes([
            $this->getPublishesPath('public/horizon') => public_path('vendor/horizon'),
            $this->getPublishesPath('public/larametrics') => public_path('vendor/larametrics')
        ], 'sitec-public');


        $this->loadViews();
        $this->loadTranslations();

    }

    private function loadViews()
    {
        // View namespace
        $viewsPath = $this->getResourcesPath('views');
        $this->loadViewsFrom($viewsPath, 'tracking');
        $this->publishes([
            $viewsPath => base_path('resources/views/vendor/tracking'),
        ], 'sitec-views');


        // // Publish lanaguage files
        // $this->publishes([
        //     $this->getResourcesPath('lang') => resource_path('lang/vendor/tracking')
        // ], 'lang');

        // // Load translations
        // $this->loadTranslationsFrom($this->getResourcesPath('lang'), 'tracking');
    }
    
    private function loadTranslations()
    {
        // $translationsPath = $this->getResourcesPath('lang');
        // $this->loadTranslationsFrom($translationsPath, 'tracking');
        // $this->publishes([
        //     $translationsPath => resource_path('lang/vendor/tracking'),
        // ], 'translations');// @todo ou lang, verificar (invez de translations)
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

    private function getDistPath($folder = '')
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
