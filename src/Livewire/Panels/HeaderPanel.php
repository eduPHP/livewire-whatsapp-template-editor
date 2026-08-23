<?php

namespace WaTemplates\Livewire\Panels;

use Illuminate\Contracts\View\View;
use Livewire\WithFileUploads;
use WaTemplates\Contracts\MediaUploader;
use WaTemplates\Draft\Parameter;
use WaTemplates\Enums\HeaderFormat;
use WaTemplates\Validation\TemplateValidator;

/**
 * The format switch and whichever fields that format needs.
 *
 * Media formats are gated on a `MediaUploader`: without one there is no way to
 * turn a file into the `header_handle` Meta requires, so those options are
 * disabled with that reason rather than hidden.
 */
class HeaderPanel extends Panel
{
    use WithFileUploads;

    public string $format = 'TEXT';

    public string $text = '';

    /** @var array<string,string> */
    public array $examples = [];

    public ?string $handle = null;

    public mixed $upload = null;

    public ?string $uploadError = null;

    /**
     * Why media formats are unavailable, or null when they are.
     *
     * Passed down from the orchestrator as a plain string rather than as a
     * `Capabilities` object: Livewire serializes public properties to the
     * browser and back, and an interface-holding object does not survive that
     * trip. The uploader itself is resolved from the container when a file
     * actually arrives.
     */
    public ?string $mediaReason = null;

    /**
     * @param  array<string,mixed>  $values
     * @param  array<string,list<string>>  $errors
     */
    public function mount(array $values = [], array $errors = [], ?string $mediaReason = null): void
    {
        $this->mediaReason = $mediaReason;
        $this->format = (string) ($values['format'] ?? 'TEXT');
        $this->text = (string) ($values['text'] ?? '');
        $this->examples = array_map('strval', $values['examples'] ?? []);
        $this->handle = $values['handle'] ?? null;
        $this->panelErrors = $errors;
    }

    protected function slice(): string
    {
        return 'header';
    }

    protected function values(): array
    {
        $keys = Parameter::keysIn($this->text);

        return [
            'format' => $this->format,
            'text' => $this->text,
            'examples' => array_intersect_key($this->examples, array_flip($keys)),
            'handle' => $this->handle,
        ];
    }

    /**
     * Append the header's one allowed variable.
     *
     * A text header supports exactly one, so this is a no-op once it is there
     * rather than a second insert the validator would immediately reject.
     */
    public function addVariable(): void
    {
        if (Parameter::keysIn($this->text) !== []) {
            return;
        }

        $this->text = rtrim($this->text).' {{variavel}}';
        $this->examples['variavel'] = '';

        $this->publish();
    }

    public function renameVariable(string $from, string $to): void
    {
        $to = Parameter::normaliseName($to);

        if ($to === '' || $from === $to) {
            return;
        }

        $this->text = str_replace('{{'.$from.'}}', '{{'.$to.'}}', $this->text);

        $examples = [];

        foreach ($this->examples as $key => $value) {
            $examples[$key === $from ? $to : $key] = $value;
        }

        $this->examples = $examples;

        $this->publish();
    }

    public function removeVariable(string $name): void
    {
        $this->text = trim((string) preg_replace(
            '/\s*\{\{'.preg_quote($name, '/').'\}\}/',
            '',
            $this->text,
        ));

        unset($this->examples[$name]);

        $this->publish();
    }

    /**
     * Upload the chosen file and keep the handle it returns.
     *
     * The handle is not the media id `POST /<PHONE_NUMBER_ID>/media` returns —
     * that id is for sending, this is for template creation, and swapping them
     * produces a template Meta accepts but cannot render.
     */
    public function updatedUpload(): void
    {
        $uploader = app()->bound(MediaUploader::class) ? app(MediaUploader::class) : null;

        if ($uploader === null || $this->upload === null) {
            return;
        }

        $this->uploadError = null;

        try {
            $this->handle = $uploader->uploadForTemplate(
                $this->upload->get(),
                $this->upload->getMimeType() ?? 'application/octet-stream',
                $this->upload->getClientOriginalName(),
            );
        } catch (\Throwable $exception) {
            $this->uploadError = $exception->getMessage();

            return;
        }

        $this->publish();
    }

    public function render(): View
    {
        return view('wa-templates::livewire.panels.header-panel', [
            'formats' => HeaderFormat::cases(),
            'keys' => Parameter::keysIn($this->text),
            'max' => TemplateValidator::HEADER_TEXT_MAX,
            /** Blade cannot assemble a literal `{{…}}` inline; see .ai/rules. */
            'variableSyntax' => '{{nome}}',
        ]);
    }
}
