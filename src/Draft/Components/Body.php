<?php

namespace WaTemplates\Draft\Components;

use WaTemplates\Draft\Dialect;
use WaTemplates\Draft\Parameter;
use WaTemplates\Enums\ParameterFormat;

/**
 * The one required component: the message text itself.
 */
final class Body implements Component
{
    /**
     * @param  array<string,string>  $examples  Example value per parameter key.
     */
    public function __construct(
        public string $text = '',
        public array $examples = [],
    ) {}

    /**
     * @return list<Parameter>
     */
    public function parameters(): array
    {
        return array_map(
            fn (string $key): Parameter => new Parameter(
                key: $key,
                example: $this->examples[$key] ?? '',
                format: Parameter::formatOf($key),
            ),
            Parameter::keysIn($this->text),
        );
    }

    public function format(): ParameterFormat
    {
        $parameters = $this->parameters();

        return $parameters === []
            ? ParameterFormat::Positional
            : $parameters[0]->format;
    }

    public function toPayload(Dialect $dialect): array
    {
        $payload = [
            'type' => $dialect->keyword('body'),
            'text' => $this->text,
        ];

        $parameters = $this->parameters();

        if ($parameters === []) {
            return $payload;
        }

        if ($this->format() === ParameterFormat::Named) {
            $payload['example'] = [
                'body_text_named_params' => array_map(
                    fn (Parameter $parameter): array => [
                        'param_name' => $parameter->key,
                        'example' => $parameter->example,
                    ],
                    $parameters,
                ),
            ];

            return $payload;
        }

        $values = array_map(fn (Parameter $parameter): string => $parameter->example, $parameters);

        $payload['example'] = [
            'body_text' => $dialect->nestedBodyExample ? [$values] : $values,
        ];

        return $payload;
    }

    public static function fromPayload(array $payload): self
    {
        $text = (string) ($payload['text'] ?? '');

        return new self($text, self::readExamples($payload, $text));
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,string>
     */
    private static function readExamples(array $payload, string $text): array
    {
        $named = $payload['example']['body_text_named_params'] ?? null;

        if (is_array($named)) {
            $examples = [];

            foreach ($named as $parameter) {
                if (isset($parameter['param_name'])) {
                    $examples[(string) $parameter['param_name']] = (string) ($parameter['example'] ?? '');
                }
            }

            return $examples;
        }

        $positional = $payload['example']['body_text'] ?? null;

        if (! is_array($positional) || $positional === []) {
            return [];
        }

        $first = $positional[array_key_first($positional)];
        $values = is_array($first) ? $first : $positional;

        /**
         * Positional examples carry no keys of their own — they are matched to
         * the text's placeholders purely by position, which is why the keys
         * come from the text rather than from the example array.
         */
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
