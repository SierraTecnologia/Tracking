<?php

namespace Siravel\Notifications\Metrics;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Tracking\Models\Metrics\LarametricsModel;

class ModelChanged extends Notification implements ShouldQueue
{
    use Queueable;

    private LarametricsModel $larametricsModel;
}
