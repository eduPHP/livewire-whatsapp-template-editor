<?php

namespace WaTemplates\Draft\Buttons;

use WaTemplates\Draft\Dialect;
use WaTemplates\Enums\ButtonType;

/**
 * Copies a promotional code to the clipboard.
 *
 * Carries no label of its own — WhatsApp renders "Copy offer code" — so the
 * example code stands in as the label for preview purposes.
 */
final class CopyCode implements Button
{
    public function __construct(public string $example = '') {}

    public function type(): ButtonType
    {
        return ButtonType::CopyCode;
    }

    public function label(): string
    {
        return 'Copy offer code';
    }

    public function toPayload(Dialect $dialect): array
    {
        return [
            'type' => $dialect->keyword('copy_code'),
            'example' => $this->example,
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self((string) ($payload['example'] ?? ''));
    }
}
