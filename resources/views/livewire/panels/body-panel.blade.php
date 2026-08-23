<x-wa-templates::form.panel
    :title="__('Body')"
    :description="__('The message itself. Required.')"
    :invalid="$this->hasErrors()"
>
    <x-wa-templates::form.field
        :errors="$this->errorsFor('body.text')"
        :count="mb_strlen($text)"
        :max="$max"
    >
        <x-wa-templates::form.textarea
            wire:model.live.debounce.400ms="text"
            :invalid="$this->errorsFor('body.text') !== []"
            rows="5"
            :placeholder="__('Olá, :syntax', ['syntax' => $variableSyntax])"
        />
    </x-wa-templates::form.field>

    <div class="mt-2 flex items-start justify-between gap-2">
        <p class="text-[11px] text-neutral-500 dark:text-neutral-400">
            {{ __('Variables are written as :syntax.', ['syntax' => $variableSyntax]) }}
        </p>

        @if ($available !== [])
            {{-- Known variables are offered by name so an operator picks one
                 rather than inventing a spelling the sending code will never
                 match. --}}
            <div x-data="{ open: false }" class="relative shrink-0">
                <x-wa-templates::form.button x-on:click="open = ! open">
                    + {{ __('Variable') }}
                    <svg class="size-3" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 15.5 5.5 9h13L12 15.5Z" />
                    </svg>
                </x-wa-templates::form.button>

                <div
                    x-show="open"
                    x-on:click.outside="open = false"
                    x-cloak
                    class="absolute right-0 z-20 mt-1 w-60 overflow-hidden rounded-md border border-neutral-200 bg-white shadow-lg dark:border-neutral-700 dark:bg-neutral-900"
                >
                    <p class="border-b border-neutral-200 px-2 py-1.5 text-[10px] font-semibold uppercase tracking-wide text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">
                        {{ __('Filled automatically') }}
                    </p>

                    @foreach ($available as $name => $sample)
                        <button
                            type="button"
                            wire:click="addPrefilledVariable('{{ $name }}')"
                            x-on:click="open = false"
                            class="flex w-full items-center justify-between gap-2 px-2 py-1.5 text-left hover:bg-neutral-50 dark:hover:bg-neutral-800"
                        >
                            <span class="font-mono text-[11px] text-blue-700 dark:text-blue-300">{{ $name }}</span>
                            <span class="truncate text-[10px] text-neutral-400 dark:text-neutral-500">{{ $sample }}</span>
                        </button>
                    @endforeach

                    <button
                        type="button"
                        wire:click="addVariable"
                        x-on:click="open = false"
                        class="w-full border-t border-neutral-200 px-2 py-1.5 text-left text-[11px] text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800"
                    >
                        {{ __('Custom variable…') }}
                    </button>
                </div>
            </div>
        @else
            <x-wa-templates::form.button wire:click="addVariable" class="shrink-0">
                + {{ __('Variable') }}
            </x-wa-templates::form.button>
        @endif
    </div>

    @if ($keys !== [])
        {{--
            One example row per distinct variable, derived from the text rather
            than managed by hand — Meta wants exactly one example each, so a
            separate list would only be a way to get it wrong.

            Meta calls these mandatory and means it: a template whose variables
            carry no realistic example is rejected at review, so the requirement
            is stated rather than left to be discovered.
        --}}
        <div class="mt-3 border-t border-neutral-200 pt-3 dark:border-neutral-800">
            <div class="mb-1 flex items-center gap-2">
                <p class="text-xs font-semibold text-neutral-900 dark:text-neutral-100">
                    {{ __('Variable examples') }}
                </p>

                <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">
                    {{ __('Required') }}
                </span>
            </div>

            <p class="mb-2 text-[11px] text-neutral-500 dark:text-neutral-400">
                {{ __('Meta requires a realistic example of what each variable will carry before it will approve the template.') }}
            </p>

            <div class="space-y-2">
                @foreach ($keys as $key)
                    <div wire:key="body-var-{{ $key }}" class="rounded border border-neutral-200 p-2 dark:border-neutral-800">
                        <div class="flex items-center gap-2">
                            {{-- The name is editable in place: a variable named
                                 when the message was half-written rarely still
                                 reads correctly once it is finished. --}}
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

                        @error('rename.'.$key)
                            <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        @if ($this->isPrefilled($key))
                            {{-- No example to ask for: the app knows what this
                                 carries and supplies Meta's required sample
                                 itself. --}}
                            <p class="mt-1.5 text-[11px] text-neutral-500 dark:text-neutral-400">
                                {{ __('Filled automatically when the message is sent. Example: :sample', ['sample' => $prefilled[$key]]) }}
                            </p>
                        @else
                            <x-wa-templates::form.field
                                class="mt-1.5"
                                :label="__('Example for :name', ['name' => $key])"
                                :errors="$this->errorsFor('body.examples.'.$key)"
                            >
                                <x-wa-templates::form.input
                                    wire:model.live.debounce.400ms="examples.{{ $key }}"
                                    :invalid="$this->errorsFor('body.examples.'.$key) !== []"
                                    :placeholder="__('Ex: João Silva')"
                                />
                            </x-wa-templates::form.field>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-wa-templates::form.panel>
