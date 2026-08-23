# wa-templates

A Livewire editor and visualizer for WhatsApp Business message templates.

Plain Blade and Tailwind — no UI library — so consumers are not forced onto
Mary, Flux, or Filament.

## Install

```bash
composer require wa-connect/wa-templates
```

Tailwind 4 is CSS-first and does not scan vendor paths by default, so register
the package's views in your stylesheet:

```css
@source '../../vendor/wa-connect/wa-templates/resources/views/**/*.blade.php';
```

**A missed `@source` renders the phone mock unstyled**, which is a confusing
first-run failure rather than an obvious one.

## Use

The editor is a black box with two openings: an optional existing template in,
and a `template-changed` event out.

```blade
<livewire:wa-template-editor :template="$existing" />
```

```php
#[On('template-changed')]
public function handle(array $payload, bool $valid, array $errors): void
{
    // $payload is the POST /<WABA_ID>/message_templates body, verbatim.
}
```

The package never submits. It produces the payload; you decide when and how to
send it. That keeps credentials, workspace scoping, and any duplicate-name rules
on your side, where they belong.

The payload is emitted even when `$valid` is false, so you can save a draft an
operator is still working on.

### Visualizer

The visualizer is usable on its own. Given a stored `components` array it renders
the template inside a mock phone — which is what a connection screen wants for an
already-approved template.

```blade
<livewire:wa-template-visualizer :template="$template->components" contact-name="Lucky Shrub" />
```

Quick-reply buttons mock the reply they would send; the other button types
reveal what tapping them would do, including the resolved URL with its variable
substituted.

## Capabilities

Two optional interfaces. Bind whichever you have; an unbound one disables the
components that need it **with a stated reason** rather than hiding them.

| Interface | Unlocks |
| --- | --- |
| `WaTemplates\Contracts\MediaUploader` | Media headers, media carousels, limited-time offers |
| `WaTemplates\Contracts\CatalogSource` | Product list (MPM) buttons, product carousels |
| `WaTemplates\Contracts\VariableSource` | Pre-filled variables in the "+ Variable" picker |

```php
$this->app->bind(MediaUploader::class, ResumableUploader::class);
```

`MediaUploader::uploadForTemplate()` must return a `header_handle` from the
Resumable Upload API. That is **not** the media id `POST /<PHONE_NUMBER_ID>/media`
returns — that id is for sending, the handle is for template creation, and
swapping them yields a template Meta accepts but cannot render.

### Pre-filled variables

Variables your app already fills at send time — a contact's name, the business
name — are offered by name so an operator picks one instead of inventing a
spelling your sending code will never match.

```php
class AppVariables implements VariableSource
{
    public function variables(): array
    {
        return [
            new PrefilledVariable('nome_contato', 'Ana Souza'),
            new PrefilledVariable('nome_salao', 'Studio Bella'),
        ];
    }
}
```

Each carries a **sample**, and that is not decoration: Meta rejects a create
request whose variables have no example, so the sample is what gets sent. The
operator is never asked to supply one, and the preview substitutes it like any
other example.

Declaring a variable only makes it *available*. A template need not use it, and
an unused one appears nowhere in the payload. A pre-filled variable cannot be
renamed in the editor — its name is the contract your sending code matches on.

## Component reference

Every property the draft model carries, and the Meta key each maps to. The
editor is one way to populate this; the classes are usable directly.

### `TemplateDraft`

| Property | Type | Meta key | Notes |
| --- | --- | --- | --- |
| `name` | `string` | `name` | 512 chars, `^[a-z0-9_]+$` |
| `language` | `string` | `language` | Locale code, e.g. `pt_BR` |
| `category` | `Category` | `category` | `MARKETING`, `UTILITY`, `AUTHENTICATION` |
| `body` | `Body` | `components[]` | Required; the only mandatory component |
| `header` | `?Header` | `components[]` | |
| `footer` | `?Footer` | `components[]` | |
| `buttons` | `Buttons` | `components[]` | Omitted from the payload when empty |
| `carousel` | `?Carousel` | `components[]` | |
| `limitedTimeOffer` | `?LimitedTimeOffer` | `components[]` | |
| `parameterFormat` | `?ParameterFormat` | `parameter_format` | Inferred as `NAMED` when the body uses named variables; positional stays absent, which is Meta's default |
| `dialect` | `Dialect` | — | Not sent. Remembers the incidental shape a payload arrived in |

`toPayload()` orders components as Meta requires: header, limited-time offer,
body, footer, buttons, carousel.

### Components

