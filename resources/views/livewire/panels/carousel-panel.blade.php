<x-wa-templates::form.panel
    :title="__('Carousel')"
    :description="__('Up to 10 cards. Every card shares one structure.')"
    :invalid="$this->hasErrors()"
>
    <x-slot:actions>
        <x-wa-templates::form.button variant="danger" wire:click="remove">
            {{ __('Remove') }}
        </x-wa-templates::form.button>
    </x-slot:actions>

    {{--
        The schema is edited once and applied to every card. Meta rejects a
        carousel whose cards differ structurally, so the editor makes that state
        unreachable rather than merely validating against it.
    --}}
    <div class="rounded border border-neutral-200 bg-neutral-50 p-2 dark:border-neutral-800 dark:bg-neutral-800/40">
        <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
            {{ __('Card structure') }}
        </p>

        <x-wa-templates::form.field :label="__('Media')">
            <x-wa-templates::form.select wire:model.live="schema.format" wire:change="applySchema">
                <option value="IMAGE" @disabled($mediaReason !== null)>{{ __('Image') }}</option>
                <option value="VIDEO" @disabled($mediaReason !== null)>{{ __('Video') }}</option>
                <option value="PRODUCT" @disabled($catalogReason !== null)>{{ __('Catalog product') }}</option>
            </x-wa-templates::form.select>
        </x-wa-templates::form.field>

        @foreach ([$mediaReason, $catalogReason] as $reason)
            @if ($reason)
                <p class="mt-2 rounded border border-amber-200 bg-amber-50 px-2 py-1.5 text-[11px] text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                    {{ $reason }}
                </p>
            @endif
        @endforeach

        <div class="mt-2 flex flex-wrap items-center gap-1">
            @foreach ($schema['buttons'] as $index => $button)
                <span class="inline-flex items-center gap-1 rounded border border-neutral-300 bg-white px-1.5 py-1 text-[11px] dark:border-neutral-700 dark:bg-neutral-900">
                    {{ $cardTypes[$button['type']] ?? $button['type'] }}

                    <button type="button" wire:click="removeSchemaButton({{ $index }})" class="text-neutral-400 hover:text-red-600" aria-label="{{ __('Remove') }}">&times;</button>
                </span>
            @endforeach

            @if (count($schema['buttons']) < 2)
                @foreach ($cardTypes as $value => $label)
                    <x-wa-templates::form.button wire:click="addSchemaButton('{{ $value }}')">
                        + {{ $label }}
                    </x-wa-templates::form.button>
                @endforeach
            @endif
        </div>
    </div>

    <div class="mt-3 space-y-2">
        @foreach ($cards as $index => $card)
            <div wire:key="card-{{ $index }}" class="rounded border border-neutral-200 p-2 dark:border-neutral-800">
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-[11px] font-medium text-neutral-500 dark:text-neutral-400">
                        {{ __('Card :number', ['number' => $index + 1]) }}
                    </span>

                    <x-wa-templates::form.button variant="danger" wire:click="removeCard({{ $index }})">
                        {{ __('Remove') }}
                    </x-wa-templates::form.button>
                </div>

                @if ($card['format'] !== 'PRODUCT')
                    <x-wa-templates::form.field
                        :label="__('Asset handle')"
                        :errors="$this->errorsFor('carousel.cards.'.$index.'.header.handle')"
                    >
                        <x-wa-templates::form.input
                            wire:model.live.debounce.400ms="cards.{{ $index }}.handle"
                            :invalid="$this->errorsFor('carousel.cards.'.$index.'.header.handle') !== []"
                            placeholder="4::aW..."
                        />
                    </x-wa-templates::form.field>
                @endif

                @foreach ($card['buttons'] as $buttonIndex => $button)
                    <x-wa-templates::form.field
                        class="mt-2"
                        :label="($cardTypes[$button['type']] ?? $button['type']).' — '.__('label')"
                        :errors="$this->errorsFor('carousel.cards.'.$index.'.buttons.'.$buttonIndex.'.text')"
                    >
                        <x-wa-templates::form.input
                            wire:key="card-{{ $index }}-button-{{ $buttonIndex }}"
                            wire:model.live.debounce.400ms="cards.{{ $index }}.buttons.{{ $buttonIndex }}.text"
                            :invalid="$this->errorsFor('carousel.cards.'.$index.'.buttons.'.$buttonIndex.'.text') !== []"
                        />
                    </x-wa-templates::form.field>

                    @if ($button['type'] === 'URL')
                        <x-wa-templates::form.field
                            class="mt-1"
                            :errors="$this->errorsFor('carousel.cards.'.$index.'.buttons.'.$buttonIndex.'.url')"
                        >
                            <x-wa-templates::form.input
                                wire:model.live.debounce.400ms="cards.{{ $index }}.buttons.{{ $buttonIndex }}.url"
                                :invalid="$this->errorsFor('carousel.cards.'.$index.'.buttons.'.$buttonIndex.'.url') !== []"
                                :placeholder="'https://example.com/'.$variableExample"
                            />
                        </x-wa-templates::form.field>
                    @endif

                    @if ($button['type'] === 'PHONE_NUMBER')
                        <x-wa-templates::form.field class="mt-1">
                            <x-wa-templates::form.input
                                wire:model.live.debounce.400ms="cards.{{ $index }}.buttons.{{ $buttonIndex }}.phone_number"
                                placeholder="+15550051310"
                            />
                        </x-wa-templates::form.field>
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>

    <x-wa-templates::form.button
        class="mt-2"
        wire:click="addCard"
        :disabled="$atLimit"
        :reason="$atLimit ? __('A carousel may have at most :max cards.', ['max' => $maxCards]) : null"
    >
        + {{ __('Card') }}
    </x-wa-templates::form.button>

    @foreach ($this->errorsFor('carousel') as $error)
        <p class="mt-2 text-[11px] text-red-600 dark:text-red-400">{{ $error }}</p>
    @endforeach
</x-wa-templates::form.panel>
