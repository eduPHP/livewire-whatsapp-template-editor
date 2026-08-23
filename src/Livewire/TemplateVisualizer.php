<?php

namespace WaTemplates\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use WaTemplates\Draft\TemplateDraft;
use WaTemplates\Livewire\Support\DraftState;
use WaTemplates\Rendering\TemplateRenderer;

/**
 * A read-only preview of a template inside a mock phone.
 *
 * Deliberately usable on its own: given a stored `components` array it renders
 * the bubble, which is what a connection screen wants for an approved template.
 * Inside the editor it is nested and re-rendered as the draft changes.
 */
class TemplateVisualizer extends Component
{
    /**
     * A Meta create request, or the bare `components` array a listing stores.
     *
     * @var array<string,mixed>
     */
    public array $template = [];

    public string $contactName = 'Business';

    public bool $keyboard = false;

    public function mount(array $template = [], ?string $contactName = null, bool $keyboard = false): void
    {
        $this->template = $template;
        $this->contactName = $contactName ?? $this->contactName;
        $this->keyboard = $keyboard;
    }

    public function render(): View
    {
        $draft = $this->template === []
            ? new TemplateDraft
            : DraftState::parse($this->template);

        return view('wa-templates::livewire.template-visualizer', [
            'preview' => (new TemplateRenderer)->render($draft),
        ]);
    }
}
