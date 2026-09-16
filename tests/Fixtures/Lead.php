<?php

declare(strict_types=1);

namespace Tracking\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Lead extends Model
{
    protected $table = 'leads';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(fn (Lead $lead) => $lead->uuid ??= (string) Str::uuid());
    }

    public function events(): HasMany
    {
        return $this->hasMany(LeadEvent::class)->latest();
    }
}
