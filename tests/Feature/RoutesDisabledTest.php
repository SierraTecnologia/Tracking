<?php

declare(strict_types=1);

namespace Tracking\Tests\Feature;

use Tracking\Tests\TestCase;

class RoutesDisabledTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('tracking.script.enabled', false);
        $app['config']->set('tracking.routes.enabled', false);
    }

    public function test_script_e_ingestao_podem_ser_desligados_para_a_app_registrar_as_proprias_rotas(): void
    {
        $this->get('/js/tracker.js')->assertNotFound();
        $this->postJson('/api/v1/leads/x/eventos', ['type' => 'a', 'description' => 'b'])->assertNotFound();
    }
}
