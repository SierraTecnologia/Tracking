<?php

declare(strict_types=1);

namespace Tracking\Models\Statistics;

use Illuminate\Database\Eloquent\Model;
use Support\Cacheable\CacheableEloquent;
use Support\Traits\ValidatingTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    use ValidatingTrait;
    use CacheableEloquent;

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'name',
        'action',
        'middleware',
        'path',
        'parameters',
    ];

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'name' => 'string',
        'action' => 'string',
        'middleware' => 'json',
        'path' => 'string',
        'parameters' => 'json',
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
     * The default rules that the model will validate against.
     *
     * @var array
     */
    protected $rules = [
        'name' => 'required|string',
        'action' => 'required|string',
        'middleware' => 'nullable|array',
        'path' => 'required|string',
        'parameters' => 'nullable|array',
    ];

    /**
     * Whether the model should throw a
     * ValidationException if it fails validation.
     *
     * @var bool
     */
    protected $throwValidationExceptions = true;

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('tracking.statistics.tables.routes'));
    }

    /**
     * The route may have many requests.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function requests(): HasMany
    {
        return $this->hasMany(config('tracking.statistics.models.request'), 'route_id', 'id');
    }
}
