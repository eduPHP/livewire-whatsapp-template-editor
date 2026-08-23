<?php

namespace WaTemplates\Capabilities;

/**
 * The parts of the editor a missing capability can switch off.
 *
 * Text and location headers, the body, the footer and the plain button types
 * are always available — they need nothing beyond the Message Templates API.
 */
enum Feature: string
{
    case TextHeader = 'text_header';
    case LocationHeader = 'location_header';
    case MediaHeader = 'media_header';
    case MediaCarousel = 'media_carousel';
    case ProductCarousel = 'product_carousel';
    case MultiProduct = 'multi_product';
    case LimitedTimeOffer = 'limited_time_offer';
}
