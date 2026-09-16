# Release Notes

---

## [Unreleased](https://github.com/sierratecnologia/tracking/compare/v2.3.0...develop)

## [v2.3.0 (2026-09-16)](https://github.com/sierratecnologia/tracking/compare/v2.2.1...v2.3.0)

### ✨ Novidades

- [x] **Jornada do lead** — `Tracking::journey($alvo)` e `Tracking\Support\Journey`: origem (UTM, referrer ou direto), dispositivo, página de entrada, rolagem máxima, seções vistas, interações, cliques no WhatsApp e em contato, duração, **engajamento 0-100** e resumo em uma frase para o painel.
- [x] **Conexão com o CRM** — evento `Tracking\Events\EventRecorded` disparado a cada gravação, com alvo, registro, tipo, descrição e meta.
- [x] **Trait `HasTrackingEvents`** — qualquer Model ganha `trackingEvents()`, `track()` e `journey()`; a service reconhece a relação sem configuração.
- [x] **Alvo configurável** — `tracking.target.model` + `key`, ou `TargetResolver` próprio; o endpoint deixa de depender de `App\Models\Lead`.
- [x] **Rotas por configuração** — script e ingestão registrados em `routes/tracking.php`, com prefixo, caminho e middleware configuráveis e desligáveis para a aplicação apontar a própria rota ao controller da lib.
- [x] **`tracking:prune`** — retenção dos eventos comportamentais (`retention_days`), sem tocar no que o CRM gravou.
- [x] **tracker.js 2.3** — id de visitante persistente e de sessão, envio em lote por `sendBeacon` em `text/plain` (sem preflight), evento de saída com tempo na página, cliques em `tel:`/`mailto:` e links externos, `[data-track]` para marcar elementos, hook de `submit`, `data-lead-paths` (cobre `/leads` e `/funil/contato`), `identify()`, `pageview()` para SPA, `data-respect-dnt` e `data-debug`.

### 🎨 Melhorias

- [x] **Limites de sanidade** — eventos por requisição, tamanho de descrição, teto de bytes do meta (perde as chaves maiores e marca `truncado`) e lista opcional de tipos aceitos.
- [x] **`tracking_events`** — colunas indexadas `session_id`, `visitor_id`, `site` e `url` preenchidas a partir do meta, morph `trackable` e `lead_id` por compatibilidade; scopes `behavioral`, `ofType`, `forSession`, `forVisitor`, `forSite`.
- [x] **`EventType`** — vocabulário dos eventos com rótulo e cor para painéis.
- [x] **tracker.js servido com ETag** e `304` quando o navegador já tem a versão.
- [x] **README reescrito** em português com o fluxo completo, e CI com PHP 8.3/8.4 × Laravel 11/12/13.

### 🔧 Técnico

- [x] **Removido tudo que não era rastreio:** modelos e migrations de `statistics_*` e `analytics`, `AnalyticsService`, middlewares vazios, jobs, comandos de publish/migrate, views do Larametrics, ferramenta Vue, `publishes/` de Horizon e Larametrics, configs de Psalm, PHP-CS-Fixer, StyleCI, Scrutinizer, Travis e Grump. A lib depende só de `illuminate/*`.
- [x] Testes reescritos (34) cobrindo payload, service, jornada, endpoint com resolver, prune e entrega do script. `composer.lock` e `dist/` deixam de ser versionados.

## [v2.2.1 (2026-09-15)](https://github.com/sierratecnologia/tracking/compare/v2.2.0...v2.2.1)

### 🔧 Técnico

- [x] Ajustes de frontend RICA-2026.

## [v2.2.0 (2026-09-15)](https://github.com/sierratecnologia/tracking/compare/v2.0.0...v2.2.0)

### 🎨 Melhorias

- [x] Compatibilidade com PHP 8.4, revisão de modelos e migrations, suíte de testes passando.

## [v2.0.0 (2026-09-14)](https://github.com/sierratecnologia/tracking/compare/v1.0.0...v2.0.0)

### 🔧 Técnico

- [x] PHP ^8.3 e Laravel ^13.

## [v1.0.0 (2018-10-01)](https://github.com/sierratecnologia/tracking/releases/tag/v1.0.0)

### ✨ Novidades

- [x] Pacote renomeado para `sierratecnologia/tracking`, com rastreio de requisições, agentes, dispositivos, caminhos e rotas (Laravel 5.7+).
