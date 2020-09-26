<?php

declare(strict_types=1);

namespace Tracking\Models\Statistics;

use Illuminate\Database\Eloquent\Model;
use Muleta\Recursos\Cacheable\CacheableEloquent;
use Muleta\Traits\Models\ValidatingTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Geoip extends Model
{
    use ValidatingTrait;
    use CacheableEloquent;

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'client_ip',
        'latitude',
        'longitude',
        'country_code',
        'client_ips',
        'is_from_trusted_proxy',
        'division_code',
        'postal_code',
        'timezone',
        'city',
    ];

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'client_ip' => 'string',
        'latitude' => 'string',
        'longitude' => 'string',
        'country_code' => 'string',
        'client_ips' => 'json',
        'is_from_trusted_proxy' => 'boolean',
        'division_code' => 'string',
        'postal_code' => 'string',
        'timezone' => 'string',
        'city' => 'string',
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
