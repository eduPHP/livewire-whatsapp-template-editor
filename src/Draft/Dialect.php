<?php

namespace WaTemplates\Draft;

/**
 * The incidental shape of the payload a template was parsed from.
 *
 * Meta is inconsistent with its own vocabulary in two ways that carry no
 * meaning but do change bytes:
 *
 * 1. Case. A listing returns `"type": "BODY"`, while every documented create
 *    request writes `"type": "body"`. Both are accepted on write.
 * 2. Nesting. `example.body_text` is documented flat (`["Pablo"]`) but every
 *    worked example — and everything Meta actually returns — wraps it in
 *    another array (`[["Pablo"]]`).
 *
 * Neither difference changes what the template *is*, so neither belongs in the
 * draft model proper. But `parse(build(x)) === x` is a test obligation, so the
 * draft has to remember which dialect it was handed and write back in it.
 *
 * A draft built from scratch defaults to lowercase keys and nested body
 * examples: the form every worked example in `docs/templates/` uses.
 */
final readonly class Dialect
{
    public function __construct(
        public bool $upperCaseKeywords = false,
        public bool $nestedBodyExample = true,
    ) {}

    /**
     * Infer the dialect from a `components` array as Meta handed it over.
     *
     * @param  list<array<string,mixed>>  $components
     */
    public static function detect(array $components): self
    {
        return new self(
            upperCaseKeywords: self::detectUpperCase($components),
            nestedBodyExample: self::detectNestedBodyExample($components),
        );
    }

    /**
     * @param  list<array<string,mixed>>  $components
     */
    private static function detectUpperCase(array $components): bool
    {
        foreach ($components as $component) {
            $type = $component['type'] ?? null;

            if (is_string($type) && $type !== '') {
                return $type === strtoupper($type);
            }
        }

        return false;
    }

    /**
     * A body with no example tells us nothing, so the default stands.
     *
     * @param  list<array<string,mixed>>  $components
     */
    private static function detectNestedBodyExample(array $components): bool
    {
        foreach ($components as $component) {
            if (strtoupper((string) ($component['type'] ?? '')) !== 'BODY') {
                continue;
            }

            $example = $component['example']['body_text'] ?? null;

            if (! is_array($example) || $example === []) {
                continue;
            }

            return is_array($example[array_key_first($example)]);
        }

        return true;
    }

    /**
     * Write a keyword back in the case it arrived in.
     */
    public function keyword(string $value): string
    {
        return $this->upperCaseKeywords ? strtoupper($value) : strtolower($value);
    }
}
