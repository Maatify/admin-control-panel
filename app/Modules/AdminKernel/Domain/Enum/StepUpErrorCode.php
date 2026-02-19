<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Enum;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;

enum StepUpErrorCode: string implements ErrorCodeInterface
{
    case STEP_UP_REQUIRED = 'STEP_UP_REQUIRED';

    public function getValue(): string
    {
        return $this->value;
    }
}
