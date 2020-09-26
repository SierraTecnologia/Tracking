<?php

declare(strict_types=1);

namespace Tracking\Models\Statistics;

use Illuminate\Database\Eloquent\Model;
use Muleta\Recursos\Cacheable\CacheableEloquent;
use Illuminate\Database\Eloquent\Builder;
use Muleta\Traits\Models\ValidatingTrait;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Datum extends Model
{
    use ValidatingTrait;
    use CacheableEloquent;

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'session_id',
        'user_id',
        'user_type',
        'status_code',
        'uri',
        'method',
        'server',
        'input',
        'created_at',
    ];

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'session_id' => 'string',
        'user_id' => 'integer',
        'user_type' => 'string',
        'status_code' => 'integer',
        'uri' => 'string',
        'method' => 'string',
        'server' => 'json',
        'input' => 'json',
        'created_at' => 'datetime',
    ];

    /**
     * {@inheritdoc}
     */
    public $timestamps = false;

    /**
     * {@inheritdoc}
     */
    protected $observables = [
        'validating',
        'validated',
    ];

    /**
     * Whether the model should throw a
     * ValidationException if it fails validation.
     *
     * @var bool
     */
    protected $throwValidationExceptions = true;
}
