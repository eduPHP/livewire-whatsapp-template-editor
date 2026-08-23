@props(['for'])

@php
    /**
     * A short, hand-picked set rather than a full emoji index: these messages
     * are order updates and appointment reminders, and the handful that
     * actually appear in them beat a searchable grid of two thousand.
     */
    $emoji = ['👋', '😊', '🎉', '✅', '❌', '⏰', '📅', '📍', '📦', '🚚', '💳', '🔔', '⭐', '❤️', '🙏', '📞', '💬', '⚠️'];
@endphp

{{--
    Inserts at the caret rather than appending, and does it in the browser: the
    operator's cursor position is a fact only the DOM knows, and a server round
    trip would return the text with the caret at the end.

    The field is addressed by id (`for`) rather than found by walking the DOM,
    so moving the button relative to the textarea cannot quietly break it.

    The field's `wire:model` is notified by hand afterwards — writing to
    `.value` from script does not fire the `input` event Livewire listens for,
    so without the dispatch the emoji would appear on screen and never reach the
    draft.
--}}
<div
    x-data="{
        insert(char) {
            const field = document.getElementById(@js($for))

            if (! field) { return }

            const at = field.selectionStart ?? field.value.length
            field.value = field.value.slice(0, at) + char + field.value.slice(field.selectionEnd ?? at)
            field.dispatchEvent(new Event('input', { bubbles: true }))

            this.$nextTick(() => {
                field.focus()
                field.setSelectionRange(at + char.length, at + char.length)
            })
        },
        open: false,
    }"
    class="relative"
>
    <x-wa-templates::form.button x-on:click="open = ! open">
        <span aria-hidden="true">☺</span>
        {{ __('Emoji') }}
    </x-wa-templates::form.button>

    <div
        x-show="open"
        x-on:click.outside="open = false"
        x-cloak
        class="absolute left-0 z-20 mt-1 grid w-56 grid-cols-6 gap-0.5 rounded-md border border-neutral-200 bg-white p-1.5 shadow-lg dark:border-neutral-700 dark:bg-neutral-900"
    >
        @foreach ($emoji as $char)
            <button
                type="button"
                x-on:click="insert(@js($char)); open = false"
                class="rounded p-1 text-lg leading-none hover:bg-neutral-100 dark:hover:bg-neutral-800"
            >{{ $char }}</button>
        @endforeach
    </div>
</div>
