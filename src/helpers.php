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

if (! function_exists('wa_templates_trans')) {
    /**
     * Translate a message, falling back to its English sentence.
     *
     * The domain layer runs headless — `TemplateValidator` is plain PHP a host
     * can use with no application booted, and the README documents that. Calling
     * `__()` directly would break it: the helper resolves `translator` from the
     * container and dies with "Target class [translator] does not exist" when
     * there is no container to resolve from.
     *
     * So translation is treated as a capability like any other. With a
     * translator bound the message goes through `lang/pt_BR.json`, keyed by the
     * English sentence; without one the sentence itself is the output, which is
     * exactly what an untranslated key would have rendered anyway.
     *
     * @param  array<string,scalar>  $replace
     */
    function wa_templates_trans(string $message, array $replace = []): string
    {
        if (function_exists('app') && app()->bound('translator')) {
            return (string) __($message, $replace);
        }

        return wa_templates_replace($message, $replace);
    }
}

if (! function_exists('wa_templates_replace')) {
    /**
     * Substitute `:name` placeholders the way Laravel's translator would.
     *
     * Longest key first, so `:max_length` is not half-eaten by `:max`.
     *
     * @param  array<string,scalar>  $replace
     */
    function wa_templates_replace(string $message, array $replace = []): string
    {
        if ($replace === []) {
            return $message;
        }

        uksort($replace, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        foreach ($replace as $key => $value) {
            $message = str_replace(':'.$key, (string) $value, $message);
        }

        return $message;
    }
}

if (! function_exists('wa_templates_trans_choice')) {
    /**
     * Pluralise a message, falling back to the English branch of the string.
     *
     * Same reasoning as `wa_templates_trans()`. The fallback understands only
     * the `{1}singular|[2,*]plural` form this package actually uses, rather
     * than reimplementing Laravel's full selector.
     *
     * @param  array<string,scalar>  $replace
     */
    function wa_templates_trans_choice(string $message, int $number, array $replace = []): string
    {
        if (function_exists('app') && app()->bound('translator')) {
            return (string) trans_choice($message, $number, $replace);
        }

        $segments = explode('|', $message);
        $chosen = $number === 1 ? $segments[0] : ($segments[1] ?? $segments[0]);

        return wa_templates_replace(
            (string) preg_replace('/^(\{\d+\}|\[\d+,\s*\*?\d*\])/', '', $chosen),
            $replace,
        );
    }
}
