<?php

namespace WaTemplates\Livewire\Panels;

use Illuminate\Contracts\View\View;
use WaTemplates\Draft\Parameter;
use WaTemplates\Validation\TemplateValidator;

/**
 * The message text, its named variables, and one example per variable.
 *
 * Variables are inserted by name rather than typed as raw `{{…}}`, and the
 * example rows are derived from the text rather than managed by hand: Meta
 * wants exactly one example per distinct variable, so a separately-managed list
 * would only be a way to get it wrong.
 */
class BodyPanel extends Panel
{
    public string $text = '';

    /** @var array<string,string> */
    public array $examples = [];

    /**
     * Variables the host fills at send time, as `name => sample`.
     *
     * Passed down from the orchestrator as a plain array rather than as a
     * `VariableSource`: Livewire serializes public properties to the browser
     * and back, and an interface-holding object does not survive that trip.
     *
     * @var array<string,string>
     */
    public array $prefilled = [];

    /**
     * @param  array<string,mixed>  $values
     * @param  array<string,list<string>>  $errors
     */
    /**
     * @param  array<string,mixed>  $values
     * @param  array<string,list<string>>  $errors
     * @param  array<string,string>  $prefilled
     */
    public function mount(array $values = [], array $errors = [], array $prefilled = []): void
    {
        $this->text = (string) ($values['text'] ?? '');
        $this->examples = array_map('strval', $values['examples'] ?? []);
        $this->panelErrors = $errors;
        $this->prefilled = $prefilled;
    }

    /**
     * Whether `$name` is one the host fills itself.
     */
    public function isPrefilled(string $name): bool
    {
        return array_key_exists($name, $this->prefilled);
    }

    /**
     * The pre-filled variables not yet used, so the picker stops offering one
     * that is already in the text.
     *
     * @return array<string,string>
     */
    public function availablePrefilled(): array
    {
        return array_diff_key($this->prefilled, array_flip(Parameter::keysIn($this->text)));
    }

    protected function slice(): string
    {
        return 'body';
    }

    protected function values(): array
    {
        /**
         * Drop examples whose parameter no longer appears in the text. Meta
         * rejects a template carrying an example for a parameter that is not
         * there, and an operator who deletes `{{2}}` should not have to know
         * that.
         */
        $keys = Parameter::keysIn($this->text);

        return [
            'text' => $this->text,
            'examples' => array_intersect_key($this->examples, array_flip($keys)),
        ];
    }

    /**
     * @return list<string>
     */
    public function parameterKeys(): array
    {
        return Parameter::keysIn($this->text);
    }

    /**
     * Append a new named variable to the text.
     *
     * Named rather than numbered because a name survives editing: inserting a
     * variable ahead of `{{2}}` renumbers everything after it and silently
     * repoints every example, while `{{nome}}` means the same thing wherever it
     * moves. Meta accepts either, and `parameter_format` records which.
     */
    public function addVariable(): void
    {
        $name = $this->uniqueVariableName();

        $this->text = rtrim($this->text).' {{'.$name.'}}';
        $this->examples[$name] = '';

        $this->publish();
    }

    /**
     * Insert a variable the host already knows how to fill.
     *
     * Its example is taken from the host's own sample rather than asked for.
     * Meta still requires one on the create request — a template whose
     * variables carry no example is rejected — but the host knows what this
     * variable carries, so asking the operator would be asking a question the
     * app can already answer.
     */
    public function addPrefilledVariable(string $name): void
    {
        if (! $this->isPrefilled($name) || in_array($name, Parameter::keysIn($this->text), true)) {
            return;
        }

        $this->text = rtrim($this->text).' {{'.$name.'}}';
        $this->examples[$name] = $this->prefilled[$name];

        $this->publish();
    }

    /**
     * Rename a variable everywhere it appears, keeping its example with it.
     *
     * A rename that collided with an existing name would merge two variables
     * into one and quietly drop an example, so a taken name is refused rather
     * than resolved.
     */
    public function renameVariable(string $from, string $to): void
    {
        /**
         * A pre-filled variable's name IS the contract with the sending code,
         * which substitutes by name. Renaming `{{nome_contato}}` to anything
         * else leaves a variable nothing fills, and the template would go to
         * review carrying a placeholder that arrives empty on every send.
         */
        if ($this->isPrefilled($from)) {
            return;
        }

        $to = Parameter::normaliseName($to);

        if ($to === '' || $from === $to) {
            return;
        }

        if (in_array($to, Parameter::keysIn($this->text), true)) {
            $this->addError('rename.'.$from, __('That name is already used.'));

            return;
        }

        // Taking a reserved name would silently hand this variable to the
        // sending code, which fills by name and would overwrite whatever the
        // operator meant it to carry.
        if ($this->isPrefilled($to)) {
            $this->addError('rename.'.$from, __('That name is reserved for a value the app fills automatically.'));

            return;
        }

        $this->resetErrorBag('rename.'.$from);

        $this->text = str_replace('{{'.$from.'}}', '{{'.$to.'}}', $this->text);

        $this->examples = self::renameKey($this->examples, $from, $to);

        $this->publish();
    }

