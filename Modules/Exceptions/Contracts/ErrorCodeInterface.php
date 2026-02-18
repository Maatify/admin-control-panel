<?php

declare(strict_types=1);

namespace Maatify\Exceptions\Contracts;

interface ErrorCodeInterface
{
    public function getValue(): string;
}
