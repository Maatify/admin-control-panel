<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Response;

use JsonSerializable;
use Maatify\Exceptions\Contracts\ApiAwareExceptionInterface;
use Psr\Http\Message\ResponseInterface;

final class JsonResponseFactory
{
    /**
     * @param array<string,mixed>|JsonSerializable $data
     */
    public function data(
        ResponseInterface $response,
        array|JsonSerializable $data,
        int $status = 200
    ): ResponseInterface {

        $response->getBody()->rewind();

        $response->getBody()->write(
            json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->withStatus($status);
    }

    /**
     * For Action endpoints (Canonical rule: 204 No Content)
     */
    public function noContent(ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->withStatus(204);
    }

    /**
     * Optional success payload (legacy support)
     */
    public function success(
        ResponseInterface $response,
        int $status = 200
    ): ResponseInterface {

        return $this->data(
            $response,
            [
                'success' => true,
            ],
            $status
        );
    }

    /**
     * Legacy manual error (Backward compatibility)
     *
     * @param array<string, mixed> $meta
     */
    public function error(
        ResponseInterface $response,
        string $message,
        int $status = 400,
        string $code = 'error',
        array $meta = []
    ): ResponseInterface {

        $payload = [
            'success' => false,
            'error' => [
                'code'    => $code,
                'message' => $message,
                'meta'    => $meta,
            ],
        ];

        return $this->data($response, $payload, $status);
    }

    /**
     * Canonical Exception-aware error
     */
    public function fromException(
        ResponseInterface $response,
        ApiAwareExceptionInterface $exception
    ): ResponseInterface {

        $message = $exception->isSafe()
            ? $exception->getMessage()
            : 'Internal Server Error';

        $payload = [
            'success' => false,
            'error' => [
                'code'      => $exception->getErrorCode()->getValue(),
                'category'  => $exception->getCategory()->getValue(),
                'message'   => $message,
                'meta'      => $exception->getMeta(),
                'retryable' => $exception->isRetryable(),
            ],
        ];

        return $this->data(
            $response,
            $payload,
            $exception->getHttpStatus()
        );
    }
}
