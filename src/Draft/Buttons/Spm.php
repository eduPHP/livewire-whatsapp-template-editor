<?php

namespace WaTemplates\Draft\Buttons;

use WaTemplates\Draft\Dialect;
use WaTemplates\Enums\ButtonType;

/**
 * Opens a single product's details inside WhatsApp. Only valid on a product
 * carousel card, where the product comes from the catalog at send time.
 */
final class Spm implements Button
{
    public function __construct(public string $text = 'View') {}

    public function type(): ButtonType
    {
        return ButtonType::Spm;
    }

    public function label(): string
    {
        return $this->text;
    }

    public function toPayload(Dialect $dialect): array
    {
        return [
            'type' => $dialect->keyword('spm'),
            'text' => $this->text,
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self((string) ($payload['text'] ?? 'View'));
    }
}
