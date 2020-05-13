<?php

declare(strict_types=1);

namespace Tracking\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CleanStatisticsRequests implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        ! \Illuminate\Support\Facades\Config::get('tracking.statistics.lifetime') || app('tracking.statistics.request')->where('created_at', '<=', now()->subDays(\Illuminate\Support\Facades\Config::get('tracking.statistics.lifetime')))->delete();
    }
}
