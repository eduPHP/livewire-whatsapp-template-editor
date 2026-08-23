@props(['node'])

@php
    $format = $node->attribute('format');
@endphp

@if ($format === 'text')
    @if ($node->text !== '')
        <p class="px-2 pt-2 text-[13px] font-semibold text-neutral-900 dark:text-neutral-100">{{ $node->text }}</p>
    @endif
@else
    @if ($format === 'location')
        {{--
            WhatsApp renders a location header as a wide map card with the place
            name and address beneath it, not as an icon tile. The coordinates
            arrive per message rather than with the template, so the card is
            drawn with stand-in text — an operator writing "your order is on its
            way to the location above" needs to see the block that sentence
            refers to, at the size it will actually occupy.
        --}}
        <div class="overflow-hidden rounded-t-lg">
            <div
                class="flex aspect-[2/1] items-center justify-center bg-[#e8eaed] text-neutral-400 dark:bg-white/5 dark:text-neutral-500"
                style="background-image: linear-gradient(rgb(0 0 0 / 0.06) 1px, transparent 1px), linear-gradient(90deg, rgb(0 0 0 / 0.06) 1px, transparent 1px); background-size: 24px 24px;"
            >
                <svg class="size-7 text-[#ea4335]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z" />
                </svg>
            </div>

            <div class="bg-neutral-50 px-2 py-1.5 dark:bg-white/5">
                <p class="text-[12px] font-medium text-neutral-500 dark:text-neutral-400">{{ __('Place name') }}</p>
                <p class="text-[11px] text-neutral-400 dark:text-neutral-500">{{ __('Street address, city') }}</p>
            </div>
        </div>
    @else
        {{--
            Media is a `header_handle`, not a URL — Meta gives back no
            previewable address for an uploaded template asset, so the mock
            shows the shape and proportion the recipient will see rather than
            pretending to the content.
        --}}
        <div @class([
            'flex items-center justify-center rounded-t-lg bg-neutral-100 text-neutral-400 dark:bg-white/5 dark:text-neutral-500',
            'aspect-video' => in_array($format, ['image', 'video', 'product'], true),
            'h-16' => $format === 'document',
        ])>
            <div class="flex flex-col items-center gap-1">
                <svg class="size-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    @switch($format)
                        @case('video')
                            <path d="M4 6h10a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Zm14 3.5 4-2.5v10l-4-2.5v-5Z" />
                            @break
                        @case('document')
                            <path d="M6 2h7l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm7 1.5V8h4.5L13 3.5Z" />
                            @break
                        @case('location')
                            <path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z" />
                            @break
                        @default
                            <path d="M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm2 11h12l-3.5-4.5-2.5 3-2-2.5L6 16Zm2.5-6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                    @endswitch
                </svg>
                <span class="text-[10px] uppercase tracking-wide">{{ $format }}</span>
            </div>
        </div>
    @endif
@endif
