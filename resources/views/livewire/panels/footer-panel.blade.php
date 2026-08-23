<x-wa-templates::form.panel
    :title="__('Footer')"
    :description="__('Small print under the body. No variables.')"
    :invalid="$this->hasErrors()"
>
    <x-slot:actions>
        <x-wa-templates::form.button variant="danger" wire:click="remove">
            {{ __('Remove') }}
        </x-wa-templates::form.button>
    </x-slot:actions>

    <x-wa-templates::form.field
        :errors="$this->errorsFor('footer.text')"
        :count="mb_strlen($text)"
        :max="$max"
    >
        <x-wa-templates::form.input
            wire:model.live.debounce.400ms="text"
            :invalid="$this->errorsFor('footer.text') !== []"
            :placeholder="__('Reply STOP to unsubscribe')"
        />
    </x-wa-templates::form.field>
</x-wa-templates::form.panel>
