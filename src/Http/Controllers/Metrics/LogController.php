<?php

namespace Tracking\Http\Controllers\Metrics;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Tracking\Models\Metrics\LarametricsLog;

class LogController extends Controller
{

    public function index(Request $request)
    {
        $logs = LarametricsLog::orderBy('created_at', 'desc')
            ->get();
            
        return view(
            'rica.larametrics::logs.index', [
            'logs' => $logs,
            'pageTitle' => 'Laravel Logs'
            ]
        );
    }

    public function show(LarametricsLog $log)
    {
        return view(
            'rica.larametrics::logs.show', [
            'log' => $log,
            'pageTitle' => 'Viewing Log Details'
            ]
        );
    }

}
