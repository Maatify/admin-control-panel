<?php

declare(strict_types=1);

namespace App\Modules\InputNormalization\Normalizer;

use App\Modules\InputNormalization\Contracts\InputNormalizerInterface;

final class InputNormalizer implements InputNormalizerInterface
{
    public function normalize(array $input): array
    {
        // Pagination: per_page wins over limit
        if (!array_key_exists('per_page', $input) && array_key_exists('limit', $input)) {
            $input['per_page'] = $input['limit'];
        }

        // Date Range: from_date wins over from
        if (!array_key_exists('from_date', $input) && array_key_exists('from', $input)) {
            $input['from_date'] = $input['from'];
        }

        // Date Range: to_date wins over to
        if (!array_key_exists('to_date', $input) && array_key_exists('to', $input)) {
            $input['to_date'] = $input['to'];
        }

        return $input;
    }
}
