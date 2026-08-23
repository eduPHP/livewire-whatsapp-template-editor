@props(['cards'])

{{--
    Cards scroll horizontally with real touch and trackpad gestures: a flex row
    in an `overflow-x-auto` container with scroll-snap per card. Native momentum
    on touch devices, and no gesture library.

    Because every card shares one structure, this renders one card component N
    times — the same constraint the editor enforces.
--}}
<div
    x-data="{
        cards: {{ count($cards) }},
        go(index) {
            this.card = Math.max(0, Math.min(index, this.cards - 1))
            this.$refs.track.children[this.card].scrollIntoView({
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                block: 'nearest',
                inline: 'center',
            })
        },
        track() {
            const el = this.$refs.track
            this.card = Math.round(el.scrollLeft / (el.scrollWidth / this.cards))
        },
    }"
    class="relative border-t border-neutral-200 pt-2 dark:border-white/10"
>
    <div
        x-ref="track"
        x-on:scroll.debounce.100="track()"
        x-on:keydown.arrow-right.prevent="go(card + 1)"
        x-on:keydown.arrow-left.prevent="go(card - 1)"
        tabindex="0"
        role="group"
        aria-label="{{ __('Carousel cards') }}"
        class="flex snap-x snap-mandatory gap-2 overflow-x-auto px-2 pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
    >
        @foreach ($cards as $index => $card)
            <div
                class="w-[78%] shrink-0 snap-center overflow-hidden rounded-lg border border-neutral-200 bg-white dark:border-white/10 dark:bg-[#111b21]"
            >
                <x-wa-templates::phone.header :node="$card" />

                @if ($card->text !== '')
                    <p class="whitespace-pre-wrap break-words px-2 py-1.5 text-[12px] leading-snug text-neutral-900 dark:text-neutral-100">{{ $card->text }}</p>
                @endif

                @if ($card->children !== [])
                    <div class="border-t border-neutral-200 dark:border-white/10">
                        @foreach ($card->children as $button)
                            <x-wa-templates::phone.button :node="$button" :last="$loop->last" />
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @if (count($cards) > 1)
        {{-- Dots indicate position; arrows appear on pointer-capable devices. --}}
        <div class="flex items-center justify-center gap-1.5 pb-2">
            <button
                type="button"
                x-on:click="go(card - 1)"
                class="hidden rounded p-0.5 text-neutral-400 hover:text-neutral-600 [@media(pointer:fine)]:block dark:hover:text-neutral-200"
                aria-label="{{ __('Previous card') }}"
            >
                <svg class="size-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M15.4 7.4 14 6l-6 6 6 6 1.4-1.4-4.6-4.6 4.6-4.6Z" /></svg>
            </button>

            @foreach ($cards as $index => $card)
                <span
                    class="size-1.5 rounded-full transition"
                    :class="card === {{ $index }} ? 'bg-neutral-500 dark:bg-neutral-300' : 'bg-neutral-300 dark:bg-neutral-600'"
                ></span>
            @endforeach

            <button
                type="button"
                x-on:click="go(card + 1)"
                class="hidden rounded p-0.5 text-neutral-400 hover:text-neutral-600 [@media(pointer:fine)]:block dark:hover:text-neutral-200"
                aria-label="{{ __('Next card') }}"
            >
                <svg class="size-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m8.6 16.6 1.4 1.4 6-6-6-6-1.4 1.4 4.6 4.6-4.6 4.6Z" /></svg>
            </button>
        </div>
    @endif
</div>
