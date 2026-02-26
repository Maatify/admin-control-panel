<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Tests\Http\Controllers;

use Maatify\AdminKernel\Domain\DTO\RedirectTokenResponseDTO;
use Maatify\AdminKernel\Domain\Security\RedirectToken\RedirectTokenServiceInterface;
use Maatify\AdminKernel\Http\Controllers\Api\Auth\RedirectTokenController;
use Maatify\Validation\Guard\ValidationGuard;
use Maatify\Validation\Validator\RespectValidator;
use Maatify\Validation\DTO\ValidationResultDTO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class RedirectTokenControllerTest extends TestCase
{
    private RedirectTokenServiceInterface&MockObject $tokenService;
    private ValidationGuard $validationGuard;
    private RedirectTokenController $controller;

    protected function setUp(): void
    {
        $this->tokenService = $this->createMock(RedirectTokenServiceInterface::class);

        // ValidationGuard is final, so we use a real instance with a mocked Validator
        $validator = $this->createMock(\Maatify\Validation\Contracts\ValidatorInterface::class);
        $validator->method('validate')->willReturn(new \Maatify\Validation\DTO\ValidationResultDTO(true));

        $this->validationGuard = new ValidationGuard($validator);

        $this->controller = new RedirectTokenController($this->tokenService, $this->validationGuard);
    }

    public function testCreate_ValidRequest_ReturnsToken(): void
    {
        $path = '/dashboard';
        $token = 'signed_token_123';
        $requestData = ['path' => $path];

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($requestData);

        $this->tokenService->expects($this->once())
            ->method('create')
            ->with($path)
            ->willReturn($token);

        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);

        // The DTO implements JsonSerializable
        $expectedJson = json_encode([
            'token' => $token,
            'redirect_url' => '/2fa/verify?r=' . $token
        ], JSON_THROW_ON_ERROR);

        $stream->expects($this->once())
            ->method('write')
            ->with($expectedJson);

        $response->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $result = $this->controller->create($request, $response);

        $this->assertSame($response, $result);
    }
}
