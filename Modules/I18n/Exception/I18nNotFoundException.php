<?php

declare(strict_types=1);

namespace Maatify\I18n\Exception;

use Maatify\Exceptions\Exception\NotFound\ResourceNotFoundMaatifyException;

abstract class I18nNotFoundException extends ResourceNotFoundMaatifyException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        ?\Maatify\Exceptions\Contracts\ErrorCodeInterface $errorCodeOverride = null
    ) {
        parent::__construct(
            message: $message,
            code: $code,
            previous: $previous,
            errorCodeOverride: $errorCodeOverride,
            policy: \Maatify\I18n\Domain\Policy\I18nErrorPolicy::instance()
        );
    }
}
