<?php

namespace WaTemplates\Enums;

/**
 * How a template addresses its variables.
 *
 * Meta treats this as a template-level property (`parameter_format`), but it
 * changes the shape of every component's `example` key, so components read it
 * rather than guessing from the text they hold.
 */
enum ParameterFormat: string
{
    case Positional = 'POSITIONAL';
    case Named = 'NAMED';

    public static function fromPayload(?string $value): self
    {
        return match (strtoupper((string) $value)) {
            'NAMED' => self::Named,
            default => self::Positional,
        };
    }
}
