@props(['variant' => 'secondary', 'disabled' => false, 'reason' => null])

{{--
    A disabled control states its reason rather than vanishing. A hidden feature
    reads as one that does not exist; a disabled one with "no product catalog is
    connected" tells the operator what to fix.
--}}
<button
    type="button"
    @disabled($disabled)
    @if ($disabled && $reason) title="{{ $reason }}" @endif
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium transition disabled:cursor-not-allowed disabled:opacity-50 '.match ($variant) {
        'primary' => 'bg-neutral-900 text-white hover:bg-neutral-700 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200',
        'danger' => 'text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10',
        default => 'border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200 dark:hover:bg-neutral-800',
    }]) }}
>
    {{ $slot }}
</button>
