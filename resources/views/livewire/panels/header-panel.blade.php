<div>
    @if ($mediaReason)
        <p class="mb-2 rounded border border-amber-200 bg-amber-50 px-2 py-1.5 text-[11px] text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
            {{ $mediaReason }}
        </p>
    @endif

    @if ($format === 'TEXT')
        <div>
            <x-wa-templates::form.field
                :label="__('Header text')"
                :errors="$this->errorsFor('header.text')"
                :count="mb_strlen($text)"
                :max="$max"
            >
                <x-wa-templates::form.input
                    wire:model.live.debounce.400ms="text"
                    :invalid="$this->errorsFor('header.text') !== []"
                    :placeholder="__('Seu pedido :syntax', ['syntax' => $variableSyntax])"
                />
            </x-wa-templates::form.field>

            {{-- A text header takes exactly one variable, so the button is
                 offered only while it has none. --}}
            @if ($keys === [])
                <div class="mt-2 flex justify-end">
                    <x-wa-templates::form.button wire:click="addVariable">
                        + {{ __('Variable') }}
                    </x-wa-templates::form.button>
                </div>
            @endif

            @foreach ($keys as $key)
                <div wire:key="header-var-{{ $key }}" class="mt-2 rounded border border-neutral-200 p-2 dark:border-neutral-800">
                    <div class="flex items-center gap-2">
                        <span class="shrink-0 font-mono text-[11px] text-neutral-400 dark:text-neutral-500">&#123;&#123;</span>

                        <input
                            type="text"
                            value="{{ $key }}"
                            wire:change="renameVariable('{{ $key }}', $event.target.value)"
                            class="min-w-0 flex-1 rounded border border-transparent bg-blue-50 px-1.5 py-0.5 font-mono text-[11px] text-blue-700 outline-none focus:border-blue-400 focus:bg-white dark:bg-blue-500/15 dark:text-blue-300 dark:focus:bg-neutral-900"
                            aria-label="{{ __('Variable name') }}"
                        >

                        <span class="shrink-0 font-mono text-[11px] text-neutral-400 dark:text-neutral-500">&#125;&#125;</span>

                        <button
                            type="button"
                            wire:click="removeVariable('{{ $key }}')"
                            class="shrink-0 rounded p-1 text-neutral-400 hover:text-red-600 dark:hover:text-red-400"
                            aria-label="{{ __('Remove variable') }}"
                        >
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M18.3 5.71 12 12l6.3 6.29-1.41 1.42L10.59 13.4 4.3 19.71 2.89 18.3 9.17 12 2.89 5.71 4.3 4.29l6.29 6.3 6.3-6.3 1.41 1.42Z" />
                            </svg>
                        </button>
                    </div>

                    <x-wa-templates::form.field
                        class="mt-1.5"
                        :label="__('Example for :name', ['name' => $key])"
                        :errors="$this->errorsFor('header.examples.'.$key)"
                    >
                        <x-wa-templates::form.input
                            wire:model.live.debounce.400ms="examples.{{ $key }}"
                            :invalid="$this->errorsFor('header.examples.'.$key) !== []"
                            :placeholder="__('Ex: João Silva')"
                        />
                    </x-wa-templates::form.field>
                </div>
            @endforeach
        </div>
    @elseif ($format === 'LOCATION')
        {{-- There is deliberately no coordinate input. The pin travels with each
             message, not with the template, so one approved template serves
             every destination — baking an address in would mean a separate
             approved template per place. --}}
        <p class="rounded border border-neutral-200 bg-neutral-50 px-2 py-1.5 text-[11px] text-neutral-600 dark:border-neutral-800 dark:bg-neutral-800/40 dark:text-neutral-400">
            {{ __('The pin, place name and address are supplied per message when you send it — not here. That way one approved template covers every destination.') }}
        </p>
    @else
        <div>
            <x-wa-templates::form.field
                :label="__('Example asset')"
                :hint="__('Uploaded to Meta to obtain a header handle. Not the same as a media id used for sending.')"
                :errors="$this->errorsFor('header.handle')"
            >
                <input
                    type="file"
                    wire:model="upload"
                    @disabled($mediaReason !== null)
                    class="block w-full text-xs text-neutral-600 file:mr-2 file:rounded-md file:border-0 file:bg-neutral-100 file:px-2.5 file:py-1.5 file:text-xs file:font-medium file:text-neutral-700 disabled:opacity-50 dark:text-neutral-300 dark:file:bg-neutral-800 dark:file:text-neutral-200"
                >
            </x-wa-templates::form.field>

            <div wire:loading wire:target="upload" class="mt-1 text-[11px] text-neutral-500">
                {{ __('Uploading…') }}
            </div>

            @if ($uploadError)
                <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $uploadError }}</p>
            @endif

            @if ($handle)
                <p class="mt-1 truncate font-mono text-[11px] text-neutral-500 dark:text-neutral-400" title="{{ $handle }}">
                    {{ __('Handle') }}: {{ $handle }}
                </p>
            @endif
        </div>
    @endif
</div>
