@props(['preview'])

{{--
    The template bubble.

    Inbound — left-aligned and white — because a template arrives *from* the
    business. Previewing it as an outbound green bubble would be wrong about who
    is speaking.
--}}
<div class="flex justify-start">
    <div class="relative max-w-[85%] rounded-lg rounded-tl-none bg-white shadow-sm dark:bg-[#202c33]">
        @if ($preview->header)
            <x-wa-templates::phone.header :node="$preview->header" />
        @endif

        @if ($preview->offer)
            <x-wa-templates::phone.offer :node="$preview->offer" />
        @endif

        <div class="px-2 pb-1 pt-2">
            @if ($preview->body !== '')
                <p class="whitespace-pre-wrap break-words text-[13px] leading-snug text-neutral-900 dark:text-neutral-100">{!! wa_templates_format($preview->body) !!}</p>
            @endif

            @if ($preview->footer !== '')
                <p class="mt-1 text-[11px] text-neutral-500 dark:text-neutral-400">{{ $preview->footer }}</p>
            @endif

            <p class="mt-0.5 text-right text-[10px] text-neutral-400 dark:text-neutral-500">{{ $timestamp ?? '12:00' }}</p>
        </div>

        @if ($preview->hasCards())
            <x-wa-templates::phone.carousel :cards="$preview->cards" />
        @endif

        @if ($preview->buttons !== [])
            <div class="border-t border-neutral-200 dark:border-white/10">
                @foreach ($preview->buttons as $button)
                    <x-wa-templates::phone.button :node="$button" :last="$loop->last" />
                @endforeach
            </div>
        @endif
    </div>
</div>
