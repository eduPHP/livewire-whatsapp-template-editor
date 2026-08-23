<?php

namespace WaTemplates\Livewire\Panels;

use Illuminate\Contracts\View\View;
use WaTemplates\Enums\Category;
use WaTemplates\Enums\ParameterFormat;

/**
 * Name, language, category, and the parameter dialect.
 */
class MetadataPanel extends Panel
{
    public string $name = '';

    public string $language = 'en_US';

    public string $category = 'UTILITY';

    public ?string $parameter_format = null;

    public bool $upper_case = false;

    public bool $nested_body_example = true;

    /**
     * @param  array<string,mixed>  $values
     * @param  array<string,list<string>>  $errors
     */
    public function mount(array $values = [], array $errors = []): void
    {
        $this->name = (string) ($values['name'] ?? '');
        $this->language = (string) ($values['language'] ?? 'en_US');
        $this->category = (string) ($values['category'] ?? 'UTILITY');
        $this->parameter_format = $values['parameter_format'] ?? null;
        $this->upper_case = (bool) ($values['upper_case'] ?? false);
        $this->nested_body_example = (bool) ($values['nested_body_example'] ?? true);
        $this->panelErrors = $errors;
    }

    protected function slice(): string
    {
        return 'meta';
    }

    protected function errorPrefix(): string
    {
        return 'name';
    }

    protected function values(): array
    {
        return [
            'name' => $this->name,
            'language' => $this->language,
            'category' => $this->category,
            'parameter_format' => $this->parameter_format,
            'upper_case' => $this->upper_case,
            'nested_body_example' => $this->nested_body_example,
        ];
    }

    public function render(): View
    {
        return view('wa-templates::livewire.panels.metadata-panel', [
            'categories' => array_combine(
                array_map(fn (Category $case): string => $case->value, Category::cases()),
                array_map(fn (Category $case): string => ucfirst(strtolower($case->name)), Category::cases()),
            ),
            /**
             * What each category means for the operator choosing one. The names
             * alone do not distinguish them, and the choice is consequential:
             * category decides both the price of a send and how strict the
             * review is.
             */
            'categoryHints' => [
                Category::Utility->value => __('confirmations, notices'),
                Category::Marketing->value => __('offers, announcements'),
                Category::Authentication->value => __('verification codes'),
            ],
            'languages' => self::languages(),
            'formats' => [
                ParameterFormat::Positional->value => __('Numbered — {{1}}, {{2}}'),
                ParameterFormat::Named->value => __('Named — {{order_number}}'),
            ],
            /** The name rule is Meta's, not a house style, so it is stated. */
            'nameHint' => __('Lowercase letters, numbers and underscores. Cannot be changed once approved.'),
        ]);
    }

    /**
     * Meta's template language codes, by name.
     *
     * A shortlist rather than the full ~80 Meta accepts: a select of that
     * length is worse to use than a short one covering nearly every template
     * this app will carry. The stored value is Meta's code either way, so a
     * template imported in an unlisted language round-trips untouched — it is
     * only the picker that is short, not the format.
     *
     * @return array<string,string>
     */
    public static function languages(): array
    {
        return [
            'pt_BR' => __('Portuguese (BR)'),
            'pt_PT' => __('Portuguese (PT)'),
            'en' => __('English'),
            'en_US' => __('English (US)'),
            'en_GB' => __('English (UK)'),
            'es' => __('Spanish'),
            'es_AR' => __('Spanish (AR)'),
            'es_MX' => __('Spanish (MX)'),
            'fr' => __('French'),
            'de' => __('German'),
            'it' => __('Italian'),
        ];
    }
}
