---
paths:
  - 'src/Contracts/**'
---

# Contracts

## Pre-filled variables carry their own Meta example
`VariableSource` is the third optional capability (alongside `CatalogSource`/`MediaUploader`). It declares variables the HOST fills at send time — contact name, business name — each as a `PrefilledVariable(name, sample)`.

The `sample` is load-bearing, not decoration: Meta rejects a create request whose variables carry no example, so inserting a pre-filled variable writes the host's sample into `examples` immediately. That is what lets the operator submit without typing a sample, and what makes the preview substitute correctly.

Three rules the editor enforces:
- Declaring a variable makes it AVAILABLE, never mandatory. An unused one appears nowhere in the payload.
- A pre-filled variable cannot be renamed — its name is the contract the sending code matches on, so a rename leaves a placeholder nothing fills.
- A custom variable cannot be renamed ONTO a reserved name, which would silently hand it to the sending code.

Bind it in the host: `app()->bind(VariableSource::class, …)`. Unbound means no pre-filled variables and the plain "+ Variable" button, exactly as before.

## Closed variable sets are a marker interface, not a flag
`ClosedVariableSource extends VariableSource` and adds no methods; hosts bind it under the same `VariableSource::class` key and the editor checks `instanceof`. Deliberately NOT an `isClosed(): bool` on `VariableSource` — that would force every existing and future implementor to write `return false;`, a breaking change to a published interface for a question most hosts do not have.

Same idiom as the other capabilities: an unbound interface IS the absent capability. Declaring variables makes them available; only the closed marker turns availability into exclusivity.
