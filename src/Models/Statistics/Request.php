<?php

declare(strict_types=1);

namespace Tracking\Models\Statistics;

use Illuminate\Database\Eloquent\Model;
use Muleta\Recursos\Cacheable\CacheableEloquent;
use Illuminate\Database\Eloquent\Builder;
use Muleta\Traits\Models\ValidatingTrait;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Request extends Model
{
    use ValidatingTrait;
    use CacheableEloquent;

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'route_id',
        'agent_id',
        'device_id',
        'platform_id',
        'path_id',
        'geoip_id',
        'user_id',
        'user_type',
        'session_id',
        'status_code',
        'protocol_version',
        'referer',
        'language',
        'is_no_cache',
        'wants_json',
        'is_secure',
        'is_json',
        'is_ajax',
        'is_pjax',
        'created_at',
    ];

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'route_id' => 'integer',
        'agent_id' => 'integer',
        'device_id' => 'integer',
        'platform_id' => 'integer',
        'path_id' => 'integer',
        'geoip_id' => 'integer',
        'user_id' => 'integer',
        'user_type' => 'string',
        'session_id' => 'string',
        'status_code' => 'integer',
        'protocol_version' => 'string',
        'referer' => 'string',
        'language' => 'string',
        'is_no_cache' => 'boolean',
        'wants_json' => 'boolean',
        'is_secure' => 'boolean',
        'is_json' => 'boolean',
        'is_ajax' => 'boolean',
        'is_pjax' => 'boolean',
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

    /**
     * {@inheritdoc}
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(
            function (self $request) {
                $request->path()->increment('count');
                $request->route()->increment('count');
                $request->geoip()->increment('count');
                $request->agent()->increment('count');
                $request->device()->increment('count');
                $request->platform()->increment('count');
            }
        );
    }
}
