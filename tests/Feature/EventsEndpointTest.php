<?php

declare(strict_types=1);

namespace Tracking\Tests\Feature;

use Tracking\Models\TrackingEvent;
use Tracking\Tests\Fixtures\Contact;
use Tracking\Tests\Fixtures\TokenResolver;
use Tracking\Tests\TestCase;

class EventsEndpointTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('tracking.target.model', Contact::class);
    }

    public function test_grava_eventos_json_no_alvo_pelo_uuid(): void
    {
        $contato = Contact::create(['name' => 'Ana']);

        $this->postJson("/api/v1/leads/{$contato->uuid}/eventos", [
            'events' => [
                ['type' => 'clique_wa', 'description' => 'Clicou no WhatsApp', 'meta' => ['session_id' => 's_1']],
                ['type' => 'rolagem', 'description' => 'Rolou 75%', 'meta' => ['profundidade' => 75]],
            ],
        ])->assertOk()->assertJson(['ok' => true, 'recorded' => 2]);

        $this->assertSame(2, $contato->trackingEvents()->count());
        $this->assertSame('s_1', TrackingEvent::ofType('clique_wa')->first()->session_id);
    }

    public function test_aceita_beacon_text_plain(): void
    {
        $contato = Contact::create(['name' => 'Beto']);

        $this->call('POST', "/api/v1/leads/{$contato->uuid}/eventos", [], [], [], ['CONTENT_TYPE' => 'text/plain'], json_encode([
            'type' => 'saida',
            'description' => 'Saiu após 40s',
            'meta' => ['segundos' => 40],
        ]))->assertOk()->assertJson(['recorded' => 1]);
    }

    public function test_preflight_responde_204(): void
    {
        $this->call('OPTIONS', '/api/v1/leads/qualquer/eventos')->assertNoContent();
    }

    public function test_alvo_inexistente_404(): void
    {
        $this->postJson('/api/v1/leads/00000000-0000-0000-0000-000000000000/eventos', ['type' => 'a', 'description' => 'b'])
            ->assertNotFound()->assertJson(['error' => 'lead_nao_encontrado']);
    }

    public function test_janela_expirada_410(): void
    {
        $contato = Contact::create(['name' => 'Caio']);
        $contato->forceFill(['created_at' => now()->subHours(7)])->save();

        $this->postJson("/api/v1/leads/{$contato->uuid}/eventos", ['type' => 'a', 'description' => 'b'])
            ->assertStatus(410)->assertJson(['error' => 'janela_expirada']);
    }

    public function test_sem_alvo_configurado_avisa_em_vez_de_quebrar(): void
    {
        config()->set('tracking.target.model', null);
        app()->forgetInstance('tracking');

        $this->postJson('/api/v1/leads/x/eventos', ['type' => 'a', 'description' => 'b'])
            ->assertStatus(500)->assertJson(['error' => 'alvo_nao_configurado']);
    }

    public function test_resolver_customizado(): void
    {
        config()->set('tracking.target.resolver', TokenResolver::class);
        app()->forgetInstance('tracking');
        $contato = Contact::create(['name' => 'token-da-dora']);

        $this->postJson('/api/v1/leads/token-da-dora/eventos', ['type' => 'interacao', 'description' => 'ok'])
            ->assertOk()->assertJson(['recorded' => 1]);

        $this->assertSame(1, $contato->trackingEvents()->count());
    }
}
