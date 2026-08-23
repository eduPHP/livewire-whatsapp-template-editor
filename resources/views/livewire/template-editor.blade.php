@php
    use WaTemplates\Capabilities\Feature;

    $mediaReason = $capabilities->reasonAgainst(Feature::MediaHeader);
    $catalogReason = $capabilities->reasonAgainst(Feature::MultiProduct);
@endphp

<div
    x-data="{ preview: false }"
    class="wa-templates-editor grid gap-4 lg:grid-cols-[minmax(0,1fr)_22rem]"
>
    <div class="space-y-3 pb-16 lg:pb-0">
        <livewire:wa-template-metadata-panel
            :values="$state['meta'] ?? []"
            :errors="$errors->errors"
            :key="'meta'"
        />

        @if (isset($state['header']))
            <livewire:wa-template-header-panel
                :values="$state['header']"
                :errors="$errors->errors"
                :media-reason="$mediaReason"
                :key="'header'"
            />
        @endif

        <livewire:wa-template-body-panel
            :values="$state['body'] ?? []"
            :errors="$errors->errors"
            :prefilled="$prefilled"
            :key="'body'"
        />

        @if (isset($state['footer']))
            <livewire:wa-template-footer-panel
                :values="$state['footer']"
                :errors="$errors->errors"
                :key="'footer'"
            />
        @endif

        <livewire:wa-template-buttons-panel
            :values="$state['buttons'] ?? []"
            :errors="$errors->errors"
            :catalog-reason="$catalogReason"
            :key="'buttons'"
        />

        @if (isset($state['carousel']))
            <livewire:wa-template-carousel-panel
                :values="$state['carousel']"
                :errors="$errors->errors"
                :media-reason="$mediaReason"
                :catalog-reason="$catalogReason"
                :key="'carousel'"
            />
        @endif

        {{-- Optional components are added rather than always present. --}}
        <div class="flex flex-wrap gap-1.5">
            @unless (isset($state['header']))
                <x-wa-templates::form.button wire:click="addComponent('header')">
                    + {{ __('Header') }}
                </x-wa-templates::form.button>
            @endunless

            @unless (isset($state['footer']))
                <x-wa-templates::form.button
                    wire:click="addComponent('footer')"
                    :disabled="isset($state['limited_time_offer'])"
                    :reason="isset($state['limited_time_offer']) ? __('A limited-time offer template cannot have a footer.') : null"
                >
                    + {{ __('Footer') }}
                </x-wa-templates::form.button>
            @endunless

            @unless (isset($state['carousel']))
                <x-wa-templates::form.button
                    wire:click="addComponent('carousel')"
                    :disabled="$mediaReason !== null && $catalogReason !== null"
                    :reason="$mediaReason ?? $catalogReason"
                >
                    + {{ __('Carousel') }}
                </x-wa-templates::form.button>
            @endunless
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
        class="hidden fixed inset-x-0 bottom-0 z-40 max-h-[85vh] overflow-y-auto rounded-t-2xl border-t border-neutral-200 bg-white p-4 shadow-2xl dark:border-neutral-800 dark:bg-neutral-900 lg:!block lg:static lg:z-auto lg:max-h-none lg:overflow-visible lg:rounded-none lg:border-0 lg:bg-transparent lg:p-0 lg:shadow-none lg:sticky lg:top-4 lg:self-start lg:dark:bg-transparent"
    >
        {{-- Sheet handle and close, on small screens only. --}}
        <div class="mb-3 flex items-center justify-between lg:hidden">
            <span class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                {{ __('Preview') }}
            </span>

            <button
                type="button"
                x-on:click="preview = false"
                class="rounded p-1 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200"
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
            same bubble without any of the editor around it.
        --}}
        <livewire:wa-template-visualizer
            :template="$draft->components()"
            :key="'preview-'.md5(json_encode($draft->components()))"
        />

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
