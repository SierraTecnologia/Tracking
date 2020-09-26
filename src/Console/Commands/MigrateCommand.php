<?php

declare(strict_types=1);

namespace Tracking\Console\Commands;

use Illuminate\Console\Command;

class MigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sierratecnologia:migrate:statistics {--force : Force the operation to run when in production.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate SierraTecnologia Statistics Tables.';
}
