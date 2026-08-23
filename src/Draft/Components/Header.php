<?php

namespace WaTemplates\Draft\Components;

use WaTemplates\Draft\Dialect;
use WaTemplates\Draft\Parameter;
use WaTemplates\Enums\HeaderFormat;
use WaTemplates\Enums\ParameterFormat;

/**
 * The optional component above the body — text, media, a map, or a catalog
 * product.
 *
 * Only the text format carries content at creation. A media header is a
 * `header_handle` from the Resumable Upload API and nothing else; a location or
 * product header is its `format` and nothing else, because both resolve at send
 * time.
 */
final class Header implements Component
{
    /**
     * @param  array<string,string>  $examples
     */
    public function __construct(
        public HeaderFormat $format = HeaderFormat::Text,
        public string $text = '',
        public array $examples = [],
        public ?string $handle = null,
    ) {}

    public static function text(string $text, array $examples = []): self
    {
        return new self(HeaderFormat::Text, $text, $examples);
    }

    public static function media(HeaderFormat $format, string $handle): self
    {
        return new self($format, handle: $handle);
    }

    /**
     * @return list<Parameter>
     */
    public function parameters(): array
    {
        if ($this->format !== HeaderFormat::Text) {
            return [];
        }

        return array_map(
            fn (string $key): Parameter => new Parameter(
                key: $key,
                example: $this->examples[$key] ?? '',
                format: Parameter::formatOf($key),
            ),
            Parameter::keysIn($this->text),
        );
    }

    public function toPayload(Dialect $dialect): array
    {
        $payload = [
            'type' => $dialect->keyword('header'),
            'format' => $dialect->keyword($this->format->value),
        ];

        if ($this->format->isMedia()) {
            if ($this->handle !== null) {
                $payload['example'] = ['header_handle' => [$this->handle]];
            }

            return $payload;
        }

        if ($this->format !== HeaderFormat::Text) {
            return $payload;
        }

        $payload['text'] = $this->text;

        $parameters = $this->parameters();

        if ($parameters === []) {
            return $payload;
        }

        /**
         * A text header takes exactly one parameter, but the example key still
         * follows the same named/positional split the body uses. Header
         * positional examples are flat in every documented shape — unlike
         * `body_text`, they are never double-wrapped.
         */
        if ($parameters[0]->format === ParameterFormat::Named) {
            $payload['example'] = [
                'header_text_named_params' => array_map(
                    fn (Parameter $parameter): array => [
                        'param_name' => $parameter->key,
                        'example' => $parameter->example,
                    ],
                    $parameters,
                ),
            ];

            return $payload;
        }

        $payload['example'] = [
            'header_text' => array_map(
                fn (Parameter $parameter): string => $parameter->example,
                $parameters,
            ),
        ];

        return $payload;
    }

    public static function fromPayload(array $payload): self
    {
        $format = HeaderFormat::fromPayload((string) ($payload['format'] ?? 'TEXT'));
        $text = (string) ($payload['text'] ?? '');

        $handle = $payload['example']['header_handle'][0] ?? null;

        return new self(
            format: $format,
            text: $text,
            examples: $format === HeaderFormat::Text ? self::readExamples($payload, $text) : [],
            handle: is_string($handle) ? $handle : null,
        );
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,string>
     */
    private static function readExamples(array $payload, string $text): array
    {
        $named = $payload['example']['header_text_named_params'] ?? null;

        if (is_array($named)) {
            $examples = [];

            foreach ($named as $parameter) {
                if (isset($parameter['param_name'])) {
                    $examples[(string) $parameter['param_name']] = (string) ($parameter['example'] ?? '');
                }
            }

            return $examples;
        }

        $positional = $payload['example']['header_text'] ?? null;

        if (! is_array($positional) || $positional === []) {
            return [];
        }

        $first = $positional[array_key_first($positional)];
        $values = is_array($first) ? $first : $positional;

        $keys = Parameter::keysIn($text);
        $examples = [];

        foreach (array_values($values) as $index => $value) {
            if (isset($keys[$index]) && is_scalar($value)) {
                $examples[$keys[$index]] = (string) $value;
            }
        }

        return $examples;
    }
}
