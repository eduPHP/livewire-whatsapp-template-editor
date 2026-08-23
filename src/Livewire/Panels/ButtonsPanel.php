<?php

namespace WaTemplates\Livewire\Panels;

use Illuminate\Contracts\View\View;
use WaTemplates\Enums\ButtonType;
use WaTemplates\Validation\TemplateValidator;

/**
 * The button repeater, one row per button with its type.
 *
 * Rows can be reordered because order is meaningful: quick replies must be
 * contiguous, so `QR, URL, QR` is rejected while `URL, Phone, QR, QR` is fine.
 * An operator who cannot move a row cannot fix that.
 */
class ButtonsPanel extends Panel
{
    /** @var list<array<string,mixed>> */
    public array $buttons = [];

    public ?string $catalogReason = null;

    /**
     * @param  array<string,mixed>  $values
     * @param  array<string,list<string>>  $errors
     */
    public function mount(array $values = [], array $errors = [], ?string $catalogReason = null): void
    {
        $this->buttons = array_values($values['buttons'] ?? []);
        $this->panelErrors = $errors;
        $this->catalogReason = $catalogReason;
    }

    protected function slice(): string
    {
        return 'buttons';
    }

    protected function values(): array
    {
        return ['buttons' => array_values($this->buttons)];
    }

    public function add(string $type): void
    {
        $this->buttons[] = [
            'type' => $type,
            'text' => match (ButtonType::from($type)) {
                ButtonType::Mpm => 'View items',
                ButtonType::Spm => 'View',
                default => '',
            },
            'url' => '',
            'example' => null,
            'phone_number' => '',
            'payload' => null,
        ];

        $this->publish();
    }

    public function removeButton(int $index): void
    {
        unset($this->buttons[$index]);

        $this->buttons = array_values($this->buttons);

        $this->publish();
    }

    /**
     * Move a row one place up or down.
     */
    public function move(int $index, int $offset): void
    {
        $target = $index + $offset;

        if (! isset($this->buttons[$index], $this->buttons[$target])) {
            return;
        }

        [$this->buttons[$index], $this->buttons[$target]] = [$this->buttons[$target], $this->buttons[$index]];

        $this->publish();
    }

    public function render(): View
    {
        return view('wa-templates::livewire.panels.buttons-panel', [
            /** Blade mangles a literal `{{…}}` in an attribute, so it comes from here. */
            'variableExample' => '{{1}}',
            'types' => [
                ButtonType::QuickReply->value => __('Quick reply'),
                ButtonType::Url->value => __('URL'),
                ButtonType::PhoneNumber->value => __('Phone number'),
                ButtonType::CopyCode->value => __('Copy code'),
                ButtonType::Mpm->value => __('Product list (MPM)'),
            ],
            'labelMax' => TemplateValidator::BUTTON_LABEL_MAX,
            'quickReplyMax' => TemplateValidator::QUICK_REPLY_TEXT_MAX,
            'copyCodeMax' => TemplateValidator::COPY_CODE_MAX,
            'atLimit' => count($this->buttons) >= TemplateValidator::BUTTONS_MAX,
        ]);
    }
}
