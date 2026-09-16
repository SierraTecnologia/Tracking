<?php

declare(strict_types=1);

namespace Tracking\Tests\Fixtures;

use Tracking\Contracts\TargetResolver;

/** Resolver alternativo: o identificador é o nome do contato. */
class TokenResolver implements TargetResolver
{
    public function resolve(string $identifier): ?object
    {
        return Contact::query()->where('name', $identifier)->first();
    }
}
