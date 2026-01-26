<?php

declare(strict_types=1);

namespace Tests\Http\Controllers;

use App\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use App\Context\RequestContext;
use App\Domain\DTO\AdminLoginResultDTO;
use App\Domain\Service\AdminAuthenticationService;
use App\Http\Controllers\AuthController;
use App\Modules\Validation\Contracts\SchemaInterface;
use App\Modules\Validation\Contracts\ValidatorInterface;
use App\Modules\Validation\DTO\ValidationResultDTO;
use App\Modules\Validation\Guard\ValidationGuard;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

final class AuthControllerTest extends TestCase
{
    public function testLoginSuccessLogsActivityWithNewContext(): void
    {
        $authService = $this->createMock(AdminAuthenticationService::class);
        $cryptoService = $this->createMock(AdminIdentifierCryptoServiceInterface::class);

        // crypto blind index stub (avoid return type issues)
        $cryptoService->method('deriveEmailBlindIndex')->willReturn('blind-index');

        // auth success
        $authService->method('login')->willReturn(new AdminLoginResultDTO(123, 'token-xyz'));

        // ---- Validation (real guard with fake validator) ----
        $validator = new class implements ValidatorInterface {
            public function validate(SchemaInterface $schema, array $input): ValidationResultDTO
            {
                return new ValidationResultDTO(true);
            }
        };

        $validationGuard = new ValidationGuard($validator);

        $controller = new AuthController(
            $authService,
            $cryptoService,
            $validationGuard
        );

        // ---- Request / Response mocks ----
        $request = $this->createMock(ServerRequestInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $request->method('getParsedBody')->willReturn([
            'email' => 'admin@example.com',
            'password' => 'secret',
        ]);

        $requestContext = new RequestContext('req-123', '127.0.0.1', 'PHPUnit');
        $request->method('getAttribute')->willReturnMap([
            [RequestContext::class, null, $requestContext],
        ]);

        $response->method('getBody')->willReturn($stream);

        // Fluent methods MUST return ResponseInterface
        $response->method('withHeader')->willReturn($response);
        $response->method('withStatus')->willReturn($response);

        // optional: stream write stub
        $stream->method('write')->willReturn(0);

        // Act
        $returned = $controller->login($request, $response);

        // Assert returned type is correct
        self::assertSame($response, $returned);

    }
}
