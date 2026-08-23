<?php

namespace WaTemplates\Livewire\Support;

use WaTemplates\Draft\Buttons\Button;
use WaTemplates\Draft\Buttons\CopyCode;
use WaTemplates\Draft\Buttons\Mpm;
use WaTemplates\Draft\Buttons\PhoneNumber;
use WaTemplates\Draft\Buttons\QuickReply;
use WaTemplates\Draft\Buttons\Spm;
use WaTemplates\Draft\Buttons\Url;
use WaTemplates\Draft\Components\Body;
use WaTemplates\Draft\Components\Buttons;
use WaTemplates\Draft\Components\Card;
use WaTemplates\Draft\Components\Carousel;
use WaTemplates\Draft\Components\Footer;
use WaTemplates\Draft\Components\Header;
use WaTemplates\Draft\Components\LimitedTimeOffer;
use WaTemplates\Draft\Dialect;
use WaTemplates\Draft\Parameter;
use WaTemplates\Draft\TemplateDraft;
use WaTemplates\Enums\ButtonType;
use WaTemplates\Enums\Category;
use WaTemplates\Enums\HeaderFormat;
use WaTemplates\Enums\ParameterFormat;

/**
 * The draft in the flat, JSON-safe shape Livewire round-trips.
 *
 * Livewire serializes public properties to the browser and back on every
 * request, so the domain model — enums, nullable objects, polymorphic buttons —
 * cannot live on a component property directly. This converts in both
 * directions and is the only place that knows how.
 *
 * It is not a second payload format. The state array is an editing convenience;
 * `TemplateDraft` remains the only thing that speaks Meta's vocabulary.
 */
final class DraftState
{
    /**
     * @return array<string,mixed>
     */
    public static function fromDraft(TemplateDraft $draft): array
    {
        $state = [
            'meta' => [
                'name' => $draft->name,
                'language' => $draft->language,
                'category' => $draft->category->value,
                'parameter_format' => $draft->parameterFormat?->value,
                'upper_case' => $draft->dialect->upperCaseKeywords,
                'nested_body_example' => $draft->dialect->nestedBodyExample,
            ],
            'body' => [
                'text' => $draft->body->text,
                'examples' => $draft->body->examples,
            ],
        ];

        if ($draft->header !== null) {
            $state['header'] = [
                'format' => $draft->header->format->value,
                'text' => $draft->header->text,
                'examples' => $draft->header->examples,
                'handle' => $draft->header->handle,
            ];
        }

        if ($draft->footer !== null) {
            $state['footer'] = ['text' => $draft->footer->text];
        }

        if (! $draft->buttons->isEmpty()) {
            $state['buttons'] = ['buttons' => array_map(
                fn (Button $button): array => self::fromButton($button),
                $draft->buttons->buttons,
            )];
        }

        if ($draft->carousel !== null) {
            $state['carousel'] = ['cards' => array_map(
                fn (Card $card): array => self::fromCard($card),
                $draft->carousel->cards,
            )];
        }

        if ($draft->limitedTimeOffer !== null) {
            $state['limited_time_offer'] = [
                'text' => $draft->limitedTimeOffer->text,
                'has_expiration' => $draft->limitedTimeOffer->hasExpiration,
            ];
        }

        return $state;
    }

    /**
     * @param  array<string,mixed>  $state
     */
    public static function toDraft(array $state): TemplateDraft
    {
        $meta = $state['meta'] ?? [];

        return new TemplateDraft(
            name: (string) ($meta['name'] ?? ''),
            language: (string) ($meta['language'] ?? 'en_US'),
            category: Category::fromPayload((string) ($meta['category'] ?? 'UTILITY')),
            body: new Body(
                text: (string) ($state['body']['text'] ?? ''),
                examples: array_map('strval', $state['body']['examples'] ?? []),
            ),
            header: isset($state['header']) ? new Header(
                format: HeaderFormat::fromPayload((string) ($state['header']['format'] ?? 'TEXT')),
                text: (string) ($state['header']['text'] ?? ''),
                examples: array_map('strval', $state['header']['examples'] ?? []),
                handle: $state['header']['handle'] ?? null,
            ) : null,
            footer: isset($state['footer'])
                ? new Footer((string) ($state['footer']['text'] ?? ''))
                : null,
            buttons: new Buttons(array_map(
                fn (array $button): Button => self::toButton($button),
                array_values($state['buttons']['buttons'] ?? []),
            )),
            carousel: isset($state['carousel']) ? new Carousel(array_map(
                fn (array $card): Card => self::toCard($card),
                array_values($state['carousel']['cards'] ?? []),
            )) : null,
            limitedTimeOffer: isset($state['limited_time_offer']) ? new LimitedTimeOffer(
                text: (string) ($state['limited_time_offer']['text'] ?? ''),
                hasExpiration: (bool) ($state['limited_time_offer']['has_expiration'] ?? false),
            ) : null,
            dialect: new Dialect(
                upperCaseKeywords: (bool) ($meta['upper_case'] ?? false),
                nestedBodyExample: (bool) ($meta['nested_body_example'] ?? true),
            ),
            parameterFormat: self::resolveParameterFormat($meta, $state),
        );
    }

