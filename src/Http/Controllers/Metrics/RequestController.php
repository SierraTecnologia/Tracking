<?php

namespace Tracking\Http\Controllers\Metrics;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Tracking\Models\Metrics\LarametricsRequest;

class RequestController extends Controller
{
    
    public function index()
    {
        $requests = LarametricsRequest::orderBy('created_at', 'desc')
            ->get();
            
        return view(
            'rica.larametrics::requests.index', [
            'requests' => $requests,
            'pageTitle' => 'Laravel Requests'
            ]
        );
    }

    public function show(LarametricsRequest $request)
    {
        return view(
            'rica.larametrics::requests.show', [
            'request' => $request,
            'pageTitle' => 'Viewing Request #' . $request->id
            ]
        );
    }

}
