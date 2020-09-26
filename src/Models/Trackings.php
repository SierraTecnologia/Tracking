<?php

namespace Tracking\Models;

use Muleta\Traits\Models\ArchiveTrait;


class Trackings extends ArchiveTrait
{
    
    public string $table = 'audits';

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
