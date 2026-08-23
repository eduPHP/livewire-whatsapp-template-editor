@props(['contactName' => 'Business'])

{{--
    The frameless preview: the same bubble on the same chat ground, without the
    device around it.

    Used beside the editor's form, where the phone shell was decoration
    competing with the fields for width. What survives is what actually tells
    the operator something — who is speaking, the patterned ground that makes it
    read as a chat, and the bubble itself. What goes is the hardware: notch,
    bezel, composer and keyboard.

    Deliberately not a variant flag inside `phone.frame`: the two share no
    markup once the shell is gone, and threading `@if ($chrome)` through the
    frame would leave a component that draws neither shape well.
--}}
<div {{ $attributes->merge(['class' => 'wa-phone-card mx-auto w-full max-w-[22rem] overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900']) }}>
    <div class="flex items-center gap-3 border-b border-neutral-200 bg-neutral-50 px-3 py-2.5 dark:border-neutral-800 dark:bg-neutral-800/50">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-neutral-200 text-sm font-medium text-neutral-600 dark:bg-neutral-700 dark:text-neutral-200">
            {{ mb_strtoupper(mb_substr($contactName, 0, 1)) }}
        </div>

        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-neutral-900 dark:text-neutral-100">{{ $contactName }}</p>
            <p class="text-[11px] text-neutral-500 dark:text-neutral-400">{{ __('online') }}</p>
        </div>
    </div>

    {{-- Same hand-built approximation of the chat wallpaper as the phone frame:
         WhatsApp's own doodle tile is copyrighted, and the point is only that
         the bubble sits on a patterned ground rather than flat white. --}}
    <div
        x-ref="chat"
        class="relative max-h-[26rem] overflow-y-auto overscroll-contain bg-[#efeae2] px-3 py-4 dark:bg-[#0b141a]"
        style="background-image: radial-gradient(circle at 1px 1px, rgb(0 0 0 / 0.04) 1px, transparent 0); background-size: 18px 18px;"
    >
        <div class="flex flex-col gap-2">
            {{ $slot }}
        </div>
    </div>

    @isset($sheet)
        {{ $sheet }}
    @endisset
</div>
