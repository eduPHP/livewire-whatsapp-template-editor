<?php

namespace WaTemplates\Draft\Buttons;

use WaTemplates\Draft\Dialect;
use WaTemplates\Enums\ButtonType;

/**
 * Sends its own label back as a message when tapped.
 *
 * The optional payload is what `messages.button.payload` carries on the
 * webhook; the recipient never sees it.
 */
final class QuickReply implements Button
{
    public function __construct(
        public string $text = '',
        public ?string $payload = null,
    ) {}

    public function type(): ButtonType
    {
        return ButtonType::QuickReply;
    }

    public function label(): string
    {
        return $this->text;
    }

    public function toPayload(Dialect $dialect): array
    {
        return [
            'type' => $dialect->keyword('quick_reply'),
            'text' => $this->text,
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self((string) ($payload['text'] ?? ''));
    }
}
