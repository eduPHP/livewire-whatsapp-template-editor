@props([
    'contactName' => 'Business',
    'keyboard' => false,
])

{{--
    The device shell.

    Fixed aspect ratio and max-width bounded, so it scales down on narrow
    screens rather than overflowing. The chat body scrolls; the frame never
    does.

    Frame-local state lives in the surrounding `<x-wa-templates::phone.preview>`
    scope rather than on the server: a `wire:model` round trip per carousel swipe
    would be visibly laggy, and none of this state is anything the host needs.
--}}
<div {{ $attributes->merge(['class' => 'wa-phone mx-auto w-full max-w-[22rem]']) }}>
    <div class="relative aspect-[9/19] w-full overflow-hidden rounded-[2.5rem] border-[10px] border-neutral-900 bg-neutral-900 shadow-2xl dark:border-black">
        {{-- Notch --}}
        <div class="absolute inset-x-0 top-0 z-30 flex justify-center">
            <div class="h-6 w-32 rounded-b-2xl bg-neutral-900 dark:bg-black"></div>
        </div>

        <div class="flex h-full flex-col bg-[#efeae2] dark:bg-[#0b141a]">
            <x-wa-templates::phone.chat-header :contact-name="$contactName" />

            {{--
                The chat wallpaper. WhatsApp's own doodle tile is copyrighted, so
                this is a hand-built approximation in the same two tones — the
                point is that the bubble sits on a patterned ground rather than
                flat white, which is what makes the preview read as a chat.
            --}}
            <div
                x-ref="chat"
                class="relative flex-1 overflow-y-auto overscroll-contain px-3 py-3"
                style="background-image: radial-gradient(circle at 1px 1px, rgb(0 0 0 / 0.04) 1px, transparent 0); background-size: 18px 18px;"
            >
                <div class="flex min-h-full flex-col justify-end gap-2">
                    {{ $slot }}
                </div>
            </div>

            @isset($sheet)
                {{ $sheet }}
            @endisset

            <x-wa-templates::phone.composer />

            <x-wa-templates::phone.keyboard />
        </div>
    </div>
</div>
