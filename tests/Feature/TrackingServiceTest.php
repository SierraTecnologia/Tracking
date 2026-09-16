<?php

declare(strict_types=1);

namespace Tracking\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Tracking\Events\EventRecorded;
use Tracking\Facades\Tracking;
use Tracking\Models\TrackingEvent;
use Tracking\Services\TrackingService;
use Tracking\Tests\Fixtures\Contact;
use Tracking\Tests\Fixtures\Lead;
use Tracking\Tests\Fixtures\LeadEvent;
use Tracking\Tests\TestCase;

class TrackingServiceTest extends TestCase
{
    public function test_grava_sem_alvo_na_tabela_da_lib(): void
    {
        $event = Tracking::recordEvent(null, 'navegacao', 'Acessou /comecar', [
            'utm_source' => 'google',
            'session_id' => 's_abc',
            'visitor_id' => 'v_abc',
            'site' => 'ricasolucoes.com.br',
            'url' => 'https://ricasolucoes.com.br/comecar',
            'elapsed_ms' => 120,
        ]);

        $this->assertInstanceOf(TrackingEvent::class, $event);
        $this->assertSame('s_abc', $event->session_id);
        $this->assertSame('v_abc', $event->visitor_id);
        $this->assertSame('ricasolucoes.com.br', $event->site);
        $this->assertSame(120, $event->elapsed_ms);
        $this->assertSame(1, TrackingEvent::forSession('s_abc')->count());
    }

    public function test_grava_no_alvo_com_a_trait(): void
    {
        $contato = Contact::create(['name' => 'Ana']);

        $contato->track('clique_wa', 'Clicou no WhatsApp');
        Tracking::recordSteps($contato, [
            ['type' => 'navegacao', 'description' => 'Acessou /'],
            ['type' => 'rolagem', 'description' => 'Rolou 50%', 'meta' => ['profundidade' => 50]],
        ]);

        $this->assertSame(3, $contato->trackingEvents()->count());
        $this->assertSame(Contact::class, TrackingEvent::first()->trackable_type);
        $this->assertSame(50, $contato->journey()->maxScroll());
    }

    public function test_grava_na_relacao_events_do_lead_do_crm(): void
    {
        $lead = Lead::create(['name' => 'Bruno']);

        $gravados = Tracking::recordSteps($lead, [
            ['type' => 'navegacao', 'description' => 'Acessou /'],
            ['type' => 'interacao', 'description' => 'Selecionou chip'],
        ]);

        $this->assertSame(2, $gravados);
        $this->assertSame(2, LeadEvent::where('lead_id', $lead->id)->count());
        $this->assertSame(0, TrackingEvent::count());
        $this->assertSame(2, Tracking::journey($lead)->count());
    }

    public function test_tipos_fora_da_lista_sao_descartados(): void
    {
        config()->set('tracking.allowed_types', ['navegacao']);

        $gravados = Tracking::recordSteps(null, [
            ['type' => 'navegacao', 'description' => 'ok'],
            ['type' => 'hack', 'description' => 'não'],
        ]);

        $this->assertSame(1, $gravados);
        $this->assertNull(Tracking::recordEvent(null, 'hack', 'não'));
    }

    public function test_dispara_evento_para_o_crm_escutar(): void
    {
        Event::fake([EventRecorded::class]);
        $contato = Contact::create(['name' => 'Carla']);

        $contato->track('clique_wa', 'Clicou no WhatsApp', ['texto' => 'Falar']);

        Event::assertDispatched(EventRecorded::class, fn (EventRecorded $e) => $e->target->is($contato)
            && $e->type === 'clique_wa'
            && $e->meta['texto'] === 'Falar');
    }

    public function test_janela_expira_pela_data_de_criacao_do_alvo(): void
    {
        $contato = Contact::create(['name' => 'Dora']);
        $this->assertFalse(Tracking::isWindowExpired($contato));

        $contato->forceFill(['created_at' => now()->subHours(7)])->save();
        $this->assertTrue(Tracking::isWindowExpired($contato));
        $this->assertFalse(Tracking::isWindowExpired($contato, 8));
    }

    public function test_singleton_e_alias_resolvem_o_mesmo_servico(): void
    {
        $this->assertSame(app('tracking'), app(TrackingService::class));
    }
}
