<?php

namespace Tracking\Http\Middleware;

use Closure;
use Siravel\Services\System\AnalyticsService;

class Analytics
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$request->ajax()) {
            app(AnalyticsService::class)->log($request);
        }

        return $next($request);
    }
}
