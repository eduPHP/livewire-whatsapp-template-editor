<?php

use Livewire\Livewire;
use WaTemplates\Livewire\Panels\BodyPanel;

it('labels the picker "Insert variable" when the set is closed', function () {
    // Under a closed set the button can only insert an existing variable. A
    // "+" read as "create", so operators never opened it to find the list.
    Livewire::test(BodyPanel::class, [
        'prefilled' => ['cliente' => 'Ana'],
        'customVariableReason' => 'Only the offered variables can be used.',
    ])
        ->assertSee('Insert variable')
        ->assertDontSee('+ Variable');
});

it('keeps "+ Variable" while the operator may invent one', function () {
    Livewire::test(BodyPanel::class, ['prefilled' => ['cliente' => 'Ana']])
        ->assertSee('+ Variable')
        ->assertDontSee('Insert variable');
});

it('shows a variable the host fills in the placeholder', function () {
    // The placeholder is the first example an operator copies; `{{nome}}` was
    // refused by a host whose set calls it `cliente`.
    Livewire::test(BodyPanel::class, [
        'prefilled' => ['cliente' => 'Ana', 'data' => '14/03'],
        'customVariableReason' => 'Only the offered variables can be used.',
    ])
        ->assertSee('placeholder="Olá, {{cliente}}"', escape: false)
        ->assertDontSee('{{nome}}', escape: false);
});

it('falls back to a generic placeholder when the host declares no variables', function () {
    Livewire::test(BodyPanel::class)
        ->assertSee('placeholder="Olá, {{nome}}"', escape: false);
});
