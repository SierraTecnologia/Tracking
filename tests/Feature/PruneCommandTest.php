<?php

declare(strict_types=1);

namespace Tracking\Tests\Feature;

use Tracking\Models\TrackingEvent;
use Tracking\Tests\TestCase;

class PruneCommandTest extends TestCase
{
    public function test_apaga_so_os_comportamentais_antigos(): void
    {
        TrackingEvent::create(['type' => 'rolagem', 'description' => 'velho', 'created_at' => now()->subDays(200)]);
        TrackingEvent::create(['type' => 'rolagem', 'description' => 'novo', 'created_at' => now()->subDays(10)]);
        TrackingEvent::create(['type' => 'nota', 'description' => 'do CRM, velha', 'created_at' => now()->subDays(400)]);

        $this->artisan('tracking:prune')->assertSuccessful()->expectsOutputToContain('1 evento(s)');

        $this->assertSame(2, TrackingEvent::count());
        $this->assertNull(TrackingEvent::where('description', 'velho')->first());
    }

    public function test_aceita_dias_por_opcao(): void
    {
        TrackingEvent::create(['type' => 'secao', 'description' => 'x', 'created_at' => now()->subDays(5)]);

        $this->artisan('tracking:prune', ['--days' => 3])->assertSuccessful();

        $this->assertSame(0, TrackingEvent::count());
    }
}
