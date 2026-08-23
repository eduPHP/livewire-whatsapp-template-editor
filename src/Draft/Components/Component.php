<?php

namespace WaTemplates\Draft\Components;

use WaTemplates\Draft\Dialect;

interface Component
{
    /**
     * This component's slice of `components`.
     *
     * @return array<string,mixed>
     */
    public function toPayload(Dialect $dialect): array;

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromPayload(array $payload): self;
}
