<?php

declare(strict_types=1);

namespace Tracking\Console;

use Illuminate\Console\Command;
use Tracking\Services\TrackingService;
use Tracking\Support\EventType;

/**
 * Apaga eventos comportamentais antigos. O que o CRM gravou por conta
 * própria (criado, nota, status...) fica.
 */
class PruneCommand extends Command
{
    protected $signature = 'tracking:prune {--days= : Dias a manter (padrão: tracking.retention_days)}';

    protected $description = 'Remove eventos comportamentais mais antigos que o período de retenção.';

    public function handle(TrackingService $tracking): int
    {
        $dias = (int) ($this->option('days') ?: config('tracking.retention_days', 180));

        if ($dias < 1) {
            $this->error('Informe um número de dias maior que zero.');

            return self::INVALID;
        }

        $modelo = $tracking->eventModel();
        $limite = now()->subDays($dias);

        $removidos = $modelo::query()
            ->whereIn('type', EventType::behavioral())
            ->where('created_at', '<', $limite)
            ->delete();

        $this->info("{$removidos} evento(s) anteriores a {$limite->toDateString()} removidos.");

        return self::SUCCESS;
    }
}
