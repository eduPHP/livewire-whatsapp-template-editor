<?php

namespace WaTemplates\Enums;

/**
 * The editor's wizard steps, in the order an operator walks them.
 *
 * The order is not the order Meta's payload uses, and deliberately so: the
 * payload is positional (header, body, footer, buttons), while this asks for
 * the two things a template cannot exist without — its name and its text —
 * before either of the optional parts. An operator who abandons the flow
 * halfway has still answered the questions that matter.
 */
enum Step: string
{
    case Identification = 'identification';
    case Content = 'content';
    case Buttons = 'buttons';
    case Framing = 'framing';

    /**
     * @return list<self>
     */
    public static function ordered(): array
    {
        return self::cases();
    }

    public function position(): int
    {
        return array_search($this, self::ordered(), true) + 1;
    }

    public function title(): string
    {
        return match ($this) {
            self::Identification => __('Identification'),
            self::Content => __('Content'),
            self::Buttons => __('Buttons'),
            self::Framing => __('Header and footer'),
        };
    }

    /**
     * The one-line gloss under the step title.
     */
    public function summary(): string
    {
        return match ($this) {
            self::Identification => __('name, language and type'),
            self::Content => __('text and variables'),
            self::Buttons => __('optional'),
            self::Framing => __('optional'),
        };
    }

    /**
     * Whether Meta requires anything from this step.
     *
     * Only the first two are required, which is what lets the last two offer a
     * "skip" — stated on the step itself rather than left for the operator to
     * infer from an empty form.
     */
    public function isOptional(): bool
    {
        return match ($this) {
            self::Identification, self::Content => false,
            self::Buttons, self::Framing => true,
        };
    }

    /**
     * Validator error paths this step is answerable for.
     *
     * A step shows its own errors and blocks its own Continue; an error
     * belonging to a later step must not stop the operator reaching it, or the
     * wizard would deadlock on a field the current step does not show.
     *
     * @return list<string>
     */
    public function errorPrefixes(): array
    {
        return match ($this) {
            self::Identification => ['name', 'language', 'category'],
            self::Content => ['body'],
            self::Buttons => ['buttons'],
            self::Framing => ['header', 'footer', 'carousel', 'limited_time_offer'],
        };
    }

    public function next(): ?self
    {
        return self::ordered()[$this->position()] ?? null;
    }

    public function previous(): ?self
    {
        return self::ordered()[$this->position() - 2] ?? null;
    }
}
