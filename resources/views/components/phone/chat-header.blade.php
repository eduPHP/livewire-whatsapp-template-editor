@props(['contactName' => 'Business'])

<div class="relative z-20 flex items-center gap-3 bg-[#f0f2f5] px-3 pb-2 pt-8 dark:bg-[#202c33]">
    <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-neutral-300 text-sm font-medium text-neutral-600 dark:bg-neutral-600 dark:text-neutral-200">
        {{ mb_strtoupper(mb_substr($contactName, 0, 1)) }}
    </div>

    <div class="min-w-0">
        <p class="truncate text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $contactName }}</p>
        <p class="text-[11px] text-neutral-500 dark:text-neutral-400">{{ __('online') }}</p>
    </div>
</div>