    /**
     * The dialect to declare on the payload.
     *
     * An explicit choice wins. Otherwise a body using named variables declares
     * `parameter_format: NAMED`, which Meta's own worked example for named
     * templates sends and which its docs require — a template carrying
     * `body_text_named_params` without it is refused.
     *
     * Positional stays null rather than declaring `POSITIONAL`: it is Meta's
     * default, and emitting it would add a key to every round-tripped fixture
     * that did not arrive with one.
     *
     * @param  array<string,mixed>  $meta
     * @param  array<string,mixed>  $state
     */
    private static function resolveParameterFormat(array $meta, array $state): ?ParameterFormat
    {
        if (($meta['parameter_format'] ?? null) !== null) {
            return ParameterFormat::fromPayload((string) $meta['parameter_format']);
        }

        $keys = Parameter::keysIn((string) ($state['body']['text'] ?? ''));

        if ($keys !== [] && Parameter::formatOf($keys[0]) === ParameterFormat::Named) {
            return ParameterFormat::Named;
        }

        return null;
    }

    /**
     * Accept either a whole create request or the bare `components` array a
     * template listing stores.
     *
     * @param  array<string,mixed>  $template
     */
    public static function parse(array $template): TemplateDraft
    {
        return array_is_list($template)
            ? TemplateDraft::fromComponents($template)
            : TemplateDraft::fromPayload($template);
    }

    /**
     * @return array<string,mixed>
     */
    private static function fromButton(Button $button): array
    {
        return [
            'type' => $button->type()->value,
            'text' => $button->label(),
            'url' => $button instanceof Url ? $button->url : '',
            'example' => match (true) {
                $button instanceof Url => $button->example,
                $button instanceof CopyCode => $button->example,
                default => null,
            },
            'phone_number' => $button instanceof PhoneNumber ? $button->phoneNumber : '',
            'payload' => $button instanceof QuickReply ? $button->payload : null,
        ];
    }

    /**
     * @param  array<string,mixed>  $button
     */
    private static function toButton(array $button): Button
    {
        $text = (string) ($button['text'] ?? '');

        return match (ButtonType::from((string) ($button['type'] ?? 'QUICK_REPLY'))) {
            ButtonType::QuickReply => new QuickReply($text, $button['payload'] ?? null),
            ButtonType::Url => new Url($text, (string) ($button['url'] ?? ''), $button['example'] ?? null),
            ButtonType::PhoneNumber => new PhoneNumber($text, (string) ($button['phone_number'] ?? '')),
            ButtonType::CopyCode => new CopyCode((string) ($button['example'] ?? '')),
            ButtonType::Mpm => new Mpm($text),
            ButtonType::Spm => new Spm($text),
        };
    }

    /**
     * @return array<string,mixed>
     */
    private static function fromCard(Card $card): array
    {
        return [
            'format' => $card->header->format->value,
            'handle' => $card->header->handle,
            'body' => $card->body?->text ?? '',
            'examples' => $card->body?->examples ?? [],
            'buttons' => array_map(
                fn (Button $button): array => self::fromButton($button),
                $card->buttons->buttons,
            ),
        ];
    }

    /**
     * @param  array<string,mixed>  $card
     */
    private static function toCard(array $card): Card
    {
        $body = (string) ($card['body'] ?? '');

        return new Card(
            header: new Header(
                format: HeaderFormat::fromPayload((string) ($card['format'] ?? 'IMAGE')),
                handle: $card['handle'] ?? null,
            ),
            body: $body === '' ? null : new Body($body, array_map('strval', $card['examples'] ?? [])),
            buttons: new Buttons(array_map(
                fn (array $button): Button => self::toButton($button),
                array_values($card['buttons'] ?? []),
            )),
        );
    }
}
