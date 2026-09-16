<?php

declare(strict_types=1);

namespace Tracking\Tests\Feature;

use Tracking\Tests\TestCase;

class AssetDeliveryTest extends TestCase
{
    public function test_serve_o_tracker_na_rota_padrao(): void
    {
        $response = $this->get('/js/tracker.js');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/javascript', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('max-age=86400', (string) $response->headers->get('Cache-Control'));
        $this->assertNotEmpty($response->headers->get('ETag'));
        $this->assertStringContainsString('Universal Lead & Behavioral Tracker', (string) $response->getContent());
        $this->assertStringContainsString('window.RicaTracker', (string) $response->getContent());
        $this->assertStringContainsString('window.Tracking', (string) $response->getContent());
    }

    public function test_serve_o_tracker_no_alias(): void
    {
        $this->get('/tracking/tracker.js')->assertStatus(200);
    }

    public function test_responde_304_quando_o_navegador_ja_tem_a_versao(): void
    {
        $etag = (string) $this->get('/js/tracker.js')->headers->get('ETag');

        $this->get('/js/tracker.js', ['If-None-Match' => $etag])->assertStatus(304);
    }
}
