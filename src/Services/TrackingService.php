<?php

declare(strict_types=1);

namespace Tracking\Services;

use DateTimeInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tracking\Contracts\TargetResolver;
use Tracking\Events\EventRecorded;
use Tracking\Models\TrackingEvent;
use Tracking\Resolvers\ModelTargetResolver;
use Tracking\Support\Journey;
use Tracking\Support\TrackingPayload;

/**
 * Grava eventos em um alvo e conta a jornada dele.
 *
 * Onde o evento é gravado depende do alvo, nesta ordem:
 *  1. alvo com `trackingEvents()` (trait HasTrackingEvents) — morph;
 *  2. alvo com `events()` (ex.: Lead do CRM com a própria tabela);
 *  3. `tracking.event_model` (ou TrackingEvent), ligando por lead_id ou morph.
 */
class TrackingService
{
    public function __construct(
        protected ?string $eventModelClass = null,
        protected ?Dispatcher $dispatcher = null,
        protected ?TargetResolver $resolver = null,
    ) {
        $this->eventModelClass = $eventModelClass ?: config('tracking.event_model');
    }

    /**
     * @return list<array{type: string, description: string, meta: array<string, mixed>|null, created_at: Carbon|null, elapsed_ms: int|null}>
     */
    public function parsePayload(Request $request): array
    {
        return TrackingPayload::fromRequest($request);
    }

    /**
     * Grava um evento. Devolve o registro criado ou nulo quando o tipo não
     * é aceito pela configuração.
     *
     * @param  array<string, mixed>|null  $meta
     */
    public function recordEvent(
        mixed $target,
        string $type,
        string $description,
        ?array $meta = null,
        ?DateTimeInterface $createdAt = null,
    ): mixed {
        $normalizado = TrackingPayload::normalize([
            'type' => $type,
            'description' => $description,
            'meta' => $meta,
            'created_at' => $createdAt?->format(DATE_ATOM),
        ]);

        if ($normalizado === null || ! $this->accepts($normalizado['type'])) {
            return null;
        }

        $timestamp = $createdAt ?? now();
        $dados = [
            'type' => $normalizado['type'],
            'description' => $normalizado['description'],
            'meta' => $normalizado['meta'],
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];

        $evento = $this->persist($target, $dados);

        $this->dispatcher?->dispatch(new EventRecorded(
            $target,
            $evento,
            $dados['type'],
            $dados['description'],
            $dados['meta'],
        ));

        return $evento;
    }

    /**
     * Grava os passos que o navegador acumulou antes da conversão.
     *
     * @param  array<int, mixed>  $steps
     * @return int quantos foram gravados
     */
    public function recordSteps(mixed $target, array $steps): int
    {
        return $this->recordNormalized($target, TrackingPayload::normalizeList($steps));
    }

    /** Grava os eventos contidos na requisição (fetch ou beacon). */
    public function recordRequestEvents(mixed $target, Request $request): int
    {
        return $this->recordNormalized($target, $this->parsePayload($request));
    }

    /**
     * O identificador do alvo circula no navegador: depois da janela ele
     * deixa de aceitar eventos, para ninguém escrever num lead antigo.
     */
    public function isWindowExpired(mixed $target, ?int $maxHours = null): bool
    {
        $maxHours ??= (int) (config('tracking.target.window_hours') ?? config('tracking.lead_window_hours') ?? 6);

        if ($target instanceof Model && $target->created_at !== null) {
            return $target->created_at->copy()->addHours($maxHours)->isPast();
        }

        return false;
    }

    /** Resolve o alvo a partir do identificador da URL. */
    public function resolveTarget(string $identifier): ?object
    {
        return $this->resolver()->resolve($identifier);
    }

    public function resolver(): TargetResolver
    {
        if ($this->resolver !== null) {
            return $this->resolver;
        }

        $classe = config('tracking.target.resolver');

        return $this->resolver = is_string($classe) && class_exists($classe)
            ? app($classe)
            : new ModelTargetResolver;
    }

    /** Eventos do alvo, do mais antigo ao mais novo. */
    public function eventsFor(mixed $target): Collection
    {
        if ($target instanceof Model && method_exists($target, 'trackingEvents')) {
            return $target->trackingEvents()->reorder()->orderBy('created_at')->orderBy('id')->get();
        }

        if ($target instanceof Model && method_exists($target, 'events')) {
            return $target->events()->reorder()->orderBy('created_at')->orderBy('id')->get();
        }

        if (! $target instanceof Model) {
            return new Collection;
        }

        $modelo = $this->eventModel();
        $query = $modelo::query();

        if ($this->hasColumn($modelo, 'lead_id')) {
            $query->where('lead_id', $target->getKey());
        } else {
            $query->where('trackable_type', $target->getMorphClass())->where('trackable_id', $target->getKey());
        }

        return $query->orderBy('created_at')->orderBy('id')->get();
    }

    /** A jornada do alvo, pronta para o painel e para o score. */
    public function journey(mixed $target): Journey
    {
        return Journey::fromEvents($this->eventsFor($target));
    }

    /** @return class-string<Model> */
    public function eventModel(): string
    {
        if ($this->eventModelClass && class_exists($this->eventModelClass)) {
            return $this->eventModelClass;
        }

        return TrackingEvent::class;
    }

    public function accepts(string $type): bool
    {
        $permitidos = config('tracking.allowed_types');

        return ! is_array($permitidos) || in_array($type, $permitidos, true);
    }

    /** @param list<array{type: string, description: string, meta: array<string, mixed>|null, created_at: Carbon|null, elapsed_ms: int|null}> $eventos */
    private function recordNormalized(mixed $target, array $eventos): int
    {
        $gravados = 0;

        foreach ($eventos as $item) {
            $evento = $this->recordEvent($target, $item['type'], $item['description'], $item['meta'], $item['created_at']);

            if ($evento !== null) {
                $gravados++;
            }
        }

        return $gravados;
    }

    /** @param array<string, mixed> $dados */
    private function persist(mixed $target, array $dados): mixed
    {
        if ($target instanceof Model && method_exists($target, 'trackingEvents')) {
            return $target->trackingEvents()->create($dados);
        }

        if ($target instanceof Model && method_exists($target, 'events')) {
            return $target->events()->create($dados);
        }

        $modelo = $this->eventModel();

        if ($target instanceof Model) {
            if ($this->hasColumn($modelo, 'lead_id') && ! $this->hasColumn($modelo, 'trackable_type')) {
                $dados['lead_id'] = $target->getKey();
            } else {
                $dados['trackable_type'] = $target->getMorphClass();
                $dados['trackable_id'] = $target->getKey();
            }
        }

        return $modelo::query()->create($dados);
    }

    /** @var array<string, bool> */
    private array $colunas = [];

    /** @param class-string<Model> $modelo */
    private function hasColumn(string $modelo, string $coluna): bool
    {
        $chave = $modelo.'.'.$coluna;

        if (! array_key_exists($chave, $this->colunas)) {
            $instancia = new $modelo;
            $this->colunas[$chave] = $instancia->getConnection()->getSchemaBuilder()->hasColumn($instancia->getTable(), $coluna);
        }

        return $this->colunas[$chave];
    }
}
