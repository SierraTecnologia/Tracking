<?php

namespace Tracking\Models;

class Metrics extends Model
{
    public $table = 'metrics';

    public $primaryKey = 'id';

    /**
     * @var string[]
     *
     * @psalm-var array{0: string, 1: string}
     */
    public $fillable = [
        'token',
        'data',
    ];

    /**
     * @var array
     */
    public $rules = [];
}
