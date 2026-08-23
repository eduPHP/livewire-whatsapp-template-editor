<?php

namespace WaTemplates\Draft\Components;

use WaTemplates\Draft\Dialect;

/**
 * The expiring-offer band: a heading of at most 16 characters and an optional
 * countdown.
 *
 * A template carrying this component may not have a footer, and its body is
 * capped at 600 characters rather than the usual 1024.
 */
final class LimitedTimeOffer implements Component
{
    public function __construct(
        public string $text = '',
        public bool $hasExpiration = false,
    ) {}

    public function toPayload(Dialect $dialect): array
    {
        return [
            'type' => $dialect->keyword('limited_time_offer'),
            'limited_time_offer' => [
                'text' => $this->text,
                'has_expiration' => $this->hasExpiration,
            ],
        ];
    }

    public static function fromPayload(array $payload): self
    {
        $offer = $payload['limited_time_offer'] ?? [];

        return new self(
            text: (string) ($offer['text'] ?? ''),
            hasExpiration: (bool) ($offer['has_expiration'] ?? false),
        );
    }
}
