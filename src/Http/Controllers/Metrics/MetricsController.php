<?php

namespace Siravel\Http\Controllers\System\Metrics;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Siravel\Providers\Metrics\LogParser;
use Siravel\Models\Metrics\LarametricsLog;
use Siravel\Models\Metrics\LarametricsModel;
use Siravel\Models\Metrics\LarametricsRequest;

class MetricsController extends Controller
{
    
    public function index()
    {
        $requests = LarametricsRequest::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $logs = LarametricsLog::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $models = LarametricsModel::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('larametrics::metrics.index', [
            'pageTitle' => 'Dashboard',
            'requests' => $requests,
            'logs' => $logs,
            'models' => $models
        ]);
    }

    public function logs()
    {
        return view('larametrics::logs.index');
    }

    public function logShow($index)
    {
        $logArray = LogParser::all();

        if(!isset($logArray[$index])) {
            return abort(404);
        }

        return view('larametrics::logs.show', [
            'log' => $logArray[$index],
            'pageTitle' => 'Viewing Log'
        ]);
    }

}
