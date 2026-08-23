<x-wa-templates::form.panel :title="__('Template details')" :invalid="$this->hasErrors()">
    <div class="grid gap-3 sm:grid-cols-2">
        <x-wa-templates::form.field
            :label="__('Name')"
            :hint="$nameHint"
            :errors="$this->errorsFor('name')"
            class="sm:col-span-2"
        >
            <x-wa-templates::form.input
                wire:model.live.debounce.400ms="name"
                :invalid="$this->errorsFor('name') !== []"
                placeholder="order_confirmation"
            />
        </x-wa-templates::form.field>

        <x-wa-templates::form.field :label="__('Language')">
            <x-wa-templates::form.input wire:model.live.debounce.400ms="language" placeholder="en_US" />
        </x-wa-templates::form.field>

        <x-wa-templates::form.field :label="__('Category')">
            <x-wa-templates::form.select wire:model.live="category" :options="$categories" />
        </x-wa-templates::form.field>

        <x-wa-templates::form.field
            :label="__('Parameter style')"
            :hint="__('Named parameters read better; numbered ones are what most existing templates use.')"
            class="sm:col-span-2"
        >
            <x-wa-templates::form.select wire:model.live="parameter_format">
                <option value="">{{ __('Match the body text') }}</option>

                @foreach ($formats as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-wa-templates::form.select>
        </x-wa-templates::form.field>
    </div>
</x-wa-templates::form.panel>
