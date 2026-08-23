@props(['variant' => 'secondary', 'disabled' => false, 'reason' => null, 'loadingWhen' => null, 'loadingLabel' => null])

{{--
    A disabled control states its reason rather than vanishing. A hidden feature
    reads as one that does not exist; a disabled one with "no product catalog is
    connected" tells the operator what to fix.

    `loadingWhen` is for a button whose work the browser cannot see finish — one
    that hands off through `$dispatch` rather than `wire:click`, so Livewire's
    own disable-while-in-flight never covers it. It takes an ALPINE EXPRESSION,
    not a PHP boolean: the state it reflects lives in the browser and is raised
    by the same click that starts the work, so no server render stands between
    the two. Both label and spinner are rendered and Alpine picks, which is also
    why the plain label survives with JS unavailable.

    `loadingLabel` swaps the text for the duration, because a button that still
    reads "Submit for approval" while submitting invites the second click that
    sends the template twice.
--}}
<button
    type="button"
    @disabled($disabled)
    @if ($loadingWhen) x-bind:disabled="{{ $loadingWhen }}" @endif
    @if ($disabled && $reason) title="{{ $reason }}" @endif
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium transition disabled:cursor-not-allowed disabled:opacity-50 '.match ($variant) {
        'primary' => 'bg-accent text-accent-foreground hover:opacity-90',
        'danger' => 'text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10',
        /** The chosen option in a segmented switch: outlined in the accent
            rather than filled with it, so it does not read as the step's
            primary action the way Continue does. */
        'selected' => 'border border-accent bg-accent/10 text-accent-text',
        default => 'border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200 dark:hover:bg-neutral-800',
    }]) }}
>
    @if ($loadingWhen)
        <svg
            x-show="{{ $loadingWhen }}"
            x-cloak
            class="size-3 shrink-0 animate-spin"
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden="true"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z" />
        </svg>
    @endif

    @if ($loadingWhen && $loadingLabel)
        <span x-show="! ({{ $loadingWhen }})">{{ $slot }}</span>
        <span x-show="{{ $loadingWhen }}" x-cloak>{{ $loadingLabel }}</span>
    @else
        {{ $slot }}
    @endif
</button>
