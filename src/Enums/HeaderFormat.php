<?php

namespace WaTemplates\Enums;

enum HeaderFormat: string
{
    case Text = 'TEXT';
    case Image = 'IMAGE';
    case Video = 'VIDEO';
    case Document = 'DOCUMENT';
    case Location = 'LOCATION';
    case Product = 'PRODUCT';

    public static function fromPayload(string $value): self
    {
        return self::from(strtoupper($value));
    }

    public function isMedia(): bool
    {
        return in_array($this, [self::Image, self::Video, self::Document], true);
    }
}
