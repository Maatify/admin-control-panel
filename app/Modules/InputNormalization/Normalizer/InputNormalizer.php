<?php

declare(strict_types=1);

namespace App\Modules\InputNormalization\Normalizer;

use App\Modules\InputNormalization\Contracts\InputNormalizerInterface;

final class InputNormalizer implements InputNormalizerInterface
{
    public function normalize(array $input): array
    {
        // Pagination: per_page wins over limit
        if (array_key_exists('per_page', $input)) {
            // Canonical exists, it wins.
            // Ensure legacy key matches if present (optional, but safer for consistency if we wanted,
            // but strict rules say "Preserve original values" so we don't touch limit if per_page exists).
        } elseif (array_key_exists('limit', $input)) {
            // Canonical missing, Legacy exists. Map legacy to canonical.
            $input['per_page'] = $input['limit'];
        }

        // Date Range: from_date wins over from
        if (array_key_exists('from_date', $input)) {
            // Canonical exists.
        } elseif (array_key_exists('from', $input)) {
            $input['from_date'] = $input['from'];
        }

        // Date Range: to_date wins over to
        if (array_key_exists('to_date', $input)) {
            // Canonical exists.
        } elseif (array_key_exists('to', $input)) {
            $input['to_date'] = $input['to'];
        }

        return $input;
    }
}
