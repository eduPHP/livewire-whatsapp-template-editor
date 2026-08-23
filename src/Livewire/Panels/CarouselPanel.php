<?php

namespace WaTemplates\Livewire\Panels;

use Illuminate\Contracts\View\View;
use WaTemplates\Enums\ButtonType;
use WaTemplates\Validation\TemplateValidator;

/**
 * One card schema, applied to every row.
 *
 * Meta rejects a carousel whose cards differ structurally, so the editor does
 * not offer per-card structure at all: the schema — header format and button
 * types — is edited once, and each card row fills in only its own content. That
 * makes the invalid state unreachable rather than merely validated against.
 */
class CarouselPanel extends Panel
{
    /** @var list<array<string,mixed>> */
    public array $cards = [];

    /**
     * The shared card structure: header format plus one entry per button.
     *
     * @var array<string,mixed>
     */
    public array $schema = [
        'format' => 'IMAGE',
        'buttons' => [],
    ];

    public ?string $mediaReason = null;

    public ?string $catalogReason = null;

    /**
     * @param  array<string,mixed>  $values
     * @param  array<string,list<string>>  $errors
     */
    public function mount(
        array $values = [],
        array $errors = [],
        ?string $mediaReason = null,
        ?string $catalogReason = null,
    ): void {
        $this->cards = array_values($values['cards'] ?? []);
        $this->panelErrors = $errors;
        $this->mediaReason = $mediaReason;
        $this->catalogReason = $catalogReason;

        if ($this->cards !== []) {
            $this->schema = [
                'format' => $this->cards[0]['format'] ?? 'IMAGE',
                'buttons' => array_map(
                    fn (array $button): array => ['type' => $button['type'], 'text' => $button['text']],
                    $this->cards[0]['buttons'] ?? [],
                ),
            ];
        }
    }

    protected function slice(): string
    {
        return 'carousel';
    }

    protected function values(): array
    {
        return ['cards' => array_values($this->cards)];
    }

    public function addCard(): void
    {
        if (count($this->cards) >= TemplateValidator::CAROUSEL_CARDS_MAX) {
            return;
        }

        $this->cards[] = $this->blankCard();

        $this->publish();
    }

    public function removeCard(int $index): void
    {
        unset($this->cards[$index]);

        $this->cards = array_values($this->cards);

        $this->publish();
    }

    public function addSchemaButton(string $type): void
    {
        if (count($this->schema['buttons']) >= 2) {
            return;
        }

        $this->schema['buttons'][] = ['type' => $type, 'text' => ''];

        $this->applySchema();
    }

    public function removeSchemaButton(int $index): void
    {
        unset($this->schema['buttons'][$index]);

        $this->schema['buttons'] = array_values($this->schema['buttons']);

        $this->applySchema();
    }

    /**
     * Push the schema onto every card, keeping whatever content still fits.
     *
     * A card whose button changes type keeps its label but loses the fields the
     * new type has no use for — a URL's address means nothing on a quick reply.
     */
    public function applySchema(): void
    {
        foreach ($this->cards as $cardIndex => $card) {
            $this->cards[$cardIndex]['format'] = $this->schema['format'];

            $buttons = [];

            foreach ($this->schema['buttons'] as $index => $schemaButton) {
                $existing = $card['buttons'][$index] ?? [];

                $keepsType = ($existing['type'] ?? null) === $schemaButton['type'];

                $buttons[] = [
                    'type' => $schemaButton['type'],
                    'text' => $keepsType ? ($existing['text'] ?? '') : '',
                    'url' => $schemaButton['type'] === ButtonType::Url->value ? ($existing['url'] ?? '') : '',
                    'example' => $schemaButton['type'] === ButtonType::Url->value ? ($existing['example'] ?? null) : null,
                    'phone_number' => $schemaButton['type'] === ButtonType::PhoneNumber->value ? ($existing['phone_number'] ?? '') : '',
                    'payload' => null,
                ];
            }

            $this->cards[$cardIndex]['buttons'] = $buttons;
        }

        $this->publish();
    }

    /**
     * @return array<string,mixed>
     */
    private function blankCard(): array
    {
        return [
            'format' => $this->schema['format'],
            'handle' => null,
            'body' => '',
            'examples' => [],
            'buttons' => array_map(
                fn (array $button): array => [
                    'type' => $button['type'],
                    'text' => '',
                    'url' => '',
                    'example' => null,
                    'phone_number' => '',
                    'payload' => null,
                ],
                $this->schema['buttons'],
            ),
        ];
    }

    public function render(): View
    {
        return view('wa-templates::livewire.panels.carousel-panel', [
            /** Blade mangles a literal `{{…}}` in an attribute, so it comes from here. */
            'variableExample' => '{{1}}',
            'cardTypes' => [
                ButtonType::QuickReply->value => __('Quick reply'),
                ButtonType::Url->value => __('URL'),
                ButtonType::PhoneNumber->value => __('Phone number'),
            ],
            'maxCards' => TemplateValidator::CAROUSEL_CARDS_MAX,
            'atLimit' => count($this->cards) >= TemplateValidator::CAROUSEL_CARDS_MAX,
        ]);
    }
}
