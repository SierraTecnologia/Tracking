<?php

namespace Tracking\Models;

class Analytics extends Model
{
    public $table = 'analytics';

    public $primaryKey = 'id';

    /**
     * @var string[]
     *
     * @psalm-var array{0: string, 1: string, 2: string}
     */
    public $fillable = [
        'token',
        'data',
        'business_code'
    ];
}
