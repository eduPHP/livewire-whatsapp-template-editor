<div>
    <div class="mb-1.5 flex flex-wrap items-baseline justify-between gap-2">
        <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
            {{ __('Footer') }}
            <span class="ml-1 text-xs font-normal text-neutral-500 dark:text-neutral-400">
                {{ __('A quiet line at the foot of the message · no variables · up to 60 characters') }}
            </span>
        </p>

        <x-wa-templates::form.button variant="danger" wire:click="remove" class="shrink-0">
            {{ __('Remove') }}
        </x-wa-templates::form.button>
    </div>

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
</div>
