<?php

namespace Tracking\Http\Controllers\System\Metrics;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Tracking\Models\Metrics\LarametricsRequest;

class RequestController extends Controller
{
    
    public function index()
    {
        $requests = LarametricsRequest::orderBy('created_at', 'desc')
            ->get();
            
        return view('larametrics::requests.index', [
            'requests' => $requests,
            'pageTitle' => 'Laravel Requests'
        ]);
    }

    public function show(LarametricsRequest $request)
    {
        return view('larametrics::requests.show', [
            'request' => $request,
            'pageTitle' => 'Viewing Request #' . $request->id
        ]);
    }

}
