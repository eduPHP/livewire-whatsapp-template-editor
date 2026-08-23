<div>
    <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
        <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
            {{ __('Buttons') }}
            <span class="ml-1 text-xs font-normal text-neutral-500 dark:text-neutral-400">
                {{ __('up to 10 · quick replies are grouped automatically') }}
            </span>
        </h3>

        <span class="shrink-0 text-[11px] tabular-nums text-neutral-400 dark:text-neutral-500">
            {{ count($buttons) }}/10
        </span>
    </div>

    {{-- The type is chosen first and cannot be changed afterwards: each type
         carries different fields, and Meta treats them as different things. --}}
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach ($types as $value => $label)
            @php
                /**
                 * The product-list button needs a connected catalog: without
                 * one, an approved template would open an empty list. Disabled
                 * with the reason rather than hidden.
                 */
                $needsCatalog = $value === \WaTemplates\Enums\ButtonType::Mpm->value;
                $blocked = $needsCatalog && $catalogReason !== null;
            @endphp

            <x-wa-templates::form.button
                wire:key="add-button-{{ $value }}"
                wire:click="add('{{ $value }}')"
                :disabled="$atLimit || $blocked"
                :reason="$blocked ? $catalogReason : ($atLimit ? __('A template may have at most 10 buttons.') : null)"
            >
                +{{ $label }}
            </x-wa-templates::form.button>
        @endforeach
    </div>

    @forelse ($buttons as $index => $button)
        <div wire:key="button-{{ $index }}" class="mb-3 border-t border-neutral-200 pt-3 first:border-t-0 first:pt-0 last:mb-0 dark:border-neutral-800">
            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                <div class="flex min-w-0 items-baseline gap-2">
                    <span class="shrink-0 rounded bg-accent/10 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-accent-content">
                        {{ $types[$button['type']] ?? $button['type'] }}
                    </span>

                    {{-- What the recipient's tap actually does. The type names
                         alone do not say, and the difference matters: a quick
                         reply comes back as a message, a URL leaves WhatsApp. --}}
                    <span class="truncate text-[11px] text-neutral-500 dark:text-neutral-400">
                        {{ $behaviours[$button['type']] ?? '' }}
                    </span>
                </div>

                <div class="flex items-center gap-0.5">
                    <x-wa-templates::form.button
                        wire:click="move({{ $index }}, -1)"
                        :disabled="$loop->first"
                        :aria-label="__('Move up')"
                        class="!px-1.5"
                    >↑</x-wa-templates::form.button>

                    <x-wa-templates::form.button
                        wire:click="move({{ $index }}, 1)"
                        :disabled="$loop->last"
                        :aria-label="__('Move down')"
                        class="!px-1.5"
                    >↓</x-wa-templates::form.button>

                    <x-wa-templates::form.button variant="danger" wire:click="removeButton({{ $index }})">
                        {{ __('Remove') }}
                    </x-wa-templates::form.button>
                </div>
            </div>

            @if ($button['type'] === 'COPY_CODE')
                <x-wa-templates::form.field
                    :label="__('Code')"
                    :errors="$this->errorsFor('buttons.'.$index.'.example')"
                    :count="mb_strlen((string) $button['example'])"
                    :max="$copyCodeMax"
                >
                    <x-wa-templates::form.input
                        wire:model.live.debounce.400ms="buttons.{{ $index }}.example"
                        :invalid="$this->errorsFor('buttons.'.$index.'.example') !== []"
                        placeholder="CARIBE25"
                    />
                </x-wa-templates::form.field>
            @else
                <x-wa-templates::form.field
                    :label="__('Button text')"
                    :errors="$this->errorsFor('buttons.'.$index.'.text')"
                    :count="mb_strlen((string) $button['text'])"
                    :max="$button['type'] === 'QUICK_REPLY' ? $quickReplyMax : $labelMax"
                >
                    <x-wa-templates::form.input
                        wire:model.live.debounce.400ms="buttons.{{ $index }}.text"
                        :invalid="$this->errorsFor('buttons.'.$index.'.text') !== []"
                    />
                </x-wa-templates::form.field>
            @endif

            @if ($button['type'] === 'URL')
                <x-wa-templates::form.field
                    class="mt-2"
                    :label="__('URL')"
                    :hint="__('A variable may only be appended to the end.')"
                    :errors="$this->errorsFor('buttons.'.$index.'.url')"
                >
                    <x-wa-templates::form.input
                        wire:model.live.debounce.400ms="buttons.{{ $index }}.url"
                        :invalid="$this->errorsFor('buttons.'.$index.'.url') !== []"
                        :placeholder="'https://example.com/offers?code='.$variableExample"
                    />
                </x-wa-templates::form.field>

                @if (str_contains((string) $button['url'], '{{'))
                    <x-wa-templates::form.field
                        class="mt-2"
                        :label="__('Example value')"
                        :errors="$this->errorsFor('buttons.'.$index.'.example')"
                    >
                        <x-wa-templates::form.input
                            wire:model.live.debounce.400ms="buttons.{{ $index }}.example"
                            :invalid="$this->errorsFor('buttons.'.$index.'.example') !== []"
                        />
                    </x-wa-templates::form.field>
                @endif
            @endif

            @if ($button['type'] === 'PHONE_NUMBER')
                <x-wa-templates::form.field
                    class="mt-2"
                    :label="__('Number')"
                    :errors="$this->errorsFor('buttons.'.$index.'.phone_number')"
                >
                    <x-wa-templates::form.input
                        wire:model.live.debounce.400ms="buttons.{{ $index }}.phone_number"
                        :invalid="$this->errorsFor('buttons.'.$index.'.phone_number') !== []"
                        placeholder="+15550051310"
                    />
                </x-wa-templates::form.field>
            @endif

            @if ($button['type'] === 'QUICK_REPLY')
                <x-wa-templates::form.field
                    class="mt-2"
                    :label="__('Payload')"
                    :hint="__('Optional. Sent on the webhook instead of the label.')"
                >
                    <x-wa-templates::form.input wire:model.live.debounce.400ms="buttons.{{ $index }}.payload" />
                </x-wa-templates::form.field>
            @endif
        </div>
    @empty
        <p class="rounded-lg border border-dashed border-neutral-300 px-3 py-4 text-center text-[11px] text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">
            {{ __('No buttons. The message is sent as text alone.') }}
        </p>
    @endforelse

    @foreach ($this->errorsFor('buttons') as $error)
        <p class="mt-2 text-[11px] text-red-600 dark:text-red-400">{{ $error }}</p>
    @endforeach
</div>
