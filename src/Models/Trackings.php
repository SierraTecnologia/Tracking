<?php

namespace Tracking\Models;

use Informate\Traits\ArchiveTrait;

use Informate\Traits\BusinessTrait;

class Trackings extends ArchiveTrait
{
    use BusinessTrait;
    
    public $table = 'audits';

    public $primaryKey = 'id';

    public $fillable = [
        'token',
        'data',
    ];

    public $rules = [];
}
