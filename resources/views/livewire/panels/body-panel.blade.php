<div>
    <div class="mb-3 flex items-baseline justify-between gap-3">
        <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
            {{ __('Content') }}
            <span class="ml-1 text-xs font-normal text-neutral-500 dark:text-neutral-400">
                {{ __('the message text — the only requirement besides the name') }}
            </span>
        </h3>

        <span @class([
            'shrink-0 text-[11px] tabular-nums',
            'text-neutral-400 dark:text-neutral-500' => mb_strlen($text) <= $max,
            'text-red-600 dark:text-red-400' => mb_strlen($text) > $max,
        ])>{{ mb_strlen($text) }}/{{ $max }}</span>
    </div>

    <x-wa-templates::form.field :errors="$this->errorsFor('body.text')">
        <x-wa-templates::form.textarea
            id="wa-body-text"
            wire:model.live.debounce.400ms="text"
            :invalid="$this->errorsFor('body.text') !== []"
            rows="6"
            :placeholder="__('Olá, :syntax', ['syntax' => $variableSyntax])"
        />
    </x-wa-templates::form.field>

    <div class="mt-2 flex flex-wrap items-center gap-2">
        {{-- Emoji are worth a control of their own: they are common in these
             messages and awkward to type on a desktop keyboard. --}}
        <x-wa-templates::form.emoji-picker for="wa-body-text" />
    </div>

    {{--
        One example row per distinct variable, derived from the text rather than
        managed by hand — Meta wants exactly one example each, so a separate
        list would only be a way to get it wrong.
    --}}
    <div class="mt-5 border-t border-neutral-200 pt-4 dark:border-neutral-800">
        <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
            <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                {{ __('Variables') }}
                <span class="ml-1 text-xs font-normal text-neutral-500 dark:text-neutral-400">
                    {{ __('swapped for real values at send time') }}
                </span>
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
                                <span class="font-mono text-[11px] text-accent-content">{{ $name }}</span>
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
            <div class="space-y-2">
                @foreach ($keys as $key)
                    {{-- Name, example and remove on one line: the name is
                         editable in place because a variable named when the
                         message was half-written rarely still reads correctly
                         once it is finished. --}}
                    @php
                        /** Blade cannot build a literal {{…}} inline; see .ai/rules. */
                        $inText = str_contains($text, sprintf('{{%s}}', $key));
                    @endphp

                    <div wire:key="body-var-{{ $key }}">
                        <div class="flex flex-wrap items-center gap-2 sm:flex-nowrap">
                            <input
                                type="text"
                                value="{{ $key }}"
                                @readonly($this->isPrefilled($key))
                                wire:change="renameVariable('{{ $key }}', $event.target.value)"
                                class="w-40 shrink-0 rounded border border-transparent bg-accent/10 px-2 py-1.5 font-mono text-[11px] text-accent-content outline-none read-only:opacity-70 focus:border-accent focus:bg-white dark:focus:bg-neutral-900"
                                aria-label="{{ __('Variable name') }}"
                            >

                            @if ($this->isPrefilled($key))
                                {{-- No example to ask for: the app knows what
                                     this carries and supplies Meta's required
                                     sample itself. --}}
                                <p class="min-w-0 flex-1 text-[11px] text-neutral-500 dark:text-neutral-400">
                                    {{ __('Filled automatically when the message is sent. Example: :sample', ['sample' => $prefilled[$key]]) }}
                                </p>
                            @else
                                <label class="shrink-0 text-[11px] text-neutral-500 dark:text-neutral-400">
                                    {{ __('example') }}
                                </label>

                                <x-wa-templates::form.input
                                    wire:model.live.debounce.400ms="examples.{{ $key }}"
                                    :invalid="$this->errorsFor('body.examples.'.$key) !== []"
                                    class="min-w-0 flex-1"
                                    :placeholder="__('Ex: João Silva')"
                                    :aria-label="__('Example for :name', ['name' => $key])"
                                />
                            @endif

                            {{-- States whether the variable is actually used.
                                 Meta rejects a template that declares one and
                                 never writes it, which is otherwise only
                                 discovered at review. --}}
                            <span class="shrink-0 text-[11px] text-neutral-400 dark:text-neutral-500">
                                {{ $inText ? __('in text') : __('not in text') }}
                            </span>

                            <button
                                type="button"
                                wire:click="removeVariable('{{ $key }}')"
                                class="shrink-0 rounded px-1.5 py-1 text-[11px] text-neutral-500 hover:text-red-600 dark:text-neutral-400 dark:hover:text-red-400"
                            >
                                {{ __('Remove') }}
                            </button>
                        </div>

                        @error('rename.'.$key)
                            <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        @foreach ($this->errorsFor('body.examples.'.$key) as $error)
                            <p class="mt-1 text-[11px] text-red-600 dark:text-red-400">{{ $error }}</p>
                        @endforeach
                    </div>
                @endforeach
            </div>

            {{-- Meta calls the examples mandatory and means it: a template whose
                 variables carry no realistic example is rejected at review, so
                 the requirement is stated rather than left to be discovered. --}}
            <p class="mt-3 border-t border-dashed border-neutral-200 pt-2 text-[11px] leading-relaxed text-neutral-500 dark:border-neutral-800 dark:text-neutral-400">
                {{ __('The example travels with the approval request and is what the preview shows. A variable declared but never used in the text fails review.') }}
            </p>
        @else
            <p class="text-[11px] text-neutral-500 dark:text-neutral-400">
                {{ __('No variables. The message is sent exactly as written above.') }}
            </p>
        @endif

        {{--
            The format switch is here rather than on the identification step
            because it is a property of how the text is written, and it is the
            text the operator is looking at while deciding.
        --}}
        <div class="mt-4 flex flex-wrap items-center gap-2">
            <span class="text-[11px] font-medium text-neutral-700 dark:text-neutral-300">
                {{ __('Variable format') }}
            </span>

            <x-wa-templates::form.button
                wire:click="useNamedVariables"
                :variant="$named ? 'selected' : 'secondary'"
                class="font-mono"
            >@php echo e(sprintf('{{%s}}', __('name'))) @endphp</x-wa-templates::form.button>

            <x-wa-templates::form.button
                wire:click="useNumberedVariables"
                :variant="$named ? 'secondary' : 'selected'"
                class="font-mono"
            >{{ $numberedExample }}</x-wa-templates::form.button>

            <span class="text-[11px] text-neutral-500 dark:text-neutral-400">
                {{ __('named ones read better and survive reordering') }}
            </span>
        </div>
    </div>
</div>
