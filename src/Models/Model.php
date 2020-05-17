<?php

namespace Tracking\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Auth;

class Model extends EloquentModel
{
    /**
     * Provides public access to get the raw attribute value from the model.
     * Used in areas where no mutations are required but performance is critical.
     *
     * @param  $key
     * @return mixed
     */
    public function getRawAttribute($key)
    {
        return parent::getAttributeFromArray($key);
    }

    public static function boot()
    {
        parent::boot();

    }
}