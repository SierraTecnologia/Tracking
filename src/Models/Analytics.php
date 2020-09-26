<?php

namespace Tracking\Models;

class Analytics extends Model
{
    
    public string $table = 'analytics';

    public string $primaryKey = 'id';

    /**
     * @var string[]
     *
     * @psalm-var array{0: string, 1: string, 2: string}
     */
    public array $fillable = [
        'token',
        'data',
        'business_code'
    ];
}
