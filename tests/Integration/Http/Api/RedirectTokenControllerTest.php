<?php

declare(strict_types=1);

namespace Tests\Integration\Http\Api;

use Maatify\AdminKernel\Context\AdminContext;
use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\DTO\RedirectTokenResponseDTO;
use Maatify\AdminKernel\Domain\Security\RedirectToken\RedirectTokenServiceInterface;
use Maatify\AdminKernel\Http\Controllers\Api\Auth\RedirectTokenController;
use Maatify\Validation\Contracts\ValidatorInterface;
use Maatify\Validation\DTO\ValidationResultDTO;
use Maatify\Validation\Guard\ValidationGuard;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

class RedirectTokenControllerTest extends TestCase
{
    private RedirectTokenServiceInterface&MockObject $tokenService;
    private ValidationGuard $validationGuard;
    private RedirectTokenController $controller;

    protected function setUp(): void
    {
        $this->tokenService = $this->createMock(RedirectTokenServiceInterface::class);

        $validatorMock = $this->createMock(ValidatorInterface::class);
        // ValidationResultDTO is final, cannot be mocked easily with standard mock.
        // We can create a real instance if it's a simple DTO.
        $resultDTO = new ValidationResultDTO(true);
        $validatorMock->method('validate')->willReturn($resultDTO);

        $this->validationGuard = new ValidationGuard($validatorMock);
        $this->controller = new RedirectTokenController($this->tokenService, $this->validationGuard);
    }

    public function testCreateGeneratesTokenAndUrl(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/auth/sign-redirect')
            ->withParsedBody(['path' => '/dashboard?foo=bar']);

        $token = 'signed-token-123';
        $this->tokenService->expects($this->once())
            ->method('create')
            ->with('/dashboard?foo=bar')
            ->willReturn($token);

        $response = $this->controller->create($request, new Response());

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);

        $this->assertSame($token, $body['token']);
        $this->assertSame('/2fa/verify?r=' . $token, $body['redirect_url']);
    }

    public function testCreateValidatesInput(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/auth/sign-redirect')
            ->withParsedBody(['path' => '']); // Empty path

        $this->expectException(\Maatify\Validation\Exceptions\ValidationFailedException::class);

        // ValidationGuard will throw if validation fails.
        // We mocked validator earlier to return true, so we need to mock failure here.
        // But setUp() created a fixed guard. We need to replace it or verify logic differently.
        // Wait, Validator is injected.
        // So for this test, we cannot easily change the behavior of the *injected* validator
        // because the controller was created in setUp with a specific mock.
        // We should recreate the controller for this test or use a different pattern.

        // Let's assume validation passes for now or skip this test if we can't easily mock failure
        // without recreating dependencies.
        // Recreating is fine.

        $tokenService = $this->createMock(RedirectTokenServiceInterface::class);
        $validatorMock = $this->createMock(ValidatorInterface::class);
        $resultMock = new \Maatify\Validation\DTO\ValidationResultDTO(false, ['path' => 'Required']);
        $validatorMock->method('validate')->willReturn($resultMock);

        $guard = new ValidationGuard($validatorMock);
        $controller = new RedirectTokenController($tokenService, $guard);

        $controller->create($request, new Response());
    }
}
