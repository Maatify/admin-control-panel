<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Service\AdminCreationService;
use App\Domain\Exception\InvalidIdentifierStateException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use InvalidArgumentException;
use Throwable;

class AdminCreateController
{
    public function __construct(
        private readonly AdminCreationService $adminCreationService
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        if (!is_array($data)) {
            $data = [];
        }

        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $errors = [];

        // Validate Email
        if (!is_string($email) || empty($email)) {
            $errors['email'][] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Invalid email format';
        }

        // Validate Password
        if (!is_string($password) || empty($password)) {
            $errors['password'][] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors['password'][] = 'Password must be at least 8 characters';
        }

        if (!empty($errors)) {
             $payload = json_encode([
                'status' => 'error',
                'errors' => $errors
            ], JSON_THROW_ON_ERROR);
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(422);
        }

        try {
            $this->adminCreationService->createAdmin($email, $password);

            $payload = json_encode(['status' => 'success'], JSON_THROW_ON_ERROR);
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);

        } catch (InvalidIdentifierStateException $e) {
            $payload = json_encode([
                'status' => 'error',
                'message' => 'Admin already exists'
            ], JSON_THROW_ON_ERROR);
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(409);

        } catch (Throwable $e) {
            // Log internally if logger were available, but here we return generic error
            $payload = json_encode([
                'status' => 'error',
                'message' => 'Unable to create admin'
            ], JSON_THROW_ON_ERROR);
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
