<?php

declare(strict_types=1);

namespace Tracking\Tests\Feature;

use Tracking\Models\Analytics;
use Tracking\Models\Metrics;
use Tracking\Models\Metrics\LarametricsLog;
use Tracking\Models\Metrics\LarametricsModel;
use Tracking\Models\Metrics\LarametricsNotification;
use Tracking\Models\Metrics\LarametricsRequest;
use Tracking\Models\Statistics\Agent;
use Tracking\Models\Statistics\Datum;
use Tracking\Models\Statistics\Device;
use Tracking\Models\Statistics\Geoip;
use Tracking\Models\Statistics\Path;
use Tracking\Models\Statistics\Platform;
use Tracking\Models\Statistics\Request as StatRequest;
use Tracking\Models\Statistics\Route as StatRoute;
use Tracking\Models\Trackings;
use Tracking\Tests\TestCase;

class ModelsTest extends TestCase
{
    public function test_analytics_model()
    {
        $analytics = Analytics::create([
            'token' => 'tok_abc123',
            'data' => json_encode(['page' => '/home']),
            'business_code' => 'BIZ_01',
        ]);

        $this->assertInstanceOf(Analytics::class, $analytics);
        $this->assertEquals('tok_abc123', $analytics->token);
        $this->assertEquals('BIZ_01', $analytics->business_code);
    }

    public function test_metrics_and_trackings_models()
    {
        $metrics = Metrics::create([
            'token' => 'metric_tok_1',
            'data' => json_encode(['cpu' => '15%']),
        ]);

        $this->assertInstanceOf(Metrics::class, $metrics);
        $this->assertEquals('metric_tok_1', $metrics->token);

        $tracking = new Trackings([
            'token' => 'audit_tok_1',
            'data' => json_encode(['action' => 'login']),
        ]);

        $this->assertInstanceOf(Trackings::class, $tracking);
        $this->assertEquals('audits', $tracking->getTable());
        $this->assertEquals('audit_tok_1', $tracking->token);
    }

    public function test_larametrics_models()
    {
        $log = LarametricsLog::create([
            'level' => 'error',
            'message' => 'Something failed',
            'trace' => json_encode(['file' => 'test.php']),
        ]);
        $this->assertInstanceOf(LarametricsLog::class, $log);
        $this->assertEquals('error', $log->level);

        $modelMetric = LarametricsModel::create([
            'model' => 'App\\User',
            'model_id' => 1,
            'method' => 'created',
            'changes' => json_encode(['id' => 1]),
        ]);
        $this->assertInstanceOf(LarametricsModel::class, $modelMetric);
        $this->assertEquals('App\\User', $modelMetric->model);

        $requestMetric = LarametricsRequest::create([
            'method' => 'GET',
            'uri' => '/api/test',
            'ip' => '127.0.0.1',
            'headers' => json_encode([]),
            'start_time' => 1700000000.0,
            'end_time' => 1700000000.1,
        ]);
        $this->assertInstanceOf(LarametricsRequest::class, $requestMetric);
        $this->assertEquals('/api/test', $requestMetric->uri);

        $notif = LarametricsNotification::create([
            'action' => 'logged_error',
            'filter' => 'all',
            'notify_by' => 'email',
            'last_fired_at' => now(),
        ]);
        $this->assertInstanceOf(LarametricsNotification::class, $notif);
    }

    public function test_statistics_models_and_relationships()
    {
        $route = StatRoute::create([
            'name' => 'api.users.index',
            'path' => '/api/users',
            'action' => 'UserController@index',
            'middleware' => ['api', 'auth'],
            'parameters' => [],
        ]);
        $this->assertInstanceOf(StatRoute::class, $route);

        $path = Path::create([
            'host' => 'example.com',
            'locale' => 'en',
            'path' => '/api/users',
            'method' => 'GET',
            'parameters' => ['page' => 1],
        ]);
        $this->assertInstanceOf(Path::class, $path);

        $agent = Agent::create([
            'name' => 'Chrome',
            'kind' => 'browser',
            'family' => 'Chrome',
            'version' => '120.0',
        ]);
        $this->assertInstanceOf(Agent::class, $agent);

        $device = Device::create([
            'family' => 'Macintosh',
            'model' => 'MacBook Pro',
            'brand' => 'Apple',
        ]);
        $this->assertInstanceOf(Device::class, $device);

        $platform = Platform::create([
            'family' => 'macOS',
            'version' => '14.0',
        ]);
        $this->assertInstanceOf(Platform::class, $platform);

        $geoip = Geoip::create([
            'client_ip' => '192.168.1.1',
            'latitude' => '-23.5505',
            'longitude' => '-46.6333',
            'country_code' => 'BR',
            'city' => 'São Paulo',
            'timezone' => 'America/Sao_Paulo',
        ]);
        $this->assertInstanceOf(Geoip::class, $geoip);

        $request = StatRequest::create([
            'session_id' => 'sess_test_123',
            'route_id' => $route->id,
            'agent_id' => $agent->id,
            'device_id' => $device->id,
            'platform_id' => $platform->id,
            'path_id' => $path->id,
            'geoip_id' => $geoip->id,
            'language' => 'en_US',
            'status_code' => 200,
            'protocol_version' => 'HTTP/1.1',
            'created_at' => now(),
        ]);

        $this->assertInstanceOf(StatRequest::class, $request);
        $this->assertEquals($route->id, $request->route->id);
        $this->assertEquals($path->id, $request->path->id);
        $this->assertEquals($agent->id, $request->agent->id);
        $this->assertEquals($device->id, $request->device->id);
        $this->assertEquals($platform->id, $request->platform->id);
        $this->assertEquals($geoip->id, $request->geoip->id);

        $this->assertCount(1, $route->requests);
        $this->assertCount(1, $path->requests);
        $this->assertCount(1, $agent->requests);
        $this->assertCount(1, $device->requests);
        $this->assertCount(1, $platform->requests);
        $this->assertCount(1, $geoip->requests);

        $datum = Datum::create([
            'session_id' => 'sess_12345',
            'status_code' => 200,
            'uri' => '/api/users',
            'method' => 'GET',
            'server' => ['REMOTE_ADDR' => '127.0.0.1'],
            'input' => [],
            'created_at' => now(),
        ]);
        $this->assertInstanceOf(Datum::class, $datum);
        $this->assertEquals('sess_12345', $datum->session_id);
    }
}
