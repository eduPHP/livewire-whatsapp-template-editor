<?php

use WaTemplates\Draft\Components\Body;
use WaTemplates\Draft\TemplateDraft;
use WaTemplates\Enums\Category;
use WaTemplates\Validation\TemplateValidator;

it('round-trips every one of Meta\'s worked examples', function (string $fixture) {
    // `parse(build(x)) === x` is the package's central obligation: a template
    // read from a listing and written back must be byte-identical, or a sync
    // would rewrite templates it only meant to read.
    $payload = waTemplateFixture($fixture);

    expect(TemplateDraft::fromPayload($payload)->toPayload())->toBe($payload);
})->with([
    'abandoned_cart',
    'carousel_media_cards',
    'limited_time_offer',
    'order_confirmation',
    'order_delivery_update',
    'reservation_confirmation',
    'seasonal_promotion',
]);

it('validates a template without any application booted', function () {
    // The domain layer is pure: no container, no config, no database. That is
    // what makes it usable headless, and this asserts it stays that way.
    $draft = new TemplateDraft(
        name: 'order_update',
        category: Category::Utility,
        body: new Body('Your order is on its way.'),
    );

    expect((new TemplateValidator)->validate($draft)->passes())->toBeTrue();
});

it('refuses a variable outside a closed set', function () {
    $draft = new TemplateDraft(
        name: 'order_update',
        category: Category::Utility,
        body: new Body('Total: {{preco}}', ['preco' => 'R$ 40']),
    );

    expect((new TemplateValidator(['nome_contato']))->validate($draft)->for('body.examples.preco'))
        ->not->toBeEmpty();
});
