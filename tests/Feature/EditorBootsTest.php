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

it('opens a new template in the language the host asked for', function () {
    // A host serving one market should not make every operator change the
    // language before typing anything. `en_US` stays the default only when the
    // host says nothing.
    Livewire::test(TemplateEditor::class, ['language' => 'pt_BR'])
        ->assertSet('state.meta.language', 'pt_BR');

    Livewire::test(TemplateEditor::class)
        ->assertSet('state.meta.language', 'en_US');
});

it('keeps an imported template in its own language', function () {
    // The prop is a starting value for a NEW draft, not an override. Applying
    // it to a parsed template would silently re-language something Meta has
    // already approved under its original code.
    $template = waTemplateFixture('order_confirmation');

    expect($template['language'])->not->toBe('pt_BR');

    Livewire::test(TemplateEditor::class, ['template' => $template, 'language' => 'pt_BR'])
        ->assertSet('state.meta.language', $template['language']);
});

it('carries a language code the picker does not list', function () {
    // Meta accepts ~80 codes and the picker lists eleven. A host passing an
    // unlisted one must get it verbatim rather than a silent reset — the view
    // renders it as its own option.
    Livewire::test(TemplateEditor::class, ['language' => 'ja'])
        ->assertSet('state.meta.language', 'ja');
});
