<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Contracts;

interface ErrorCategoryInterface
{
    public function getValue(): string;
}
