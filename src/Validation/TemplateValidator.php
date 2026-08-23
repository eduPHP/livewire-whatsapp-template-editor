<?php

namespace WaTemplates\Validation;

use WaTemplates\Draft\Buttons\Button;
use WaTemplates\Draft\Buttons\CopyCode;
use WaTemplates\Draft\Buttons\PhoneNumber;
use WaTemplates\Draft\Buttons\QuickReply;
use WaTemplates\Draft\Buttons\Url;
use WaTemplates\Draft\Components\Card;
use WaTemplates\Draft\Parameter;
use WaTemplates\Draft\TemplateDraft;
use WaTemplates\Enums\ButtonType;
use WaTemplates\Enums\Category;
use WaTemplates\Enums\HeaderFormat;
use WaTemplates\Enums\ParameterFormat;

/**
 * Every rule Meta enforces at template creation, checked before submission.
 *
 * All limits trace to `docs/templates/components.md`, which is authoritative
 * where the per-category pages disagree with it.
 */
final class TemplateValidator
{
    public const NAME_MAX = 512;

    public const BODY_MAX = 1024;

    public const LTO_BODY_MAX = 600;

    public const LTO_HEADING_MAX = 16;

    public const HEADER_TEXT_MAX = 60;

    public const FOOTER_MAX = 60;

    public const BUTTONS_MAX = 10;

    public const QUICK_REPLY_MAX = 10;

    public const QUICK_REPLY_TEXT_MAX = 25;

    public const URL_MAX = 2;

    public const URL_LENGTH_MAX = 2000;

    public const BUTTON_LABEL_MAX = 25;

    public const COPY_CODE_MAX = 20;

    public const PHONE_NUMBER_MAX = 20;

    public const CAROUSEL_CARDS_MAX = 10;

    /** @var array<string,list<string>> */
    private array $errors = [];

    /**
     * The only variable names allowed, or null when any name is allowed.
     *
     * A host that fills every variable by name (`ClosedVariableSource`) cannot
     * honour one the operator invented, so an out-of-set name is an error here
     * rather than a placeholder discovered empty on the first send. Null — the
     * default — is the open set the validator has always assumed, which keeps
     * the domain layer usable headless with no capability wired up at all.
     *
     * @param  list<string>|null  $allowedParameters
     */
    public function __construct(private readonly ?array $allowedParameters = null) {}

    public function validate(TemplateDraft $draft): ValidationResult
    {
        $this->errors = [];

        $this->validateName($draft);
        $this->validateBody($draft);
        $this->validateHeader($draft);
        $this->validateFooter($draft);
        $this->validateButtons($draft);
        $this->validateCarousel($draft);
        $this->validateLimitedTimeOffer($draft);

        return new ValidationResult($this->errors);
    }

    private function fail(string $path, string $message): void
    {
        $this->errors[$path][] = $message;
    }

    private function validateName(TemplateDraft $draft): void
    {
        if ($draft->name === '') {
            $this->fail('name', wa_templates_trans('A template name is required.'));

            return;
        }

        if (mb_strlen($draft->name) > self::NAME_MAX) {
            $this->fail('name', wa_templates_trans('A template name may be at most :max characters.', ['max' => self::NAME_MAX]));
        }

        if (preg_match('/^[a-z0-9_]+$/', $draft->name) !== 1) {
            $this->fail('name', wa_templates_trans('A template name may contain only lowercase letters, numbers and underscores.'));
        }
    }

    private function validateBody(TemplateDraft $draft): void
    {
        $max = $draft->limitedTimeOffer !== null ? self::LTO_BODY_MAX : self::BODY_MAX;

        if (trim($draft->body->text) === '') {
            $this->fail('body.text', wa_templates_trans('The body text is required.'));
        }

        if (mb_strlen($draft->body->text) > $max) {
            $this->fail('body.text', wa_templates_trans('The body may be at most :max characters.', ['max' => $max]));
        }

        $this->validateParameters('body', $draft->body->text, $draft->body->examples);
    }

