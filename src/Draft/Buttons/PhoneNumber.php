<?php

namespace WaTemplates\Draft\Buttons;

use WaTemplates\Draft\Dialect;
use WaTemplates\Enums\ButtonType;

final class PhoneNumber implements Button
{
    public function __construct(
        public string $text = '',
        public string $phoneNumber = '',
    ) {}

    public function type(): ButtonType
    {
        return ButtonType::PhoneNumber;
    }

    public function label(): string
    {
        return $this->text;
    }

    public function toPayload(Dialect $dialect): array
    {
        return [
            'type' => $dialect->keyword('phone_number'),
            'text' => $this->text,
            'phone_number' => $this->phoneNumber,
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            text: (string) ($payload['text'] ?? ''),
            phoneNumber: (string) ($payload['phone_number'] ?? ''),
        );
    }
}
