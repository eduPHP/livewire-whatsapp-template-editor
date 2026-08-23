<?php

namespace WaTemplates\Draft\Buttons;

use InvalidArgumentException;

final class ButtonFactory
{
    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromPayload(array $payload): Button
    {
        $type = strtoupper((string) ($payload['type'] ?? ''));

        return match ($type) {
            'QUICK_REPLY' => QuickReply::fromPayload($payload),
            'URL' => Url::fromPayload($payload),
            'PHONE_NUMBER' => PhoneNumber::fromPayload($payload),
            'COPY_CODE' => CopyCode::fromPayload($payload),
            'MPM' => Mpm::fromPayload($payload),
            'SPM' => Spm::fromPayload($payload),
            default => throw new InvalidArgumentException("Unsupported button type [{$type}]."),
        };
    }
}
