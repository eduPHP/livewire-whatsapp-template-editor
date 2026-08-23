{{--
    A mock keyboard over the lower third.

    Its purpose is vertical-space realism: with the keyboard up an operator sees
    how much of a long template is actually visible before scrolling, which is
    the difference between a template that reads at a glance and one that does
    not. Purely local Alpine state — there is nothing here the server needs.

    `motion-reduce:transition-none` honours `prefers-reduced-motion`, since a
    panel sliding over a third of the frame is exactly the kind of motion that
    setting exists to suppress.
--}}
<div
    x-show="keyboard"
    x-transition:enter="transition ease-out duration-200 motion-reduce:transition-none"
    x-transition:enter-start="translate-y-full"
    x-transition:enter-end="translate-y-0"
    x-transition:leave="transition ease-in duration-150 motion-reduce:transition-none"
    x-transition:leave-start="translate-y-0"
    x-transition:leave-end="translate-y-full"
    x-cloak
    class="select-none bg-[#d1d5db] px-1 pb-2 pt-1 dark:bg-[#1f2c34]"
    aria-hidden="true"
>
    @foreach (['qwertyuiop', 'asdfghjkl', 'zxcvbnm'] as $index => $row)
        <div @class([
            'flex justify-center gap-1 py-[3px]',
            'px-4' => $index === 1,
            'px-6' => $index === 2,
        ])>
            @foreach (str_split($row) as $key)
                <div class="flex h-7 flex-1 items-center justify-center rounded bg-white text-[11px] font-medium text-neutral-800 shadow-sm dark:bg-[#3b4a54] dark:text-neutral-100">
                    {{ $key }}
                </div>
            @endforeach
        </div>
    @endforeach

    <div class="flex justify-center gap-1 py-[3px]">
        <div class="flex h-7 w-10 items-center justify-center rounded bg-neutral-400 text-[10px] text-white dark:bg-[#2a3942]">?123</div>
        <div class="h-7 flex-1 rounded bg-white shadow-sm dark:bg-[#3b4a54]"></div>
        <div class="flex h-7 w-10 items-center justify-center rounded bg-neutral-400 text-[10px] text-white dark:bg-[#2a3942]">↵</div>
    </div>
</div>
