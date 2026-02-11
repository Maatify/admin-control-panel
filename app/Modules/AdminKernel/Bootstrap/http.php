<?php

declare(strict_types=1);

use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Exception\EntityAlreadyExistsException;
use Maatify\AdminKernel\Domain\Exception\EntityInUseException;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\Exception\InvalidOperationException;
use Maatify\AdminKernel\Domain\Exception\PermissionDeniedException;
use Maatify\I18n\Exception\DomainNotAllowedException;
use Maatify\Validation\Exceptions\ValidationFailedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

return function (App $app): void {
    $httpJsonError = function (
        int $status,
        string $code,
        string $message
    ) use ($app): ResponseInterface {
        $payload = json_encode(
            [
                'message' => $message,
                'code'    => $code,
            ],
            JSON_THROW_ON_ERROR
        );

        $response = $app->getResponseFactory()->createResponse($status);
        $response->getBody()->write($payload);

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

    // 1️⃣ Validation (422)
    $errorMiddleware->setErrorHandler(
        ValidationFailedException::class,
        function (
            ServerRequestInterface $request,
            Throwable $exception,
            bool $displayErrorDetails,
            bool $logErrors,
            bool $logErrorDetails
        ) use ($app): ResponseInterface {

            // ✅ Telemetry (best-effort, never breaks error handler)
            try {
                $context = $request->getAttribute(RequestContext::class);
                if ($context instanceof RequestContext) {
                    // Telemetry Logic commented out in source, preserved here as comments
                }
            } catch (Throwable $e) {
                try {
                    $container = $app->getContainer();
                    if ($container !== null) {
                        $logger = $container->get(\Psr\Log\LoggerInterface::class);
                        if ($logger instanceof \Psr\Log\LoggerInterface) {
                            $logger->warning('Telemetry failure in ValidationFailedException handler', [
                                'exception_class' => get_class($e),
                                'message' => $e->getMessage(),
                            ]);
                        }
                    }
                } catch (Throwable) {
                    // swallow
                }
            }

            /** @var ValidationFailedException $exception */
            $payload = json_encode(
                [
                    'error' => 'Invalid request payload',
                    'errors' => $exception->getErrors()
                ],
                JSON_THROW_ON_ERROR
            );

            $response = $app->getResponseFactory()->createResponse(422);
            $response->getBody()->write($payload);

            return $response->withHeader('Content-Type', 'application/json');
        }
    );

    // 2️⃣ 400
    $errorMiddleware->setErrorHandler(
        HttpBadRequestException::class,
        function (
            ServerRequestInterface $request,
            HttpBadRequestException $exception
        ) use ($httpJsonError) {
            return $httpJsonError(
                400,
                'BAD_REQUEST',
                $exception->getMessage()
            );
        }
    );

    $errorMiddleware->setErrorHandler(
        PermissionDeniedException::class,
        function (
            ServerRequestInterface $request,
            PermissionDeniedException $exception
        ) use ($httpJsonError) {
            return $httpJsonError(
                403,
                'PERMISSION_DENIED',
                $exception->getMessage()
            );
        }
    );

    // 3️⃣ 401
    $errorMiddleware->setErrorHandler(
        HttpUnauthorizedException::class,
        function (
            ServerRequestInterface $request,
            HttpUnauthorizedException $exception
        ) use ($httpJsonError) {
            return $httpJsonError(
                401,
                'UNAUTHORIZED',
                $exception->getMessage() ?: 'Authentication required.'
            );
        }
    );

    // 4️⃣ 403
    $errorMiddleware->setErrorHandler(
        HttpForbiddenException::class,
        function (
            ServerRequestInterface $request,
            HttpForbiddenException $exception
        ) use ($httpJsonError) {
            return $httpJsonError(
                403,
                'FORBIDDEN',
                $exception->getMessage() ?: 'Access denied.'
            );
        }
    );

    // 5️⃣ 404
    $errorMiddleware->setErrorHandler(
        HttpNotFoundException::class,
        function (
            ServerRequestInterface $request,
            HttpNotFoundException $exception
        ) use ($httpJsonError) {
            return $httpJsonError(
                404,
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
        ) use ($httpJsonError) {
            return $httpJsonError(
                409,
                'ENTITY_ALREADY_EXISTS',
                $exception->getMessage()
            );
        }
    );

    $errorMiddleware->setErrorHandler(
        EntityInUseException::class,
        function (
            ServerRequestInterface $request,
            Throwable $exception
        ) use ($httpJsonError) {
            return $httpJsonError(
                409,
                'ENTITY_IN_USE',
                $exception->getMessage()
            );
        }
    );

    $errorMiddleware->setErrorHandler(
        EntityNotFoundException::class,
        function (
            ServerRequestInterface $request,
            Throwable $exception
        ) use ($httpJsonError) {
            return $httpJsonError(
                404,
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
        ) use ($httpJsonError) {
            return $httpJsonError(
                409,
                'INVALID_OPERATION',
                $exception->getMessage()
            );
        }
    );

    // 6️⃣ ❗ LAST — catch-all
    $errorMiddleware->setErrorHandler(
        Throwable::class,
        function (
            ServerRequestInterface $request,
            Throwable $exception,
            bool $displayErrorDetails
        ) use ($app) {
            try {
                /** @var RequestContext|null $context */
                $context = $request->getAttribute(RequestContext::class);
                // Telemetry logic commented out in source
            } catch (Throwable $telemetryFailure) {
                try {
                    $container = $app->getContainer();
                    if ($container !== null) {
                        /** @var \Psr\Log\LoggerInterface $logger */
                        $logger = $container->get(\Psr\Log\LoggerInterface::class);
                        $logger->warning('Telemetry failure while handling Throwable', [
                            'exception_class' => $telemetryFailure::class,
                            'message' => $telemetryFailure->getMessage(),
                        ]);
                    }
                } catch (Throwable) {
                    // swallow
                }
            }

            throw $exception;
        }
    );

    $errorMiddleware->setErrorHandler(
        DomainNotAllowedException::class,
        function (
            ServerRequestInterface $request,
            DomainNotAllowedException $exception
        ) use ($httpJsonError) {
            return $httpJsonError(
                422,
                'DOMAIN_NOT_ALLOWED',
                $exception->getMessage()
            );
        }
    );



};
