<?php

declare(strict_types=1);

use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Exception\EntityAlreadyExistsException;
use Maatify\AdminKernel\Domain\Exception\EntityInUseException;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\Exception\InvalidOperationException;
use Maatify\AdminKernel\Domain\Exception\PermissionDeniedException;
use Maatify\Exceptions\Contracts\ApiAwareExceptionInterface;
use Maatify\Exceptions\Exception\MaatifyException;
use Maatify\I18n\Exception\DomainNotAllowedException;
use Maatify\Validation\Exceptions\ValidationFailedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

return function (App $app): void {
    // Helper to format consistent unified JSON response
    $unifiedJsonError = function (
        int $status,
        string $code,
        string $category,
        string $message,
        array $meta = [],
        bool $retryable = false
    ) use ($app): ResponseInterface {
        $payload = [
            'success' => false,
            'error' => [
                'code'      => $code,
                'category'  => $category,
                'message'   => $message,
                'meta'      => $meta,
                'retryable' => $retryable,
            ],
        ];

        $response = $app->getResponseFactory()->createResponse($status);
        $response->getBody()->write(
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        return $response->withHeader('Content-Type', 'application/json');
    };

    /**
     * 🔒 REQUIRED — Canonical
     * Enables JSON parsing for application/json
     */
    $app->addBodyParsingMiddleware();

    $errorMiddleware = $app->addErrorMiddleware(
        true,   // displayErrorDetails (dev only)
        false,  // logErrors
        false   // logErrorDetails
    );

    // 1️⃣ Validation (422) - UNIFIED
    $errorMiddleware->setErrorHandler(
        ValidationFailedException::class,
        function (
            ServerRequestInterface $request,
            Throwable $exception,
            bool $displayErrorDetails,
            bool $logErrors,
            bool $logErrorDetails
        ) use ($unifiedJsonError): ResponseInterface {

            // ✅ Telemetry (best-effort, never breaks error handler)
            try {
                $context = $request->getAttribute(RequestContext::class);
                if ($context instanceof RequestContext) {
                    // Telemetry Logic commented out in source, preserved here as comments
                }
            } catch (Throwable $e) {
                // swallow
            }

            /** @var ValidationFailedException $exception */
            return $unifiedJsonError(
                422,
                'INVALID_ARGUMENT',
                'VALIDATION',
                'Invalid request payload',
                ['validation_errors' => $exception->getErrors()]
            );
        }
    );

    // 2️⃣ 400 - UNIFIED
    $errorMiddleware->setErrorHandler(
        HttpBadRequestException::class,
        function (
            ServerRequestInterface $request,
            HttpBadRequestException $exception
        ) use ($unifiedJsonError) {
            return $unifiedJsonError(
                400,
                'BAD_REQUEST',
                'VALIDATION',
                $exception->getMessage()
            );
        }
    );

    $errorMiddleware->setErrorHandler(
        PermissionDeniedException::class,
        function (
            ServerRequestInterface $request,
            PermissionDeniedException $exception
        ) use ($unifiedJsonError) {
            return $unifiedJsonError(
                403,
                'PERMISSION_DENIED',
                'AUTHORIZATION',
                $exception->getMessage()
            );
        }
    );

    // 3️⃣ 401 - UNIFIED
    $errorMiddleware->setErrorHandler(
        HttpUnauthorizedException::class,
        function (
            ServerRequestInterface $request,
            HttpUnauthorizedException $exception
        ) use ($unifiedJsonError) {
            return $unifiedJsonError(
                401,
                'UNAUTHORIZED',
                'AUTHENTICATION',
                $exception->getMessage() ?: 'Authentication required.'
            );
        }
    );

    // 4️⃣ 403 - UNIFIED
    $errorMiddleware->setErrorHandler(
        HttpForbiddenException::class,
        function (
            ServerRequestInterface $request,
            HttpForbiddenException $exception
        ) use ($unifiedJsonError) {
            return $unifiedJsonError(
                403,
                'FORBIDDEN',
                'AUTHORIZATION',
                $exception->getMessage() ?: 'Access denied.'
            );
        }
    );

    // 5️⃣ 404 - UNIFIED
    $errorMiddleware->setErrorHandler(
        HttpNotFoundException::class,
        function (
            ServerRequestInterface $request,
            HttpNotFoundException $exception
        ) use ($unifiedJsonError) {
            return $unifiedJsonError(
                404,
                'RESOURCE_NOT_FOUND',
                'NOT_FOUND',
                $exception->getMessage() ?: 'Resource not found.'
            );
        }
    );

    $errorMiddleware->setErrorHandler(
        EntityAlreadyExistsException::class,
        function (
            ServerRequestInterface $request,
            Throwable $exception
        ) use ($unifiedJsonError) {
            return $unifiedJsonError(
                409,
                'ENTITY_ALREADY_EXISTS',
                'CONFLICT',
                $exception->getMessage()
            );
        }
    );

    $errorMiddleware->setErrorHandler(
        EntityInUseException::class,
        function (
            ServerRequestInterface $request,
            Throwable $exception
        ) use ($unifiedJsonError) {
            return $unifiedJsonError(
                409,
                'ENTITY_IN_USE',
                'CONFLICT',
                $exception->getMessage()
            );
        }
    );

    $errorMiddleware->setErrorHandler(
        EntityNotFoundException::class,
        function (
            ServerRequestInterface $request,
            Throwable $exception
        ) use ($unifiedJsonError) {
            return $unifiedJsonError(
                404,
                'NOT_FOUND',
                'NOT_FOUND',
                $exception->getMessage()
            );
        }
    );

    $errorMiddleware->setErrorHandler(
        InvalidOperationException::class,
        function (
            ServerRequestInterface $request,
            InvalidOperationException $exception
        ) use ($unifiedJsonError) {
            return $unifiedJsonError(
                409,
                'INVALID_OPERATION',
                'UNSUPPORTED',
                $exception->getMessage()
            );
        }
    );

    $errorMiddleware->setErrorHandler(
        DomainNotAllowedException::class,
        function (
            ServerRequestInterface $request,
            DomainNotAllowedException $exception
        ) use ($unifiedJsonError) {
            return $unifiedJsonError(
                422,
                'DOMAIN_NOT_ALLOWED',
                'BUSINESS_RULE',
                $exception->getMessage()
            );
        }
    );

    // 🆕 Unified Maatify Exception Handler
    $errorMiddleware->setErrorHandler(
        MaatifyException::class,
        function (
            ServerRequestInterface $request,
            MaatifyException $exception
        ) use ($unifiedJsonError): ResponseInterface {
            return $unifiedJsonError(
                $exception->getHttpStatus(),
                $exception->getErrorCode()->getValue(),
                $exception->getCategory()->getValue(),
                $exception->isSafe() ? $exception->getMessage() : 'Internal Server Error',
                $exception->getMeta(),
                $exception->isRetryable()
            );
        },
        true // 🔥 VERY IMPORTANT — handle subclasses
    );

    // 6️⃣ ❗ LAST — catch-all - UNIFIED & FIXED (No Rethrow)
    $errorMiddleware->setErrorHandler(
        Throwable::class,
        function (
            ServerRequestInterface $request,
            Throwable $exception,
            bool $displayErrorDetails
        ) use ($unifiedJsonError): ResponseInterface {

            try {
                /** @var RequestContext|null $context */
                $context = $request->getAttribute(RequestContext::class);
                // Telemetry logic placeholder
            } catch (Throwable) {
                // swallow
            }

            // A) ApiAware (Unified Model)
            if ($exception instanceof ApiAwareExceptionInterface) {
                return $unifiedJsonError(
                    $exception->getHttpStatus(),
                    $exception->getErrorCode()->getValue(),
                    $exception->getCategory()->getValue(),
                    $exception->isSafe() ? $exception->getMessage() : 'Internal Server Error',
                    $exception->getMeta(),
                    $exception->isRetryable()
                );
            }

            // B) Slim Http Exceptions (Map to Unified Model)
            if ($exception instanceof HttpBadRequestException) {
                return $unifiedJsonError(400, 'BAD_REQUEST', 'VALIDATION', $exception->getMessage());
            }
            if ($exception instanceof HttpUnauthorizedException) {
                return $unifiedJsonError(401, 'UNAUTHORIZED', 'AUTHENTICATION', $exception->getMessage());
            }
            if ($exception instanceof HttpForbiddenException) {
                return $unifiedJsonError(403, 'FORBIDDEN', 'AUTHORIZATION', $exception->getMessage());
            }
            if ($exception instanceof HttpNotFoundException) {
                return $unifiedJsonError(404, 'RESOURCE_NOT_FOUND', 'NOT_FOUND', $exception->getMessage());
            }
            if ($exception instanceof HttpMethodNotAllowedException) {
                return $unifiedJsonError(405, 'METHOD_NOT_ALLOWED', 'UNSUPPORTED', $exception->getMessage());
            }

            // C) Fallback System Error (Catch-All)
            $meta = [];

            // Meta: Always include "exception_class"
            $meta['exception_class'] = get_class($exception);

            if ($displayErrorDetails) {
                // Meta: Development only fields
                $meta['file'] = $exception->getFile();
                $meta['line'] = $exception->getLine();
                $meta['trace'] = $exception->getTraceAsString();

                // Message: Development -> real message
                $message = $exception->getMessage();
            } else {
                // Message: Production -> safe message
                $message = 'Internal Server Error';
            }

            return $unifiedJsonError(
                500,
                'INTERNAL_ERROR',
                'SYSTEM',
                $message,
                $meta
            );
        }
    );

};
