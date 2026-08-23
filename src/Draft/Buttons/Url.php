<?php

namespace WaTemplates\Draft\Buttons;

use WaTemplates\Draft\Dialect;
use WaTemplates\Enums\ButtonType;

/**
 * Opens a URL in the device browser, leaving WhatsApp.
 *
 * Supports one variable, and only appended to the end of the URL — a variable
 * anywhere else is rejected at review.
 */
final class Url implements Button
{
    public function __construct(
        public string $text = '',
        public string $url = '',
        public ?string $example = null,
    ) {}

    public function type(): ButtonType
    {
        return ButtonType::Url;
    }

    public function label(): string
    {
        return $this->text;
    }

    public function hasVariable(): bool
    {
        return str_contains($this->url, '{{');
    }

    /**
     * The URL a recipient would actually open, with the example substituted.
     *
     * Meta's own worked examples are inconsistent about what `example` holds:
     * `components.md` supplies the bare variable value (`summer2023`) while
     * `marketing-limited-time-offer.md` supplies a whole resolved URL. Both
     * appear in approved templates, so both are handled — a value that already
     * looks like a URL replaces the whole thing rather than being substituted
     * into it.
     */
    public function resolvedUrl(): string
    {
        if ($this->example === null || $this->example === '') {
            return $this->url;
        }

        if (str_starts_with($this->example, 'http://') || str_starts_with($this->example, 'https://')) {
            return $this->example;
        }

        return (string) preg_replace('/\{\{\s*[A-Za-z0-9_]+\s*\}\}/', $this->example, $this->url);
    }

    public function toPayload(Dialect $dialect): array
    {
        $payload = [
            'type' => $dialect->keyword('url'),
            'text' => $this->text,
            'url' => $this->url,
        ];

        if ($this->example !== null) {
            $payload['example'] = [$this->example];
        }

        return $payload;
    }

    public static function fromPayload(array $payload): self
    {
        $example = $payload['example'][0] ?? null;

        return new self(
            text: (string) ($payload['text'] ?? ''),
            url: (string) ($payload['url'] ?? ''),
            example: is_string($example) ? $example : null,
        );
    }
}
