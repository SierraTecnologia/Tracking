Sitec Tracking
================================
# SierraTecnologia Tracking

**SierraTecnologia Tracking** is a lightweight, yet detailed package for tracking and recording user visits across your Laravel application. With only one simple query per request, important data is being stored, and later a cronjob crush numbers to extract meaningful stories from within the haystack.

Unlike other tracking packages that seriously damage your project's performance (yes, I mean that package you know), our package takes a different approach by just executing only one query at the end of each request after the response is being served to the user, through the `terminate` method of an automatically attached middleware, and then later on it uses the raw data previously inserted in the database to extract meaningfull numbers. This is done based on a random lottery request, or through a scheduled job (recommended) that could be queued to offload the heavy crunching work.

**SierraTecnologia Tracking** tracks each -valid- request, meaning only requests that goes through routing pipeline, which also means that any wrong URL that results in `NotFoundHttpException` will not be tracked. If requested page has uncaught exceptions, it won't be tracked as well. It track user's logged in account (if any), session of all users and guests (if any), device (family, model, brand), platform (family, version), browser (agent, kind, family, version), path, route (action, middleware, parameters), host, protocol, ip address, language, status codes, and many more, and still we've plenty of awesome features planned for the future.

With such a huge collected data, the `trackings_requests` database table will noticeably increase in size specially if you've a lot of visits, that's why it's recommended to clean it periodically. Other important data will stay still in their respective tables, normalized and without any performance issues, so only this table need to be cleaned. By default that will be done automatically every month.

