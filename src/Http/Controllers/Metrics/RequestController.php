<?php

namespace Tracking\Http\Controllers\Metrics;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Tracking\Models\Metrics\LarametricsRequest;

class RequestController extends Controller
{
    
    public function index(Request $request): \Illuminate\View\View
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

    public function show(LarametricsRequest $request): \Illuminate\View\View
    {
        return view(
            'rica.larametrics::requests.show', [
            'request' => $request,
            'pageTitle' => 'Viewing Request #' . $request->id
            ]
        );
    }

}
