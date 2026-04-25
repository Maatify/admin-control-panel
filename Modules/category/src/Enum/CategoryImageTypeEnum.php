<?php

declare(strict_types=1);

namespace Maatify\Category\Enum;

/**
 * The four supported image slots per category.
 *
 * Each slot can hold one image path per language, giving a matrix of:
 *   (category_id, image_type, language_id) → path
 *
 *  image         → default / general-purpose image
 *  mobile_image  → optimised for mobile renderers
 *  api_image     → served by the consumer API (e.g. mobile app)
 *  website_image → displayed on the public-facing website
 */
enum CategoryImageTypeEnum: string
{
    case Image        = 'image';
    case MobileImage  = 'mobile_image';
    case ApiImage     = 'api_image';
    case WebsiteImage = 'website_image';
}

