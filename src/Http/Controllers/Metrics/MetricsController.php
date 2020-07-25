<?php

namespace Tracking\Http\Controllers\Metrics;

use Illuminate\Http\Request;
use Tracking\Providers\Metrics\LogParser;
use Tracking\Models\Metrics\LarametricsLog;
use Tracking\Models\Metrics\LarametricsModel;
use Tracking\Models\Metrics\LarametricsRequest;
use Facilitador\Http\Controllers\Admin\Base as Controller;

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

        return $this->populateView(
            'tracking::larametrics.metrics.index', [
            'pageTitle' => 'Dashboard',
            'requests' => $requests,
            'logs' => $logs,
            'models' => $models
            ]
        );
    }

    public function logs()
    {
        return $this->populateView('tracking::larametrics.logs.index');
    }

    public function logShow($index)
    {
        $logArray = LogParser::all();

        if(!isset($logArray[$index])) {
            return abort(404);
        }

        return $this->populateView(
            'tracking::larametrics.logs.show', [
            'log' => $logArray[$index],
            'pageTitle' => 'Viewing Log'
            ]
        );
    }

}
