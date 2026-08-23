<div>
    <div class="mb-4">
        <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
            {{ __('Identification') }}
            <span class="ml-1 text-xs font-normal text-neutral-500 dark:text-neutral-400">
                {{ __('how the platform recognises the template — the recipient sees none of this') }}
            </span>
        </h3>
    </div>

    <div class="space-y-4">
        <x-wa-templates::form.field
            :label="__('Template name')"
            :hint="$nameHint"
            :errors="$this->errorsFor('name')"
        >
            <x-wa-templates::form.input
                wire:model.live.debounce.400ms="name"
                :invalid="$this->errorsFor('name') !== []"
                class="font-mono"
                placeholder="order_confirmation"
            />
        </x-wa-templates::form.field>

        <div class="grid gap-4 sm:grid-cols-2">
            {{-- Language and category are the two facts Meta treats as part of
                 the template's identity: a template is approved per language,
                 and its category decides both the pricing and the review it
                 goes through. Side by side because they are read together. --}}
            <x-wa-templates::form.field :label="__('Language')">
                <x-wa-templates::form.select wire:model.live="language">
                    {{-- An imported template may carry any of the ~80 codes
                         Meta accepts, and the picker deliberately lists only
                         the common ones. Its own code is added so opening such
                         a template does not silently re-language it to whatever
                         happens to sit first in the list. --}}
                    @unless (array_key_exists($language, $languages))
                        <option value="{{ $language }}">{{ $language }}</option>
                    @endunless

                    @foreach ($languages as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-wa-templates::form.select>
            </x-wa-templates::form.field>

            <x-wa-templates::form.field :label="__('Type')">
                <x-wa-templates::form.select wire:model.live="category">
                    @foreach ($categories as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-wa-templates::form.select>

                <p class="text-[11px] text-neutral-500 dark:text-neutral-400">
                    {{ $categoryHints[$category] ?? '' }}
                </p>
            </x-wa-templates::form.field>
        </div>
    </div>
</div>
