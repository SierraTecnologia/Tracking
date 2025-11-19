Sitec Tracking
================================
# SierraTecnologia Tracking

**SierraTecnologia Tracking** is a lightweight, yet detailed package for tracking and recording user visits across your Laravel application. With only one simple query per request, important data is being stored, and later a cronjob crush numbers to extract meaningful stories from within the haystack.

Unlike other tracking packages that seriously damage your project's performance (yes, I mean that package you know 😅), our package takes a different approach by just executing only one query at the end of each request after the response is being served to the user, through the `terminate` method of an automatically attached middleware, and then later on it uses the raw data previously inserted in the database to extract meaningfull numbers. This is done based on a random lottery request, or through a scheduled job (recommended) that could be queued to offload the heavy crunching work.

**SierraTecnologia Tracking** tracks each -valid- request, meaning only requests that goes through routing pipeline, which also means that any wrong URL that results in `NotFoundHttpException` will not be tracked. If requested page has uncaught exceptions, it won't be tracked as well. It track user's logged in account (if any), session of all users and guests (if any), device (family, model, brand), platform (family, version), browser (agent, kind, family, version), path, route (action, middleware, parameters), host, protocol, ip address, language, status codes, and many more, and still we've plenty of awesome features planned for the future.

With such a huge collected data, the `trackings_requests` database table will noticeably increase in size specially if you've a lot of visits, that's why it's recommended to clean it periodically. Other important data will stay still in their respective tables, normalized and without any performance issues, so only this table need to be cleaned. By default that will be done automatically every month.