    private function validateHeader(TemplateDraft $draft): void
    {
        $header = $draft->header;

        if ($header === null) {
            return;
        }

        if ($header->format === HeaderFormat::Location && $draft->category === Category::Authentication) {
            $this->fail('header.format', wa_templates_trans('A location header is available to utility and marketing templates only.'));
        }

        if ($header->format->isMedia()) {
            if (($header->handle ?? '') === '') {
                $this->fail('header.handle', wa_templates_trans('A media header needs an uploaded asset handle.'));
            }

            return;
        }

        if ($header->format !== HeaderFormat::Text) {
            return;
        }

        if (trim($header->text) === '') {
            $this->fail('header.text', wa_templates_trans('A text header needs text.'));
        }

        if (mb_strlen($header->text) > self::HEADER_TEXT_MAX) {
            $this->fail('header.text', wa_templates_trans('A header may be at most :max characters.', ['max' => self::HEADER_TEXT_MAX]));
        }

        if (count(Parameter::keysIn($header->text)) > 1) {
            $this->fail('header.text', wa_templates_trans('A text header supports at most one parameter.'));
        }

        $this->validateParameters('header', $header->text, $header->examples);
    }

    private function validateFooter(TemplateDraft $draft): void
    {
        $footer = $draft->footer;

        if ($footer === null) {
            return;
        }

        if ($draft->limitedTimeOffer !== null) {
            $this->fail('footer.text', wa_templates_trans('A limited-time offer template cannot have a footer.'));
        }

        if (mb_strlen($footer->text) > self::FOOTER_MAX) {
            $this->fail('footer.text', wa_templates_trans('A footer may be at most :max characters.', ['max' => self::FOOTER_MAX]));
        }

        /**
         * `components.md` is explicit that the footer supports no parameters at
         * all. `utility.md` says variables are supported; it is wrong, and a
         * template built on its word is rejected at review.
         */
        if (Parameter::keysIn($footer->text) !== []) {
            $this->fail('footer.text', wa_templates_trans('A footer cannot contain parameters.'));
        }
    }

    private function validateButtons(TemplateDraft $draft): void
    {
        $buttons = $draft->buttons->buttons;

        if ($buttons === []) {
            return;
        }

        if (count($buttons) > self::BUTTONS_MAX) {
            $this->fail('buttons', wa_templates_trans('A template may have at most :max buttons.', ['max' => self::BUTTONS_MAX]));
        }

        $this->validateButtonCounts('buttons', $buttons);
        $this->validateContiguity('buttons', $buttons);

        foreach ($buttons as $index => $button) {
            $this->validateButton("buttons.{$index}", $button);
        }
    }

    /**
     * @param  list<Button>  $buttons
     */
    private function validateButtonCounts(string $path, array $buttons): void
    {
        $counts = [];

        foreach ($buttons as $button) {
            $counts[$button->type()->value] = ($counts[$button->type()->value] ?? 0) + 1;
        }

        /**
         * Each limit carries a whole sentence rather than a noun interpolated
         * into a shared one. A translator handed "quick reply" alone cannot
         * place it: Portuguese inflects the number and the noun together, and
         * the count moves. Whole sentences cost a few more keys and translate
         * correctly.
         */
        $limits = [
            ButtonType::QuickReply->value => [
                self::QUICK_REPLY_MAX,
                'A template may have at most :max quick reply buttons.',
            ],
            ButtonType::Url->value => [
                self::URL_MAX,
                'A template may have at most :max URL buttons.',
            ],
            ButtonType::CopyCode->value => [1, 'A template may have at most one copy code button.'],
            ButtonType::PhoneNumber->value => [1, 'A template may have at most one phone number button.'],
            ButtonType::Mpm->value => [1, 'A template may have at most one product list button.'],
            ButtonType::Spm->value => [1, 'A template may have at most one product button.'],
        ];

        foreach ($limits as $type => [$limit, $message]) {
            if (($counts[$type] ?? 0) > $limit) {
                $this->fail($path, wa_templates_trans($message, ['max' => $limit]));
            }
        }
    }

    /**
     * Quick replies must sit together — they cannot be interleaved with other
     * button types.
     *
     * `QR, URL, QR` is rejected; `URL, Phone, QR, QR` is fine.
     *
     * @param  list<Button>  $buttons
     */
    private function validateContiguity(string $path, array $buttons): void
    {
        $isQuickReply = array_map(
            fn (Button $button): bool => $button->type() === ButtonType::QuickReply,
            $buttons,
        );

        $runs = 0;
        $previous = false;

        foreach ($isQuickReply as $current) {
            if ($current && ! $previous) {
                $runs++;
            }

            $previous = $current;
        }

        if ($runs > 1) {
            $this->fail($path, wa_templates_trans('Quick reply buttons must be grouped together, not interleaved with other button types.'));
        }
    }

