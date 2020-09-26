<?php

namespace Tracking\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Auth;

class Model extends EloquentModel
{


    public static function boot()
    {
        parent::boot();

    }
}