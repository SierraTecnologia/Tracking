<?php

namespace SiObject\Http\Middleware;

use Closure;
use Siravel\Services\System\TrackingsService;

class Trackings
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
            app(TrackingsService::class)->log($request);
        }

        return $next($request);
    }
}
