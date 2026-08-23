@props(['node', 'last' => false])

@php
    $action = $node->attribute('action');
@endphp

{{--
    Quick replies mock the reply; every other type is demonstrative.

    A quick reply sends its own label back as a message, so tapping one appends
    that outbound bubble and scrolls to it — showing the conversation continue is
    both more truthful and more useful than describing the behaviour in a panel.

    The others leave WhatsApp or act on the device. There is no conversation to
    advance, and a design surface should not open real URLs, so tapping reveals
    what *would* happen in a sheet at the base of the frame.
--}}
<button
    type="button"
    @class([
        'flex w-full items-center justify-center gap-1.5 px-2 py-2 text-[13px] font-medium text-[#027eb5] transition hover:bg-neutral-50 dark:text-[#53bdeb] dark:hover:bg-white/5',
        'border-t border-neutral-200 dark:border-white/10' => ! $last,
    ])
    @if ($action === 'reply')
        x-on:click="tapReply(@js($node->text), @js($node->attribute('payload')))"
    @else
        x-on:click="openSheet(@js([
            'label' => $node->text,
            'detail' => $node->attribute('detail'),
            'note' => $node->attribute('note'),
            'copy' => $action === 'copy',
        ]))"
    @endif
>
    <span class="truncate">{{ $node->text }}</span>
</button>
