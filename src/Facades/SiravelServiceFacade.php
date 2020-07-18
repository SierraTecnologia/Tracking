<?php

namespace Tracking\Facades;

use Illuminate\Support\Facades\Facade;

class TrackingServiceFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'TrackingService';
    }
}
