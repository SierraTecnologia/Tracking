<?php

declare(strict_types=1);

namespace Tracking\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tracking\Http\Middleware\Analytics;
use Tracking\Http\Middleware\TrackStatistics;
use Tracking\Tests\TestCase;

class MiddlewareTest extends TestCase
{
    public function test_analytics_middleware_passes_request()
    {
        $middleware = new Analytics();
        $request = Request::create('/test-route', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_track_statistics_middleware_passes_request()
    {
        $middleware = new TrackStatistics();
        $request = Request::create('/test-stats', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('Stats Tracked', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Stats Tracked', $response->getContent());
    }
}
