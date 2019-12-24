<?php

namespace Tracking\Services;

class MenuService
{

    public static function getAdminMenu()
    {
        $tracking = [];
        $tracking[] = [
            'text'        => 'Analytics',
            'route'       => 'tracking.analytics',
            'icon'        => 'dashboard',
            'icon_color'  => 'blue',
            'label_color' => 'success',
            // 'access' => \App\Models\Role::$ADMIN
        ];
        $tracking[] = [
            'text'        => 'Metrics',
            'route'       => 'larametrics::metrics.index',
            'icon'        => 'dashboard',
            'icon_color'  => 'blue',
            'label_color' => 'success',
            // 'access' => \App\Models\Role::$ADMIN
        ];

        return $tracking;
    }
}
