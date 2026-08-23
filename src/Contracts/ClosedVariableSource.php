<?php

namespace WaTemplates\Contracts;

/**
 * A `VariableSource` whose set is the ONLY set — no invented variables.
 *
 * `VariableSource` alone is additive: it makes variables pickable but cannot
 * stop an operator inventing `{{preco}}`, because the body is a free textarea
 * and the editor owns it. A host that substitutes strictly by name — every
 * value comes from a known column, nothing is looked up at send time — needs to
 * say so, otherwise a template goes to review carrying a placeholder that
 * arrives empty on every send.
 *
 * Implementing this marker declares exactly that. The editor then hides both
 * custom-variable affordances with a stated reason, refuses `addVariable()`,
 * and `TemplateValidator` rejects any name outside the set.
 *
 * It is a separate interface rather than a method on `VariableSource` so that
 * declaring variables never forces a host to answer a question it does not
 * have: an open set stays exactly what it was.
 */
interface ClosedVariableSource extends VariableSource {}
