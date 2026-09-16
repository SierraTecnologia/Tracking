<?php

declare(strict_types=1);

namespace Tracking\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tracking\Resolvers\ModelTargetResolver;
use Tracking\Services\TrackingService;

/**
 * Recebe os eventos que acontecem depois da conversão (clique no WhatsApp,
 * rolagem, etapa 2...). Aceita JSON e text/plain (sendBeacon).
 */
class TrackingEventController
{
    public function __construct(
        protected TrackingService $tracking,
    ) {}

    public function store(Request $request, string $uuid): JsonResponse
    {
        $resolver = $this->tracking->resolver();

        if ($resolver instanceof ModelTargetResolver && ! $resolver->configured()) {
            return response()->json([
                'ok' => false,
                'error' => 'alvo_nao_configurado',
                'message' => 'Defina tracking.target.model ou tracking.target.resolver.',
            ], 500);
        }

        $alvo = $this->tracking->resolveTarget($uuid);

        if ($alvo === null) {
            return response()->json([
                'ok' => false,
                'error' => 'lead_nao_encontrado',
            ], 404);
        }

        if ($this->tracking->isWindowExpired($alvo)) {
            return response()->json([
                'ok' => false,
                'error' => 'janela_expirada',
            ], 410);
        }

        return response()->json([
            'ok' => true,
            'recorded' => $this->tracking->recordRequestEvents($alvo, $request),
        ]);
    }
}
