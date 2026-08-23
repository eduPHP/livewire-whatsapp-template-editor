{{--
    A full-width informational band at the head of a step.

    Distinct from `form.field`'s hint, which explains one input; this explains
    the step itself — usually that nothing in it is required, which is the
    single most useful thing to say to an operator facing an empty optional
    form.

    Informational, not a warning: this is not a state anyone needs to fix.
--}}
<div {{ $attributes->merge(['class' => 'rounded-lg border border-sky-200 bg-sky-50 px-3 py-2.5 text-xs leading-relaxed text-sky-900 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-200']) }}>
    {{ $slot }}
</div>
