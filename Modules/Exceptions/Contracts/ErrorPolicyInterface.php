<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Contracts;

interface ErrorPolicyInterface
{
    public function validate(
        ErrorCodeInterface $code,
        ErrorCategoryInterface $category
    ): void;

    public function severity(
        ErrorCategoryInterface $category
    ): int;
}

