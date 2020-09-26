<?php

namespace Tracking\Models\Metrics;

use Illuminate\Database\Eloquent\Model;

class LarametricsLog extends Model
{
    protected string $table = 'larametrics_logs';

    /**
     * @var array
     */
    public array $guarded = [];
}