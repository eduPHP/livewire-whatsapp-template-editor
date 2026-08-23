<?php

namespace WaTemplates\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use WaTemplates\Capabilities\Capabilities;
use WaTemplates\Capabilities\Feature;
use WaTemplates\Contracts\CatalogSource;
use WaTemplates\Contracts\MediaUploader;
use WaTemplates\Contracts\PrefilledVariable;
use WaTemplates\Contracts\VariableSource;
use WaTemplates\Draft\TemplateDraft;
use WaTemplates\Livewire\Support\DraftState;
use WaTemplates\Rendering\TemplateRenderer;
use WaTemplates\Validation\TemplateValidator;

/**
 * The editor's orchestrator: it owns the draft, and nothing else does.
 *
 * Panels hold their own field state and dispatch their slice upward; this
 * component reassembles, validates, and emits `template-changed`. No panel
 * knows about Meta's payload shape — that lives entirely in the domain layer.
 *
 * The package never submits. It produces the payload; the host decides when and
 * how to send it, which keeps credentials, workspace scoping and the
 * duplicate-name rule on the host side where they belong.
 */
class TemplateEditor extends Component
{
    /**
     * The draft in transport form.
     *
     * Livewire round-trips public properties through JSON, so the draft lives
     * here as an array and is rehydrated into the domain model on each use
     * rather than being held as an object across requests.
     *
     * @var array<string,mixed>
     */
    public array $state = [];

    /**
     * @param  array<string,mixed>|null  $template  A Meta create request, or its bare `components` array.
     */
    public function mount(?array $template = null): void
    {
        $this->state = DraftState::fromDraft(
            $template === null ? new TemplateDraft : DraftState::parse($template),
        );

        $this->emitChange();
    }

    /**
     * What this installation can build.
     *
     * Resolved from the container on each request rather than held on a public
     * property: Livewire serializes public properties to the browser and back,
     * and neither of these interfaces survives that trip. The host binds
     * whichever it has — an unbound one is a capability this installation does
     * not have, which is exactly what `Capabilities` is there to express.
     */
    public function capabilities(): Capabilities
    {
        return new Capabilities(
            app()->bound(CatalogSource::class) ? app(CatalogSource::class) : null,
            app()->bound(MediaUploader::class) ? app(MediaUploader::class) : null,
        );
    }

    public function draft(): TemplateDraft
    {
        return DraftState::toDraft($this->state);
    }

    /**
     * Variables the host fills at send time, as `name => sample`.
     *
     * Flattened to a plain array before it reaches a panel: Livewire round-trips
     * public properties through JSON, and the contract's objects would not
     * survive. Resolved per request from the container like the other
     * capabilities, so an unbound source simply means no pre-filled variables.
     *
     * @return array<string,string>
     */
    public function prefilledVariables(): array
    {
        if (! app()->bound(VariableSource::class)) {
            return [];
        }

        $variables = [];

        foreach (app(VariableSource::class)->variables() as $variable) {
            if ($variable instanceof PrefilledVariable) {
                $variables[$variable->name] = $variable->sample;
            }
        }

        return $variables;
    }

    /**
     * A panel changed one slice of the draft.
     *
     * Named `$slice` rather than `$component`: Livewire spreads a dispatched
     * event's params into the container's method invoker, and a parameter
     * called `component` is taken for a dependency to resolve rather than a
     * value to bind — which throws `BindingResolutionException` on every real
     * browser request, while `Livewire::test()` passes.
     *
     * @param  array<string,mixed>  $values
     */
    #[On('component-updated')]
    public function componentUpdated(string $slice, array $values): void
    {
        $this->state[$slice] = $values;

        $this->emitChange();
    }

    /**
     * Add one of the optional components in its empty state.
     *
     * The draft omits a component entirely rather than carrying an empty one,
     * because `components` is positional and Meta reads an empty footer as a
     * footer.
     */
    public function addComponent(string $slice): void
    {
        $this->state[$slice] = match ($slice) {
            'header' => ['format' => 'TEXT', 'text' => '', 'examples' => [], 'handle' => null],
            'footer' => ['text' => ''],
            'carousel' => ['cards' => []],
            'limited_time_offer' => ['text' => '', 'has_expiration' => false],
            default => [],
        };

        $this->emitChange();
    }

    #[On('component-removed')]
    public function componentRemoved(string $slice): void
    {
        unset($this->state[$slice]);

        $this->emitChange();
    }

    /**
     * Hand the host an approval-ready payload plus whether it would be accepted.
     *
     * The payload is emitted even when invalid: a host may want to save a draft
     * the operator is still working on, and deciding that is the host's call,
     * not this package's.
     */
    private function emitChange(): void
    {
        $draft = $this->draft();
        $result = (new TemplateValidator)->validate($draft);

        $this->dispatch(
            'template-changed',
            payload: $draft->toPayload(),
            valid: $result->passes(),
            errors: $result->errors,
        );
    }

    public function render(): View
    {
        $draft = $this->draft();
        $capabilities = $this->capabilities();

        return view('wa-templates::livewire.template-editor', [
            'draft' => $draft,
            'preview' => (new TemplateRenderer)->render($draft),
            'errors' => (new TemplateValidator)->validate($draft),
            'capabilities' => $capabilities,
            'features' => Feature::cases(),
            'prefilled' => $this->prefilledVariables(),
        ]);
    }
}
