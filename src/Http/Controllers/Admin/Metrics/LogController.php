<?php

namespace Tracking\Http\Controllers\Admin\Metrics;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Tracking\Models\Metrics\LarametricsLog;

class LogController extends Controller
{
    public function index(Request $request): \Illuminate\View\View
    {
        $logs = LarametricsLog::orderBy('created_at', 'desc')
            ->get();
            
        return view(
            'rica.larametrics::logs.index',
            [
            'logs' => $logs,
            'pageTitle' => 'Laravel Logs'
            ]
        );
    }

    public function show(LarametricsLog $log): \Illuminate\View\View
    {
        return view(
            'rica.larametrics::logs.show',
            [
            'log' => $log,
            'pageTitle' => 'Viewing Log Details'
            ]
        );
    }
}
