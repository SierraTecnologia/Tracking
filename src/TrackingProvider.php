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
use Illuminate\Routing\Router;
use Tracking\Models\Statistics\Path;
use Tracking\Models\Statistics\Agent;
use Tracking\Models\Statistics\Datum;
use Tracking\Models\Statistics\Geoip;
use Tracking\Models\Statistics\Route as RouteBase;
use Tracking\Models\Statistics\Device;
use Tracking\Models\Statistics\Request;
use Tracking\Models\Statistics\Platform;
use Support\Helpers\Traits\Models\ConsoleTools;
use Tracking\Console\Commands\MigrateCommand;
use Tracking\Console\Commands\PublishCommand;
use Tracking\Http\Middleware\TrackStatistics;
use Tracking\Http\Middleware\Analytics;
use Tracking\Console\Commands\RollbackCommand;

class TrackingProvider extends ServiceProvider
{
    use ConsoleTools;

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        MigrateCommand::class => 'command.tracking.statistics.migrate',
        PublishCommand::class => 'command.tracking.statistics.publish',
        RollbackCommand::class => 'command.tracking.statistics.rollback',
    ];

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
        /**
         * Configuracoes
         */
        \Tracking\Providers\HorizonServiceProvider::class,
        
        /**
         * 
         */

        \Audit\AuditProvider::class,

        /**
         * Externos
         */
        \Spatie\Analytics\AnalyticsServiceProvider::class,
        \Aschmelyun\Larametrics\LarametricsServiceProvider::class,
        \Laravel\Horizon\HorizonServiceProvider::class,
    ];

    /**
     * Rotas do Menu
     */
    public static $menuItens = [
        'System' => [
            'Metrics' => [
                [
                    'text'        => 'Analytics',
                    'route'       => 'tracking.analytics',
                    'icon'        => 'dashboard',
                    'icon_color'  => 'blue',
                    'label_color' => 'success',
                    // 'access' => \App\Models\Role::$ADMIN
                ],
                [
                    'text'        => 'Metrics',
                    'route'       => 'larametrics::metrics.index',
                    'icon'        => 'dashboard',
                    'icon_color'  => 'blue',
                    'label_color' => 'success',
                    // 'access' => \App\Models\Role::$ADMIN
                ],
            ],
        ],
    ];

    /**
     * Alias the services in the boot.
     */
    public function boot(Router $router)
    {
        // Push middleware to web group
        $router->pushMiddlewareToGroup('web', TrackStatistics::class);
        $router->pushMiddlewareToGroup('web', Analytics::class);


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

        // // COloquei no register pq nao tava reconhecendo as rotas para o adminlte
        // $this->app->booted(function () {
        //     $this->routes();
        // });
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


        /**
         * Facilitador Routes
         */
        // $router->middleware('siravel-analytics', Analytics::class);
        Route::group([
            'namespace' => '\Tracking\Http\Controllers',
        ], function ($router) {
            require __DIR__.'/Routes/web.php';
        });
    }

    /**
     * Register the services.
     */
    public function register()
    {
        // Merge own configs into user configs 
        $this->mergeConfigFrom($this->getPublishesPath('config/tracking/analytics.php'), 'tracking.analytics');
        $this->mergeConfigFrom($this->getPublishesPath('config/tracking/conf.php'), 'tracking.conf');
        $this->mergeConfigFrom($this->getPublishesPath('config/tracking/statistics.php'), 'tracking.statistics');

        $this->mergeConfigFrom($this->getPublishesPath('config/horizon.php'), 'horizon');
        $this->mergeConfigFrom($this->getPublishesPath('config/larametrics.php'), 'larametrics');
        $this->mergeConfigFrom($this->getPublishesPath('config/stats.php'), 'stats');
        
        // Register external packages
        $this->setProviders();
        $this->routes();
        $this->loadMigrationsFrom(__DIR__.'/Migrations');

        // // Configs
        // $this->app->config->set('Tracking.modules.Tracking', include(__DIR__.'/config.php'));

        /*
        |--------------------------------------------------------------------------
        | statistics
        |--------------------------------------------------------------------------
        */

        // Bind eloquent models to IoC container
        $this->app->singleton('tracking.statistics.datum', $datumModel = $this->app['config']['tracking.statistics.models.datum']);
        $datumModel === Datum::class || $this->app->alias('tracking.statistics.datum', Datum::class);

        $this->app->singleton('tracking.statistics.request', $requestModel = $this->app['config']['tracking.statistics.models.request']);
        $requestModel === Request::class || $this->app->alias('tracking.statistics.request', Request::class);

        $this->app->singleton('tracking.statistics.agent', $agentModel = $this->app['config']['tracking.statistics.models.agent']);
        $agentModel === Agent::class || $this->app->alias('tracking.statistics.agent', Agent::class);

        $this->app->singleton('tracking.statistics.geoip', $geoipModel = $this->app['config']['tracking.statistics.models.geoip']);
        $geoipModel === Geoip::class || $this->app->alias('tracking.statistics.geoip', Geoip::class);

        $this->app->singleton('tracking.statistics.route', $routeModel = $this->app['config']['tracking.statistics.models.route']);
        $routeModel === RouteBase::class || $this->app->alias('tracking.statistics.route', RouteBase::class);

        $this->app->singleton('tracking.statistics.device', $deviceModel = $this->app['config']['tracking.statistics.models.device']);
        $deviceModel === Device::class || $this->app->alias('tracking.statistics.device', Device::class);

        $this->app->singleton('tracking.statistics.platform', $platformModel = $this->app['config']['tracking.statistics.models.platform']);
        $platformModel === Platform::class || $this->app->alias('tracking.statistics.platform', Platform::class);

        $this->app->singleton('tracking.statistics.path', $pathModel = $this->app['config']['tracking.statistics.models.path']);
        $pathModel === Path::class || $this->app->alias('tracking.statistics.path', Path::class);

        // Register console commands
        ! $this->app->runningInConsole() || $this->registerCommands();
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
        // @todo Nao usado mais. Avaliar
        // // Publish Resources
        // ! $this->app->runningInConsole() || $this->publishesConfig('sierratecnologia/laravel-statistics');
        // ! $this->app->runningInConsole() || $this->publishesMigrations('sierratecnologia/laravel-statistics');

        // Publish config files
        $this->publishes([
            // Paths
            $this->getPublishesPath('config/tracking') => config_path('tracking'),
            // Files
            $this->getPublishesPath('config/horizon.php') => config_path('horizon.php'),
            $this->getPublishesPath('config/larametrics.php') => config_path('larametrics.php'),
            $this->getPublishesPath('config/slow-query-logger.php') => config_path('slow-query-logger.php'),
            $this->getPublishesPath('config/stats.php') => config_path('stats.php')
        ], ['config',  'sitec', 'sitec-config']);

        // Publish tracking css and js to public directory
        $this->publishes([
            $this->getDistPath() => public_path('assets/tracking'),
            $this->getPublishesPath('public/horizon') => public_path('vendor/horizon'),
            $this->getPublishesPath('public/larametrics') => public_path('vendor/larametrics')
        ], ['public',  'sitec', 'sitec-public']);


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
        ], ['views',  'sitec', 'sitec-views', 'tracking-views']);

        $viewsPath = $this->getResourcesPath('views-larametrics');
        $this->loadViewsFrom($viewsPath, 'larametrics');
        $this->publishes([
            $viewsPath => base_path('resources/views/vendor/larametrics'),
        ], ['views',  'sitec', 'sitec-views', 'tracking-views']);


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
