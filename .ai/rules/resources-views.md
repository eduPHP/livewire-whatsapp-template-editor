---
paths:
  - 'resources/views/**'
---

# Resources Views

## Blade cannot build a literal {{name}} inline
`{{ '{{'.$key.'}}' }}` compiles to broken PHP — Blade's echo matcher stops at the first `}}` inside the expression, producing `<?php echo e('{{'.$key.'); ?>' }}`. Same trap for a literal `{{1}}` written inside an attribute value.

Two working forms:
- In markup: `@php echo e(sprintf('{{%s}}', $key)) @endphp`
- As an attribute: pass the literal from the component's `render()` (e.g. `'variableExample' => '{{1}}'`) and bind it with `:placeholder="'https://…/'.$variableExample"`.

Braces inside a PHP string in a `:attr` expression (e.g. `:placeholder="__('Hi {{1}}')"`) compile fine — it is only the `{{ }}` echo syntax that breaks.

## Never write an inline @if inside a Blade component tag
`<x-foo @if ($c) aria-pressed="true" @endif>` compiles to broken PHP — "syntax error, unexpected token endif". Blade's component compiler parses the tag's attributes itself and does not run directive compilation inside them, so the `@endif` escapes the generated `renderComponent()` call.

Bind the whole value instead: `:aria-pressed="$c ? 'true' : 'false'"`, or for a plain HTML element `aria-current="{{ $c ? 'step' : 'false' }}"`.

This is a sibling of the literal-braces trap already recorded here: both are cases where an expression that reads fine in Blade fails only at compile time, and only inside a component tag.

## A button whose work belongs to the host tracks its own state in Alpine
The submit button dispatches `template-submit` and the HOST calls Meta. Livewire's automatic disable-while-in-flight only covers controls that STARTED the request, so this button is invisible to it — several silent seconds during which a second click submits the template twice.

`form.button`'s `loading-when` therefore takes an ALPINE EXPRESSION, not a PHP boolean: the flag is raised by the same click that starts the work, and no server render stands between the two. Both label and spinner are rendered with Alpine choosing, so the plain label still survives without JS.

The flag is lowered ONLY by `template-submit-settled`, which the host emits on every exit — success, refusal, and the payload it declined to send. The editor cannot lower it itself: on the paths where the host answers without re-rendering anything here, a button waiting for a re-render spins forever.

The listener sits on the editor root, not `.window` — the host addresses the event with `->to('wa-template-editor')`, and Livewire delivers those straight to the component's element without bubbling.

## The submit button is opt-out, and the host that turns it off must own the action
`TemplateEditor::$submit` defaults to TRUE — a host embedding the editor bare has nowhere else to put it, and a wizard whose last step offers no way out is the worse failure. A host with its own dialog footer passes `:submit="false"`.

This is not cosmetic. The editor's button hands off through `$dispatch('template-submit')` and raises an Alpine flag that ONLY `template-submit-settled` lowers, so a host that draws the button and never answers the event gets a button reading "Submitting…" forever — which is exactly what happened when this package was first embedded. Either wire both halves of the contract or turn the button off; drawing it beside a second primary button that does the same job is neither.
