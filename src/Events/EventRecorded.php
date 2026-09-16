<?php

declare(strict_types=1);

namespace Tracking\Events;

/**
 * Disparado a cada evento gravado. É o ponto de conexão com o CRM: um
 * listener pode recalcular score, mover etapa de funil, avisar o vendedor
 * quando o lead clicou no WhatsApp, etc.
 */
final class EventRecorded
{
    public function __construct(
        public readonly mixed $target,
        public readonly mixed $event,
        public readonly string $type,
        public readonly string $description,
        /** @var array<string, mixed>|null */
        public readonly ?array $meta,
    ) {}
}
