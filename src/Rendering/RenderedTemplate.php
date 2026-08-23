<?php

namespace WaTemplates\Rendering;

/**
 * A whole template as the recipient would meet it.
 */
final readonly class RenderedTemplate
{
    /**
     * @param  list<PreviewNode>  $buttons
     * @param  list<PreviewNode>  $cards
     */
    public function __construct(
        public ?PreviewNode $header = null,
        public ?PreviewNode $offer = null,
        public string $body = '',
        public string $footer = '',
        public array $buttons = [],
        public array $cards = [],
    ) {}

    public function hasCards(): bool
    {
        return $this->cards !== [];
    }
}
