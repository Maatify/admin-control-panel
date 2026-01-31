<?php

declare(strict_types=1);

namespace Maatify\InputNormalization\Contracts;

interface InputNormalizerInterface
{
    /**
     * Normalize the input array by mapping legacy keys to canonical keys
     * and resolving precedence.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function normalize(array $input): array;
}
