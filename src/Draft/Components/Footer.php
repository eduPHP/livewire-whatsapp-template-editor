<?php

namespace WaTemplates\Draft\Components;

use WaTemplates\Draft\Dialect;

/**
 * Small text under the body. 60 characters, and no parameters at all —
 * `components.md` is explicit that the footer supports none, which is where
 * `utility.md` is wrong.
 */
final class Footer implements Component
{
    public function __construct(public string $text = '') {}

    public function toPayload(Dialect $dialect): array
    {
        return [
            'type' => $dialect->keyword('footer'),
            'text' => $this->text,
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self((string) ($payload['text'] ?? ''));
    }
}
