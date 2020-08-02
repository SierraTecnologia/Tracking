<?php

namespace Tracking\Models;

use Muleta\Traits\Models\ArchiveTrait;


class Trackings extends ArchiveTrait
{
    
    public $table = 'audits';

    public $primaryKey = 'id';

    public $fillable = [
        'token',
        'data',
    ];

    public $rules = [];
}
