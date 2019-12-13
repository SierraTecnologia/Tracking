<?php

declare(strict_types=1);

/**
 * Configuration
 */

return [
    
    // Statistics Database Tables
    'tables' => [
        'data' => 'statistics_data',
        'paths' => 'statistics_paths',
        'geoips' => 'statistics_geoips',
        'routes' => 'statistics_routes',
        'agents' => 'statistics_agents',
        'devices' => 'statistics_devices',
        'requests' => 'statistics_requests',
        'platforms' => 'statistics_platforms',
    ],

    // Statistics Models
    'models' => [
        'path' => \Tracking\Models\Statistics\Path::class,
        'datum' => \Tracking\Models\Statistics\Datum::class,
        'geoip' => \Tracking\Models\Statistics\Geoip::class,
        'route' => \Tracking\Models\Statistics\Route::class,
        'agent' => \Tracking\Models\Statistics\Agent::class,
        'device' => \Tracking\Models\Statistics\Device::class,
        'request' => \Tracking\Models\Statistics\Request::class,
        'platform' => \Tracking\Models\Statistics\Platform::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Statistics Crunching Lottery
    |--------------------------------------------------------------------------
    |
    | Raw statistical data needs to be crunched to extract meaningful stories.
    | Here the chances that it will happen on a given request. By default,
    | the odds are 2 out of 100. For better performance consider using
    | task scheduling and set this lottery option to "FALSE" then.
    |
    */

    'lottery' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Statistics Cleaning Period
    |--------------------------------------------------------------------------
    |
    | If you would like to clean old statistics automatically, you may specify
    | the number of days after which the it will be wiped automatically.
    | Any records older than this period (in days) will be cleaned.
    |
    | Note that this cleaning process just affects `statistics_requests`
    | only! Other database tables are kept safely untouched anyway.
    |
    */

    'lifetime' => false,

];
