<?php

namespace Tracking\Models;

use Muleta\Traits\Models\ArchiveTrait;


class Trackings extends ArchiveTrait
{
    
    public $table = 'audits';

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