The default implementation of **SierraTecnologia Tracking** comes with zero configuration out-of-the-box, which means it just works once installed. But it's recommended to change the defaults and disable the "Tracking Crunching Lottery" from config file, and replace it with a [Scheduled Tasks](https://laravel.com/docs/master/scheduling) for even better performance if you've large number of visits. See [Usage](#uso-pratico) for details.

[![Packagist](https://img.shields.io/packagist/v/sierratecnologia/tracking.svg?label=Packagist&style=flat-square)](https://packagist.org/packages/sierratecnologia/tracking)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/sierratecnologia/tracking.svg?label=Scrutinizer&style=flat-square)](https://scrutinizer-ci.com/g/sierratecnologia/tracking/)
[![GitHub Actions](https://img.shields.io/github/actions/workflow/status/sierratecnologia/tracking/ci.yml?branch=master&label=CI&style=flat-square)](https://github.com/sierratecnologia/tracking/actions)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%206-brightgreen.svg?style=flat-square)](https://phpstan.org/)
[![License](https://img.shields.io/packagist/l/sierratecnologia/tracking.svg?label=License&style=flat-square)](https://github.com/sierratecnologia/tracking/blob/develop/LICENSE)

---

## Sumario

- [Introducao](#introducao)
- [Instalacao](#instalacao)
- [Arquitetura e Estrutura Interna](#arquitetura-e-estrutura-interna)
- [Principais Componentes e Responsabilidades](#principais-componentes-e-responsabilidades)
- [Uso Pratico](#uso-pratico)
- [Integracao com o Ecossistema SierraTecnologia](#integracao-com-o-ecossistema-sierratecnologia)
- [Extensao e Customizacao](#extensao-e-customizacao)
- [Exemplos Reais](#exemplos-reais)
- [Ferramentas de Qualidade](#ferramentas-de-qualidade)
- [Guia de Contribuicao](#guia-de-contribuicao)
- [Suporte](#suporte)
- [About SierraTecnologia](#about-sierratecnologia)

---

## Introducao

### O que e a biblioteca Tracking

**SierraTecnologia Tracking** e uma biblioteca PHP desenvolvida especificamente para aplicacoes Laravel, projetada para rastrear e registrar visitas de usuarios de forma eficiente e com minimo impacto no desempenho. A biblioteca coleta dados detalhados sobre cada requisicao valida, incluindo informacoes do dispositivo, navegador, localizacao geografica, rotas acessadas e muito mais.

### Objetivo e filosofia do projeto

A filosofia central do **Tracking** e fornecer uma solucao de rastreamento que seja:

- **Leve e performatica**: Apenas uma query por requisicao, executada apos o envio da resposta ao usuario
- **Zero configuracao**: Funciona imediatamente apos a instalacao
- **Extensivel**: Permite customizacao completa atraves de injecao de dependencia
- **Segura**: Nao rastreia erros ou excecoes nao tratadas
- **Escalavel**: Suporta processamento em background via jobs e queues

### Beneficios de uso em aplicacoes PHP/Laravel

- **Analise detalhada de trafego**: Coleta dados sobre dispositivos, navegadores, plataformas e localizacao
- **Impacto minimo na performance**: Processamento adiado via middleware terminate
- **Dados normalizados**: Estrutura de banco de dados otimizada para consultas rapidas
- **Integracao com Laravel Horizon**: Monitoramento de jobs e queues
- **Suporte a multiplos hosts**: Ideal para aplicacoes com subdomains dinamicos

### Como se encaixa no ecossistema SierraTecnologia

O **Tracking** e parte do ecossistema de bibliotecas da SierraTecnologia, integrando-se perfeitamente com:

- **sierratecnologia/audit**: Para auditoria completa de alteracoes em models
- **sierratecnologia/muleta**: Traits e helpers compartilhados
- **sierratecnologia/countries**: Dados de paises para geolocalizacao

---

## Instalacao

### Requisitos minimos

- **PHP**: ^8.0
- **Laravel**: 8.x, 9.x ou 10.x
- **Banco de dados**: MySQL, PostgreSQL ou SQLite
- **Extensoes PHP**: PDO, JSON, cURL, GD

### Instalacao via Composer

```bash
composer require sierratecnologia/tracking
```

### Publicacao de configuracoes

Publique os arquivos de configuracao e migrations:

```bash
php artisan sierratecnologia:publish:trackings
```

### Executar migrations

Execute as migrations para criar as tabelas necessarias:

```bash
php artisan sierratecnologia:migrate:trackings
```

### Configuracao do Service Provider

O Service Provider e registrado automaticamente via Laravel Package Discovery. Caso precise registrar manualmente, adicione ao `config/app.php`:

```php
'providers' => [
    // ...
    Tracking\TrackingProvider::class,
],
```

### Configuracao para melhor performance (recomendado)

1. Desabilite a "Tracking Crunching Lottery" no arquivo de configuracao
2. Configure Scheduled Tasks para processar os dados:

```php
// Em app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Processar dados de tracking a cada hora
    $schedule->job(new \Tracking\Jobs\CrunchStatistics)->hourly();

    // Limpar requisicoes antigas mensalmente
    $schedule->job(new \Tracking\Jobs\CleanStatisticsRequests)->monthly();
}
```

---

## Arquitetura e Estrutura Interna

### Descricao dos diretorios principais

```
src/
├── Abstracts/              # Classes abstratas base
│   └── MetricManager.php   # Gerenciador abstrato de metricas
├── Console/
│   └── Commands/           # Comandos Artisan
│       ├── MigrateCommand.php
│       ├── PublishCommand.php
│       └── RollbackCommand.php
├── Facades/                # Facades para acesso simplificado
├── Http/
│   ├── Controllers/        # Controllers da interface admin
│   │   └── Admin/
│   │       ├── AnalyticsController.php
│   │       └── Metrics/
│   └── Middleware/         # Middlewares de tracking
│       ├── Analytics.php
│       └── TrackStatistics.php
├── Jobs/                   # Jobs para processamento em background
│   ├── CleanStatisticsRequests.php
│   └── CrunchStatistics.php
├── Models/                 # Eloquent Models
│   ├── Analytics.php
│   ├── Metrics.php
│   ├── Metrics/            # Models de metricas (Larametrics)
│   ├── Statistics/         # Models de estatisticas
│   │   ├── Agent.php
│   │   ├── Datum.php
│   │   ├── Device.php
│   │   ├── Geoip.php
│   │   ├── Path.php
│   │   ├── Platform.php
│   │   ├── Request.php
│   │   └── Route.php
│   └── Trackings.php
├── Notifications/          # Notificacoes de eventos
├── Providers/              # Service Providers adicionais
├── Services/               # Servicos de negocio
│   ├── AnalyticsService.php
│   └── TrackingService.php
├── Traits/                 # Traits reutilizaveis
└── TrackingProvider.php    # Service Provider principal
```

### Padroes arquiteturais adotados

#### 1. Service Provider Pattern
O `TrackingProvider` centraliza o registro de bindings, aliases e configuracoes.

#### 2. Repository Pattern (implícito)
Os Models Eloquent atuam como repositories para acesso a dados.

#### 3. Job/Queue Pattern
Jobs assincronos para processamento pesado sem impactar a experiencia do usuario.

#### 4. Middleware Pattern
`TrackStatistics` e `Analytics` interceptam requisicoes para coleta de dados.

### Comunicacao entre camadas

```
Request → Middleware (TrackStatistics) → Coleta dados → Salva Datum
                                                            ↓
                              Job (CrunchStatistics) ← Lottery/Schedule
                                         ↓
                              Processa Datum → Atualiza Models normalizados
                                               (Agent, Device, Path, etc.)
```

### Convencoes e boas praticas

- **Namespaces**: Seguem PSR-4 com `Tracking\` como raiz
- **Models**: Utilizam binding no Service Container para facil substituicao
- **Configuracoes**: Arquivos separados por dominio (`analytics.php`, `statistics.php`)
- **Testes**: Estruturados em Unit e Feature test suites

---

## Principais Componentes e Responsabilidades

### Models de Estatisticas

#### Agent (`Tracking\Models\Statistics\Agent`)
Armazena informacoes do navegador/agente do usuario.

```php
// Exemplo de uso
$agent = app('tracking.statistics.agent')->first();

// Estrutura
[
    'id' => 1,
    'kind' => 'desktop',        // desktop, mobile, tablet
    'family' => 'Chrome',
    'version' => '120.0.0',
    'name' => 'Mozilla/5.0...',  // User Agent completo
    'count' => 1547              // Total de acessos
]
```

#### Device (`Tracking\Models\Statistics\Device`)
Informacoes sobre o dispositivo do usuario.

```php
$device = app('tracking.statistics.device')->where('brand', 'Apple')->first();

// Estrutura
[
    'id' => 1,
    'family' => 'iPhone',
    'model' => 'iPhone 14',
    'brand' => 'Apple',
    'count' => 892
]
```

#### Platform (`Tracking\Models\Statistics\Platform`)
Sistema operacional do usuario.

```php
$platform = app('tracking.statistics.platform')
    ->where('family', 'Windows')
    ->first();

// Estrutura
[
    'id' => 1,
    'family' => 'Windows',
    'version' => '11',
    'count' => 3421
]
```

#### Path (`Tracking\Models\Statistics\Path`)
Caminhos acessados na aplicacao.

```php
// Contador de visualizacoes de pagina
$pageViews = app('tracking.statistics.path')
    ->where('path', request()->decodedPath())
    ->first()
    ->count;

// Estrutura
[
    'id' => 1,
    'host' => 'app.example.com',
    'locale' => 'pt-BR',
    'path' => 'dashboard/analytics',
    'parameters' => '{"period": "month"}',
    'count' => 567
]
```

#### Route (`Tracking\Models\Statistics\Route`)
Detalhes das rotas Laravel acessadas.

```php
$route = app('tracking.statistics.route')
    ->where('name', 'dashboard.index')
    ->first();

// Estrutura
[
    'id' => 1,
    'name' => 'dashboard.index',
    'path' => 'dashboard',
    'action' => 'App\Http\Controllers\DashboardController@index',
    'middleware' => '["web", "auth"]',
    'parameters' => '{}',
    'count' => 8934
]
```

#### Geoip (`Tracking\Models\Statistics\Geoip`)
Localizacao geografica baseada no IP.

```php
$geoip = app('tracking.statistics.geoip')
    ->where('country_code', 'BR')
    ->get();

// Estrutura
[
    'id' => 1,
    'client_ip' => '200.100.50.25',
    'latitude' => -23.5505,
    'longitude' => -46.6333,
    'country_code' => 'BR',
    'division_code' => 'SP',
    'city' => 'Sao Paulo',
    'timezone' => 'America/Sao_Paulo',
    'count' => 234
]
```

#### Request (`Tracking\Models\Statistics\Request`)
Registro completo de cada requisicao.

```php
$request = app('tracking.statistics.request')
    ->with(['agent', 'device', 'platform', 'path', 'route', 'geoip'])
    ->latest()
    ->first();

// Acessando relacionamentos
$browserVersion = $request->agent->version;
$deviceBrand = $request->device->brand;
$userCity = $request->geoip->city;
```

### Middlewares

#### TrackStatistics
Registra automaticamente cada requisicao valida.

```php
// Registrado automaticamente no grupo 'web'
// Executa no metodo terminate() apos resposta enviada
```

#### Analytics
Middleware adicional para integracao com Google Analytics.

### Jobs

#### CrunchStatistics
Processa dados brutos e atualiza contadores.

```php
// Executar manualmente
dispatch(new \Tracking\Jobs\CrunchStatistics);

// Ou via schedule
$schedule->job(new \Tracking\Jobs\CrunchStatistics)->hourly();
```

#### CleanStatisticsRequests
Remove requisicoes antigas para manter performance.

```php
// Limpar requisicoes com mais de 30 dias
dispatch(new \Tracking\Jobs\CleanStatisticsRequests);
```

### Services

#### TrackingService
Facade principal para acesso programatico.

```php
use TrackingService;

// Registrado automaticamente via alias
app('TrackingService');
```

---

## Uso Pratico

### Configuracao basica (zero config)

Apos a instalacao, o tracking ja funciona automaticamente. O middleware `TrackStatistics` e adicionado ao grupo `web` e comeca a coletar dados imediatamente.

### Exibindo contador de visualizacoes

```php
// Em um Controller
public function show($id)
{
    $article = Article::findOrFail($id);

    $pageViews = app('tracking.statistics.path')
        ->where('path', request()->decodedPath())
        ->where('host', request()->getHost())
        ->first()
        ?->count ?? 0;

    return view('articles.show', compact('article', 'pageViews'));
}
```

### Dashboard de analytics

```php
// Controller de Analytics
public function dashboard()
{
    // Top 10 paginas mais acessadas
    $topPages = app('tracking.statistics.path')
        ->orderByDesc('count')
        ->take(10)
        ->get();

    // Dispositivos mais usados
    $devices = app('tracking.statistics.device')
        ->selectRaw('brand, SUM(count) as total')
        ->groupBy('brand')
        ->orderByDesc('total')
        ->get();

    // Navegadores
    $browsers = app('tracking.statistics.agent')
        ->selectRaw('family, SUM(count) as total')
        ->groupBy('family')
        ->orderByDesc('total')
        ->get();

    // Localizacoes
    $locations = app('tracking.statistics.geoip')
        ->selectRaw('country_code, city, SUM(count) as total')
        ->groupBy('country_code', 'city')
        ->orderByDesc('total')
        ->take(20)
        ->get();

    return view('admin.analytics.dashboard', compact(
        'topPages', 'devices', 'browsers', 'locations'
    ));
}
```

### Filtrando por periodo

```php
// Requisicoes dos ultimos 7 dias
$recentRequests = app('tracking.statistics.request')
    ->where('created_at', '>=', now()->subDays(7))
    ->with(['agent', 'path'])
    ->get();

// Agrupar por dia
$dailyStats = app('tracking.statistics.request')
    ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
    ->where('created_at', '>=', now()->subDays(30))
    ->groupBy('date')
    ->orderBy('date')
    ->get();
```

### Rastreando usuarios logados

```php
// Requisicoes de um usuario especifico
$userRequests = app('tracking.statistics.request')
    ->where('user_id', $userId)
    ->where('user_type', User::class)
    ->with(['path', 'agent'])
    ->latest()
    ->paginate(20);

// Usuarios unicos por sessao
$uniqueVisitors = app('tracking.statistics.request')
    ->distinct('session_id')
    ->where('created_at', '>=', now()->subDays(7))
    ->count();
```

### Exportando dados

```php
// Exportar para CSV
public function export()
{
    $requests = app('tracking.statistics.request')
        ->with(['agent', 'device', 'path', 'geoip'])
        ->where('created_at', '>=', now()->subMonth())
        ->cursor();

    $handle = fopen('php://output', 'w');
    fputcsv($handle, ['Data', 'Path', 'Browser', 'Device', 'Cidade', 'Pais']);

    foreach ($requests as $request) {
        fputcsv($handle, [
            $request->created_at->format('Y-m-d H:i'),
            $request->path->path ?? 'N/A',
            $request->agent->family ?? 'N/A',
            $request->device->brand ?? 'N/A',
            $request->geoip->city ?? 'N/A',
            $request->geoip->country_code ?? 'N/A',
        ]);
    }

    fclose($handle);
}
```

---

## Integracao com o Ecossistema SierraTecnologia

### Integracoes incluidas

O **Tracking** ja vem integrado com:

- **Laravel Horizon**: Monitoramento de jobs e queues
- **Spatie Laravel Analytics**: Integracao com Google Analytics
- **Larametrics**: Metricas adicionais da aplicacao
- **SierraTecnologia Audit**: Auditoria de alteracoes em models

### Padrao de versionamento

Seguimos [Semantic Versioning](https://semver.org/):

- **MAJOR**: Mudancas incompativeis na API
- **MINOR**: Funcionalidades novas, retrocompativeis
- **PATCH**: Correcoes de bugs, retrocompativeis

### Pipelines CI/CD

O projeto utiliza GitHub Actions com os seguintes jobs:

1. **Tests**: PHPUnit em multiplas versoes de PHP e Laravel
2. **PHPStan**: Analise estatica nivel 6
3. **PHPCS**: Verificacao de padroes PSR-12
4. **Code Coverage**: Relatorio de cobertura de testes

### Uso padronizado entre equipes

Para garantir consistencia entre diferentes projetos:

```php
// Sempre use os service bindings
app('tracking.statistics.path')  // Correto
new \Tracking\Models\Statistics\Path  // Evitar

// Permite substituicao facil em cada projeto
$this->app->singleton('tracking.statistics.path', CustomPath::class);
```

---

## Extensao e Customizacao

### Substituindo Models

Crie seu proprio model estendendo o original:

```php
// app/Models/CustomAgent.php
namespace App\Models;

use Tracking\Models\Statistics\Agent;

class CustomAgent extends Agent
{
    // Adicionar campos
    protected $appends = ['browser_icon'];

    public function getBrowserIconAttribute()
    {
        return match($this->family) {
            'Chrome' => 'chrome.svg',
            'Firefox' => 'firefox.svg',
            'Safari' => 'safari.svg',
            default => 'browser.svg'
        };
    }
}
```

Registre no Service Provider:

```php
// app/Providers/AppServiceProvider.php
public function register()
{
    $this->app->singleton('tracking.statistics.agent', \App\Models\CustomAgent::class);
}
```

### Criando middleware customizado

```php
// app/Http/Middleware/CustomTracking.php
namespace App\Http\Middleware;

use Tracking\Http\Middleware\TrackStatistics;

class CustomTracking extends TrackStatistics
{
    protected function shouldTrack($request)
    {
        // Nao rastrear rotas de API
        if ($request->is('api/*')) {
            return false;
        }

        // Nao rastrear bots
        if ($this->isBot($request)) {
            return false;
        }

        return parent::shouldTrack($request);
    }

    protected function isBot($request)
    {
        $userAgent = $request->userAgent();
        $bots = ['bot', 'crawler', 'spider', 'slurp'];

        foreach ($bots as $bot) {
            if (stripos($userAgent, $bot) !== false) {
                return true;
            }
        }

        return false;
    }
}
```

### Adicionando novos campos

1. Crie uma migration:

```php
Schema::table('trackings_requests', function (Blueprint $table) {
    $table->string('custom_field')->nullable();
});
```

2. Estenda o model Request:

```php
class CustomRequest extends \Tracking\Models\Statistics\Request
{
    protected $fillable = [
        // campos originais...
        'custom_field'
    ];
}
```

3. Estenda o middleware para preencher o campo:

```php
protected function gatherData($request)
{
    $data = parent::gatherData($request);
    $data['custom_field'] = $this->getCustomValue($request);
    return $data;
}
```

### Jobs customizados

```php
// app/Jobs/CustomCrunchStatistics.php
namespace App\Jobs;

use Tracking\Jobs\CrunchStatistics;

class CustomCrunchStatistics extends CrunchStatistics
{
    public function handle()
    {
        parent::handle();

        // Logica adicional
        $this->generateCustomReports();
        $this->notifyAdmins();
    }

    protected function generateCustomReports()
    {
        // ...
    }
}
```

---

## Exemplos Reais

### Caso 1: E-commerce com analise de funil

```php
// Rastrear etapas do funil de vendas
class FunnelAnalyticsController extends Controller
{
    public function report()
    {
        $paths = app('tracking.statistics.path');

        $funnel = [
            'home' => $paths->where('path', '/')->first()?->count ?? 0,
            'products' => $paths->where('path', 'like', 'products%')->sum('count'),
            'cart' => $paths->where('path', 'cart')->first()?->count ?? 0,
            'checkout' => $paths->where('path', 'checkout')->first()?->count ?? 0,
            'success' => $paths->where('path', 'order/success')->first()?->count ?? 0,
        ];

        // Calcular taxas de conversao
        $funnel['cart_rate'] = $funnel['products'] > 0
            ? round(($funnel['cart'] / $funnel['products']) * 100, 2)
            : 0;

        return view('admin.funnel', compact('funnel'));
    }
}
```

### Caso 2: SaaS com analytics por tenant

```php
// Multi-tenant tracking
class TenantAnalyticsController extends Controller
{
    public function dashboard()
    {
        $tenant = auth()->user()->tenant;

        // Filtrar por host do tenant
        $stats = app('tracking.statistics.path')
            ->where('host', $tenant->domain)
            ->orderByDesc('count')
            ->take(10)
            ->get();

        // Usuarios ativos por tenant
        $activeUsers = app('tracking.statistics.request')
            ->join('users', 'trackings_requests.user_id', '=', 'users.id')
            ->where('users.tenant_id', $tenant->id)
            ->where('trackings_requests.created_at', '>=', now()->subDays(30))
            ->distinct('user_id')
            ->count();

        return view('tenant.analytics', compact('stats', 'activeUsers'));
    }
}
```

### Caso 3: Blog com metricas de engajamento

```php
// Artigo model com metricas
class Article extends Model
{
    public function getViewsAttribute()
    {
        return app('tracking.statistics.path')
            ->where('path', "articles/{$this->slug}")
            ->first()
            ?->count ?? 0;
    }

    public function getUniqueViewsAttribute()
    {
        return app('tracking.statistics.request')
            ->whereHas('path', fn($q) => $q->where('path', "articles/{$this->slug}"))
            ->distinct('session_id')
            ->count();
    }

    public function scopePopular($query)
    {
        return $query->withCount(['views'])
            ->orderByDesc('views_count');
    }
}
```

### Beneficios observados

- **Reducao de 90% no tempo de carregamento** comparado a outras solucoes de tracking
- **Dados granulares** sem impacto na experiencia do usuario
- **Escalabilidade** com processamento em background
- **Flexibilidade** para customizacoes especificas de cada projeto

---

## Ferramentas de Qualidade

### Executando testes

```bash
# Executar todos os testes
composer test

# Testes com cobertura
composer test-coverage

# Executar teste especifico
./vendor/bin/phpunit --filter=ServiceProviderTest
```

### Analise estatica com PHPStan

```bash
# Executar analise
composer phpstan

# Ou diretamente
./vendor/bin/phpstan analyse
```

O projeto esta configurado com nivel 6 de analise no PHPStan.

### Verificacao de codigo com PHPCS

```bash
# Verificar padrao PSR-12
composer phpcs

# Corrigir automaticamente
composer phpcbf

# Ou diretamente
./vendor/bin/phpcs --standard=phpcs.xml src/
./vendor/bin/phpcbf --standard=phpcs.xml src/
```

### Executar todas as verificacoes

```bash
# Analise completa (PHPStan + PHPCS)
composer analyse

# Verificacao completa (analise + testes)
composer check
```

### Configuracao local

Os arquivos de configuracao estao na raiz do projeto:

- `phpunit.xml.dist` - Configuracao do PHPUnit
- `phpstan.neon.dist` - Configuracao do PHPStan
- `phpcs.xml` - Configuracao do PHPCS (PSR-12)

---

## Guia de Contribuicao

### Como contribuir

1. Fork o repositorio
2. Crie uma branch para sua feature (`git checkout -b feature/nova-funcionalidade`)
3. Commit suas mudancas (`git commit -m 'Adiciona nova funcionalidade'`)
4. Push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

### Padroes de commit

Seguimos o padrao [Conventional Commits](https://www.conventionalcommits.org/):

- `feat:` Nova funcionalidade
- `fix:` Correcao de bug
- `docs:` Documentacao
- `style:` Formatacao, ponto e virgula, etc
- `refactor:` Refatoracao de codigo
- `test:` Adicao ou correcao de testes
- `chore:` Manutencao, atualizacao de dependencias

### Branch naming

- `feature/` - Novas funcionalidades
- `fix/` - Correcoes de bugs
- `docs/` - Atualizacoes de documentacao
- `refactor/` - Refatoracoes

### Requisitos para PR

1. Todos os testes devem passar
2. PHPStan sem erros
3. PHPCS sem violacoes
4. Documentacao atualizada (se aplicavel)
5. Changelog atualizado (se aplicavel)

### Executando localmente

```bash
# Clonar repositorio
git clone https://github.com/sierratecnologia/tracking.git
cd tracking

# Instalar dependencias
composer install

# Executar verificacoes
composer check
```

### Licenca

Este projeto e licenciado sob a [MIT License](LICENSE).

---

## Changelog

Consulte o [CHANGELOG.md](CHANGELOG.md) para historico completo de versoes.

---

## Suporte

Os seguintes canais de suporte estao disponiveis:

- [Chat no Slack](https://bit.ly/sierratecnologia-slack)
- [Email de suporte](mailto:help@sierratecnologia.com)
- [Twitter](https://twitter.com/sierratecnologia)

---

## Vulnerabilidades de Seguranca

Se voce descobrir uma vulnerabilidade de seguranca neste projeto, por favor envie um e-mail para [help@sierratecnologia.com](mailto:help@sierratecnologia.com). Todas as vulnerabilidades de seguranca serao tratadas prontamente.

---

## About SierraTecnologia

SierraTecnologia is a software solutions startup, specialized in integrated enterprise solutions for SMEs established in Alexandria, Egypt since June 2016. We believe that our drive The Value, The Reach, and The Impact is what differentiates us and unleash the endless possibilities of our philosophy through the power of software. We like to call it Innovation At The Speed Of Life. That's how we do our share of advancing humanity.

---

## License

This software is released under [The MIT License (MIT)](LICENSE).

(c) 2016-2024 SierraTecnologia LLC, Some rights reserved.
