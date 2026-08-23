---
paths:
  - 'src/Validation/**'
---

# Validation

## The validator is the only thing that can close the variable set
The body is a free `wire:model.live` textarea, so the editor cannot stop an operator typing `{{preco}}`. Hiding "+ Variable" and no-opping `addVariable()` covers the affordances, but only `TemplateValidator`'s `$allowedParameters` actually makes a set closed — an out-of-set name fails `$valid`, which blocks both the step gate and the submit button. Never treat the UI guards as sufficient.

Errors key on `<path>.examples.<key>`, not `<path>.text`, so they land on the row the editor already renders for that variable.

`TemplateValidator` messages go through `wa_templates_trans()`, never `__()` directly. `__()` resolves `translator` from the container and dies with "Target class [translator] does not exist" when the domain layer runs headless — which the README documents as supported and `tests/Unit/Templates/TemplateValidatorTest.php` exercises. The helper falls back to the English sentence, which is what an untranslated key renders anyway.
