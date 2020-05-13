<?php

namespace Tracking\Http\Controllers;

use Tracking\Services\AnalyticsService;
use Illuminate\Support\Facades\Schema;
use Spatie\Analytics\Analytics;
use Spatie\Analytics\Period;

class AnalyticsController extends Controller
{
    protected $service;

    public function __construct(AnalyticsService $service)
    {
        parent::__construct();

        $this->service = $service;
    }

    public function index()
    {
        if (!is_null(config('tracking.analytics.view_id')) && config('tracking.conf.analytics') == 'google') {
            $period = Period::days(7);

            foreach (app(Analytics::class)->fetchVisitorsAndPageViews($period) as $view) {
                $visitStats['date'][] = $view['date']->format('Y-m-d');
                $visitStats['visitors'][] = $view['visitors'];
                $visitStats['pageViews'][] = $view['pageViews'];
            }

            return view('tracking::analytics.google', compact('visitStats', 'period'));
        } elseif (is_null(config('tracking.analytics.conf')) || config('tracking.conf.analytics') == 'internal') {
            if (Schema::hasTable('analytics')) {
                return view('tracking::analytics.internal')
                    ->with('stats', $this->service->getDays(15))
                    ->with('topReferers', $this->service->topReferers(15))
                    ->with('topBrowsers', $this->service->topBrowsers(15))
                    ->with('topPages', $this->service->topPages(15));
            }
        }

        return view('tracking::analytics.empty');
    }
}
