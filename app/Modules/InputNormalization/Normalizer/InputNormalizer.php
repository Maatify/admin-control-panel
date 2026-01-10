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

        // Date Range: date.from wins over from
        // We must ensure 'date' is an array (or missing) to avoid scalar overwrites/crashes
        if (array_key_exists('from', $input)) {
            if (!array_key_exists('date', $input)) {
                $input['date'] = [];
            }
            if (is_array($input['date']) && !array_key_exists('from', $input['date'])) {
                $input['date']['from'] = $input['from'];
            }
        }

        // Date Range: date.to wins over to
        if (array_key_exists('to', $input)) {
            if (!array_key_exists('date', $input)) {
                $input['date'] = [];
            }
            if (is_array($input['date']) && !array_key_exists('to', $input['date'])) {
                $input['date']['to'] = $input['to'];
            }
        }

        return $input;
    }
}
