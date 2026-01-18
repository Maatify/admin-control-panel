<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-18 02:11
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Ui\Support;

final class TelemetryMetadataViewNormalizer
{
    /**
     * Normalize metadata values for safe UI rendering.
     *
     * @param   mixed  $value
     *
     * @return mixed
     */
    public function normalize(mixed $value): mixed
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $key => $val) {
                $out[(string)$key] = $this->normalize($val);
            }

            return $out;
        }

        if (is_string($value)) {
            return htmlspecialchars(
                $value,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );
        }

        if (is_int($value) || is_float($value) || is_bool($value)) {
            return $value;
        }

        if ($value === null) {
            return null;
        }

        // Fallback for unexpected types (objects, resources)
        return htmlspecialchars(
            sprintf('[%s]', get_debug_type($value)),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}

