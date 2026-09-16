<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SierraTecnologia Tracking
|--------------------------------------------------------------------------
|
| Rastreio comportamental de visitantes e leads. A lib não sabe o que é um
| "lead": ela grava eventos em um alvo (qualquer Model) e devolve a jornada
| pronta para o CRM que a consome.
|
*/

return [

    /*
    | Script cliente (tracker.js). Servido pela própria aplicação para que
    | qualquer site estático o carregue de um único lugar.
    */
    'script' => [
        'enabled' => true,
        'route' => '/js/tracker.js',
        'aliases' => ['/tracking/tracker.js'],
        'cache_seconds' => 86400,
    ],

    /*
    | Rota de ingestão de eventos após a conversão. Desligue quando a
    | aplicação registra a própria rota apontando para o controller da lib
    | (caso de quem precisa de CORS ou throttle específicos).
    */
    'routes' => [
        'enabled' => true,
        'prefix' => 'api/v1',
        'events_path' => 'leads/{uuid}/eventos',
        'middleware' => ['throttle:120,1'],
    ],

    /*
    | Alvo dos eventos: o modelo do CRM que recebe a jornada (Lead, Contact,
    | Deal...). O resolver traduz o identificador da URL no registro; o
    | padrão busca `model` pela coluna `key`. A janela limita por quanto
    | tempo o identificador que circula no navegador continua aceitando
    | eventos.
    */
    'target' => [
        'model' => null,
        'key' => 'uuid',
        'resolver' => null,
        'window_hours' => 6,
    ],

    /*
    | Modelo Eloquent onde os eventos são gravados quando o alvo não tem a
    | própria relação. Nulo usa Tracking\Models\TrackingEvent.
    */
    'event_model' => null,

    /*
    | Limites de sanidade: o payload vem do navegador do visitante.
    */
    'limits' => [
        'events_per_request' => 50,
        'description' => 255,
        'meta_bytes' => 4096,
    ],

    /*
    | Tipos aceitos. Nulo aceita qualquer tipo (até 50 caracteres); uma lista
    | descarta o resto em silêncio.
    */
    'allowed_types' => null,

    /*
    | Dias que `tracking:prune` mantém os eventos comportamentais.
    */
    'retention_days' => 180,

    /*
    | Carregar a migration de `tracking_events`. Desligue quando a estrutura
    | vive em outra lib ou os eventos vão para uma tabela própria do CRM.
    */
    'migrations' => true,
];
