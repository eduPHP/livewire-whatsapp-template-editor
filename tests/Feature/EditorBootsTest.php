<?php

use Livewire\Livewire;
use WaTemplates\Contracts\ClosedVariableSource;
use WaTemplates\Contracts\PrefilledVariable;
use WaTemplates\Contracts\VariableSource;
use WaTemplates\Livewire\TemplateEditor;

it('boots the editor with nothing but the package installed', function () {
    // No host, no capabilities bound: an installation with none of the optional
    // interfaces is the baseline every consumer starts from.
    Livewire::test(TemplateEditor::class)
        ->assertOk()
        ->assertSet('step', 'identification')
        ->assertViewHas('customVariableReason', null);
});

it('closes the variable set when the host binds a closed source', function () {
    app()->bind(VariableSource::class, fn (): VariableSource => new class implements ClosedVariableSource
    {
        public function variables(): array
        {
            return [new PrefilledVariable('nome_contato', 'Ana Souza')];
        }
    });

    Livewire::test(TemplateEditor::class)
        ->assertViewHas('customVariableReason', fn (?string $reason): bool => $reason !== null);
});
