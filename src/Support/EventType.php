<?php

declare(strict_types=1);

namespace Tracking\Support;

/**
 * Vocabulário dos eventos que o tracker.js emite.
 *
 * O CRM pode gravar outros tipos (criado, nota, status...); estes são os
 * comportamentais, e é sobre eles que a Journey calcula engajamento.
 */
final class EventType
{
    public const NAVEGACAO = 'navegacao';

    public const ROLAGEM = 'rolagem';

    public const SECAO = 'secao';

    public const INTERACAO = 'interacao';

    public const CLIQUE_WA = 'clique_wa';

    public const CLIQUE_CONTATO = 'clique_contato';

    public const CLIQUE_EXTERNO = 'clique_externo';

    public const FORMULARIO = 'formulario';

    public const SAIDA = 'saida';

    public const EVENTO = 'evento';

    /** @return list<string> */
    public static function behavioral(): array
    {
        return [
            self::NAVEGACAO,
            self::ROLAGEM,
            self::SECAO,
            self::INTERACAO,
            self::CLIQUE_WA,
            self::CLIQUE_CONTATO,
            self::CLIQUE_EXTERNO,
            self::FORMULARIO,
            self::SAIDA,
            self::EVENTO,
        ];
    }

    public static function isBehavioral(string $type): bool
    {
        return in_array($type, self::behavioral(), true);
    }

    public static function label(string $type): string
    {
        return match ($type) {
            self::NAVEGACAO => 'Navegação',
            self::ROLAGEM => 'Rolagem',
            self::SECAO => 'Seção vista',
            self::INTERACAO => 'Interação',
            self::CLIQUE_WA => 'Clique no WhatsApp',
            self::CLIQUE_CONTATO => 'Clique em contato',
            self::CLIQUE_EXTERNO => 'Link externo',
            self::FORMULARIO => 'Formulário',
            self::SAIDA => 'Saída',
            self::EVENTO => 'Evento',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    /**
     * Cor no vocabulário do Filament/Tailwind, para painéis que listam a
     * linha do tempo.
     */
    public static function color(string $type): string
    {
        return match ($type) {
            self::NAVEGACAO => 'info',
            self::ROLAGEM => 'purple',
            self::SECAO => 'gray',
            self::INTERACAO, self::FORMULARIO => 'amber',
            self::CLIQUE_WA, self::CLIQUE_CONTATO => 'success',
            self::CLIQUE_EXTERNO => 'orange',
            self::SAIDA => 'danger',
            default => 'gray',
        };
    }
}
