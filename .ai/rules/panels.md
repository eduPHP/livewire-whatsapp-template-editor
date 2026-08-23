---
paths:
  - 'src/Livewire/Panels/**'
---

# Panels

## Template variables are named and inserted, never typed as raw braces
"+ Variable" appends `{{variavel}}` (then `variavel_2`, …) rather than a numbered `{{1}}`. Names survive editing: inserting a variable ahead of `{{2}}` renumbers everything after it and silently repoints every example, while a name means the same thing wherever it moves.

Renaming goes through `Parameter::normaliseName()`, which folds accents and coerces to Meta's `^[a-z_]+$` AS THE OPERATOR TYPES — `João Silva` → `joao_silva`. Validating instead would surface the error at submission, by which point the message has been written around the name. A rename onto an existing name is refused, not merged: merging would drop an example.

`DraftState::resolveParameterFormat()` declares `parameter_format: NAMED` whenever the body uses named variables, because Meta refuses a template carrying `body_text_named_params` without it. Positional deliberately stays null — it is Meta's default, and emitting it would add a key to every round-tripped fixture that arrived without one.

Examples are derived from the text, never a hand-managed list, and removing a variable removes its example with it.

## Renumbering variables needs two passes through placeholders
`BodyPanel::renumber()` rewrites every variable via a `\0{index}\0` placeholder before writing the final names. A single pass lets a rename find and clobber a name an earlier rename just wrote — `{{1}}`→`{{variavel}}` then `{{2}}`→`{{variavel_2}}` will re-match text the first pass produced.

The examples map is rebuilt alongside the text in the same loop, so switching format never costs the operator the samples they typed.

`useNumberedVariables()` refuses outright when any variable is pre-filled: a pre-filled name is the contract with the sending code, which substitutes by name, so numbering it leaves a placeholder nothing fills.
