<?php

declare(strict_types=1);

namespace Tracking\Console\Commands;

use Illuminate\Console\Command;

class RollbackCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sierratecnologia:rollback:statistics {--force : Force the operation to run when in production.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback SierraTecnologia Statistics Tables.';
}
