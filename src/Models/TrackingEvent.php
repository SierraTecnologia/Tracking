<?php

declare(strict_types=1);

namespace Tracking\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Tracking\Support\EventType;

class TrackingEvent extends Model
{
    protected $table = 'tracking_events';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'elapsed_ms' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Sessão, visitante, site e URL chegam dentro do meta; aqui viram
        // coluna para dar índice e agrupamento sem abrir JSON.
        static::creating(function (TrackingEvent $event): void {
            $meta = is_array($event->meta) ? $event->meta : [];

            foreach (['session_id', 'visitor_id', 'site', 'url'] as $coluna) {
                if ($event->{$coluna} === null && isset($meta[$coluna]) && is_scalar($meta[$coluna])) {
                    $event->{$coluna} = (string) $meta[$coluna];
                }
            }

            if ($event->elapsed_ms === null && isset($meta['elapsed_ms']) && is_numeric($meta['elapsed_ms'])) {
                $event->elapsed_ms = (int) $meta['elapsed_ms'];
            }
        });
    }

    public function trackable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeBehavioral(Builder $query): Builder
    {
        return $query->whereIn('type', EventType::behavioral());
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForSession(Builder $query, string $sessionId): Builder
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeForVisitor(Builder $query, string $visitorId): Builder
    {
        return $query->where('visitor_id', $visitorId);
    }

    public function scopeForSite(Builder $query, string $site): Builder
    {
        return $query->where('site', $site);
    }
}
