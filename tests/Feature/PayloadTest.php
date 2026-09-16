<?php

declare(strict_types=1);

namespace Tracking\Tests\Feature;

use Illuminate\Http\Request;
use Tracking\Support\TrackingPayload;
use Tracking\Tests\TestCase;

class PayloadTest extends TestCase
{
    public function test_le_um_evento_unico_em_json(): void
    {
        $request = $this->jsonRequest([
            'type' => 'rolagem',
            'description' => 'Rolou 50% da página',
            'meta' => ['profundidade' => 50],
            'elapsed_ms' => 2400,
        ]);

        $parsed = TrackingPayload::fromRequest($request);

        $this->assertCount(1, $parsed);
        $this->assertSame('rolagem', $parsed[0]['type']);
        $this->assertSame(['profundidade' => 50, 'elapsed_ms' => 2400], $parsed[0]['meta']);
        $this->assertSame(2400, $parsed[0]['elapsed_ms']);
    }

    public function test_le_lote_em_text_plain_como_o_beacon_manda(): void
    {
        $request = Request::create('/eventos', 'POST', [], [], [], ['CONTENT_TYPE' => 'text/plain'], json_encode([
            'events' => [
                ['type' => 'clique_wa', 'description' => 'Clicou no WhatsApp', 'session_id' => 's_1', 'visitor_id' => 'v_1'],
                ['type' => 'interacao', 'description' => 'Abriu FAQ'],
            ],
        ]));

        $parsed = TrackingPayload::fromRequest($request);

        $this->assertCount(2, $parsed);
        $this->assertSame('s_1', $parsed[0]['meta']['session_id']);
        $this->assertSame('v_1', $parsed[0]['meta']['visitor_id']);
    }

    public function test_aceita_a_chave_steps_da_captacao(): void
    {
        $parsed = TrackingPayload::fromRequest($this->jsonRequest(['steps' => [
            ['type' => 'navegacao', 'description' => 'Acessou /'],
        ]]));

        $this->assertCount(1, $parsed);
    }

    public function test_descarta_itens_sem_tipo_ou_descricao(): void
    {
        $parsed = TrackingPayload::normalizeList([
            ['type' => '', 'description' => 'x'],
            ['type' => 'a', 'description' => ''],
            'texto solto',
            ['type' => 'ok', 'description' => 'válido'],
        ]);

        $this->assertCount(1, $parsed);
    }

    public function test_corta_tipo_e_descricao_nos_limites(): void
    {
        $parsed = TrackingPayload::normalize([
            'type' => str_repeat('t', 80),
            'description' => str_repeat('d', 400),
        ]);

        $this->assertSame(50, mb_strlen($parsed['type']));
        $this->assertSame(255, mb_strlen($parsed['description']));
    }

    public function test_meta_grande_perde_as_chaves_maiores(): void
    {
        config()->set('tracking.limits.meta_bytes', 200);

        $parsed = TrackingPayload::normalize([
            'type' => 'interacao',
            'description' => 'x',
            'meta' => ['pequeno' => 'ok', 'gigante' => str_repeat('g', 500)],
        ]);

        $this->assertSame('ok', $parsed['meta']['pequeno']);
        $this->assertArrayNotHasKey('gigante', $parsed['meta']);
        $this->assertTrue($parsed['meta']['truncado']);
    }

    public function test_limita_eventos_por_requisicao(): void
    {
        config()->set('tracking.limits.events_per_request', 3);

        $itens = array_fill(0, 10, ['type' => 'rolagem', 'description' => 'x']);

        $this->assertCount(3, TrackingPayload::normalizeList($itens));
    }

    public function test_created_at_invalido_vira_nulo(): void
    {
        $parsed = TrackingPayload::normalize(['type' => 'a', 'description' => 'b', 'created_at' => 'ontem à noite']);

        $this->assertNull($parsed['created_at']);
    }

    /** @param array<string, mixed> $body */
    private function jsonRequest(array $body): Request
    {
        return Request::create('/eventos', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($body));
    }
}
