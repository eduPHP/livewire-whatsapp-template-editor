<?php

namespace WaTemplates\Contracts;

/**
 * One variable the host fills automatically.
 *
 * The sample is not a convenience: Meta rejects a create request whose
 * variables carry no example, so a pre-filled variable still has to supply one.
 * The difference is that the host supplies it rather than the operator — it
 * already knows what this variable carries, so asking would be asking a
 * question it can answer itself.
 */
final readonly class PrefilledVariable
{
    public function __construct(
        public string $name,
        public string $sample,
        public ?string $label = null,
        public ?string $description = null,
    ) {}

    public function label(): string
    {
        return $this->label ?? $this->name;
    }

    public function placeholder(): string
    {
        return '{{'.$this->name.'}}';
    }
}
