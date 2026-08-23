@props(['keyboard' => false])

{{--
    Owns every piece of frame-local state: the keyboard toggle, the active
    carousel card, the open sheet, and the mocked quick-reply conversation.

    All of it is Alpine rather than server state — a `wire:model` round trip per
    swipe would be visibly laggy, and none of it is anything the host needs to
    know about. The wrapper sits outside the frame so the preview controls,
    which are deliberately not part of the phone, share the same scope.

    `reset()` runs whenever the draft changes, so a template edited after a
    button was tapped does not keep a mocked reply to a button that no longer
    exists.
--}}
<div
    x-data="{
        keyboard: @js($keyboard),
        sheet: null,
        replies: [],
        card: 0,
        tapReply(label, payload) {
            this.replies.push({ label, payload, id: this.replies.length })
            this.$nextTick(() => this.scrollToEnd())
        },
        openSheet(detail) {
            this.sheet = this.sheet?.label === detail.label ? null : detail
        },
        reset() {
            this.replies = []
            this.sheet = null
        },
        scrollToEnd() {
            const chat = this.$refs.chat
            if (chat) { chat.scrollTop = chat.scrollHeight }
        },
    }"
    x-on:draft-changed.window="reset()"
    {{ $attributes }}
>
    {{ $slot }}
</div>
