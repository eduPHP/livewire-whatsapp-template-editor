<?php

namespace WaTemplates\Enums;

enum Category: string
{
    case Marketing = 'MARKETING';
    case Utility = 'UTILITY';
    case Authentication = 'AUTHENTICATION';

    public static function fromPayload(string $value): self
    {
        return self::from(strtoupper($value));
    }
}
