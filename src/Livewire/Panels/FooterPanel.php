<?php

namespace WaTemplates\Livewire\Panels;

use Illuminate\Contracts\View\View;
use WaTemplates\Validation\TemplateValidator;

class FooterPanel extends Panel
{
    public string $text = '';

    /**
     * @param  array<string,mixed>  $values
     * @param  array<string,list<string>>  $errors
     */
    public function mount(array $values = [], array $errors = []): void
    {
        $this->text = (string) ($values['text'] ?? '');
        $this->panelErrors = $errors;
    }

    protected function slice(): string
    {
        return 'footer';
    }

    protected function values(): array
    {
        return ['text' => $this->text];
    }

    public function render(): View
    {
        return view('wa-templates::livewire.panels.footer-panel', [
            'max' => TemplateValidator::FOOTER_MAX,
        ]);
    }
}
