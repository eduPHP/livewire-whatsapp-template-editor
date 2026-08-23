{{--
    What a non-reply button would do, as a transient inline sheet at the base of
    the frame. One at a time, dismissible.

    This doubles as a URL check: an operator sees the resolved address and can
    tell immediately whether the variable landed in the right place — which is
    the only position `components.md` allows it to occupy.
--}}
<div
    x-show="sheet"
    x-transition.opacity
    x-cloak
    class="relative z-20 border-t border-neutral-200 bg-white px-3 py-2 dark:border-white/10 dark:bg-[#202c33]"
>
    <div class="flex items-start justify-between gap-2">
        <div class="min-w-0">
            <p class="text-[11px] font-medium text-neutral-500 dark:text-neutral-400" x-text="sheet?.note"></p>

            <template x-if="sheet?.detail">
                <p class="break-all font-mono text-[11px] text-neutral-900 dark:text-neutral-100" x-text="sheet.detail"></p>
            </template>

            <template x-if="sheet?.copy">
                <p class="mt-0.5 text-[10px] text-[#027d69] dark:text-[#7fe3cf]">{{ __('Copied to clipboard') }}</p>
            </template>
        </div>

        <button
            type="button"
            x-on:click="sheet = null"
            class="shrink-0 rounded p-1 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200"
            aria-label="{{ __('Dismiss') }}"
        >
            <svg class="size-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M18.3 5.71 12 12l6.3 6.29-1.41 1.42L10.59 13.4 4.3 19.71 2.89 18.3 9.17 12 2.89 5.71 4.3 4.29l6.29 6.3 6.3-6.3 1.41 1.42Z" />
            </svg>
        </button>
    </div>
</div>
