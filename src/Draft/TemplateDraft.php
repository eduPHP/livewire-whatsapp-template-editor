<?php

namespace WaTemplates\Draft;

use WaTemplates\Draft\Components\Body;
use WaTemplates\Draft\Components\Buttons;
use WaTemplates\Draft\Components\Carousel;
use WaTemplates\Draft\Components\Footer;
use WaTemplates\Draft\Components\Header;
use WaTemplates\Draft\Components\LimitedTimeOffer;
use WaTemplates\Enums\Category;
use WaTemplates\Enums\ParameterFormat;

/**
 * A template being edited: everything `POST /<WABA_ID>/message_templates` needs
 * and nothing about how it gets there.
 *
 * This is deliberately not a thin wrapper over the payload array. It has to
 * survive a round trip through Meta's own vocabulary, which is case-inconsistent
 * and dialect-split, so the incidental shape lives in `Dialect` while the
 * meaning lives here.
 */
final class TemplateDraft
{
    public function __construct(
        public string $name = '',
        public string $language = 'en_US',
        public Category $category = Category::Utility,
        public Body $body = new Body,
        public ?Header $header = null,
        public ?Footer $footer = null,
        public Buttons $buttons = new Buttons,
        public ?Carousel $carousel = null,
        public ?LimitedTimeOffer $limitedTimeOffer = null,
        public Dialect $dialect = new Dialect,
        public ?ParameterFormat $parameterFormat = null,
    ) {}

    /**
     * The format the template addresses its variables by.
     *
     * Meta only sends `parameter_format` back for named templates, so an absent
     * value is inferred from the body rather than assumed positional — a
     * template parsed from a listing must build back to the same dialect.
     */
    public function parameterFormat(): ParameterFormat
    {
        return $this->parameterFormat ?? $this->body->format();
    }

    /**
     * @return array{name:string,language:string,category:string,components:list<array<string,mixed>>}
     */
    public function toPayload(): array
    {
        $payload = [
            'name' => $this->name,
            'language' => $this->language,
            'category' => $this->dialect->keyword($this->category->value),
        ];

        if ($this->parameterFormat !== null) {
            $payload['parameter_format'] = $this->dialect->keyword($this->parameterFormat->value);
        }

        $payload['components'] = $this->components();

        return $payload;
    }

    /**
     * Meta's required component order.
     *
     * Header, body, footer, buttons is the documented order for a plain
     * template. The two special components slot in where their own worked
     * examples put them: `limited_time_offer` sits between the header and the
     * body, and `carousel` sits after the body — it replaces the footer and
     * button row rather than joining them.
     *
     * @return list<array<string,mixed>>
     */
    public function components(): array
    {
        $components = [];

        if ($this->header !== null) {
            $components[] = $this->header->toPayload($this->dialect);
        }

        if ($this->limitedTimeOffer !== null) {
            $components[] = $this->limitedTimeOffer->toPayload($this->dialect);
        }

        $components[] = $this->body->toPayload($this->dialect);

        if ($this->footer !== null) {
            $components[] = $this->footer->toPayload($this->dialect);
        }

        if (! $this->buttons->isEmpty()) {
            $components[] = $this->buttons->toPayload($this->dialect);
        }

        if ($this->carousel !== null) {
            $components[] = $this->carousel->toPayload($this->dialect);
        }

        return $components;
    }

    /**
     * Parse a template as Meta hands it over — either a whole create request or
     * the bare `components` array a listing returns.
     *
     * @param  array<string,mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        $components = $payload['components'] ?? [];
        $components = is_array($components) ? array_values($components) : [];

        $draft = new self(
            name: (string) ($payload['name'] ?? ''),
            language: (string) ($payload['language'] ?? 'en_US'),
            category: Category::fromPayload((string) ($payload['category'] ?? 'UTILITY')),
            dialect: Dialect::detect($components),
            parameterFormat: isset($payload['parameter_format'])
                ? ParameterFormat::fromPayload((string) $payload['parameter_format'])
                : null,
        );

        foreach ($components as $component) {
            if (! is_array($component)) {
                continue;
            }

            match (strtoupper((string) ($component['type'] ?? ''))) {
                'HEADER' => $draft->header = Header::fromPayload($component),
                'BODY' => $draft->body = Body::fromPayload($component),
                'FOOTER' => $draft->footer = Footer::fromPayload($component),
                'BUTTONS' => $draft->buttons = Buttons::fromPayload($component),
                'CAROUSEL' => $draft->carousel = Carousel::fromPayload($component),
                'LIMITED_TIME_OFFER' => $draft->limitedTimeOffer = LimitedTimeOffer::fromPayload($component),
                default => null,
            };
        }

        return $draft;
    }

    /**
     * Parse the `components` array alone — the shape `CloudTemplateSync` stores.
     *
     * @param  list<array<string,mixed>>  $components
     */
    public static function fromComponents(array $components, string $name = '', string $language = 'en_US', ?Category $category = null): self
    {
        return self::fromPayload([
            'name' => $name,
            'language' => $language,
            'category' => ($category ?? Category::Utility)->value,
            'components' => $components,
        ]);
    }
}
