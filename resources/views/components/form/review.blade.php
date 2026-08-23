@props(['draft', 'state' => []])

@php
    use WaTemplates\Draft\Parameter;
    use WaTemplates\Enums\HeaderFormat;

    $meta = $state['meta'] ?? [];

    /**
     * Counted from the body text rather than from the examples map: the text is
     * what Meta reads, and an example left behind by a deleted variable would
     * otherwise inflate the count the operator is checking.
     */
    $variables = count(Parameter::keysIn((string) ($state['body']['text'] ?? '')));

    $header = isset($state['header'])
        ? ucfirst(strtolower(HeaderFormat::from($state['header']['format'] ?? 'TEXT')->name))
        : null;

    $rows = [
        __('Name') => ($meta['name'] ?? '') !== '' ? $meta['name'] : '—',
        __('Type') => ucfirst(strtolower((string) ($meta['category'] ?? 'UTILITY'))),
        __('Language') => $meta['language'] ?? '—',
        __('Variables') => (string) $variables,
        __('Buttons') => (string) count($state['buttons']['buttons'] ?? []),
        __('Header') => $header ?? __('no'),
        __('Footer') => isset($state['footer']) && ($state['footer']['text'] ?? '') !== '' ? __('yes') : __('no'),
    ];
@endphp

{{--
    The last thing on the last step: what is about to be sent, in one line.

    Every value here is already visible on some earlier step, which is the
    point — this is a re-read before an irreversible action, not new
    information. An approved template's name can never be changed, so the name
    in particular is worth seeing once more.
--}}
<div class="border-t border-neutral-200 pt-4 dark:border-neutral-800">
    <p class="mb-3 text-[10px] font-semibold uppercase tracking-widest text-neutral-400 dark:text-neutral-500">
        {{ __('Review of what will be sent') }}
    </p>

    <dl class="flex flex-wrap gap-x-8 gap-y-3">
        @foreach ($rows as $label => $value)
            <div wire:key="review-{{ Str::slug($label) }}" class="min-w-0">
                <dt class="text-[11px] text-neutral-500 dark:text-neutral-400">{{ $label }}</dt>
                <dd class="truncate text-sm font-semibold text-neutral-900 dark:text-neutral-100">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>
</div>
