<?php

namespace Tracking\Models\Metrics;

use Illuminate\Database\Eloquent\Model;

class LarametricsModel extends Model
{
    protected string $table = 'larametrics_models';

    /**
     * @var array
     */
    public array $guarded = [];
}