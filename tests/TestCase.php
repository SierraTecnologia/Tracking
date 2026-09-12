<?php

declare(strict_types=1);

namespace Tracking\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Tracking\TrackingProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            TrackingProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('tracking.statistics.enabled', true);
        $app['config']->set('tracking.statistics.tables.data', 'statistics_data');
        $app['config']->set('tracking.statistics.tables.routes', 'statistics_routes');
        $app['config']->set('tracking.statistics.tables.paths', 'statistics_paths');
        $app['config']->set('tracking.statistics.tables.devices', 'statistics_devices');
        $app['config']->set('tracking.statistics.tables.agents', 'statistics_agents');
        $app['config']->set('tracking.statistics.tables.platforms', 'statistics_platforms');
        $app['config']->set('tracking.statistics.tables.geoips', 'statistics_geoips');
        $app['config']->set('tracking.statistics.tables.requests', 'statistics_requests');
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
