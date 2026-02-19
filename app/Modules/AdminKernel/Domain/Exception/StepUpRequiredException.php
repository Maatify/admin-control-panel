<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Exception;

use Maatify\AdminKernel\Domain\Enum\StepUpErrorCode;
use Maatify\Exceptions\Constants\ErrorCategoryEnum;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Exception\MaatifyException;

class StepUpRequiredException extends MaatifyException
{
    private string $scope;

    public function __construct(string $scope, string $message = 'Additional authentication required.')
    {
        $this->scope = $scope;
        parent::__construct($message, 403);
    }

    public function getErrorCode(): ErrorCodeInterface
    {
        return StepUpErrorCode::STEP_UP_REQUIRED;
    }

    public function getCategory(): ErrorCategoryEnum
    {
        return ErrorCategoryEnum::AUTHORIZATION;
    }

    public function isSafe(): bool
    {
        return true;
    }

    public function getMeta(): array
    {
        return [
            'scope' => $this->scope,
        ];
    }
}