    private function validateButton(string $path, Button $button): void
    {
        if ($button instanceof QuickReply) {
            $this->validateLabel($path, $button->text, self::QUICK_REPLY_TEXT_MAX);

            return;
        }

        if ($button instanceof Url) {
            $this->validateLabel($path, $button->text, self::BUTTON_LABEL_MAX);
            $this->validateUrl($path, $button);

            return;
        }

        if ($button instanceof PhoneNumber) {
            $this->validateLabel($path, $button->text, self::BUTTON_LABEL_MAX);

            if ($button->phoneNumber === '') {
                $this->fail($path.'.phone_number', wa_templates_trans('A phone number button needs a number.'));
            }

            if (mb_strlen($button->phoneNumber) > self::PHONE_NUMBER_MAX) {
                $this->fail($path.'.phone_number', wa_templates_trans('A phone number may be at most :max characters.', ['max' => self::PHONE_NUMBER_MAX]));
            }

            return;
        }

        if ($button instanceof CopyCode) {
            if ($button->example === '') {
                $this->fail($path.'.example', wa_templates_trans('A copy code button needs an example code.'));
            }

            if (mb_strlen($button->example) > self::COPY_CODE_MAX) {
                $this->fail($path.'.example', wa_templates_trans('A copy code may be at most :max characters.', ['max' => self::COPY_CODE_MAX]));
            }

            return;
        }

        $this->validateLabel($path, $button->label(), self::BUTTON_LABEL_MAX);
    }

    private function validateLabel(string $path, string $label, int $max): void
    {
        if (trim($label) === '') {
            $this->fail($path.'.text', wa_templates_trans('A button needs a label.'));

            return;
        }

        if (mb_strlen($label) > $max) {
            $this->fail($path.'.text', wa_templates_trans('A button label may be at most :max characters.', ['max' => $max]));
        }
    }

    private function validateUrl(string $path, Url $button): void
    {
        if ($button->url === '') {
            $this->fail($path.'.url', wa_templates_trans('A URL button needs a URL.'));

            return;
        }

        if (mb_strlen($button->url) > self::URL_LENGTH_MAX) {
            $this->fail($path.'.url', wa_templates_trans('A URL may be at most :max characters.', ['max' => self::URL_LENGTH_MAX]));
        }

        $variables = Parameter::keysIn($button->url);

        if ($variables === []) {
            return;
        }

        if (count($variables) > 1) {
            $this->fail($path.'.url', wa_templates_trans('A URL button supports at most one variable.'));
        }

        /**
         * The variable may only be appended to the end of the URL. Anywhere
         * else and the template is rejected at review, so it is worth catching
         * here where the operator can still see what they typed.
         */
        if (preg_match('/\{\{\s*[A-Za-z0-9_]+\s*\}\}$/', $button->url) !== 1) {
            $this->fail($path.'.url', wa_templates_trans('A URL variable may only be appended to the end of the URL.'));
        }

        if (($button->example ?? '') === '') {
            $this->fail($path.'.example', wa_templates_trans('A URL with a variable needs an example value.'));
        }
    }

    private function validateCarousel(TemplateDraft $draft): void
    {
        $carousel = $draft->carousel;

        if ($carousel === null) {
            return;
        }

        if ($draft->category !== Category::Marketing) {
            $this->fail('carousel', wa_templates_trans('Carousel templates are marketing-category only.'));
        }

        if ($carousel->cards === []) {
            $this->fail('carousel', wa_templates_trans('A carousel needs at least one card.'));

            return;
        }

        if (count($carousel->cards) > self::CAROUSEL_CARDS_MAX) {
            $this->fail('carousel', wa_templates_trans('A carousel may have at most :max cards.', ['max' => self::CAROUSEL_CARDS_MAX]));
        }

        if (! $carousel->isUniform()) {
            $this->fail('carousel', wa_templates_trans('Every card in a carousel must have the same components.'));
        }

        foreach ($carousel->cards as $index => $card) {
            $this->validateCard("carousel.cards.{$index}", $card);
        }
    }

