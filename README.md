# SierraTecnologia Tracking

Rastreio comportamental de visitantes e leads para Laravel. A lib entrega um
`tracker.js` universal para sites estáticos, a ingestão dos eventos por beacon
e a **jornada** pronta para o CRM: de onde a pessoa veio, o que viu, quanto
tempo ficou e quão quente está.

Ela não sabe o que é um "lead". Grava eventos em um alvo (qualquer Model) e
deixa o CRM decidir o que fazer com eles.

```
Site estático ──tracker.js──▶ POST /leads (steps) ──▶ Lead criado
                            └▶ POST /leads/{uuid}/eventos ──▶ eventos depois da conversão
                                                                    │
                                                    Tracking::journey($lead)
                                                    ──▶ origem, seções, rolagem, WhatsApp, engajamento
```

## Instalação

```shell
composer require sierratecnologia/tracking
php artisan vendor:publish --tag=tracking-config   # opcional
php artisan migrate                                 # cria tracking_events (opcional, ver abaixo)
```

No site (estático ou não):

```html
<script defer src="https://sua-central.com.br/js/tracker.js"
        data-site="meusite.com.br"
        data-api="https://sua-central.com.br/api/v1"></script>
```

## O que o tracker.js registra

| Tipo             | Quando                                                        |
| ---------------- | ------------------------------------------------------------- |
| `navegacao`      | página aberta, com UTM, referrer, dispositivo, idioma         |
| `rolagem`        | 25 %, 50 %, 75 % e 100 % da página                            |
| `secao`          | seção visível por mais de 1,2 s (`section[id]`, `#planos`...) |
| `interacao`      | chips `[data-need]`, FAQ, qualquer `[data-track="Rótulo"]`    |
| `clique_wa`      | link `wa.me` / `whatsapp.com` / `.lead-wa-btn`                |
| `clique_contato` | links `tel:` e `mailto:`                                      |
| `clique_externo` | link para outro domínio                                       |
| `formulario`     | começou a preencher / enviou um formulário                    |
| `saida`          | saiu da página, com o tempo total                             |

Antes de existir lead, os passos ficam no `sessionStorage`. O tracker
intercepta o `fetch` que cria o lead (URLs com `/leads` ou `/funil/contato`,
configurável via `data-lead-paths`), anexa os passos como `steps` e guarda o
`uuid` (ou `lead`) devolvido. A partir daí cada evento sai em lote, por
`sendBeacon` em `text/plain` (sem preflight), inclusive ao fechar a aba.

Atributos opcionais: `data-events-path` (padrão `/leads/{uuid}/eventos`),
`data-lead-paths`, `data-batch-ms`, `data-respect-dnt="true"`, `data-debug="true"`.

API pública (`window.RicaTracker`):

```js
RicaTracker.track('interacao', 'Baixou a proposta', { arquivo: 'proposta.pdf' });
RicaTracker.identify(uuid);   // liga o navegador a um lead criado por fora
RicaTracker.pageview();       // SPA: nova rota
RicaTracker.flush();
RicaTracker.getSteps();       // passos acumulados antes da conversão
```

## Servidor

### Gravar eventos

```php
use Tracking\Facades\Tracking;

Tracking::recordSteps($lead, $request->input('steps', []));   // etapa 1 da captação
Tracking::recordRequestEvents($lead, $request);               // endpoint de eventos
Tracking::recordEvent($lead, 'clique_wa', 'Clicou no WhatsApp', ['texto' => 'Falar']);
```

Onde o evento cai depende do alvo, nesta ordem:

1. alvo com `trackingEvents()` (trait `Tracking\Concerns\HasTrackingEvents`) — morph em `tracking_events`;
2. alvo com `events()` — a tabela do próprio CRM (ex.: `lead_events`);
3. `tracking.event_model` (padrão `Tracking\Models\TrackingEvent`).

```php
use Tracking\Concerns\HasTrackingEvents;

class Contact extends Model
{
    use HasTrackingEvents;   // ->track(), ->trackingEvents(), ->journey()
}
```

### A jornada

```php
$jornada = Tracking::journey($lead);

$jornada->summary();      // "Chegou via Google (cpc, sites) pelo celular em /, ficou 4 min, rolou 100% da página, passou por Planos e FAQ, clicou no WhatsApp."
$jornada->engagement();   // 0-100
$jornada->origin();       // "Google (cpc, sites)" | "instagram.com" | "direto"
$jornada->sections();     // ['Planos', 'FAQ']
$jornada->toArray();      // tudo, para API ou painel
```

`Tracking\Support\Journey::fromEvents($colecao)` aceita Models ou arrays; só
os tipos de `Tracking\Support\EventType::behavioral()` entram na conta.

### Endpoint de eventos

A lib registra `POST api/v1/leads/{uuid}/eventos` (e o `OPTIONS`). O alvo é
resolvido por `tracking.target.model` + `tracking.target.key`, ou por um
resolver próprio:

```php
// config/tracking.php
'target' => [
    'model' => App\Models\Lead::class,
    'key' => 'uuid',
    'resolver' => null,           // classe que implementa Tracking\Contracts\TargetResolver
    'window_hours' => 6,          // depois disso o uuid não aceita mais eventos (410)
],
```

Precisa de CORS ou throttle próprios? Desligue `tracking.routes.enabled` e
aponte sua rota para `Tracking\Http\Controllers\TrackingEventController`.

### Conectar ao CRM

Cada gravação dispara `Tracking\Events\EventRecorded` com `target`, `event`,
`type`, `description` e `meta`:

```php
Event::listen(EventRecorded::class, function (EventRecorded $e) {
    if ($e->type === 'clique_wa') {
        $e->target->forceFill(['status' => 'quente'])->save();
    }
});
```

E o engajamento entra no score:

```php
$score += (int) round(Tracking::journey($lead)->engagement() / 10);
```

### Retenção

```shell
php artisan tracking:prune            # tracking.retention_days (180)
php artisan tracking:prune --days=30
```

Apaga só os tipos comportamentais; o que o CRM gravou (nota, status...) fica.

## Configuração

| Chave                        | Padrão                    |
| ---------------------------- | ------------------------- |
| `script.route`               | `/js/tracker.js`          |
| `routes.prefix`              | `api/v1`                  |
| `routes.events_path`         | `leads/{uuid}/eventos`    |
| `routes.middleware`          | `['throttle:120,1']`      |
| `target.model` / `key`       | `null` / `uuid`           |
| `target.window_hours`        | `6`                       |
| `event_model`                | `TrackingEvent`           |
| `limits.events_per_request`  | `50`                      |
| `limits.description`         | `255`                     |
| `limits.meta_bytes`          | `4096`                    |
| `allowed_types`              | `null` (qualquer)         |
| `retention_days`             | `180`                     |
| `migrations`                 | `true`                    |

## Testes

```shell
composer test
```

## Licença

MIT. (c) SierraTecnologia.
