<?php

declare(strict_types=1);

namespace Tracking\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Tracking\Concerns\HasTrackingEvents;

class Contact extends Model
{
    use HasTrackingEvents;

    protected $table = 'contacts';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(fn (Contact $contact) => $contact->uuid ??= (string) Str::uuid());
    }
}
