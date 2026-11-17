<?php

use Livewire\Attributes\Reactive;
use Livewire\Livewire;
use WaTemplates\Livewire\Panels\BodyPanel;
use WaTemplates\Livewire\Panels\Panel;

it('takes its errors as a reactive prop so the panel follows the editor', function () {
    // The panels carry static keys and never remount. A plain mount prop is
    // read once, so the errors froze at whatever they were when the step
    // opened: a body typed into an empty step kept "The body text is
    // required." under it for good.
    $property = new ReflectionProperty(Panel::class, 'panelErrors');

    expect($property->getAttributes(Reactive::class))->not->toBeEmpty();
});

it('renders the errors the editor passes down', function () {
    Livewire::test(BodyPanel::class, [
        'values' => ['text' => 'Olá {{nome}}', 'examples' => ['nome' => 'Ana']],
        'panelErrors' => ['body.examples.nome' => ['The variable {{nome}} is not one this app can fill.']],
    ])->assertSee('The variable {{nome}} is not one this app can fill.', escape: false);
});