    /**
     * Remove a variable and its example from the text.
     */
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
     * `variavel`, then `variavel_2`, and so on.
     */
    private function uniqueVariableName(): string
    {
        $taken = Parameter::keysIn($this->text);
        $base = 'variavel';

        if (! in_array($base, $taken, true)) {
            return $base;
        }

        $suffix = 2;

        while (in_array($base.'_'.$suffix, $taken, true)) {
            $suffix++;
        }

        return $base.'_'.$suffix;
    }

    /**
     * Rename a key in place, so the examples keep the text's own order.
     *
     * @param  array<string,string>  $examples
     * @return array<string,string>
     */
    private static function renameKey(array $examples, string $from, string $to): array
    {
        $renamed = [];

        foreach ($examples as $key => $value) {
            $renamed[$key === $from ? $to : $key] = $value;
        }

        return $renamed;
    }

    /**
     * Whether the text is written with named variables rather than numbered.
     *
     * A body with no variables at all counts as named: that is the format the
     * "+ Variable" button will produce, so the switch shows what the operator
     * would get rather than a default they never chose.
     */
    public function usesNamedVariables(): bool
    {
        $keys = $this->parameterKeys();

        if ($keys === []) {
            return true;
        }

        return ! ctype_digit($keys[0]);
    }

    /**
     * Rewrite the text's variables as `{{1}}`, `{{2}}`, … in the order they
     * appear.
     *
     * The examples follow the rename, so switching format never costs the
     * operator the samples they typed. A pre-filled variable blocks the switch
     * entirely: its name is the contract with the sending code, and numbering
     * it would leave a placeholder nothing fills.
     */
    public function useNumberedVariables(): void
    {
        $keys = $this->parameterKeys();

        if ($keys === [] || $this->usesNamedVariables() === false) {
            return;
        }

        foreach ($keys as $key) {
            if ($this->isPrefilled($key)) {
                $this->addError('format', __('This message uses a variable the app fills by name, which cannot be numbered.'));

                return;
            }
        }

        $this->resetErrorBag('format');
        $this->renumber($keys, fn (int $position): string => (string) $position);
    }

    /**
     * Rewrite numbered variables back to names.
     *
     * There is no original name to restore — `{{1}}` never carried one — so
     * they become `variavel`, `variavel_2`, …, which is what "+ Variable" would
     * have produced. The operator renames from there.
     */
    public function useNamedVariables(): void
    {
        $keys = $this->parameterKeys();

        if ($keys === [] || $this->usesNamedVariables()) {
            return;
        }

        $this->resetErrorBag('format');
        $this->renumber($keys, fn (int $position): string => $position === 1 ? 'variavel' : 'variavel_'.$position);
    }

    /**
     * Rewrite every variable to a new name derived from its position.
     *
     * Done in two passes through placeholders that cannot collide with either
     * vocabulary: renaming `{{1}}`→`{{variavel}}` in one pass would let a later
     * rename find and clobber the name an earlier one just wrote.
     *
     * @param  list<string>  $keys
     * @param  callable(int): string  $name
     */
    private function renumber(array $keys, callable $name): void
    {
        $renamed = [];
        $examples = [];

        foreach (array_values($keys) as $index => $key) {
            $to = $name($index + 1);
            $placeholder = "\0{$index}\0";

            $this->text = str_replace('{{'.$key.'}}', $placeholder, $this->text);
            $renamed[$placeholder] = '{{'.$to.'}}';
            $examples[$to] = $this->examples[$key] ?? '';
        }

        $this->text = str_replace(array_keys($renamed), array_values($renamed), $this->text);
        $this->examples = $examples;

        $this->publish();
    }

    public function render(): View
    {
        return view('wa-templates::livewire.panels.body-panel', [
            'keys' => $this->parameterKeys(),
            'available' => $this->availablePrefilled(),
            'max' => TemplateValidator::BODY_MAX,
            'named' => $this->usesNamedVariables(),
            /** Blade cannot assemble a literal `{{…}}` inline; see .ai/rules. */
            'variableSyntax' => '{{nome}}',
            'numberedExample' => '{{1}}',
        ]);
    }
}
