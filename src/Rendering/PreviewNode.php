<?php

namespace WaTemplates\Rendering;

/**
 * One renderable piece of the preview bubble.
 *
 * A flat, view-agnostic description of what to draw. Blade decides how it
 * looks; this decides what is there. Keeping the two apart is what lets the
 * live editor preview and the read-only visualizer share one renderer.
 */
final readonly class PreviewNode
{
    /**
     * @param  array<string,mixed>  $attributes
     * @param  list<PreviewNode>  $children
     */
    public function __construct(
        public string $type,
        public string $text = '',
        public array $attributes = [],
        public array $children = [],
    ) {}

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}
