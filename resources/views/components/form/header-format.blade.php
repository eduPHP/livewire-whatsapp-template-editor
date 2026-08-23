@props(['state' => [], 'mediaReason' => null])

@php
    use WaTemplates\Enums\HeaderFormat;

    /**
     * "None" is a real option here, not the absence of one: it is what removes
     * the header component. The draft omits a component rather than carrying an
     * empty one, because `components` is positional and Meta reads an empty
     * header as a header.
     */
    $current = isset($state['header']) ? ($state['header']['format'] ?? 'TEXT') : null;

    $labels = [
        HeaderFormat::Text->value => __('Text'),
        HeaderFormat::Image->value => __('Image'),
        HeaderFormat::Document->value => __('Document'),
        HeaderFormat::Location->value => __('Location'),
    ];
@endphp

{{--
    A segmented switch rather than the `<select>` the header panel carries: the
    formats are few, mutually exclusive, and change which fields exist below —
    a shape worth showing all of at once.

    Video is deliberately absent, matching the four formats the switch offers.
    It remains reachable on an imported template, whose format the header panel
    still renders.
--}}
<div class="space-y-2">
    <div>
        <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">{{ __('Header') }}</p>
        <p class="text-[11px] text-neutral-500 dark:text-neutral-400">
            {{ __('A line above the text, or a piece of media.') }}
        </p>
    </div>

    <div class="flex flex-wrap gap-2">
        <x-wa-templates::form.button
            wire:click="removeComponent('header')"
            :variant="$current === null ? 'selected' : 'secondary'"
            :aria-pressed="$current === null ? 'true' : 'false'"
        >
            {{ __('None') }}
        </x-wa-templates::form.button>

        @foreach ($labels as $value => $label)
            @php
                /**
                 * A media format without an uploader would produce a header
                 * with no handle, which Meta refuses. Disabled with the reason
                 * rather than hidden: a hidden feature reads as one that does
                 * not exist.
                 */
                $blocked = HeaderFormat::from($value)->isMedia() && $mediaReason !== null;
            @endphp

            <x-wa-templates::form.button
                wire:key="header-format-{{ $value }}"
                wire:click="setHeaderFormat('{{ $value }}')"
                :variant="$current === $value ? 'selected' : 'secondary'"
                :disabled="$blocked"
                :reason="$blocked ? $mediaReason : null"
                :aria-pressed="$current === $value ? 'true' : 'false'"
            >
                {{ $label }}
            </x-wa-templates::form.button>
        @endforeach
    </div>
</div>
