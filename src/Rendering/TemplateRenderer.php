<?php

namespace WaTemplates\Rendering;

use WaTemplates\Draft\Buttons\Button;
use WaTemplates\Draft\Buttons\CopyCode;
use WaTemplates\Draft\Buttons\Mpm;
use WaTemplates\Draft\Buttons\PhoneNumber;
use WaTemplates\Draft\Buttons\QuickReply;
use WaTemplates\Draft\Buttons\Spm;
use WaTemplates\Draft\Buttons\Url;
use WaTemplates\Draft\Components\Card;
use WaTemplates\Draft\Components\Header;
use WaTemplates\Draft\TemplateDraft;

/**
 * Turns a template into the message a recipient would receive.
 *
 * Accepts either a draft or a raw Meta `components` array, so the live editor
 * preview and the read-only visualizer of an approved template cannot drift
 * apart — there is one renderer, and both go through it.
 */
final class TemplateRenderer
{
    /**
     * @param  TemplateDraft|list<array<string,mixed>>  $template
     */
    public function render(TemplateDraft|array $template): RenderedTemplate
    {
        $draft = is_array($template)
            ? TemplateDraft::fromComponents($template)
            : $template;

        return new RenderedTemplate(
            header: $this->renderHeader($draft->header),
            offer: $draft->limitedTimeOffer === null ? null : new PreviewNode(
                type: 'offer',
                text: $draft->limitedTimeOffer->text,
                attributes: ['has_expiration' => $draft->limitedTimeOffer->hasExpiration],
            ),
            body: $this->substitute($draft->body->text, $draft->body->examples),
            footer: $draft->footer?->text ?? '',
            buttons: array_map(
                fn (Button $button): PreviewNode => $this->renderButton($button),
                $draft->buttons->buttons,
            ),
            cards: array_map(
                fn (Card $card): PreviewNode => $this->renderCard($card),
                $draft->carousel?->cards ?? [],
            ),
        );
    }

    /**
     * Placeholder substitution.
     *
     * A placeholder with no example is left standing rather than blanked — the
     * same rule `ConnectionTemplate::renderPreview()` follows, and for the same
     * reason: an operator seeing `{{2}}` learns immediately that a variable is
     * unaccounted for, where an empty gap reads as a typo in the wording.
     *
     * @param  array<string,string>  $examples
     */
    public function substitute(string $text, array $examples): string
    {
        foreach ($examples as $key => $value) {
            if ($value !== '') {
                $text = str_replace('{{'.$key.'}}', $value, $text);
            }
        }

        return $text;
    }

    private function renderHeader(?Header $header): ?PreviewNode
    {
        if ($header === null) {
            return null;
        }

        return new PreviewNode(
            type: 'header',
            text: $this->substitute($header->text, $header->examples),
            attributes: [
                'format' => strtolower($header->format->value),
                'handle' => $header->handle,
            ],
        );
    }

    /**
     * Every button carries what tapping it would do, because the visualizer
     * demonstrates the outcome rather than describing it.
     */
    private function renderButton(Button $button): PreviewNode
    {
        $attributes = ['type' => strtolower($button->type()->value)];

        $attributes += match (true) {
            $button instanceof QuickReply => [
                'action' => 'reply',
                'reply' => $button->text,
                'payload' => $button->payload,
            ],
            $button instanceof Url => [
                'action' => 'sheet',
                'detail' => $button->resolvedUrl(),
                'note' => 'Opens in the browser',
            ],
            $button instanceof PhoneNumber => [
                'action' => 'sheet',
                'detail' => $button->phoneNumber,
                'note' => 'Calls this number',
            ],
            $button instanceof CopyCode => [
                'action' => 'copy',
                'detail' => $button->example,
                'note' => 'Copies the code',
            ],
            $button instanceof Mpm, $button instanceof Spm => [
                'action' => 'sheet',
                'detail' => null,
                'note' => 'Opens the product list inside WhatsApp',
            ],
            default => ['action' => 'none'],
        };

        return new PreviewNode(
            type: 'button',
            text: $button->label(),
            attributes: $attributes,
        );
    }

    private function renderCard(Card $card): PreviewNode
    {
        return new PreviewNode(
            type: 'card',
            text: $card->body === null
                ? ''
                : $this->substitute($card->body->text, $card->body->examples),
            attributes: [
                'format' => strtolower($card->header->format->value),
                'handle' => $card->header->handle,
            ],
            children: array_map(
                fn (Button $button): PreviewNode => $this->renderButton($button),
                $card->buttons->buttons,
            ),
        );
    }
}
