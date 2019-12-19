<?php

namespace Tracking\Services;

class MenuService
{

    public static function getAdminMenu()
    {
        $tracking = [];
        $tracking[] = [
            'text'        => 'Analytics',
            'url'         => route('tracking.analytics'),
            'icon'        => 'dashboard',
            'icon_color'  => 'blue',
            'label_color' => 'success',
            // 'access' => \App\Models\Role::$ADMIN
        ];
        $tracking[] = [
            'text'        => 'Metrics',
            'url'         => route('larametrics::metrics.index'),
            'icon'        => 'dashboard',
            'icon_color'  => 'blue',
            'label_color' => 'success',
            // 'access' => \App\Models\Role::$ADMIN
        ];

        return $tracking;
    }
}
