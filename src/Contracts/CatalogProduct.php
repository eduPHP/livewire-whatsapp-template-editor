<?php

namespace WaTemplates\Contracts;

final readonly class CatalogProduct
{
    public function __construct(
        public string $retailerId,
        public string $name,
        public ?string $price = null,
        public ?string $imageUrl = null,
    ) {}
}