| Class | Property | Type | Meta key |
| --- | --- | --- | --- |
| `Body` | `text` | `string` | `text` — 1024 chars (600 with an LTO) |
| | `examples` | `array<string,string>` | `example.body_text` or `example.body_text_named_params` |
| `Header` | `format` | `HeaderFormat` | `format` |
| | `text` | `string` | `text` — text format only, 60 chars, max 1 variable |
| | `examples` | `array<string,string>` | `example.header_text` or `example.header_text_named_params` |
| | `handle` | `?string` | `example.header_handle[0]` — media formats only |
| `Footer` | `text` | `string` | `text` — 60 chars, **no variables** |
| `Buttons` | `buttons` | `list<Button>` | `buttons[]` — 10 total |
| `Carousel` | `cards` | `list<Card>` | `cards[]` — 10 max, all structurally identical |
| `Card` | `header` | `Header` | `components[]` — `IMAGE`, `VIDEO` or `PRODUCT` |
| | `body` | `?Body` | `components[]` |
| | `buttons` | `Buttons` | `components[]` — 2 max per card |
| `LimitedTimeOffer` | `text` | `string` | `limited_time_offer.text` — 16 chars |
| | `hasExpiration` | `bool` | `limited_time_offer.has_expiration` |

A `Card` carries no `card_index`: that appears only on send, never at creation.

### Buttons

| Class | Property | Type | Meta key | Limit |
| --- | --- | --- | --- | --- |
| `QuickReply` | `text` | `string` | `text` | 25 chars, 10 per template |
| | `payload` | `?string` | — | Not sent at creation; arrives on the webhook as `messages.button.payload` |
| `Url` | `text` | `string` | `text` | 25 chars |
| | `url` | `string` | `url` | 2000 chars, 2 per template, 1 variable **appended at the end only** |
| | `example` | `?string` | `example[0]` | Required when the URL has a variable |
| `PhoneNumber` | `text` | `string` | `text` | 25 chars |
| | `phoneNumber` | `string` | `phone_number` | 20 chars, 1 per template |
| `CopyCode` | `example` | `string` | `example` | 20 chars, 1 per template |
| `Mpm` | `text` | `string` | `text` | 1 per template; needs a `CatalogSource` |
| `Spm` | `text` | `string` | `text` | Carousel cards only |

Quick replies must be **contiguous**: `URL, Phone, QR, QR` is accepted,
`QR, URL, QR` is not.

### Enums

- `Category` — `MARKETING`, `UTILITY`, `AUTHENTICATION`
- `HeaderFormat` — `TEXT`, `IMAGE`, `VIDEO`, `DOCUMENT`, `LOCATION`, `PRODUCT`
- `ButtonType` — `QUICK_REPLY`, `URL`, `PHONE_NUMBER`, `COPY_CODE`, `MPM`, `SPM`
- `ParameterFormat` — `POSITIONAL`, `NAMED`

### What the editor does not cover

Authentication templates (`docs/templates/auth.md`) have their own one-time-password
button and a fixed body Meta supplies — they are not built here. Neither is the
voice-call button. Both are `AUTHENTICATION`-shaped work rather than gaps in the
components above.

Location headers take no properties at all: the pin, place name and address are
supplied per message at send time, which is what lets one approved template serve
every destination.

## Validation

`TemplateValidator` returns errors keyed by component path — `buttons.2.text`,
`body.examples.nome` — so a UI can highlight the offending field rather than
showing a form-level list.

```php
$result = (new TemplateValidator)->validate($draft);

$result->passes();              // bool
$result->for('body.text');      // list<string> for one path
$result->under('buttons');      // every error beneath a prefix
$result->all();                 // flat list
```

Limits are exposed as constants (`TemplateValidator::BODY_MAX`, `::BUTTONS_MAX`,
…) so a UI can render counters without restating them.

## Rendering

`TemplateRenderer::render()` accepts a `TemplateDraft` **or** a raw Meta
`components` array, so a live editor preview and a read-only view of an approved
template cannot drift apart.

```php
$rendered = (new TemplateRenderer)->render($draft);

$rendered->header;    // ?PreviewNode
$rendered->offer;     // ?PreviewNode
$rendered->body;      // string, examples substituted
$rendered->footer;    // string
$rendered->buttons;   // list<PreviewNode>
$rendered->cards;     // list<PreviewNode>
```

Examples are substituted into the text. A placeholder with **no** example is
left standing rather than blanked: `{{2}}` on screen says a variable is
unaccounted for, where an empty gap reads as a typo in the wording.

Each button `PreviewNode` carries what tapping it would do — `action` is one of
`reply`, `sheet`, `copy`, `none`, with `detail` holding the resolved URL, the
number, or the code.

## Headless use

The domain half runs without Livewire:

```php
$draft = TemplateDraft::fromPayload($metaCreateRequest);
$result = (new TemplateValidator)->validate($draft);
$rendered = (new TemplateRenderer)->render($draft);
```

`TemplateDraft` round-trips Meta's own payloads byte for byte, including its
case inconsistencies (`BODY` on read, `body` on write) and the nesting variants
of `example.body_text`.
