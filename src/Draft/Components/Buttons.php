<?php

namespace WaTemplates\Draft\Components;

use WaTemplates\Draft\Buttons\Button;
use WaTemplates\Draft\Buttons\ButtonFactory;
use WaTemplates\Draft\Dialect;
use WaTemplates\Enums\ButtonType;

/**
 * The button row. Meta models this as one component holding up to ten buttons,
 * not as ten components.
 */
final class Buttons implements Component
{
    /**
     * @param  list<Button>  $buttons
     */
    public function __construct(public array $buttons = []) {}

    /**
     * @return list<Button>
     */
    public function ofType(ButtonType $type): array
    {
        return array_values(array_filter(
            $this->buttons,
            fn (Button $button): bool => $button->type() === $type,
        ));
    }

    public function isEmpty(): bool
    {
        return $this->buttons === [];
    }

    public function toPayload(Dialect $dialect): array
    {
        return [
            'type' => $dialect->keyword('buttons'),
            'buttons' => array_map(
                fn (Button $button): array => $button->toPayload($dialect),
                $this->buttons,
            ),
        ];
    }

    public static function fromPayload(array $payload): self
    {
        $buttons = $payload['buttons'] ?? [];

        return new self(array_map(
            fn (array $button): Button => ButtonFactory::fromPayload($button),
            is_array($buttons) ? array_values($buttons) : [],
        ));
    }
}
