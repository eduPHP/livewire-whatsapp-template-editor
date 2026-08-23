<?php

namespace WaTemplates\Draft\Components;

use WaTemplates\Draft\Dialect;

/**
 * One card in a carousel: a header, an optional body, and up to two buttons.
 *
 * A card is not a template. It carries its own components array but no
 * `card_index` — that appears only on send, not at creation.
 */
final class Card
{
    public function __construct(
        public Header $header = new Header,
        public ?Body $body = null,
        public Buttons $buttons = new Buttons,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function toPayload(Dialect $dialect): array
    {
        $components = [$this->header->toPayload($dialect)];

        if ($this->body !== null) {
            $components[] = $this->body->toPayload($dialect);
        }

        if (! $this->buttons->isEmpty()) {
            $components[] = $this->buttons->toPayload($dialect);
        }

        return ['components' => $components];
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        $card = new self;

        foreach ($payload['components'] ?? [] as $component) {
            match (strtoupper((string) ($component['type'] ?? ''))) {
                'HEADER' => $card->header = Header::fromPayload($component),
                'BODY' => $card->body = Body::fromPayload($component),
                'BUTTONS' => $card->buttons = Buttons::fromPayload($component),
                default => null,
            };
        }

        return $card;
    }

    /**
     * The structural fingerprint Meta requires every card on a template to
     * share — same header format, same button types in the same order.
     *
     * Content may differ freely; structure may not.
     */
    public function structure(): string
    {
        $buttons = array_map(
            fn ($button): string => $button->type()->value,
            $this->buttons->buttons,
        );

        return implode('|', [
            $this->header->format->value,
            $this->body === null ? 'no-body' : 'body',
            implode(',', $buttons),
        ]);
    }
}
