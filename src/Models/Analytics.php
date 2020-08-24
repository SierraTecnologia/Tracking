<?php

namespace Tracking\Models;

class Analytics extends Model
{
    
    public $table = 'analytics';

    public $primaryKey = 'id';

    public $fillable = [
        'token',
        'data',
        'business_code'
    ];

    public $rules = [];
}
