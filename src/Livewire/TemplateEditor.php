<?php

namespace WaTemplates\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use WaTemplates\Capabilities\Capabilities;
use WaTemplates\Capabilities\Feature;
use WaTemplates\Contracts\CatalogSource;
use WaTemplates\Contracts\ClosedVariableSource;
use WaTemplates\Contracts\MediaUploader;
use WaTemplates\Contracts\PrefilledVariable;
use WaTemplates\Contracts\VariableSource;
use WaTemplates\Draft\TemplateDraft;
use WaTemplates\Enums\Step;
use WaTemplates\Livewire\Support\DraftState;
use WaTemplates\Rendering\TemplateRenderer;
use WaTemplates\Validation\TemplateValidator;
use WaTemplates\Validation\ValidationResult;

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
     * Which wizard step is on screen, as the enum's value.
     *
     * A string rather than the enum itself because Livewire round-trips public
     * properties through JSON; `step()` is the typed accessor everything else
     * uses.
     */
    public string $step = 'identification';

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

    public function step(): Step
    {
        return Step::tryFrom($this->step) ?? Step::Identification;
    }

    /**
     * Move to a step by name.
     *
     * Free navigation is deliberate: the numbered header is clickable, so an
     * operator who has filled in the body can jump back to the name without
     * walking the steps in reverse. The gate below only guards `continue()`,
     * which is the path a first-time operator follows.
     */
    public function goToStep(string $step): void
    {
        $this->step = (Step::tryFrom($step) ?? Step::Identification)->value;
    }

    /**
     * Advance, unless this step's own fields are wrong.
     *
     * Scoped to the current step's error prefixes rather than the whole
     * validation result: an empty body is a real error the moment the editor
     * opens, and gating step 1 on the entire template would refuse to let the
     * operator reach the body field that would fix it.
     */
    public function continue(): void
    {
        if ($this->stepErrors($this->step()) !== []) {
            return;
        }

        $next = $this->step()->next();

        if ($next !== null) {
            $this->step = $next->value;
        }
    }

    public function back(): void
    {
        $previous = $this->step()->previous();

        if ($previous !== null) {
            $this->step = $previous->value;
        }
    }

    /**
     * Errors belonging to one step, flattened for display under it.
     *
     * @return list<string>
     */
    public function stepErrors(Step $step): array
    {
        $result = new ValidationResult($this->validation()->errors);
        $errors = [];

        foreach ($step->errorPrefixes() as $prefix) {
            foreach ($result->under($prefix) as $messages) {
                $errors = array_merge($errors, $messages);
            }
        }

        return array_values(array_unique($errors));
    }

    private function validation(): ValidationResult
    {
        return $this->validator()->validate($this->draft());
    }

    /**
     * A validator that knows which variable names this installation can fill.
     *
     * Built here rather than instantiated at each call site so the step gate,
     * the emitted event and the rendered errors cannot disagree about whether
     * an invented variable is allowed — three `new TemplateValidator` calls with
     * different arguments would let Continue pass what Submit refuses.
     */
    private function validator(): TemplateValidator
    {
        return new TemplateValidator($this->allowedVariables());
    }

    /**
     * The only variable names allowed, or null when the operator may invent one.
     *
     * Null unless the host bound a `ClosedVariableSource`: declaring variables
     * makes them available, and only the closed marker turns availability into
     * exclusivity.
     *
     * @return list<string>|null
     */
    public function allowedVariables(): ?array
    {
        if (! $this->hasClosedVariableSet()) {
            return null;
        }

        return array_keys($this->prefilledVariables());
    }

    /**
     * Whether the host declared its variable set the only set.
     */
    public function hasClosedVariableSet(): bool
    {
        return app()->bound(VariableSource::class)
            && app(VariableSource::class) instanceof ClosedVariableSource;
    }

    /**
     * Why custom variables are unavailable, or null when they are allowed.
     *
     * A stated reason rather than a silently missing button, for the same
     * reason `Capabilities::reasonAgainst()` gives one: a hidden control reads
     * as a feature that does not exist, while a disabled one tells the operator
     * what is going on.
     */
    public function customVariableReason(): ?string
    {
        return $this->hasClosedVariableSet()
            ? __('This app fills variables by name, so only the variables it offers can be used.')
            : null;
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
     * Remove a component from the toolbar rather than from inside its panel.
     *
     * Same effect as the panel's own Remove button; it exists because the
     * header's "None" option is part of the format switch, which sits outside
     * the panel it removes — and when the header is absent there is no panel to
     * press a button in.
     */
    public function removeComponent(string $slice): void
    {
        $this->componentRemoved($slice);
    }

    /**
     * Choose the header format, adding the header if it is not there yet.
     *
     * Switching format keeps the text and handle already entered: an operator
     * flipping between Image and Document to see which reads better should not
     * lose the caption they wrote. Meta ignores whichever keys the chosen
     * format does not use, and `TemplateDraft` only emits the relevant ones.
     */
    public function setHeaderFormat(string $format): void
    {
        $this->state['header'] = [
            'format' => $format,
            'text' => $this->state['header']['text'] ?? '',
            'examples' => $this->state['header']['examples'] ?? [],
            'handle' => $this->state['header']['handle'] ?? null,
        ];

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
        $result = $this->validator()->validate($draft);

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
        $step = $this->step();

        return view('wa-templates::livewire.template-editor', [
            'draft' => $draft,
            'preview' => (new TemplateRenderer)->render($draft),
            'errors' => $this->validator()->validate($draft),
            'capabilities' => $capabilities,
            'features' => Feature::cases(),
            'prefilled' => $this->prefilledVariables(),
            'customVariableReason' => $this->customVariableReason(),
            'currentStep' => $step,
            'steps' => Step::ordered(),
            'stepErrors' => $this->stepErrors($step),
        ]);
    }
}
