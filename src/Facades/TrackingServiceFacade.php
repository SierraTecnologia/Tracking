<?php

namespace Tracking\Facades;

use Illuminate\Support\Facades\Facade;

class TrackingServiceFacade extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'TrackingService';
    }
}
