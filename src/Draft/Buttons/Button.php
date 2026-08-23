<?php

namespace WaTemplates\Draft\Buttons;

use WaTemplates\Draft\Dialect;
use WaTemplates\Enums\ButtonType;

interface Button
{
    public function type(): ButtonType;

    /**
     * @return array<string,mixed>
     */
    public function toPayload(Dialect $dialect): array;

    /**
     * The label the recipient sees on the button face.
     */
    public function label(): string;
}
