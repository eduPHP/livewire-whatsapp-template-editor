<?php

namespace WaTemplates\Draft;

use WaTemplates\Enums\ParameterFormat;

/**
 * One variable in a text component, with the example value Meta requires.
 *
 * Positional parameters are addressed by their number (`{{1}}`), named ones by
 * their name (`{{order_number}}`). The `key` holds whichever applies; the
 * distinction lives in `format`.
 */
final readonly class Parameter
{
    public function __construct(
        public string $key,
        public string $example,
        public ParameterFormat $format = ParameterFormat::Positional,
    ) {}

    public function placeholder(): string
    {
        return '{{'.$this->key.'}}';
    }

    /**
     * Every distinct placeholder in `$text`, in first-appearance order.
     *
     * Meta wants exactly one example per *distinct* parameter, so a text that
     * repeats `{{1}}` twice still declares one example. Order matters for the
     * positional dialect, where examples are matched by array position alone.
     *
     * @return list<string>
     */
    public static function keysIn(string $text): array
    {
        preg_match_all('/\{\{\s*([A-Za-z0-9_]+)\s*\}\}/', $text, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    public static function formatOf(string $key): ParameterFormat
    {
        return ctype_digit($key) ? ParameterFormat::Positional : ParameterFormat::Named;
    }

    /**
     * Coerce a typed name into the only shape Meta accepts.
     *
     * Named parameters must be lowercase letters and underscores. Rather than
     * let an operator type `Order Number` and meet a validation error at
     * submission — by which point the template has been written around it —
     * the name is normalised as it is typed: accents are folded, spaces and
     * dashes become underscores, and anything else is dropped.
     */
    public static function normaliseName(string $name): string
    {
        $name = mb_strtolower(trim($name));

        $folded = @iconv('UTF-8', 'ASCII//TRANSLIT', $name);

        if (is_string($folded)) {
            $name = $folded;
        }

        $name = (string) preg_replace('/[\s-]+/', '_', $name);
        $name = (string) preg_replace('/[^a-z_]/', '', $name);

        return trim($name, '_');
    }
}
