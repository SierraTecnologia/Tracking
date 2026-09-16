<?php

declare(strict_types=1);

namespace Tracking\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class LeadEvent extends Model
{
    protected $table = 'lead_events';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }
}
