@php
    use WaTemplates\Capabilities\Feature;
    use WaTemplates\Enums\Step;

    $mediaReason = $capabilities->reasonAgainst(Feature::MediaHeader);
    $catalogReason = $capabilities->reasonAgainst(Feature::MultiProduct);
@endphp

<div
    x-data="{ preview: false }"
    class="wa-templates-editor grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]"
>
    <div class="min-w-0">
        {{--
            The step header. Numbers rather than checkmarks: a step is not
            "done" in a way this form can verify — an operator may legitimately
            leave buttons empty — so the marker states position, not progress.

            Every step is reachable by click, including ones ahead. The wizard
            orders the questions; it does not lock them.
        --}}
        <nav
            class="flex flex-wrap items-stretch gap-x-6 border-b border-neutral-200 dark:border-neutral-800"
            aria-label="{{ __('Template steps') }}"
        >
            @foreach ($steps as $case)
                @php
                    $isCurrent = $case === $currentStep;
                    $hasErrors = $this->stepErrors($case) !== [];
                @endphp

                <button
                    type="button"
                    wire:key="step-{{ $case->value }}"
                    wire:click="goToStep('{{ $case->value }}')"
                    aria-current="{{ $isCurrent ? 'step' : 'false' }}"
                    @class([
                        'group -mb-px flex items-center gap-2.5 border-b-2 py-3 text-left transition',
                        'border-accent' => $isCurrent,
                        'border-transparent hover:border-neutral-300 dark:hover:border-neutral-700' => ! $isCurrent,
                    ])
                >
                    <span @class([
                        'flex size-6 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold tabular-nums transition',
                        'bg-accent text-accent-foreground' => $isCurrent,
                        'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300' => ! $isCurrent && $hasErrors,
                        'bg-neutral-100 text-neutral-500 group-hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400 dark:group-hover:bg-neutral-700' => ! $isCurrent && ! $hasErrors,
                    ])>
                        {{ $case->position() }}
                    </span>

                    <span class="min-w-0">
                        <span @class([
                            'block truncate text-sm font-semibold',
                            'text-neutral-900 dark:text-neutral-100' => $isCurrent,
                            'text-neutral-600 dark:text-neutral-400' => ! $isCurrent,
                        ])>{{ $case->title() }}</span>

                        <span class="block truncate text-[11px] text-neutral-500 dark:text-neutral-500">
                            {{ $case->summary() }}
                        </span>
                    </span>
                </button>
            @endforeach
        </nav>

        {{-- Only the active step's fields are mounted. The panels themselves are
             unchanged: each still owns its slice and publishes upward, so which
             step shows a panel is purely a question of layout. --}}
        <div class="py-5">
            @if ($currentStep === Step::Identification)
                <livewire:wa-template-metadata-panel
                    :values="$state['meta'] ?? []"
                    :errors="$errors->errors"
                    :key="'meta'"
                />
            @elseif ($currentStep === Step::Content)
                <livewire:wa-template-body-panel
                    :values="$state['body'] ?? []"
                    :errors="$errors->errors"
                    :prefilled="$prefilled"
                    :key="'body'"
                />
            @elseif ($currentStep === Step::Buttons)
                <x-wa-templates::form.notice>
                    <span class="font-semibold">{{ __('Optional step') }}</span>
                    {{ __('A text-only template is the format most often approved. Buttons can be added later — but the template returns to the approval queue.') }}
                </x-wa-templates::form.notice>

                <div class="mt-4">
                    <livewire:wa-template-buttons-panel
                        :values="$state['buttons'] ?? []"
                        :errors="$errors->errors"
                        :catalog-reason="$catalogReason"
                        :key="'buttons'"
                    />
                </div>
            @elseif ($currentStep === Step::Framing)
                <x-wa-templates::form.notice>
                    <span class="font-semibold">{{ __('Optional step') }}</span>
                    {{ __('A header and footer frame the message. If the content already speaks for itself, skip straight to the review.') }}
                </x-wa-templates::form.notice>

                <div class="mt-4 space-y-4">
                    {{-- Unlike the other steps, the header is a choice of format
                         before it is a set of fields, so the panel is preceded
                         by the format switch that decides whether it exists at
                         all. "None" removes the component rather than emptying
                         it: `components` is positional and Meta reads an empty
                         header as a header. --}}
                    <x-wa-templates::form.header-format
                        :state="$state"
                        :media-reason="$mediaReason"
                    />

                    @if (isset($state['header']))
                        <livewire:wa-template-header-panel
                            :values="$state['header']"
                            :errors="$errors->errors"
                            :media-reason="$mediaReason"
                            :key="'header'"
                        />
                    @endif

                    @if (isset($state['footer']))
                        <livewire:wa-template-footer-panel
                            :values="$state['footer']"
                            :errors="$errors->errors"
                            :key="'footer'"
                        />
                    @else
                        <x-wa-templates::form.field
                            :label="__('Footer')"
                            :hint="__('A quiet line at the foot of the message · no variables · up to 60 characters')"
                        >
                            {{-- Rendered as an inert input rather than an "add
                                 footer" button: the mockup's operator types
                                 into it and the footer appears, which is one
                                 gesture instead of two. --}}
                            <x-wa-templates::form.input
                                wire:keydown.debounce.400ms="addComponent('footer')"
                                :placeholder="__('Reply STOP to unsubscribe')"
                            />
                        </x-wa-templates::form.field>
                    @endif

                    @if (isset($state['carousel']))
                        <livewire:wa-template-carousel-panel
                            :values="$state['carousel']"
                            :errors="$errors->errors"
                            :media-reason="$mediaReason"
                            :catalog-reason="$catalogReason"
                            :key="'carousel'"
                        />
                    @else
                        <div>
                            <x-wa-templates::form.button
                                wire:click="addComponent('carousel')"
                                :disabled="$mediaReason !== null && $catalogReason !== null"
                                :reason="$mediaReason ?? $catalogReason"
                            >
                                + {{ __('Carousel') }}
                            </x-wa-templates::form.button>
                        </div>
                    @endif

                    <x-wa-templates::form.review :draft="$draft" :state="$state" />
                </div>
            @endif
        </div>

        {{--
            The step footer. It states what the current step demands before the
            operator presses anything — the mockups put a sentence between Back
            and Continue precisely so a refusal to advance is never a mystery.
        --}}
        <div class="flex flex-wrap items-center gap-3 border-t border-neutral-200 pt-4 dark:border-neutral-800">
            <x-wa-templates::form.button
                wire:click="back"
                :disabled="$currentStep->previous() === null"
            >
                {{ __('Back') }}
            </x-wa-templates::form.button>

            <p class="min-w-0 flex-1 text-xs text-neutral-500 dark:text-neutral-400">
                @if ($stepErrors !== [])
                    <span class="text-red-600 dark:text-red-400">{{ $stepErrors[0] }}</span>
                @elseif ($currentStep->isOptional())
                    {{ __('Nothing on this step is required.') }}
                @elseif ($currentStep === Step::Identification)
                    {{ __('Valid name — you can continue.') }}
                @else
                    {{ __('The content is the last thing the platform requires.') }}
                @endif
            </p>

            @if ($currentStep->isOptional() && $currentStep->next() !== null)
                <x-wa-templates::form.button wire:click="goToStep('{{ $currentStep->next()->value }}')">
                    {{ __('Skip step') }}
                </x-wa-templates::form.button>
            @endif

            @if ($currentStep->next() !== null)
                <x-wa-templates::form.button
                    variant="primary"
                    wire:click="continue"
                    :disabled="$stepErrors !== []"
                >
                    {{ __('Continue') }}
                </x-wa-templates::form.button>
            @else
                {{-- The last step carries the submit. The host owns the action,
                     so this only asks for it: `template-submit` is what
                     `TemplateCreator` listens for. --}}
                <x-wa-templates::form.button
                    variant="primary"
                    x-on:click="$dispatch('template-submit')"
                    :disabled="$errors->fails()"
                    :reason="$errors->fails() ? __('Some steps still need attention.') : null"
                >
                    {{ __('Submit for approval') }}
                </x-wa-templates::form.button>
            @endif
        </div>
    </div>

    {{-- On a phone the preview is a sheet rather than a second column: stacked
         under the panels it sat a full scroll away from the fields it reflects,
         which is where a preview stops being one. --}}
    <button
        type="button"
        x-on:click="preview = true"
        x-show="! preview"
        class="fixed inset-x-4 bottom-4 z-30 flex items-center justify-center gap-2 rounded-full bg-neutral-900 px-4 py-3 text-sm font-medium text-white shadow-lg lg:hidden dark:bg-white dark:text-neutral-900"
    >
        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M7 2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm5 17.5a1 1 0 1 0 0-2 1 1 0 0 0 0 2ZM8 4v12h8V4H8Z" />
        </svg>
        {{ __('Preview') }}
    </button>

    {{-- Tap-away backdrop for the sheet. --}}
    <div
        x-show="preview"
        x-on:click="preview = false"
        x-cloak
        class="fixed inset-0 z-30 bg-black/40 lg:hidden"
    ></div>

    <div
        x-bind:class="preview ? '!block' : ''"
        class="hidden fixed inset-x-0 bottom-0 z-40 max-h-[85vh] overflow-y-auto rounded-t-2xl border-t border-neutral-200 bg-white p-4 shadow-2xl dark:border-neutral-800 dark:bg-neutral-900 lg:!block lg:z-auto lg:max-h-none lg:overflow-visible lg:rounded-none lg:border-0 lg:bg-transparent lg:p-0 lg:shadow-none lg:sticky lg:top-4 lg:self-start lg:dark:bg-transparent"
    >
        <div class="mb-3 flex items-center justify-between gap-2">
            <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                {{ __('Preview') }}
                <span class="ml-1 font-normal text-neutral-500 dark:text-neutral-400">
                    {{ __('with the example values') }}
                </span>
            </p>

            {{-- Sheet close, on small screens only. --}}
            <button
                type="button"
                x-on:click="preview = false"
                class="rounded p-1 text-neutral-400 hover:text-neutral-600 lg:hidden dark:hover:text-neutral-200"
                aria-label="{{ __('Close preview') }}"
            >
                <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M18.3 5.71 12 12l6.3 6.29-1.41 1.42L10.59 13.4 4.3 19.71 2.89 18.3 9.17 12 2.89 5.71 4.3 4.29l6.29 6.3 6.3-6.3 1.41 1.42Z" />
                </svg>
            </button>
        </div>

        {{--
            The visualizer is nested rather than inlined so it stays usable on
            its own: a connection screen showing an approved template wants the
            same bubble without any of the editor around it. Chrome-less here —
            beside the form the device shell was competing with the fields for
            width.
        --}}
        <livewire:wa-template-visualizer
            :template="$draft->components()"
            :chrome="false"
            :contact-name="__('Your business')"
            :key="'preview-'.md5(json_encode($draft->components()))"
        />

        {{-- One line naming what the preview is showing, so an operator does not
             read the example values as the message everyone receives. --}}
        <p class="mt-3 border-t border-dashed border-neutral-200 pt-3 text-[11px] leading-relaxed text-neutral-500 dark:border-neutral-800 dark:text-neutral-400">
            @if ($currentStep === Step::Buttons)
                {{ __('Buttons sit flush at the foot of the bubble, in the order you create them.') }}
            @elseif ($currentStep === Step::Framing)
                {{ __('The header sits at the top of the bubble, the footer in grey just above the time.') }}
            @else
                {{ __('The preview uses each variable\'s example value. This is how the message arrives.') }}
            @endif
        </p>

        @unless ($errors->passes())
            <div class="mt-3 rounded-lg border border-red-200 bg-red-50 p-2 dark:border-red-500/30 dark:bg-red-500/10">
                <p class="text-[11px] font-medium text-red-800 dark:text-red-300">
                    {{ __('This template would be rejected:') }}
                </p>

                <ul class="mt-1 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li class="text-[11px] text-red-700 dark:text-red-400">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endunless
    </div>
</div>
