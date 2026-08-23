<?php

use WaTemplates\Tests\TestCase;

/**
 * Feature tests boot the Testbench application; unit tests do not need it.
 *
 * The domain layer (`Draft`, `Validation`, `Rendering`) is plain PHP with no
 * container dependency, so `tests/Unit` runs without an application and stays
 * fast. Only the Livewire components require one.
 */
uses(TestCase::class)->in('Feature');

/**
 * One of Meta's worked examples, as a create-request array.
 *
 * The fixtures are byte-identical to Meta's documentation. Round-tripping them
 * is the package's central obligation — `parse(build(x)) === x` — so they are
 * loaded rather than hand-inlined.
 *
 * @return array<string,mixed>
 */
function waTemplateFixture(string $name): array
{
    return json_decode(
        (string) file_get_contents(__DIR__.'/Fixtures/'.$name.'.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}
