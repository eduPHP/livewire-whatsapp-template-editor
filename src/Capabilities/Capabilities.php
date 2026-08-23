<?php

namespace WaTemplates\Capabilities;

use WaTemplates\Contracts\CatalogSource;
use WaTemplates\Contracts\MediaUploader;

/**
 * What this installation can actually build.
 *
 * An absent capability disables a component **with a stated reason** rather
 * than hiding it. A hidden feature reads as one that does not exist; a disabled
 * one with "no product catalog is connected" tells the operator what to fix.
 */
final readonly class Capabilities
{
    public function __construct(
        private ?CatalogSource $catalog = null,
        private ?MediaUploader $uploader = null,
    ) {}

    public function hasCatalog(): bool
    {
        return $this->catalog !== null && $this->catalog->isAvailable();
    }

    public function hasUploader(): bool
    {
        return $this->uploader !== null;
    }

    public function catalog(): ?CatalogSource
    {
        return $this->hasCatalog() ? $this->catalog : null;
    }

    public function uploader(): ?MediaUploader
    {
        return $this->uploader;
    }

    public function allows(Feature $feature): bool
    {
        return $this->reasonAgainst($feature) === null;
    }

    /**
     * Why `$feature` is unavailable, or null when it is available.
     */
    public function reasonAgainst(Feature $feature): ?string
    {
        return match ($feature) {
            Feature::MediaHeader,
            Feature::MediaCarousel,
            Feature::LimitedTimeOffer => $this->hasUploader()
                ? null
                : 'No media uploader is configured, so header images and videos cannot be uploaded.',
            Feature::MultiProduct,
            Feature::ProductCarousel => $this->hasCatalog()
                ? null
                : 'No product catalog is connected to this WhatsApp Business account.',
            default => null,
        };
    }
}
