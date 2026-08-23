@props([
    'label' => null,
    'hint' => null,
    'errors' => [],
    'count' => null,
    'max' => null,
])

{{--
    One labelled field, with its character counter and any errors under it.

    Plain Blade and Tailwind, no UI library: consumers must not be forced onto
    Mary, Flux or Filament to use this package.
--}}
<div {{ $attributes->merge(['class' => 'space-y-1']) }}>
    @if ($label || $max !== null)
        <div class="flex items-baseline justify-between gap-2">
            @if ($label)
                <label class="text-xs font-medium text-neutral-700 dark:text-neutral-300">{{ $label }}</label>
            @endif

            @if ($max !== null)
                <span @class([
                    'text-[11px] tabular-nums',
                    'text-neutral-400 dark:text-neutral-500' => $count <= $max,
                    'text-red-600 dark:text-red-400' => $count > $max,
                ])>{{ $count }}/{{ $max }}</span>
            @endif
        </div>
    @endif

    {{ $slot }}

    @if ($hint)
        <p class="text-[11px] text-neutral-500 dark:text-neutral-400">{{ $hint }}</p>
    @endif

    @foreach ($errors as $error)
        <p class="text-[11px] text-red-600 dark:text-red-400">{{ $error }}</p>
    @endforeach
</div>
