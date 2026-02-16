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
        $response->getBody()->write(
            json_encode($data, JSON_THROW_ON_ERROR)
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    public function success(
        ResponseInterface $response,
        int $status = 200
    ): ResponseInterface {
        return $this->data(
            $response,
            ['status' => 'ok'],
            $status
        );
    }

    /**
     * Legacy manual error (ما نكسرش القديم)
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
                'code' => $code,
                'message' => $message,
                'meta' => $meta,
            ],
        ];

        return $this->data($response, $payload, $status);
    }

    /**
     * NEW: Exception-aware error
     */
    public function fromException(
        ResponseInterface $response,
        ApiAwareExceptionInterface $exception
    ): ResponseInterface {

        $payload = [
            'success' => false,
            'error' => [
                'code'     => $exception->getErrorCode()->value,
                'category' => $exception->getCategory()->value,
                'message'  => $exception->isSafe()
                    ? $exception->getMessage()
                    : 'Internal Server Error',
                'meta'     => $exception->getMeta(),
                'retryable'=> $exception->isRetryable(),
            ],
        ];

        return $this->data(
            $response,
            $payload,
            $exception->getHttpStatus()
        );
    }
}
