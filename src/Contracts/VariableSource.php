<?php

namespace WaTemplates\Contracts;

/**
 * Variables the host already knows how to fill at send time.
 *
 * A salon sending an appointment reminder does not invent `{{nome_contato}}` —
 * the app holds the contact's name and substitutes it on every send. Offering
 * those variables by name means an operator picks one rather than inventing a
 * spelling the sending code will never match.
 *
 * Optional, like the other capabilities. Without it the editor offers only
 * custom variables, which is the behaviour it had before.
 */
interface VariableSource
{
    /**
     * @return list<PrefilledVariable>
     */
    public function variables(): array;
}
