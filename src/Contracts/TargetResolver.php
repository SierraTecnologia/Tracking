<?php

declare(strict_types=1);

namespace Tracking\Contracts;

interface TargetResolver
{
    /**
     * Traduz o identificador que circula no navegador (uuid, token...) no
     * registro que recebe os eventos. Nulo quando não existe.
     */
    public function resolve(string $identifier): ?object;
}
