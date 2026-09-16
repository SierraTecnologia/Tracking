<?php

declare(strict_types=1);

namespace Tracking\Facades;

use Illuminate\Support\Facades\Facade;
use Tracking\Services\TrackingService;

/**
 * @method static array parsePayload(\Illuminate\Http\Request $request)
 * @method static mixed recordEvent(mixed $target, string $type, string $description, ?array $meta = null, ?\DateTimeInterface $createdAt = null)
 * @method static int recordSteps(mixed $target, array $steps)
 * @method static int recordRequestEvents(mixed $target, \Illuminate\Http\Request $request)
 * @method static bool isWindowExpired(mixed $target, ?int $maxHours = null)
 * @method static ?object resolveTarget(string $identifier)
 * @method static \Tracking\Contracts\TargetResolver resolver()
 * @method static \Illuminate\Support\Collection eventsFor(mixed $target)
 * @method static \Tracking\Support\Journey journey(mixed $target)
 * @method static string eventModel()
 * @method static bool accepts(string $type)
 *
 * @see TrackingService
 */
class Tracking extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'tracking';
    }
}
