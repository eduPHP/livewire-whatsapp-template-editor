---
paths:
  - 'src/Livewire/**'
---

# Src Livewire

## Capabilities resolve from the container, never from public properties
Livewire serializes public properties to the browser and back on every request, so `CatalogSource`/`MediaUploader` cannot be held on one — an interface-holding object does not survive the trip.

`TemplateEditor::capabilities()` resolves both from the container per request (`app()->bound(...) ? app(...) : null`); an unbound interface simply IS a capability this installation lacks, which is what `Capabilities` exists to express. Panels receive only the resulting reason STRING (`$mediaReason`, `$catalogReason`), never the object.

The draft itself has the same constraint, which is why `Livewire\Support\DraftState` exists: it converts `TemplateDraft` (enums, nullable objects, polymorphic buttons) to a flat JSON-safe array and back. It is not a second payload format — `TemplateDraft` remains the only thing that speaks Meta's vocabulary.

## Never name a Livewire listener parameter $component
Livewire spreads a dispatched event's params into the container's method invoker (`SupportEvents.php:39` → `wrap($component)->$method(...$params)`). Params arrive from the browser as a JSON OBJECT, so string keys become named arguments — and a parameter named `$component` is then treated as a dependency to resolve rather than a value to bind:

    BindingResolutionException: Unable to resolve dependency
    [Parameter #0 [ <required> string $component ]] in class ...

The trap is that this only fires on a real browser request. `Livewire::test()->dispatch(component: ...)` passed, so the editor shipped broken and the failure only appeared in laravel.log.

`TemplateEditor` therefore uses `$slice` on `componentUpdated`/`componentRemoved`/`addComponent`, and `Panel::publish()` dispatches `slice:` to match. Rename both sides together — a mismatch fails the same way.

## The step gate is scoped to one step's error paths, never the whole result
`TemplateEditor::continue()` blocks on `stepErrors($this->step())`, which only collects errors under that step's `Step::errorPrefixes()`. Gating on the full `ValidationResult` would deadlock the wizard: a new template's body is empty and therefore invalid the moment the editor opens, so step 1 would refuse to let the operator reach the body field that would fix it.

`goToStep()` is deliberately ungated — the numbered header is clickable and every step is reachable, including ones ahead. The wizard orders the questions; it does not lock them.

Also: the Livewire component has a public `string $step`, so `render()` must pass the enum as `currentStep`. A view variable named `step` is silently shadowed by the property and every `$step->method()` call fails with "Call to a member function on string".