    private function validateCard(string $path, Card $card): void
    {
        $format = $card->header->format;

        if (! in_array($format, [HeaderFormat::Image, HeaderFormat::Video, HeaderFormat::Product], true)) {
            $this->fail($path.'.header.format', wa_templates_trans('A carousel card header must be an image, a video, or a catalog product.'));
        }

        if ($format->isMedia() && ($card->header->handle ?? '') === '') {
            $this->fail($path.'.header.handle', wa_templates_trans('A media card needs an uploaded asset handle.'));
        }

        $buttons = $card->buttons->buttons;

        if (count($buttons) > 2) {
            $this->fail($path.'.buttons', wa_templates_trans('A carousel card may have at most 2 buttons.'));
        }

        foreach ($buttons as $index => $button) {
            $this->validateButton("{$path}.buttons.{$index}", $button);
        }

        if ($card->body !== null) {
            $this->validateParameters($path.'.body', $card->body->text, $card->body->examples);
        }
    }

    private function validateLimitedTimeOffer(TemplateDraft $draft): void
    {
        $offer = $draft->limitedTimeOffer;

        if ($offer === null) {
            return;
        }

        if ($draft->category !== Category::Marketing) {
            $this->fail('limited_time_offer', wa_templates_trans('Limited-time offer templates are marketing-category only.'));
        }

        if (trim($offer->text) === '') {
            $this->fail('limited_time_offer.text', wa_templates_trans('A limited-time offer needs heading text.'));
        }

        if (mb_strlen($offer->text) > self::LTO_HEADING_MAX) {
            $this->fail('limited_time_offer.text', wa_templates_trans('The offer heading may be at most :max characters.', ['max' => self::LTO_HEADING_MAX]));
        }
    }

    /**
     * Parameter naming, sequencing, and example coverage for one text
     * component.
     *
     * @param  array<string,string>  $examples
     */
    private function validateParameters(string $path, string $text, array $examples): void
    {
        $keys = Parameter::keysIn($text);

        if ($keys === []) {
            return;
        }

        $formats = array_unique(array_map(
            fn (string $key): ParameterFormat => Parameter::formatOf($key),
            $keys,
        ), SORT_REGULAR);

        if (count($formats) > 1) {
            $this->fail($path.'.text', wa_templates_trans('A template cannot mix numbered and named parameters.'));
        }

        if (Parameter::formatOf($keys[0]) === ParameterFormat::Positional) {
            $numbers = array_map('intval', $keys);
            sort($numbers);

            if ($numbers !== range(1, count($numbers))) {
                $this->fail($path.'.text', wa_templates_trans('Numbered parameters must run in sequence from {{1}}.'));
            }
        } else {
            foreach ($keys as $key) {
                if (preg_match('/^[a-z_]+$/', $key) !== 1) {
                    $this->fail($path.'.text', wa_templates_trans('The parameter :name may contain only lowercase letters and underscores.', ['name' => '{{'.$key.'}}']));
                }
            }
        }

        $this->validateAgainstClosedSet($path, $keys);

        foreach ($keys as $key) {
            if (($examples[$key] ?? '') === '') {
                $this->fail($path.'.examples.'.$key, wa_templates_trans('The parameter :name needs an example value.', ['name' => '{{'.$key.'}}']));
            }
        }
    }

    /**
     * Refuse a variable the host has no way to fill.
     *
     * Keyed on `<path>.examples.<key>` rather than `<path>.text` so it lands on
     * the same row the editor already renders for that variable — the operator
     * reads it next to the name they typed, not as a message about the whole
     * message.
     *
     * @param  list<string>  $keys
     */
    private function validateAgainstClosedSet(string $path, array $keys): void
    {
        if ($this->allowedParameters === null) {
            return;
        }

        foreach ($keys as $key) {
            if (! in_array($key, $this->allowedParameters, true)) {
                $this->fail(
                    $path.'.examples.'.$key,
                    wa_templates_trans('The variable :name is not one this app can fill. Pick one of the offered variables instead.', ['name' => '{{'.$key.'}}']),
                );
            }
        }
    }
}
