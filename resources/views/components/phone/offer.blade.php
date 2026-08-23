@props(['node'])

{{-- The expiring-offer band, with its countdown when one is configured. --}}
<div class="mx-2 mt-2 rounded bg-[#e7f8f4] px-2 py-1.5 dark:bg-[#0f3b34]">
    <p class="text-[12px] font-semibold text-[#027d69] dark:text-[#7fe3cf]">{{ $node->text }}</p>

    @if ($node->attribute('has_expiration'))
        <p class="text-[10px] text-[#027d69]/80 dark:text-[#7fe3cf]/80">{{ __('Offer ends in 23h 59m') }}</p>
    @endif
</div>
