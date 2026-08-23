{{--
    A mocked quick-reply response: right-aligned, green, timestamped — exactly
    what the recipient's own message would look like.

    Tapping a second button appends a second reply rather than replacing the
    first. A recipient could do exactly that, and seeing two stacked replies is a
    genuine signal that the button set is ambiguous.

    Each reply is dismissible so an operator can try each button in turn.
--}}
<template x-for="reply in replies" :key="reply.id">
    <div class="flex justify-end">
        <button
            type="button"
            x-on:click="replies = replies.filter(r => r.id !== reply.id)"
            class="max-w-[85%] rounded-lg rounded-tr-none bg-[#d9fdd3] px-2 py-1.5 text-left shadow-sm transition hover:opacity-80 dark:bg-[#005c4b]"
            :title="'{{ __('Dismiss this reply') }}'"
        >
            <p class="break-words text-[13px] leading-snug text-neutral-900 dark:text-neutral-100" x-text="reply.label"></p>

            {{--
                A quick reply may carry a payload distinct from its label. The
                label is what the recipient sees; the payload is what the webhook
                carries, so both are shown rather than conflated.
            --}}
            <template x-if="reply.payload">
                <p class="mt-0.5 font-mono text-[10px] text-neutral-500 dark:text-neutral-300">
                    {{ __('payload') }}: <span x-text="reply.payload"></span>
                </p>
            </template>

            <p class="mt-0.5 text-right text-[10px] text-neutral-500 dark:text-neutral-300/70">12:00</p>
        </button>
    </div>
</template>
