<?php

namespace WaTemplates\Contracts;

/**
 * A connected e-commerce catalog.
 *
 * Null until a catalog exists. Its absence disables the product components with
 * a stated reason rather than hiding them — an operator who cannot find MPM
 * should learn why, not conclude the feature does not exist.
 */
interface CatalogSource
{
    public function isAvailable(): bool;

    /**
     * @return list<CatalogProduct>
     */
    public function products(?string $search = null, int $limit = 50): array;

    public function catalogId(): ?string;
}
