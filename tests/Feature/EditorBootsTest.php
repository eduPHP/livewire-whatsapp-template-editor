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

it('shows the submit button working while the host completes the submission', function () {
    // The editor does not submit: it dispatches `template-submit` and the host
    // calls Meta. That means Livewire's disable-while-in-flight never covers
    // this button — the request it starts belongs to another component — so the
    // button tracks its own state in Alpine, raised on the click.
    //
    // Without it the operator gets several silent seconds and clicks again,
    // submitting the template twice.
    $rendered = Livewire::test(TemplateEditor::class)
        ->set('step', 'framing')
        ->html();

    expect($rendered)->toContain("submitting = true; \$dispatch('template-submit')");

    // Both labels ship and Alpine picks, so a button mid-submission never still
    // reads "Submit for approval" — the text that invites the second click.
    expect($rendered)->toContain('Submit for approval');
    expect($rendered)->toContain('Submitting…');

    // Nothing but the host's reply lowers the flag. It is addressed to this
    // component, so the listener is on the root rather than `.window`.
    expect($rendered)->toContain('x-on:template-submit-settled="submitting = false"');
    expect($rendered)->toContain('x-bind:disabled="submitting"');
});

it('lets a host with its own footer turn the submit button off', function () {
    // Two primary buttons on one screen is not a redundancy — it is a question
    // about which one the operator is answering. A host embedding the editor in
    // its own dialog already draws a footer, so it says so and keeps one.
    $rendered = Livewire::test(TemplateEditor::class, ['submit' => false])
        ->set('step', 'framing')
        ->html();

    expect($rendered)->not->toContain("\$dispatch('template-submit')");
    expect($rendered)->not->toContain('Submit for approval');

    // The step itself is untouched: only the button goes.
    expect($rendered)->toContain('Back');
});

it('draws the submit button when the host says nothing', function () {
    // The default is on. A host that embeds the editor bare has nowhere else to
    // put it, and a wizard ending on a step with no way out is the worse
    // failure of the two.
    $rendered = Livewire::test(TemplateEditor::class)
        ->set('step', 'framing')
        ->html();

    expect($rendered)->toContain('Submit for approval');
});
