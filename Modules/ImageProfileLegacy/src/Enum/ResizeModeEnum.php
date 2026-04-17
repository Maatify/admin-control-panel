<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/image-profile
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-04-17
 */

declare(strict_types=1);

namespace Maatify\ImageProfileLegacy\Enum;

/**
 * Controls how an image is resized when the source aspect ratio differs
 * from the target dimensions.
 *
 * Fit    — scale to fit entirely within the bounding box; no cropping;
 *          may produce letterboxing / pillarboxing if aspect ratios differ.
 *
 * Fill   — scale to cover the entire bounding box then centre-crop to exact
 *          dimensions; no empty space; part of the image may be lost.
 *
 * Stretch — ignore the source aspect ratio and force the image to exactly
 *           the requested width and height; may distort the image.
 */
enum ResizeModeEnum: string
{
    /** Scale uniformly to fit inside the bounding box — no crop, no distortion. */
    case Fit = 'fit';

    /** Scale to fill, then centre-crop to exact dimensions — no empty space. */
    case Fill = 'fill';

    /** Force exact dimensions regardless of aspect ratio — may distort. */
    case Stretch = 'stretch';
}
