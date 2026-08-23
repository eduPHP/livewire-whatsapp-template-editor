<?php

use Illuminate\Support\HtmlString;

if (! function_exists('wa_templates_format')) {
    /**
     * WhatsApp's own inline markup, as the recipient would see it rendered.
     *
     * `*bold*`, `_italic_`, `~strikethrough~` and ```monospace```. Escaping
     * happens first, so template text can never inject markup of its own — the
     * formatting is applied to already-safe output.
     */
    function wa_templates_format(string $text): HtmlString
    {
        $escaped = e($text);

        $patterns = [
            '/```(.+?)```/s' => '<code class="rounded bg-black/5 px-1 font-mono text-[12px] dark:bg-white/10">$1</code>',
            '/(?<!\w)\*(.+?)\*(?!\w)/s' => '<strong>$1</strong>',
            '/(?<!\w)_(.+?)_(?!\w)/s' => '<em>$1</em>',
            '/(?<!\w)~(.+?)~(?!\w)/s' => '<del>$1</del>',
        ];

        return new HtmlString(
            preg_replace(array_keys($patterns), array_values($patterns), $escaped)
        );
    }
}
