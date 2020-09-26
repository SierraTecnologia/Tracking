<?php

declare(strict_types=1);

namespace Tracking\Models\Statistics;

use Illuminate\Database\Eloquent\Model;
use Muleta\Recursos\Cacheable\CacheableEloquent;
use Muleta\Traits\Models\ValidatingTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    use ValidatingTrait;
    use CacheableEloquent;

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'name',
        'kind',
        'family',
        'version',
    ];

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'name' => 'string',
        'kind' => 'string',
        'family' => 'string',
        'version' => 'string',
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
