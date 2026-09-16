<?php

declare(strict_types=1);

namespace Tracking\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Lê o que o tracker.js manda e devolve eventos normalizados.
 *
 * Aceita JSON (fetch), text/plain (sendBeacon, que não dispara preflight)
 * e formulário comum. O corpo pode ser um evento só, `{events: [...]}` ou
 * `{steps: [...]}` — o nome que a etapa 1 da captação usa.
 */
final class TrackingPayload
{
    /**
     * @return list<array{type: string, description: string, meta: array<string, mixed>|null, created_at: Carbon|null, elapsed_ms: int|null}>
     */
    public static function fromRequest(Request $request): array
    {
        $content = $request->getContent();
        $dados = null;

        if (is_string($content) && trim($content) !== '') {
            $dados = json_decode($content, true);
        }

        if (! is_array($dados)) {
            $dados = $request->all();
        }

        return self::normalizeList(self::items($dados));
    }

    /**
     * @param  array<string, mixed>  $dados
     * @return list<mixed>
     */
    public static function items(array $dados): array
    {
        if (isset($dados['events']) && is_array($dados['events'])) {
            return array_values($dados['events']);
        }

        if (isset($dados['steps']) && is_array($dados['steps'])) {
            return array_values($dados['steps']);
        }

        return [$dados];
    }

    /**
     * @param  array<int, mixed>  $items
     * @return list<array{type: string, description: string, meta: array<string, mixed>|null, created_at: Carbon|null, elapsed_ms: int|null}>
     */
    public static function normalizeList(array $items): array
    {
        $limite = (int) (config('tracking.limits.events_per_request') ?? 50);
        $normalized = [];

        foreach ($items as $item) {
            if (count($normalized) >= $limite) {
                break;
            }

            $evento = self::normalize($item);

            if ($evento !== null) {
                $normalized[] = $evento;
            }
        }

        return $normalized;
    }

    /**
     * @return array{type: string, description: string, meta: array<string, mixed>|null, created_at: Carbon|null, elapsed_ms: int|null}|null
     */
    public static function normalize(mixed $item): ?array
    {
        if (! is_array($item)) {
            return null;
        }

        $type = isset($item['type']) ? mb_substr(trim((string) $item['type']), 0, 50) : '';
        $description = isset($item['description']) ? trim((string) $item['description']) : '';

        if ($type === '' || $description === '') {
            return null;
        }

        $descricaoMax = (int) (config('tracking.limits.description') ?? 255);
        $description = mb_substr($description, 0, $descricaoMax);

        $meta = isset($item['meta']) && is_array($item['meta']) ? $item['meta'] : [];

        $elapsedMs = null;
        if (isset($item['elapsed_ms']) && is_numeric($item['elapsed_ms'])) {
            $elapsedMs = max(0, (int) $item['elapsed_ms']);
            $meta['elapsed_ms'] = $elapsedMs;
        }

        // Sessão e visitante podem vir no topo do evento ou dentro do meta.
        foreach (['session_id', 'visitor_id', 'site', 'url'] as $chave) {
            if (isset($item[$chave]) && is_scalar($item[$chave]) && ! isset($meta[$chave])) {
                $meta[$chave] = mb_substr((string) $item[$chave], 0, $chave === 'url' ? 512 : 190);
            }
        }

        $meta = self::capMeta($meta);

        $createdAt = null;
        if (! empty($item['created_at']) && is_string($item['created_at'])) {
            try {
                $createdAt = Carbon::parse($item['created_at']);
            } catch (\Throwable) {
                $createdAt = null;
            }
        }

        return [
            'type' => $type,
            'description' => $description,
            'meta' => $meta !== [] ? $meta : null,
            'created_at' => $createdAt,
            'elapsed_ms' => $elapsedMs,
        ];
    }

    /**
     * Meta acima do limite perde as chaves maiores até caber. O evento
     * continua valendo; só o detalhe some.
     *
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private static function capMeta(array $meta): array
    {
        $limite = (int) (config('tracking.limits.meta_bytes') ?? 4096);

        while ($meta !== [] && strlen((string) json_encode($meta)) > $limite) {
            uasort($meta, fn ($a, $b) => strlen((string) json_encode($b)) <=> strlen((string) json_encode($a)));
            unset($meta[array_key_first($meta)]);
            $meta['truncado'] = true;
        }

        return $meta;
    }
}
