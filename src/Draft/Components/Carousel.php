<?php

namespace WaTemplates\Draft\Components;

use WaTemplates\Draft\Dialect;

/**
 * Up to 10 horizontally scrollable cards below the body.
 *
 * Every card must be structurally identical — a carousel where card 1 has a URL
 * button and card 2 has a quick reply is rejected outright. The editor enforces
 * this by editing one card schema and applying it to every row.
 */
final class Carousel implements Component
{
    /**
     * @param  list<Card>  $cards
     */
    public function __construct(public array $cards = []) {}

    public function toPayload(Dialect $dialect): array
    {
        return [
            'type' => $dialect->keyword('carousel'),
            'cards' => array_map(
                fn (Card $card): array => $card->toPayload($dialect),
                $this->cards,
            ),
        ];
    }

    public static function fromPayload(array $payload): self
    {
        $cards = $payload['cards'] ?? [];

        return new self(array_map(
            fn (array $card): Card => Card::fromPayload($card),
            is_array($cards) ? array_values($cards) : [],
        ));
    }

    /**
     * Whether every card shares one structure.
     */
    public function isUniform(): bool
    {
        if ($this->cards === []) {
            return true;
        }

        $structures = array_map(fn (Card $card): string => $card->structure(), $this->cards);

        return count(array_unique($structures)) === 1;
    }
}
