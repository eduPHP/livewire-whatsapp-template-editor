@props(['invalid' => false, 'rows' => 4])

<textarea
    rows="{{ $rows }}"
    {{ $attributes->merge(['class' => 'block w-full rounded-md border px-2.5 py-1.5 text-sm shadow-sm outline-none transition placeholder:text-neutral-400 focus:ring-2 dark:bg-neutral-900 dark:text-neutral-100 '.($invalid
        ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20 dark:border-red-500/60'
        : 'border-neutral-300 focus:border-neutral-400 focus:ring-neutral-900/10 dark:border-neutral-700 dark:focus:ring-white/10')]) }}
>{{ $slot }}</textarea>
