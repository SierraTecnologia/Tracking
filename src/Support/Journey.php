<?php

declare(strict_types=1);

namespace Tracking\Support;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * A jornada de um visitante contada a partir dos eventos.
 *
 * Recebe qualquer lista de eventos (Models ou arrays com type, description,
 * meta e created_at) e responde o que um vendedor quer saber antes de
 * ligar: de onde veio, o que viu, quanto tempo ficou e quão quente está.
 */
final class Journey implements Arrayable, JsonSerializable
{
    /** @var list<array{type: string, description: string, meta: array<string, mixed>, created_at: ?\DateTimeInterface}> */
    private array $events = [];

    private function __construct() {}

    /** @param iterable<mixed> $events */
    public static function fromEvents(iterable $events): self
    {
        $journey = new self;

        foreach ($events as $event) {
            $normalizado = self::normalize($event);

            if ($normalizado !== null && EventType::isBehavioral($normalizado['type'])) {
                $journey->events[] = $normalizado;
            }
        }

        usort($journey->events, function (array $a, array $b): int {
            $ta = $a['created_at']?->getTimestamp() ?? 0;
            $tb = $b['created_at']?->getTimestamp() ?? 0;

            return $ta <=> $tb ?: ($a['meta']['elapsed_ms'] ?? 0) <=> ($b['meta']['elapsed_ms'] ?? 0);
        });

        return $journey;
    }

    public function isEmpty(): bool
    {
        return $this->events === [];
    }

    public function count(): int
    {
        return count($this->events);
    }

    /** Origem legível: campanha, referrer ou acesso direto. */
    public function origin(): ?string
    {
        $primeira = $this->first(EventType::NAVEGACAO);

        if ($primeira === null) {
            return null;
        }

        $meta = $primeira['meta'];

        if (! empty($meta['utm_source'])) {
            $origem = ucfirst((string) $meta['utm_source']);
            $detalhes = array_filter([$meta['utm_medium'] ?? null, $meta['utm_campaign'] ?? null]);

            return $detalhes === [] ? $origem : $origem.' ('.implode(', ', $detalhes).')';
        }

        if (! empty($meta['referrer'])) {
            $host = parse_url((string) $meta['referrer'], PHP_URL_HOST);

            return is_string($host) && $host !== '' ? preg_replace('/^www\./', '', $host) : 'referrer externo';
        }

        return 'direto';
    }

    /** @return array<string, string|null> */
    public function utm(): array
    {
        $meta = $this->first(EventType::NAVEGACAO)['meta'] ?? [];

        return [
            'source' => $meta['utm_source'] ?? null,
            'medium' => $meta['utm_medium'] ?? null,
            'campaign' => $meta['utm_campaign'] ?? null,
            'term' => $meta['utm_term'] ?? null,
            'content' => $meta['utm_content'] ?? null,
        ];
    }

    public function device(): ?string
    {
        $device = $this->first(EventType::NAVEGACAO)['meta']['device'] ?? null;

        return is_string($device) && $device !== '' ? $device : null;
    }

