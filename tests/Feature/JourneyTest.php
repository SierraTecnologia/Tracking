<?php

declare(strict_types=1);

namespace Tracking\Tests\Feature;

use Tracking\Support\Journey;
use Tracking\Tests\TestCase;

class JourneyTest extends TestCase
{
    public function test_jornada_vazia(): void
    {
        $journey = Journey::fromEvents([]);

        $this->assertTrue($journey->isEmpty());
        $this->assertSame(0, $journey->engagement());
        $this->assertStringContainsString('Sem rastro', $journey->summary());
    }

    public function test_resume_a_jornada_completa(): void
    {
        $journey = Journey::fromEvents([
            ['type' => 'navegacao', 'description' => 'Acessou / via google', 'meta' => [
                'utm_source' => 'google', 'utm_medium' => 'cpc', 'utm_campaign' => 'sites',
                'device' => 'mobile', 'url' => 'https://ricasolucoes.com.br/?utm_source=google', 'elapsed_ms' => 10,
            ], 'created_at' => '2026-09-15 10:00:00'],
            ['type' => 'rolagem', 'description' => 'Rolou 50%', 'meta' => ['profundidade' => 50, 'elapsed_ms' => 20000]],
            ['type' => 'rolagem', 'description' => 'Rolou 100%', 'meta' => ['profundidade' => 100, 'elapsed_ms' => 90000]],
            ['type' => 'secao', 'description' => 'Visualizou seção: Planos', 'meta' => ['rotulo' => 'Planos']],
            ['type' => 'secao', 'description' => 'Visualizou seção: FAQ', 'meta' => ['rotulo' => 'FAQ']],
            ['type' => 'secao', 'description' => 'Visualizou seção: FAQ', 'meta' => ['rotulo' => 'FAQ']],
            ['type' => 'interacao', 'description' => 'Abriu FAQ', 'meta' => ['tipo' => 'faq']],
            ['type' => 'clique_wa', 'description' => 'Clicou no WhatsApp', 'meta' => ['elapsed_ms' => 240000]],
            ['type' => 'criado', 'description' => 'Lead criado'],
        ]);

        $this->assertSame(8, $journey->count());
        $this->assertSame('Google (cpc, sites)', $journey->origin());
        $this->assertSame('mobile', $journey->device());
        $this->assertSame('/', $journey->entryPage());
        $this->assertSame(100, $journey->maxScroll());
        $this->assertSame(['Planos', 'FAQ'], $journey->sections());
        $this->assertSame(1, $journey->interactions());
        $this->assertSame(1, $journey->whatsappClicks());
        $this->assertSame(240, $journey->durationSeconds());

        // 5 (1 página) + 20 (100%) + 8 (2 seções) + 4 (1 interação) + 15 (wa) + 8 (240/300*10)
        $this->assertSame(60, $journey->engagement());

        $resumo = $journey->summary();
        $this->assertStringContainsString('via Google (cpc, sites) pelo celular em /', $resumo);
        $this->assertStringContainsString('ficou 4 min', $resumo);
        $this->assertStringContainsString('rolou 100% da página', $resumo);
        $this->assertStringContainsString('passou por Planos e FAQ', $resumo);
        $this->assertStringContainsString('clicou no WhatsApp', $resumo);

        $this->assertSame('google', $journey->toArray()['utm']['source']);
    }

    public function test_origem_por_referrer_e_direto(): void
    {
        $porReferrer = Journey::fromEvents([
            ['type' => 'navegacao', 'description' => 'x', 'meta' => ['referrer' => 'https://www.instagram.com/p/abc']],
        ]);
        $direto = Journey::fromEvents([
            ['type' => 'navegacao', 'description' => 'x', 'meta' => []],
        ]);

        $this->assertSame('instagram.com', $porReferrer->origin());
        $this->assertSame('direto', $direto->origin());
        $this->assertStringStartsWith('Chegou direto', $direto->summary());
    }

    public function test_aceita_objetos_e_ordena_pela_data(): void
    {
        $journey = Journey::fromEvents([
            (object) ['type' => 'rolagem', 'description' => 'Rolou 25%', 'meta' => ['profundidade' => 25], 'created_at' => new \DateTimeImmutable('2026-09-15 10:05:00')],
            (object) ['type' => 'navegacao', 'description' => 'Acessou /planos', 'meta' => json_encode(['url' => 'https://x.com/planos']), 'created_at' => new \DateTimeImmutable('2026-09-15 10:00:00')],
        ]);

        $this->assertSame('/planos', $journey->entryPage());
        $this->assertSame(300, $journey->durationSeconds());
        $this->assertSame(['https://x.com/planos'], $journey->urls());
    }
}
