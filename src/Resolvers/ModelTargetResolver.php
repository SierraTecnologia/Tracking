<?php

declare(strict_types=1);

namespace Tracking\Resolvers;

use Tracking\Contracts\TargetResolver;

/**
 * Resolver padrão: busca `tracking.target.model` pela coluna
 * `tracking.target.key`.
 */
class ModelTargetResolver implements TargetResolver
{
    public function __construct(
        protected ?string $modelClass = null,
        protected ?string $key = null,
    ) {
        $this->modelClass = $modelClass ?: config('tracking.target.model') ?: config('tracking.lead_model');
        $this->key = $key ?: (string) config('tracking.target.key', 'uuid');
    }

    public function configured(): bool
    {
        return is_string($this->modelClass) && class_exists($this->modelClass);
    }

    public function resolve(string $identifier): ?object
    {
        if (! $this->configured() || $identifier === '') {
            return null;
        }

        return $this->modelClass::query()->where($this->key, $identifier)->first();
    }
}
