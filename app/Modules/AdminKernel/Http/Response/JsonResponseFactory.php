<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-11 09:45
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Response;

use JsonException;
use JsonSerializable;
use Psr\Http\Message\ResponseInterface;

final class JsonResponseFactory
{
    /**
     * @param array<string,mixed> $data
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
            'error' => [
                'code' => $code,
                'message' => $message,
                'meta' => $meta,
            ],
        ];

        $response->getBody()->write(
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

}

