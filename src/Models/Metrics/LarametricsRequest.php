<?php

namespace Tracking\Models\Metrics;

use Illuminate\Database\Eloquent\Model;

class LarametricsRequest extends Model
{
    protected string $table = 'larametrics_requests';

    /**
     * @var array
     */
    public array $guarded = [];
}