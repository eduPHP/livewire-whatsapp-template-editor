<div class="wa-templates-visualizer">
    <x-wa-templates::phone.preview :keyboard="$keyboard">
        {{--
            Preview controls sit outside the frame: they are not part of the
            phone, and putting them inside would misrepresent what the recipient
            sees.
        --}}
        <div class="mx-auto mb-3 flex max-w-[22rem] items-center justify-between gap-2">
            <label class="inline-flex cursor-pointer items-center gap-2 text-xs text-neutral-600 dark:text-neutral-400">
                <input
                    type="checkbox"
                    x-model="keyboard"
                    class="size-3.5 rounded border-neutral-300 text-[#00a884] focus:ring-[#00a884] dark:border-neutral-600 dark:bg-neutral-800"
                >
                {{ __('Show keyboard') }}
            </label>

            <button
                type="button"
                x-on:click="reset()"
                x-show="replies.length || sheet"
                x-cloak
                class="rounded px-2 py-1 text-xs text-neutral-600 underline-offset-2 hover:underline dark:text-neutral-400"
            >
                {{ __('Reset conversation') }}
            </button>
        </div>

        <x-wa-templates::phone.frame :contact-name="$contactName">
            <x-wa-templates::phone.bubble :preview="$preview" />

            <x-wa-templates::phone.reply />

            <x-slot:sheet>
                <x-wa-templates::phone.sheet />
            </x-slot:sheet>
        </x-wa-templates::phone.frame>
    </x-wa-templates::phone.preview>
</div>