The default implementation of **SierraTecnologia Tracking** comes with zero configuration out-of-the-box, which means it just works once installed. But it's recommended to change the defaults and disable the "Tracking Crunching Lottery" from config file, and replace it with a [Scheduled Tasks](https://laravel.com/docs/master/scheduling) for even better performance if you've large number of visits. See [Usage](#uso-prático) for details.

[![Packagist](https://img.shields.io/packagist/v/sierratecnologia/tracking.svg?label=Packagist&style=flat-square)](https://packagist.org/packages/sierratecnologia/tracking)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/sierratecnologia/tracking.svg?label=Scrutinizer&style=flat-square)](https://scrutinizer-ci.com/g/sierratecnologia/tracking/)
[![Tests](https://github.com/sierratecnologia/tracking/actions/workflows/ci.yml/badge.svg)](https://github.com/sierratecnologia/tracking/actions/workflows/ci.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%205-brightgreen.svg?style=flat-square)](https://phpstan.org/)
[![Code Style](https://img.shields.io/badge/code%20style-PSR--12-blue.svg?style=flat-square)](https://www.php-fig.org/psr/psr-12/)
[![StyleCI](https://styleci.io/repos/118045101/shield)](https://styleci.io/repos/118045101)
[![License](https://img.shields.io/packagist/l/sierratecnologia/tracking.svg?label=License&style=flat-square)](https://github.com/sierratecnologia/tracking/blob/develop/LICENSE)

---

## Sumário

- [Introdução](#introdução)
- [Instalação](#instalação)
- [Arquitetura e Estrutura Interna](#arquitetura-e-estrutura-interna)
- [Principais Componentes e Responsabilidades](#principais-componentes-e-responsabilidades)
- [Uso Prático](#uso-prático)
- [Integração com o Ecossistema SierraTecnologia](#integração-com-o-ecossistema-sierratecnologia)
- [Extensão e Customização](#extensão-e-customização)
- [Exemplos Reais](#exemplos-reais)
- [Guia de Contribuição](#guia-de-contribuição)
- [Ferramentas de Qualidade](#ferramentas-de-qualidade)
- [Suporte](#suporte)
- [Segurança](#segurança)

---

## Introdução

### O que é a biblioteca Tracking

O **SierraTecnologia Tracking** é uma biblioteca Laravel especializada em monitoramento e análise de visitas de usuários. Projetada com foco em performance, ela captura dados detalhados de cada requisição válida sem comprometer a velocidade da aplicação.

### Objetivo e Filosofia do Projeto

A filosofia central do Tracking é proporcionar **analytics completos com impacto mínimo na performance**. Diferentemente de outras soluções que executam múltiplas queries por requisição, o Tracking realiza:

- **Uma única query por requisição** - executada após a resposta ser enviada ao usuário
- **Processamento assíncrono** - dados brutos são processados em segundo plano via jobs
- **Limpeza automática** - dados antigos são removidos periodicamente para manter a performance

### Benefícios de Uso em Aplicações Laravel

- **Zero configuração inicial** - funciona imediatamente após instalação
- **Performance otimizada** - overhead mínimo por requisição
- **Dados normalizados** - informações organizadas em tabelas relacionais
- **Análise completa** - rastreamento de dispositivos, navegadores, geolocalização, rotas e mais
- **Integração nativa** - compatível com Laravel 7.x e 8.x
- **Extensível** - modelos substituíveis via Service Container

### Como se Encaixa no Ecossistema SierraTecnologia

O Tracking é parte fundamental do ecossistema SierraTecnologia, integrando-se com:

- **Audit** - para trilhas de auditoria detalhadas
- **Muleta** - utilitários e helpers compartilhados
- **Countries** - dados de geolocalização por país
- **Laravel Horizon** - monitoramento de filas

---

## Instalação

### Requisitos Mínimos

- **PHP**: ^7.4
- **Laravel**: 7.x ou 8.x
- **Banco de Dados**: MySQL, PostgreSQL ou SQLite
- **Extensões PHP**: dom, curl, mbstring, sqlite, bcmath, intl, gd

### Instalação via Composer

```shell
composer require sierratecnologia/tracking
```

### Publicação de Configurações

Publique os arquivos de configuração e migrações:

```shell
php artisan sierratecnologia:publish:trackings
```

### Executar Migrações

Execute as migrações para criar as tabelas necessárias:

```shell
php artisan sierratecnologia:migrate:trackings
```

### Service Provider

O pacote utiliza **auto-discovery** do Laravel, registrando automaticamente o `TrackingProvider`. Caso necessário, registre manualmente em `config/app.php`:

```php
'providers' => [
    // ...
    Tracking\TrackingProvider::class,
],
```

### Configuração Fora do Laravel

Para uso em frameworks não-Laravel, você precisará:

1. Registrar manualmente os bindings do Service Container
2. Configurar o middleware para captura de requisições
3. Agendar os jobs de processamento

---

## Arquitetura e Estrutura Interna

### Descrição dos Diretórios Principais

```
src/
├── Abstracts/           # Classes base abstratas
├── Console/Commands/    # Comandos Artisan CLI
├── Facades/             # Facades para acesso simplificado
├── Http/
│   ├── Controllers/     # Controllers da API/Admin
│   └── Middleware/      # Middleware de captura
├── Jobs/                # Jobs assíncronos (filas)
├── Models/              # Modelos Eloquent
│   ├── Statistics/      # Modelos de estatísticas
│   └── Metrics/         # Modelos de métricas
├── Notifications/       # Classes de notificação
├── Providers/           # Service Providers
├── Services/            # Serviços de negócio
└── Traits/              # Traits reutilizáveis
```

### Namespaces

O pacote utiliza o namespace raiz `Tracking\` com autoload PSR-4:

```php
// Exemplo de uso de namespaces
use Tracking\Models\Statistics\Request;
use Tracking\Services\AnalyticsService;
use Tracking\Facades\TrackingServiceFacade;
```

### Padrões Arquiteturais

O Tracking adota os seguintes padrões de projeto:

#### Service Provider Pattern
O `TrackingProvider` é o ponto central de registro, responsável por:
- Registrar middleware automaticamente no grupo `web`
- Vincular modelos como singletons no IoC Container
- Carregar migrações, views e traduções

#### Facade Pattern
Acesso estático simplificado via `TrackingServiceFacade`:
```php
use Tracking\Facades\TrackingServiceFacade;
TrackingServiceFacade::someMethod();
```

#### Job/Queue Pattern
Processamento assíncrono via jobs:
- `CrunchStatistics` - processa dados brutos
- `CleanStatisticsRequests` - limpa registros antigos

### Comunicação Entre Camadas

```
HTTP Request
    ↓
[Middleware: TrackStatistics]
    ↓
Response enviada ao usuário
    ↓
Middleware::terminate()
    ↓
Inserção em statistics_data (Datum)
    ↓
[Job: CrunchStatistics]
    ↓
Normalização em tabelas relacionais
    ↓
[Job: CleanStatisticsRequests]
    ↓
Limpeza periódica de dados antigos
```

### Convenções e Boas Práticas

A SierraTecnologia segue rigorosamente:

- **PSR-12** para estilo de código
- **PSR-4** para autoloading
- **Injeção de Dependência** via Service Container
- **Modelos como Singletons** para facilitar substituição
- **Separação de Responsabilidades** - cada classe com propósito único

---

## Principais Componentes e Responsabilidades

### Service Providers

#### TrackingProvider
`src/TrackingProvider.php`

O provider principal que:
- Registra comandos Artisan (Migrate, Publish, Rollback)
- Vincula middleware ao grupo `web`
- Registra 7 modelos Eloquent como singletons
- Publica arquivos de configuração e assets

```php
// Exemplo de registro de modelo
$this->app->singleton('tracking.statistics.agent', function () {
    return new \Tracking\Models\Statistics\Agent();
});
```

#### HorizonServiceProvider
`src/Providers/HorizonServiceProvider.php`

Configura o Laravel Horizon para monitoramento de filas e jobs.

### Services

#### AnalyticsService
`src/Services/AnalyticsService.php`

Serviço principal para processamento de analytics:

```php
use Tracking\Services\AnalyticsService;

class DashboardController
{
    public function index(AnalyticsService $analytics)
    {
        $topPages = $analytics->topPages(10);
        $topBrowsers = $analytics->topBrowsers();
        $dailyVisits = $analytics->getDays(30);

        return view('dashboard', compact('topPages', 'topBrowsers', 'dailyVisits'));
    }
}
```

**Métodos disponíveis:**
- `topReferers($limit)` - Top referenciadores HTTP
- `topPages($limit)` - Páginas mais visitadas
- `topBrowsers()` - Estatísticas de navegadores
- `getDays($days)` - Tendência diária de visitas

#### TrackingService
`src/Services/TrackingService.php`

Serviço central para operações de tracking, acessível via Facade.

### Modelos Eloquent (Statistics)

Todos os modelos de estatísticas utilizam:
- `ValidatingTrait` - validação automática
- `CacheableEloquent` - cache de queries
- Sem timestamps (`$timestamps = false`)
- Suporte a campos JSON

#### Request
`src/Models/Statistics/Request.php`

Modelo principal que armazena requisições completas:

```php
use Tracking\Models\Statistics\Request;

// Buscar requisições de um usuário
$userRequests = Request::where('user_id', $userId)
    ->with(['agent', 'device', 'platform', 'path', 'route', 'geoip'])
    ->latest()
    ->paginate(20);

// Acessar relacionamentos
foreach ($userRequests as $request) {
    echo $request->agent->family;        // Chrome
    echo $request->device->brand;        // Apple
    echo $request->platform->family;     // Mac OS X
    echo $request->geoip->city;          // São Paulo
}
```

#### Agent
`src/Models/Statistics/Agent.php`

Informações do navegador/user agent:

| Campo | Descrição |
|-------|-----------|
| name | User agent completo |
| kind | Tipo (desktop, mobile, tablet) |
| family | Família (Chrome, Firefox, Safari) |
| version | Versão do navegador |
| count | Número de hits |

#### Device
`src/Models/Statistics/Device.php`

Detalhes do dispositivo:

| Campo | Descrição |
|-------|-----------|
| family | Família (iPhone, Galaxy, etc) |
| model | Modelo específico |
| brand | Marca (Apple, Samsung) |
| count | Número de hits |

#### Platform
`src/Models/Statistics/Platform.php`

Sistema operacional:

| Campo | Descrição |
|-------|-----------|
| family | SO (Windows, Mac OS X, Android) |
| version | Versão do SO |
| count | Número de hits |

#### Path
`src/Models/Statistics/Path.php`

Caminhos de URL acessados:

```php
// Contador de visualizações de página
$pageViews = app('tracking.statistics.path')
    ->where('path', request()->decodedPath())
    ->first()
    ->count;
```

#### Route
`src/Models/Statistics/Route.php`

Detalhes das rotas Laravel:

| Campo | Descrição |
|-------|-----------|
| name | Nome da rota |
| path | Padrão do path |
| action | Controller@method |
| middleware | Array de middleware |
| parameters | Regex dos parâmetros |
| count | Número de hits |

#### Geoip
`src/Models/Statistics/Geoip.php`

Dados de geolocalização:

| Campo | Descrição |
|-------|-----------|
| client_ip | Endereço IP |
| latitude/longitude | Coordenadas |
| country_code | Código do país (2 chars) |
| city | Cidade |
| timezone | Fuso horário |
| count | Número de hits |

### Jobs

#### CrunchStatistics
`src/Jobs/CrunchStatistics.php`

Processa dados brutos da tabela `statistics_data`:
1. Lê registros do modelo `Datum`
2. Extrai e normaliza informações
3. Insere/atualiza em Agent, Device, Platform, etc.
4. Cria entrada em `statistics_requests`

#### CleanStatisticsRequests
`src/Jobs/CleanStatisticsRequests.php`

Remove registros antigos conforme configuração de retenção.

### Middleware

#### TrackStatistics
`src/Http/Middleware/TrackStatistics.php`

Middleware principal que captura dados no `terminate`:

```php
public function terminate($request, $response)
{
    // Captura dados da requisição
    // Insere em statistics_data
    // Dispara jobs via lottery ou schedule
}
```

### Console Commands

```shell
# Publicar assets e configurações
php artisan sierratecnologia:publish:trackings

# Executar migrações
php artisan sierratecnologia:migrate:trackings

# Reverter migrações
php artisan sierratecnologia:migrate:trackings --rollback
```

---

## Uso Prático

### Configuração Recomendada

1. **Publique as configurações:**
```shell
php artisan sierratecnologia:publish:trackings
```

2. **Desabilite o Lottery** em `config/tracking/statistics.php`:
```php
'lottery' => [0, 100], // Desabilitado
```

3. **Configure Scheduled Tasks** em `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    // Processar dados a cada hora
    $schedule->job(new \Tracking\Jobs\CrunchStatistics)
        ->hourly()
        ->withoutOverlapping();

    // Limpar dados antigos mensalmente
    $schedule->job(new \Tracking\Jobs\CleanStatisticsRequests)
        ->monthly()
        ->withoutOverlapping();
}
```

### Exemplos de Implementação

#### Contador de Visualizações de Página

```php
// Em um Controller ou View Composer
public function show($slug)
{
    $post = Post::where('slug', $slug)->firstOrFail();

    // Obter visualizações do Tracking
    $views = app('tracking.statistics.path')
        ->where('path', request()->decodedPath())
        ->value('count') ?? 0;

    return view('posts.show', compact('post', 'views'));
}
```

#### Dashboard de Analytics

```php
namespace App\Http\Controllers\Admin;

use Tracking\Services\AnalyticsService;
use Tracking\Models\Statistics\Request;

class AnalyticsController extends Controller
{
    public function index(AnalyticsService $analytics)
    {
        return view('admin.analytics', [
            'totalVisits' => Request::count(),
            'uniqueVisitors' => Request::distinct('session_id')->count(),
            'topPages' => $analytics->topPages(10),
            'topBrowsers' => $analytics->topBrowsers(),
            'visitsPerDay' => $analytics->getDays(30),
            'topReferers' => $analytics->topReferers(10),
        ]);
    }
}
```

#### Relatório de Dispositivos

```php
use Tracking\Models\Statistics\Device;
use Tracking\Models\Statistics\Platform;

class DeviceReportController extends Controller
{
    public function index()
    {
        $devices = Device::orderByDesc('count')
            ->take(10)
            ->get();

        $platforms = Platform::orderByDesc('count')
            ->take(10)
            ->get();

        return view('reports.devices', compact('devices', 'platforms'));
    }
}
```

#### Rastreamento por Usuário

```php
use Tracking\Models\Statistics\Request;

class UserActivityController extends Controller
{
    public function show($userId)
    {
        $activity = Request::where('user_id', $userId)
            ->where('user_type', 'App\\Models\\User')
            ->with(['path', 'geoip', 'agent'])
            ->latest('created_at')
            ->paginate(50);

        return view('users.activity', compact('activity'));
    }
}
```

### Boas Práticas

1. **Use Scheduled Tasks** em vez do Lottery para melhor performance
2. **Monitore o tamanho** da tabela `statistics_requests`
3. **Ajuste a retenção** conforme suas necessidades (padrão: 30 dias)
4. **Utilize cache** para queries frequentes de analytics
5. **Indexe campos** usados em filtros frequentes

---

## Integração com o Ecossistema SierraTecnologia

### Pacotes Relacionados

O Tracking integra-se nativamente com:

#### sierratecnologia/audit
Trilhas de auditoria para rastrear mudanças em modelos:
```php
// Tracking + Audit = Rastreamento completo de atividades
```

#### sierratecnologia/muleta
Utilitários compartilhados:
- `ConsoleTools` trait para comandos
- Helpers de configuração
- Traits de validação

#### sierratecnologia/countries
Dados geográficos para enriquecimento de GeoIP.

### Integrações Externas

#### Laravel Horizon
Monitoramento de jobs e filas:
```shell
php artisan horizon
```

#### Spatie Laravel Analytics
Integração com Google Analytics:
```php
// config/tracking/analytics.php
'google' => [
    'view_id' => env('ANALYTICS_VIEW_ID'),
    'credentials' => storage_path('app/analytics/credentials.json'),
],
```

#### Larametrics
Dashboard adicional de métricas:
- Logs da aplicação
- Mudanças em modelos
- Performance de requisições
- Notificações

### Padrões de CI/CD

A SierraTecnologia utiliza:

- **GitHub Actions** para CI/CD automatizado
- **PHPUnit** para testes unitários e de integração
- **PHPStan/Psalm** para análise estática
- **PHP CS Fixer / PHPCS** para padronização de código

### Uso por Diferentes Equipes

#### Equipe de Backend
- Monitorar performance de APIs
- Identificar endpoints mais acessados
- Rastrear erros e exceções

#### Equipe de Frontend
- Analisar comportamento de usuários
- Otimizar páginas mais visitadas
- A/B testing baseado em dados

#### Equipe de DevOps
- Monitorar saúde da aplicação
- Identificar gargalos
- Planejar escalabilidade

---

## Extensão e Customização

### Substituir Modelos Padrão

Os modelos são registrados como singletons, permitindo fácil substituição:

```php
// Em um Service Provider personalizado
public function register()
{
    // Substituir modelo de Agent
    $this->app->singleton('tracking.statistics.agent', function () {
        return new \App\Models\CustomAgent();
    });
}
```

Seu modelo customizado deve estender o original:

```php
namespace App\Models;

use Tracking\Models\Statistics\Agent as BaseAgent;

class CustomAgent extends BaseAgent
{
    // Adicionar campos extras
    protected $appends = ['browser_icon'];

    public function getBrowserIconAttribute()
    {
        return match($this->family) {
            'Chrome' => 'chrome.svg',
            'Firefox' => 'firefox.svg',
            default => 'browser.svg',
        };
    }
}
```

### Adicionar Novo Coletor

Crie um middleware personalizado:

```php
namespace App\Http\Middleware;

use Closure;
use Tracking\Models\Statistics\Datum;

class TrackCustomData
{
    public function terminate($request, $response)
    {
        Datum::create([
            'session_id' => session()->getId(),
            'custom_field' => $request->header('X-Custom-Header'),
            // ...
        ]);
    }
}
```

### Estender Jobs de Processamento

```php
namespace App\Jobs;

use Tracking\Jobs\CrunchStatistics as BaseCrunch;

class CustomCrunchStatistics extends BaseCrunch
{
    public function handle()
    {
        parent::handle();

        // Lógica adicional de processamento
        $this->processCustomMetrics();
    }

    protected function processCustomMetrics()
    {
        // Sua lógica aqui
    }
}
```

### Criar Relatórios Customizados

```php
namespace App\Services;

use Tracking\Services\AnalyticsService;
use Tracking\Models\Statistics\Request;

class CustomAnalyticsService extends AnalyticsService
{
    public function conversionRate($goalPath)
    {
        $totalVisits = Request::count();
        $conversions = Request::whereHas('path', function ($q) use ($goalPath) {
            $q->where('path', 'like', $goalPath . '%');
        })->count();

        return $totalVisits > 0
            ? round(($conversions / $totalVisits) * 100, 2)
            : 0;
    }
}
```

### Manter Compatibilidade Futura

1. **Não modifique tabelas core** - crie tabelas adicionais
2. **Estenda, não substitua** - use herança de classes
3. **Siga convenções** - mantenha padrões de nomenclatura
4. **Documente extensões** - facilite manutenção futura
5. **Teste extensivamente** - garanta compatibilidade

---

## Exemplos Reais

### Caso 1: E-commerce com Analytics de Produtos

**Antes:** Analytics genérico do Google sem detalhes de sessão local.

**Depois:** Rastreamento completo de jornada do cliente:

```php
// Controller de Produto
public function show($slug)
{
    $product = Product::where('slug', $slug)->first();

    // Visualizações diretas do Tracking
    $views = app('tracking.statistics.path')
        ->where('path', 'like', "%/products/{$slug}%")
        ->sum('count');

    // Dispositivos mais usados para este produto
    $topDevices = Request::whereHas('path', function ($q) use ($slug) {
        $q->where('path', 'like', "%/products/{$slug}%");
    })
    ->with('device')
    ->get()
    ->groupBy('device.brand')
    ->map->count()
    ->sortDesc()
    ->take(5);

    return view('products.show', compact('product', 'views', 'topDevices'));
}
```

**Benefícios:**
- Dados em tempo real
- Sem dependência de terceiros
- Insights detalhados por produto

### Caso 2: SaaS com Monitoramento de Features

**Antes:** Sem visibilidade de uso de features.

**Depois:** Dashboard completo de adoção:

```php
// Service de Feature Tracking
class FeatureAdoptionService
{
    public function getUsageStats($feature)
    {
        return [
            'total_uses' => app('tracking.statistics.route')
                ->where('name', 'like', "%{$feature}%")
                ->sum('count'),

            'unique_users' => Request::whereHas('route', function ($q) use ($feature) {
                $q->where('name', 'like', "%{$feature}%");
            })
            ->distinct('user_id')
            ->count(),

            'trend' => Request::whereHas('route', function ($q) use ($feature) {
                $q->where('name', 'like', "%{$feature}%");
            })
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get(),
        ];
    }
}
```

**Benefícios:**
- Decisões baseadas em dados
- Identificação de features subutilizadas
- ROI claro de desenvolvimento

### Caso 3: API REST com Rate Limiting Inteligente

**Antes:** Rate limiting fixo para todos.

**Depois:** Limites dinâmicos baseados em histórico:

```php
// Middleware de Rate Limiting Inteligente
class SmartRateLimiter
{
    public function handle($request, Closure $next)
    {
        $clientIp = $request->ip();

        // Histórico do cliente via Tracking
        $clientHistory = app('tracking.statistics.geoip')
            ->where('client_ip', $clientIp)
            ->first();

        // Clientes com bom histórico ganham limites maiores
        $limit = $clientHistory && $clientHistory->count > 1000
            ? 1000  // Cliente confiável
            : 100;  // Cliente novo

        // Aplicar rate limiting
        if ($this->tooManyRequests($clientIp, $limit)) {
            return response()->json(['error' => 'Too many requests'], 429);
        }

        return $next($request);
    }
}
```

**Benefícios:**
- Experiência personalizada
- Proteção inteligente
- Melhor UX para clientes fiéis

---

## Guia de Contribuição

### Como Contribuir

1. **Fork** o repositório
2. **Clone** seu fork localmente
3. **Crie uma branch** para sua feature/fix
4. **Desenvolva** seguindo os padrões
5. **Teste** suas alterações
6. **Commit** com mensagens claras
7. **Push** para seu fork
8. **Abra um Pull Request**

### Padrões de Commit

Seguimos Conventional Commits:

```
feat: adicionar suporte a tracking de WebSockets
fix: corrigir contagem duplicada em statistics_paths
docs: atualizar exemplos de uso do AnalyticsService
test: adicionar testes para CrunchStatistics job
refactor: extrair lógica de GeoIP para service dedicado
```

### Convenções de Branch

```
feature/nome-da-feature
fix/descricao-do-bug
docs/atualizacao-documentacao
refactor/nome-do-refactor
```

### Versionamento Semântico

- **MAJOR**: Mudanças incompatíveis na API
- **MINOR**: Novas funcionalidades compatíveis
- **PATCH**: Correções de bugs compatíveis

### Executar Testes Localmente

```shell
# Instalar dependências
composer install

# Executar todos os testes
composer test

# Testes com cobertura
composer test-coverage

# Análise estática com PHPStan
./vendor/bin/phpstan analyse src

# Análise estática com Psalm
composer psalm

# Verificar estilo de código
composer check-style

# Corrigir estilo automaticamente
composer fix-style
```

### Requisitos para Pull Requests

1. Testes passando (`composer test`)
2. Análise estática sem erros (`phpstan`, `psalm`)
3. Código formatado (`composer format`)
4. Documentação atualizada (se aplicável)
5. Changelog atualizado

### Licença

Este projeto está licenciado sob a [MIT License](LICENSE).

### Autores e Contato

- **SierraTecnologia** - [sierratecnologia.com.br](https://sierratecnologia.com.br)
- **Email**: help@sierratecnologia.com.br
- **Issues**: [GitHub Issues](https://github.com/sierratecnologia/tracking/issues)

---

## Ferramentas de Qualidade

### GitHub Actions CI/CD

O projeto utiliza GitHub Actions para integração contínua com:

- **Testes PHPUnit** em múltiplas versões de PHP
- **Análise estática** com PHPStan e Psalm
- **Verificação de estilo** com PHPCS (PSR-12)
- **Auto-fix** de código com PHP CS Fixer

### Executar Ferramentas Localmente

#### PHPUnit (Testes)
```shell
# Executar todos os testes
./vendor/bin/phpunit

# Testes com cobertura HTML
./vendor/bin/phpunit --coverage-html coverage

# Testes específicos
./vendor/bin/phpunit --filter=NomeDaTeste
```

#### PHPStan (Análise Estática)
```shell
# Análise completa
./vendor/bin/phpstan analyse src

# Com configuração específica
./vendor/bin/phpstan analyse -c phpstan.neon.dist
```

#### Psalm (Análise Estática)
```shell
# Análise completa
./vendor/bin/psalm

# Com relatório detalhado
./vendor/bin/psalm --show-info=true
```

#### PHPCS (Code Style)
```shell
# Verificar estilo
./vendor/bin/phpcs --standard=PSR12 src

# Corrigir automaticamente
./vendor/bin/phpcbf --standard=PSR12 src
```

#### PHP CS Fixer
```shell
# Corrigir estilo
./vendor/bin/php-cs-fixer fix --allow-risky=yes
```

### Configuração das Ferramentas

#### phpstan.neon.dist
- Level: 5 (moderado)
- Inclui extensão Larastan
- Analisa diretório `src/`

#### phpunit.xml.dist
- Suites: Unit e Feature
- Database: SQLite in-memory
- Cobertura: HTML, Clover, TAP

#### psalm.xml.dist
- Error level: 4
- Plugin Laravel
- Detecta variáveis não utilizadas

---

## Suporte

Os seguintes canais de suporte estão disponíveis:

- [Chat no Slack](https://bit.ly/sierratecnologia-slack)
- [Email de Ajuda](mailto:help@sierratecnologia.com)
- [Twitter](https://twitter.com/sierratecnologia)

---

## Segurança

Se você descobrir uma vulnerabilidade de segurança neste projeto, por favor envie um e-mail para [help@sierratecnologia.com](mailto:help@sierratecnologia.com). Todas as vulnerabilidades de segurança serão tratadas prontamente.

---

## Changelog

Consulte o [Changelog](CHANGELOG.md) para um histórico completo do projeto.

---

## About SierraTecnologia

SierraTecnologia is a software solutions startup, specialized in integrated enterprise solutions for SMEs established in Alexandria, Egypt since June 2016. We believe that our drive The Value, The Reach, and The Impact is what differentiates us and unleash the endless possibilities of our philosophy through the power of software. We like to call it Innovation At The Speed Of Life. That's how we do our share of advancing humanity.

---

## License

This software is released under [The MIT License (MIT)](LICENSE).

(c) 2016-2020 SierraTecnologia LLC, Some rights reserved.
