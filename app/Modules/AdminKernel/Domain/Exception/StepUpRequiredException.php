<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Exception;

use Maatify\Exceptions\Constants\ErrorCategoryEnum;
use Maatify\Exceptions\Constants\ErrorCodeEnum;
use Maatify\Exceptions\Exception\MaatifyException;

class StepUpRequiredException extends MaatifyException
{
    private string $scope;

    public function __construct(string $scope, string $message = 'Additional authentication required.')
    {
        $this->scope = $scope;
        parent::__construct($message, 403);
    }

    public function getErrorCode(): ErrorCodeEnum
    {
        // Reusing AUTHORIZATION_FAILED as generic if STEP_UP_REQUIRED isn't in Enum,
        // OR utilizing a custom value if Enum supports string values.
        // For strict compatibility with existing Enum, we might need to map it or assume Enum is extendable.
        // Given Phase-B strictness, let's stick to a safe default but override the string representation if possible.
        // HOWEVER, MaatifyException enforces ErrorCodeEnum return type.
        // If STEP_UP_REQUIRED is not in Enum, we must use a generic one.
        // BUT, the frontend EXPECTS "STEP_UP_REQUIRED".
        // The unified envelope uses $code = $exception->getErrorCode()->getValue().
        // If ErrorCodeEnum doesn't have STEP_UP, we have a problem.
        // Let's check ErrorCodeEnum first.
        return ErrorCodeEnum::PERMISSION_DENIED;
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
            // BRIDGE: Frontend normalizer expects 'code' in error object for unified.
            // But MaatifyException uses getErrorCode()->getValue() for the main 'code' field.
            // If we can't change ErrorCodeEnum, we can put the specific string code in meta as a hint,
            // BUT Frontend ErrorNormalizer checks data.error.code.
            // If getErrorCode() returns 'PERMISSION_DENIED', frontend breaks.
            //
            // HACK/FIX: The Global Handler (http.php) calls getErrorCode()->getValue().
            // We need to ensuring the resulting JSON has code="STEP_UP_REQUIRED".
            // Since we can't easily edit the Enum in this phase without risking other modules,
            // we will rely on the fact that we can override the code in the handler OR
            // we assume the Enum allows ad-hoc values or we use a valid one.

            // WAIT: We can't change Enum.
            // So we will use a workaround in http.php or ensure this exception is handled specifically
            // to inject the correct string code?
            //
            // BETTER: The unifiedJsonError function takes string $code.
            // In http.php:
            // $errorMiddleware->setErrorHandler(MaatifyException::class, ...)
            // uses $exception->getErrorCode()->getValue().
            //
            // If we throw StepUpRequiredException, it is a MaatifyException.
            // It will use getErrorCode()->getValue().
            //
            // If we cannot change ErrorCodeEnum, we must add a specific handler for StepUpRequiredException
            // in http.php to force the code string "STEP_UP_REQUIRED".
        ];
    }

    public function getStepUpCode(): string
    {
        return 'STEP_UP_REQUIRED';
    }
}
