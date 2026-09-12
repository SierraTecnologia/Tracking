<?php

declare(strict_types=1);

namespace Tracking\Tests\Feature;

use Illuminate\Http\Request;
use Tracking\Models\Analytics;
use Tracking\Services\AnalyticsService;
use Tracking\Services\TrackingService;
use Tracking\Tests\TestCase;

class ServicesTest extends TestCase
{
    public function test_tracking_service_instantiation()
    {
        $service = new TrackingService();
        $this->assertInstanceOf(TrackingService::class, $service);
    }

    public function test_analytics_service_logging_and_metrics()
    {
        $service = new AnalyticsService(new Analytics());

        $request = Request::create('/test-page', 'GET', [], [], [], [
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
            'HTTP_REFERER' => 'https://google.com',
            'REMOTE_ADDR' => '127.0.0.1',
            'REQUEST_URI' => '/test-page',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_TIME' => time(),
        ]);

        $service->log($request);

        $this->assertEquals(1, Analytics::count());

        $topPages = $service->topPages(30);
        $this->assertArrayHasKey('/test-page', $topPages);
        $this->assertEquals(1, $topPages['test-page'] ?? $topPages['/test-page']);

        $topReferers = $service->topReferers(30);
        $this->assertArrayHasKey('https://google.com', $topReferers);
        $this->assertEquals(1, $topReferers['https://google.com']);
    }

    public function test_analytics_convert_data_to_items()
    {
        $service = new AnalyticsService(new Analytics());

        $data = [
            json_encode(['browser' => 'Chrome']),
            json_encode(['browser' => 'Chrome']),
            json_encode(['browser' => 'Firefox']),
            json_encode(['browser' => '']),
        ];

        $results = $service->convertDataToItems($data, 'browser');

        $this->assertEquals(2, $results['Chrome']);
        $this->assertEquals(1, $results['Firefox']);
        $this->assertEquals(1, $results['unknown']);
    }
}
