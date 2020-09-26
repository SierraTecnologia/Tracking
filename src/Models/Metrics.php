<?php

namespace Tracking\Models;

class Metrics extends Model
{
    public string $table = 'metrics';

    public string $primaryKey = 'id';

    /**
     * @var string[]
     *
     * @psalm-var array{0: string, 1: string}
     */
    public array $fillable = [
        'token',
        'data',
    ];

    /**
     * @var array
     */
    public array $rules = [];
}