    public function entryPage(): ?string
    {
        $url = $this->first(EventType::NAVEGACAO)['meta']['url'] ?? null;

        if (! is_string($url) || $url === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : '/';
    }

    /** Páginas vistas (eventos de navegação). */
    public function pages(): int
    {
        return count($this->all(EventType::NAVEGACAO));
    }

    /** @return list<string> URLs distintas, na ordem em que apareceram. */
    public function urls(): array
    {
        $urls = [];

        foreach ($this->all(EventType::NAVEGACAO) as $evento) {
            $url = $evento['meta']['url'] ?? null;

            if (is_string($url) && $url !== '' && ! in_array($url, $urls, true)) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    /** Maior profundidade de rolagem (0-100). */
    public function maxScroll(): int
    {
        $max = 0;

        foreach ($this->all(EventType::ROLAGEM) as $evento) {
            $max = max($max, (int) ($evento['meta']['profundidade'] ?? 0));
        }

        return min(100, $max);
    }

    /** @return list<string> Rótulos das seções vistas, sem repetição. */
    public function sections(): array
    {
        $secoes = [];

        foreach ($this->all(EventType::SECAO) as $evento) {
            $rotulo = $evento['meta']['rotulo'] ?? $evento['meta']['secao_id'] ?? null;

            if (! is_string($rotulo) || $rotulo === '') {
                $rotulo = trim(str_replace('Visualizou seção:', '', $evento['description']));
            }

            if ($rotulo !== '' && ! in_array($rotulo, $secoes, true)) {
                $secoes[] = $rotulo;
            }
        }

        return $secoes;
    }

    public function interactions(): int
    {
        return count($this->all(EventType::INTERACAO)) + count($this->all(EventType::FORMULARIO));
    }

    public function whatsappClicks(): int
    {
        return count($this->all(EventType::CLIQUE_WA));
    }

    public function contactClicks(): int
    {
        return count($this->all(EventType::CLIQUE_CONTATO));
    }

    public function startedForm(): bool
    {
        return $this->first(EventType::FORMULARIO) !== null;
    }

    /** Tempo na página em segundos, pelo maior elapsed_ms ou pelos timestamps. */
    public function durationSeconds(): int
    {
        $maiorElapsed = 0;

        foreach ($this->events as $evento) {
            $maiorElapsed = max($maiorElapsed, (int) ($evento['meta']['elapsed_ms'] ?? 0));
        }

        if ($maiorElapsed > 0) {
            return (int) round($maiorElapsed / 1000);
        }

        $datas = array_values(array_filter(array_map(fn (array $e) => $e['created_at']?->getTimestamp(), $this->events)));

        return $datas === [] ? 0 : max($datas) - min($datas);
    }

    /**
     * Engajamento de 0 a 100.
     *
     * Serve para ordenar a fila: quem rolou tudo, abriu FAQ e clicou no
     * WhatsApp está mais perto de comprar do que quem só abriu a página.
     */
    public function engagement(): int
    {
        if ($this->isEmpty()) {
            return 0;
        }

        $pontos = 0;
        $pontos += min($this->pages(), 3) * 5;
        $pontos += (int) round($this->maxScroll() / 100 * 20);
        $pontos += min(count($this->sections()), 5) * 4;
        $pontos += min($this->interactions(), 5) * 4;
        $pontos += $this->whatsappClicks() > 0 || $this->contactClicks() > 0 ? 15 : 0;
        $pontos += (int) round(min($this->durationSeconds(), 300) / 300 * 10);

        return min(100, $pontos);
    }

    /** Frase pronta para o painel do CRM. */
    public function summary(): string
    {
        if ($this->isEmpty()) {
            return 'Sem rastro de navegação: o lead entrou sem o tracker ou o navegador bloqueou o script.';
        }

        $partes = [];

        $origem = $this->origin();
        $dispositivo = match ($this->device()) {
            'mobile' => 'pelo celular',
            'tablet' => 'pelo tablet',
            'desktop' => 'pelo computador',
            default => null,
        };

        $entrada = 'Chegou '.($origem === 'direto' ? 'direto' : 'via '.$origem);
        if ($dispositivo !== null) {
            $entrada .= ' '.$dispositivo;
        }
        if ($this->entryPage() !== null) {
            $entrada .= ' em '.$this->entryPage();
        }
        $partes[] = $entrada;

        $duracao = $this->durationSeconds();
        if ($duracao > 0) {
            $partes[] = 'ficou '.self::humanDuration($duracao);
        }

        if ($this->pages() > 1) {
            $partes[] = 'viu '.$this->pages().' páginas';
        }

        if ($this->maxScroll() > 0) {
            $partes[] = 'rolou '.$this->maxScroll().'% da página';
        }

        $secoes = $this->sections();
        if ($secoes !== []) {
            $partes[] = 'passou por '.self::joinPt(array_slice($secoes, 0, 5));
        }

        if ($this->interactions() > 0) {
            $partes[] = $this->interactions() === 1 ? 'fez 1 interação' : 'fez '.$this->interactions().' interações';
        }

        if ($this->whatsappClicks() > 0) {
            $partes[] = 'clicou no WhatsApp'.($this->whatsappClicks() > 1 ? ' '.$this->whatsappClicks().' vezes' : '');
        }

        if ($this->contactClicks() > 0) {
            $partes[] = 'clicou em telefone ou e-mail';
        }

        return ucfirst(implode(', ', $partes)).'.';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'eventos' => $this->count(),
            'origem' => $this->origin(),
            'utm' => $this->utm(),
            'dispositivo' => $this->device(),
            'pagina_entrada' => $this->entryPage(),
            'paginas' => $this->pages(),
            'urls' => $this->urls(),
            'rolagem_maxima' => $this->maxScroll(),
            'secoes' => $this->sections(),
            'interacoes' => $this->interactions(),
            'cliques_whatsapp' => $this->whatsappClicks(),
            'cliques_contato' => $this->contactClicks(),
            'iniciou_formulario' => $this->startedForm(),
            'duracao_segundos' => $this->durationSeconds(),
            'engajamento' => $this->engagement(),
            'resumo' => $this->summary(),
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /** @return array{type: string, description: string, meta: array<string, mixed>, created_at: ?\DateTimeInterface}|null */
    private function first(string $type): ?array
    {
        foreach ($this->events as $evento) {
            if ($evento['type'] === $type) {
                return $evento;
            }
        }

        return null;
    }

    /** @return list<array{type: string, description: string, meta: array<string, mixed>, created_at: ?\DateTimeInterface}> */
    private function all(string $type): array
    {
        return array_values(array_filter($this->events, fn (array $e) => $e['type'] === $type));
    }

    /** @return array{type: string, description: string, meta: array<string, mixed>, created_at: ?\DateTimeInterface}|null */
    private static function normalize(mixed $event): ?array
    {
        if (is_object($event)) {
            $type = $event->type ?? null;
            $description = $event->description ?? '';
            $meta = $event->meta ?? [];
            $createdAt = $event->created_at ?? null;
        } elseif (is_array($event)) {
            $type = $event['type'] ?? null;
            $description = $event['description'] ?? '';
            $meta = $event['meta'] ?? [];
            $createdAt = $event['created_at'] ?? null;
        } else {
            return null;
        }

        if (! is_string($type) || $type === '') {
            return null;
        }

        if (is_string($meta)) {
            $meta = json_decode($meta, true);
        }

        if (is_string($createdAt)) {
            try {
                $createdAt = new \DateTimeImmutable($createdAt);
            } catch (\Throwable) {
                $createdAt = null;
            }
        }

        return [
            'type' => $type,
            'description' => (string) $description,
            'meta' => is_array($meta) ? $meta : [],
            'created_at' => $createdAt instanceof \DateTimeInterface ? $createdAt : null,
        ];
    }

    private static function humanDuration(int $segundos): string
    {
        if ($segundos < 60) {
            return $segundos.' s';
        }

        $minutos = intdiv($segundos, 60);

        if ($minutos < 60) {
            return $minutos.' min';
        }

        return intdiv($minutos, 60).' h '.($minutos % 60).' min';
    }

    /** @param list<string> $itens */
    private static function joinPt(array $itens): string
    {
        if (count($itens) <= 1) {
            return implode('', $itens);
        }

        $ultimo = array_pop($itens);

        return implode(', ', $itens).' e '.$ultimo;
    }
}
