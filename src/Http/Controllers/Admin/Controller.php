<?php

namespace Tracking\Http\Controllers\Admin;

use Tracking\Http\Controllers\Controller as BaseController;

class Controller extends BaseController
{
    public function populateView($view, $vars)
    {
        return view(
            $view,
            $vars
        );
    }
}
