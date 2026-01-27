<?php

declare(strict_types=1);

use App\Context\RequestContext;
use App\Bootstrap\Container;
use App\Domain\Exception\InvalidOperationException;
use App\Modules\Validation\Exceptions\ValidationFailedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Create Container (This handles ENV loading and AdminConfigDTO)
$container = Container::create();

// Create App
AppFactory::setContainer($container);
$app = AppFactory::create();

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
    true,   // displayErrorDetails (dev فقط)
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
//                $factory = $app->getContainer()->get(HttpTelemetryRecorderFactory::class);
//                if ($factory instanceof HttpTelemetryRecorderFactory) {
//                    /** @var ValidationFailedException $exception */
//                    $factory->system($context)->record(
//                        eventType: TelemetryEventTypeEnum::SYSTEM_EXCEPTION,
//                        severity: TelemetrySeverityEnum::WARN,
//                        metadata: [
//                            'exception_class' => get_class($exception),
//                            'exception_message' => $exception->getMessage(),
//                            'file' => $exception->getFile(),
//                            'line' => $exception->getLine(),
//                            'errors' => $exception->getErrors(),
//                        ]
//                    );
//                }
            }
        } catch (Throwable $e) {
            try {
                $logger = $app->getContainer()->get(\Psr\Log\LoggerInterface::class);
                if ($logger instanceof \Psr\Log\LoggerInterface) {
                    $logger->warning('Telemetry failure in ValidationFailedException handler', [
                        'exception_class' => get_class($e),
                        'message' => $e->getMessage(),
                    ]);
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
    \App\Domain\Exception\EntityAlreadyExistsException::class,
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
    \App\Domain\Exception\EntityNotFoundException::class,
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
        \Psr\Http\Message\ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails
    ) use ($app) {
        try {
            /** @var RequestContext|null $context */
            $context = $request->getAttribute(RequestContext::class);

//            if ($context instanceof RequestContext) {
//                /** @var HttpTelemetryRecorderFactory $factory */
//                $factory = $app->getContainer()->get(HttpTelemetryRecorderFactory::class);
//
//                $factory
//                    ->system($context)
//                    ->record(
//                        TelemetryEventTypeEnum::SYSTEM_EXCEPTION,
//                        TelemetrySeverityEnum::ERROR,
//                        [
//                            'exception_class' => $exception::class,
//                            'message'         => $exception->getMessage(),
//                            'file'            => $exception->getFile(),
//                            'line'            => $exception->getLine(),
//                            'route_name'      => $context->getRouteName(),
//                        ]
//                    );
//            }
        } catch (Throwable $telemetryFailure) {
            try {
                /** @var \Psr\Log\LoggerInterface $logger */
                $logger = $app->getContainer()->get(\Psr\Log\LoggerInterface::class);
                $logger->warning('Telemetry failure while handling Throwable', [
                    'exception_class' => $telemetryFailure::class,
                    'message' => $telemetryFailure->getMessage(),
                ]);
            } catch (Throwable) {
                // swallow
            }
        }

        throw $exception;
    }
);


// Register Routes
$routes = require __DIR__ . '/../routes/web.php';
$routes($app);

// Run App
$app->run();
