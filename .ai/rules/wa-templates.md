---
paths:
  - '**'
---

# Wa Templates

## Template payloads round-trip through Dialect, not by normalising
`parse(build(x)) === x` is a test obligation for the 7 Meta worked examples in `tests/Fixtures/`. Meta is inconsistent with its own vocabulary in ways that carry no meaning but do change bytes, so `Draft\Dialect` remembers the incidental shape and writes it back:

- Case: a listing returns `"type": "BODY"`, every documented create request writes `"body"`. Both are accepted on write.
- Nesting: `example.body_text` is documented flat (`["Pablo"]`) but every worked example — and everything Meta returns — double-wraps it (`[["Pablo"]]`). `header_text` is never double-wrapped.

Do NOT "fix" this by normalising to one form: a template read from `CloudTemplateSync` and written back would then differ from what Meta holds. A draft built from scratch defaults to lowercase + nested, the form the docs use.

Also: `components.md` is authoritative where the per-category pages disagree. It says the FOOTER supports no parameters; `utility.md` says variables are supported and is wrong.
