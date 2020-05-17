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

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->alert($this->description);

        if (file_exists($path = 'database/migrations/sierratecnologia/laravel-statistics')) {
            $this->call(
                'migrate', [
                '--step' => true,
                '--path' => $path,
                '--force' => $this->option('force'),
                ]
            );
        } else {
            $this->warn('No migrations found! Consider publish them first: <fg=green>php artisan sierratecnologia:publish:statistics</>');
        }

        $this->line('');
    }
}
