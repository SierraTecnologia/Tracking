<?php

declare(strict_types=1);

namespace Tracking\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Serve o tracker.js com cache e ETag, para os sites estáticos carregarem
 * sempre a versão que a aplicação tem instalada.
 */
class TrackingAssetController
{
    public function script(Request $request): Response
    {
        $path = self::scriptPath();

        if (! is_file($path)) {
            return response('// tracker.js não encontrado', 404, [
                'Content-Type' => 'application/javascript; charset=utf-8',
            ]);
        }

        $conteudo = (string) file_get_contents($path);
        $etag = '"'.md5($conteudo).'"';
        $cache = (int) (config('tracking.script.cache_seconds') ?? 86400);

        $headers = [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => "public, max-age={$cache}",
            'ETag' => $etag,
            'Access-Control-Allow-Origin' => '*',
        ];

        if ($request->headers->get('If-None-Match') === $etag) {
            return response('', 304, $headers);
        }

        return response($conteudo, 200, $headers);
    }

    public static function scriptPath(): string
    {
        return dirname(__DIR__, 3).'/resources/js/tracker.js';
    }
}
