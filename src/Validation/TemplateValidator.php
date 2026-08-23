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
            $this->fail('name', 'A template name is required.');

            return;
        }

        if (mb_strlen($draft->name) > self::NAME_MAX) {
            $this->fail('name', 'A template name may be at most '.self::NAME_MAX.' characters.');
        }

        if (preg_match('/^[a-z0-9_]+$/', $draft->name) !== 1) {
            $this->fail('name', 'A template name may contain only lowercase letters, numbers and underscores.');
        }
    }

    private function validateBody(TemplateDraft $draft): void
    {
        $max = $draft->limitedTimeOffer !== null ? self::LTO_BODY_MAX : self::BODY_MAX;

        if (trim($draft->body->text) === '') {
            $this->fail('body.text', 'The body text is required.');
        }

        if (mb_strlen($draft->body->text) > $max) {
            $this->fail('body.text', 'The body may be at most '.$max.' characters.');
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
            $this->fail('header.format', 'A location header is available to utility and marketing templates only.');
        }

        if ($header->format->isMedia()) {
            if (($header->handle ?? '') === '') {
                $this->fail('header.handle', 'A media header needs an uploaded asset handle.');
            }

            return;
        }

        if ($header->format !== HeaderFormat::Text) {
            return;
        }

        if (trim($header->text) === '') {
            $this->fail('header.text', 'A text header needs text.');
        }

        if (mb_strlen($header->text) > self::HEADER_TEXT_MAX) {
            $this->fail('header.text', 'A header may be at most '.self::HEADER_TEXT_MAX.' characters.');
        }

        if (count(Parameter::keysIn($header->text)) > 1) {
            $this->fail('header.text', 'A text header supports at most one parameter.');
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
            $this->fail('footer.text', 'A limited-time offer template cannot have a footer.');
        }

        if (mb_strlen($footer->text) > self::FOOTER_MAX) {
            $this->fail('footer.text', 'A footer may be at most '.self::FOOTER_MAX.' characters.');
        }

        /**
         * `components.md` is explicit that the footer supports no parameters at
         * all. `utility.md` says variables are supported; it is wrong, and a
         * template built on its word is rejected at review.
         */
        if (Parameter::keysIn($footer->text) !== []) {
            $this->fail('footer.text', 'A footer cannot contain parameters.');
        }
    }

    private function validateButtons(TemplateDraft $draft): void
    {
        $buttons = $draft->buttons->buttons;

        if ($buttons === []) {
            return;
        }

        if (count($buttons) > self::BUTTONS_MAX) {
            $this->fail('buttons', 'A template may have at most '.self::BUTTONS_MAX.' buttons.');
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

        $limits = [
            ButtonType::QuickReply->value => [self::QUICK_REPLY_MAX, 'quick reply'],
            ButtonType::Url->value => [self::URL_MAX, 'URL'],
            ButtonType::CopyCode->value => [1, 'copy code'],
            ButtonType::PhoneNumber->value => [1, 'phone number'],
            ButtonType::Mpm->value => [1, 'product list'],
            ButtonType::Spm->value => [1, 'product'],
        ];

        foreach ($limits as $type => [$limit, $label]) {
            if (($counts[$type] ?? 0) > $limit) {
                $this->fail($path, "A template may have at most {$limit} {$label} button".($limit === 1 ? '' : 's').'.');
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
            $this->fail($path, 'Quick reply buttons must be grouped together, not interleaved with other button types.');
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
                $this->fail($path.'.phone_number', 'A phone number button needs a number.');
            }

            if (mb_strlen($button->phoneNumber) > self::PHONE_NUMBER_MAX) {
                $this->fail($path.'.phone_number', 'A phone number may be at most '.self::PHONE_NUMBER_MAX.' characters.');
            }

            return;
        }

        if ($button instanceof CopyCode) {
            if ($button->example === '') {
                $this->fail($path.'.example', 'A copy code button needs an example code.');
            }

            if (mb_strlen($button->example) > self::COPY_CODE_MAX) {
                $this->fail($path.'.example', 'A copy code may be at most '.self::COPY_CODE_MAX.' characters.');
            }

            return;
        }

        $this->validateLabel($path, $button->label(), self::BUTTON_LABEL_MAX);
    }

    private function validateLabel(string $path, string $label, int $max): void
    {
        if (trim($label) === '') {
            $this->fail($path.'.text', 'A button needs a label.');

            return;
        }

        if (mb_strlen($label) > $max) {
            $this->fail($path.'.text', "A button label may be at most {$max} characters.");
        }
    }

    private function validateUrl(string $path, Url $button): void
    {
        if ($button->url === '') {
            $this->fail($path.'.url', 'A URL button needs a URL.');

            return;
        }

        if (mb_strlen($button->url) > self::URL_LENGTH_MAX) {
            $this->fail($path.'.url', 'A URL may be at most '.self::URL_LENGTH_MAX.' characters.');
        }

        $variables = Parameter::keysIn($button->url);

        if ($variables === []) {
            return;
        }

        if (count($variables) > 1) {
            $this->fail($path.'.url', 'A URL button supports at most one variable.');
        }

        /**
         * The variable may only be appended to the end of the URL. Anywhere
         * else and the template is rejected at review, so it is worth catching
         * here where the operator can still see what they typed.
         */
        if (preg_match('/\{\{\s*[A-Za-z0-9_]+\s*\}\}$/', $button->url) !== 1) {
            $this->fail($path.'.url', 'A URL variable may only be appended to the end of the URL.');
        }

        if (($button->example ?? '') === '') {
            $this->fail($path.'.example', 'A URL with a variable needs an example value.');
        }
    }

    private function validateCarousel(TemplateDraft $draft): void
    {
        $carousel = $draft->carousel;

        if ($carousel === null) {
            return;
        }

        if ($draft->category !== Category::Marketing) {
            $this->fail('carousel', 'Carousel templates are marketing-category only.');
        }

        if ($carousel->cards === []) {
            $this->fail('carousel', 'A carousel needs at least one card.');

            return;
        }

        if (count($carousel->cards) > self::CAROUSEL_CARDS_MAX) {
            $this->fail('carousel', 'A carousel may have at most '.self::CAROUSEL_CARDS_MAX.' cards.');
        }

        if (! $carousel->isUniform()) {
            $this->fail('carousel', 'Every card in a carousel must have the same components.');
        }

        foreach ($carousel->cards as $index => $card) {
            $this->validateCard("carousel.cards.{$index}", $card);
        }
    }

    private function validateCard(string $path, Card $card): void
    {
        $format = $card->header->format;

        if (! in_array($format, [HeaderFormat::Image, HeaderFormat::Video, HeaderFormat::Product], true)) {
            $this->fail($path.'.header.format', 'A carousel card header must be an image, a video, or a catalog product.');
        }

        if ($format->isMedia() && ($card->header->handle ?? '') === '') {
            $this->fail($path.'.header.handle', 'A media card needs an uploaded asset handle.');
        }

        $buttons = $card->buttons->buttons;

        if (count($buttons) > 2) {
            $this->fail($path.'.buttons', 'A carousel card may have at most 2 buttons.');
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
            $this->fail('limited_time_offer', 'Limited-time offer templates are marketing-category only.');
        }

        if (trim($offer->text) === '') {
            $this->fail('limited_time_offer.text', 'A limited-time offer needs heading text.');
        }

        if (mb_strlen($offer->text) > self::LTO_HEADING_MAX) {
            $this->fail('limited_time_offer.text', 'The offer heading may be at most '.self::LTO_HEADING_MAX.' characters.');
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
            $this->fail($path.'.text', 'A template cannot mix numbered and named parameters.');
        }

        if (Parameter::formatOf($keys[0]) === ParameterFormat::Positional) {
            $numbers = array_map('intval', $keys);
            sort($numbers);

            if ($numbers !== range(1, count($numbers))) {
                $this->fail($path.'.text', 'Numbered parameters must run in sequence from {{1}}.');
            }
        } else {
            foreach ($keys as $key) {
                if (preg_match('/^[a-z_]+$/', $key) !== 1) {
                    $this->fail($path.'.text', "The parameter {{{$key}}} may contain only lowercase letters and underscores.");
                }
            }
        }

        foreach ($keys as $key) {
            if (($examples[$key] ?? '') === '') {
                $this->fail($path.'.examples.'.$key, "The parameter {{{$key}}} needs an example value.");
            }
        }
    }
}
