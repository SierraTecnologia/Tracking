<?php

declare(strict_types=1);

namespace Tracking\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Tracking\Facades\Tracking;
use Tracking\Models\TrackingEvent;
use Tracking\Support\Journey;

/**
 * Dá a qualquer Model do CRM uma linha do tempo comportamental.
 *
 * O alvo passa a ter `trackingEvents()`, `track()` e `journey()`; a
 * TrackingService reconhece a relação e grava nela sem configuração.
 */
trait HasTrackingEvents
{
    public function trackingEvents(): MorphMany
    {
        return $this->morphMany(config('tracking.event_model') ?: TrackingEvent::class, 'trackable');
    }

    /** @param array<string, mixed>|null $meta */
    public function track(string $type, string $description, ?array $meta = null): mixed
    {
        return Tracking::recordEvent($this, $type, $description, $meta);
    }

    public function journey(): Journey
    {
        return Tracking::journey($this);
    }
}
