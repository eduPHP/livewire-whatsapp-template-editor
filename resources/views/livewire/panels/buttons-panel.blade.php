<x-wa-templates::form.panel
    :title="__('Buttons')"
    :description="__('Up to 10. Quick replies must sit together.')"
    :invalid="$this->hasErrors()"
>
    <x-slot:actions>
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
                wire:click="add('{{ $value }}')"
                :disabled="$atLimit || $blocked"
                :reason="$blocked ? $catalogReason : ($atLimit ? __('A template may have at most 10 buttons.') : null)"
            >
                + {{ $label }}
            </x-wa-templates::form.button>
        @endforeach
    </x-slot:actions>

    @forelse ($buttons as $index => $button)
        <div wire:key="button-{{ $index }}" class="mb-2 rounded border border-neutral-200 p-2 last:mb-0 dark:border-neutral-800">
            <div class="mb-2 flex items-center justify-between gap-2">
                <span class="text-[11px] font-medium uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                    {{ $types[$button['type']] ?? $button['type'] }}
                </span>

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
                    :label="__('Label')"
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
        <p class="text-[11px] text-neutral-500 dark:text-neutral-400">{{ __('No buttons yet.') }}</p>
    @endforelse

    @foreach ($this->errorsFor('buttons') as $error)
        <p class="mt-2 text-[11px] text-red-600 dark:text-red-400">{{ $error }}</p>
    @endforeach
</x-wa-templates::form.panel>
