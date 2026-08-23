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

    /**
     * Whether to draw the device shell around the bubble.
     *
     * The editor sets this false: beside a form the phone is decoration
     * competing with the fields for width, and the operator is reading the
     * message, not judging the hardware. A connection screen showing an
     * approved template keeps it — there the phone is the whole point.
     *
     * Chrome-less also drops the keyboard toggle and the reset control, which
     * are affordances for the mocked conversation the shell hosts.
     */
    public bool $chrome = true;

    public function mount(
        array $template = [],
        ?string $contactName = null,
        bool $keyboard = false,
        bool $chrome = true,
    ): void {
        $this->template = $template;
        $this->contactName = $contactName ?? $this->contactName;
        $this->keyboard = $keyboard;
        $this->chrome = $chrome;
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
