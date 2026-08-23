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
