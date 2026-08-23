<?php

namespace WaTemplates\Livewire\Panels;

use Livewire\Component;
use WaTemplates\Livewire\TemplateEditor;
use WaTemplates\Validation\ValidationResult;

/**
 * Shared behaviour for the editor's panels.
 *
 * Each panel owns its own field state and dispatches its slice upward; the
 * orchestrator reassembles and validates. No panel knows about Meta's payload
 * shape — that lives entirely in the domain layer.
 */
abstract class Panel extends Component
{
    /**
     * Errors for this panel's subtree, keyed by component path.
     *
     * @var array<string,list<string>>
     */
    public array $panelErrors = [];

    /**
     * The key this panel's slice occupies in the draft state.
     */
    abstract protected function slice(): string;

    /**
     * This panel's current values.
     *
     * @return array<string,mixed>
     */
    abstract protected function values(): array;

    /**
     * Push this panel's slice up to the orchestrator.
     */
    protected function publish(): void
    {
        $this->dispatch('component-updated', slice: $this->slice(), values: $this->values())
            ->to(TemplateEditor::class);
    }

    public function remove(): void
    {
        $this->dispatch('component-removed', slice: $this->slice())
            ->to(TemplateEditor::class);
    }

    /**
     * Livewire calls this on every `wire:model.live` change, so a panel
     * publishes as the operator types rather than on an explicit save.
     */
    public function updated(): void
    {
        $this->publish();
    }

    /**
     * The validator's path prefix for this panel's errors.
     *
     * Usually the state slice, but not always: the metadata panel edits
     * `meta.*` while the validator keys the template name at the top level as
     * `name`, because that is where it sits in the payload.
     */
    protected function errorPrefix(): string
    {
        return $this->slice();
    }

    /**
     * @return list<string>
     */
    protected function errorsFor(string $path): array
    {
        return $this->panelErrors[$path] ?? [];
    }

    public function hasErrors(): bool
    {
        return (new ValidationResult($this->panelErrors))->under($this->errorPrefix()) !== [];
    }
}
