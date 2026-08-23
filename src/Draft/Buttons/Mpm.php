<?php

namespace WaTemplates\Draft\Buttons;

use WaTemplates\Draft\Dialect;
use WaTemplates\Enums\ButtonType;

/**
 * Opens a sectioned product list inside WhatsApp — up to 30 products in 10
 * sections, all chosen at send time. The template names no products at all.
 */
final class Mpm implements Button
{
    public function __construct(public string $text = 'View items') {}

    public function type(): ButtonType
    {
        return ButtonType::Mpm;
    }

    public function label(): string
    {
        return $this->text;
    }

    public function toPayload(Dialect $dialect): array
    {
        return [
            'type' => $dialect->keyword('mpm'),
            'text' => $this->text,
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self((string) ($payload['text'] ?? 'View items'));
    }
}
