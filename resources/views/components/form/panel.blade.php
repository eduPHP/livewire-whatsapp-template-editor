@props(['title', 'description' => null, 'invalid' => false])

<section @class([
    'rounded-lg border bg-white p-3 dark:bg-neutral-900',
    'border-red-300 dark:border-red-500/50' => $invalid,
    'border-neutral-200 dark:border-neutral-800' => ! $invalid,
])>
    <div class="mb-2 flex items-start justify-between gap-2">
        <div>
            <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">{{ $title }}</h3>

            @if ($description)
                <p class="text-[11px] text-neutral-500 dark:text-neutral-400">{{ $description }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="flex shrink-0 items-center gap-1">{{ $actions }}</div>
        @endisset
    </div>

    {{ $slot }}
</section>
