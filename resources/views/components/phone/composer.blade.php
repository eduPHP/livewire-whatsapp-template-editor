{{-- The message bar. Inert: this is a preview, not a chat client. --}}
<div class="flex items-center gap-2 bg-[#f0f2f5] px-2 py-2 dark:bg-[#202c33]">
    <div class="flex-1 rounded-full bg-white px-3 py-2 text-xs text-neutral-400 dark:bg-[#2a3942] dark:text-neutral-500">
        {{ __('Message') }}
    </div>

    <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#00a884] text-white">
        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 14a3 3 0 0 0 3-3V5a3 3 0 0 0-6 0v6a3 3 0 0 0 3 3Zm5-3a5 5 0 0 1-10 0H5a7 7 0 0 0 6 6.92V21h2v-3.08A7 7 0 0 0 19 11h-2Z" />
        </svg>
    </div>
</div>
