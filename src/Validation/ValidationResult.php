<?php

namespace WaTemplates\Validation;

/**
 * Errors keyed by component path (`buttons.2.text`) so the editor can highlight
 * the offending panel rather than showing a form-level list.
 */
final readonly class ValidationResult
{
    /**
     * @param  array<string,list<string>>  $errors
     */
    public function __construct(public array $errors = []) {}

    public function passes(): bool
    {
        return $this->errors === [];
    }

    public function fails(): bool
    {
        return ! $this->passes();
    }

    /**
     * @return list<string>
     */
    public function for(string $path): array
    {
        return $this->errors[$path] ?? [];
    }

    /**
     * Every error under a path prefix — what a panel asks for when it wants to
     * know whether anything inside it is wrong.
     *
     * @return array<string,list<string>>
     */
    public function under(string $prefix): array
    {
        return array_filter(
            $this->errors,
            fn (string $path): bool => $path === $prefix || str_starts_with($path, $prefix.'.'),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return array_merge(...array_values($this->errors)) ?: [];
    }
}
