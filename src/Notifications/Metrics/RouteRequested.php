<?php

namespace Siravel\Notifications\Metrics;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Tracking\Models\Metrics\LarametricsRequest;

class RouteRequested extends Notification implements ShouldQueue
{
    use Queueable;

    private LarametricsRequest $larametricsRequest;

    /**
     * @var (float|mixed)[]
     *
     * @psalm-var array{id: mixed, method: mixed, uri: mixed, ip: mixed, execution_time: float}
     */
    private array $requestInfo;
}
