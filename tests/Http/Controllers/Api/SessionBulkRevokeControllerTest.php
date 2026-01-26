<?php

declare(strict_types=1);

namespace Tests\Http\Controllers\Api;

use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\Service\AuthorizationService;
use App\Domain\Service\SessionRevocationService;
use App\Http\Controllers\Api\SessionBulkRevokeController;
use App\Modules\Validation\Guard\ValidationGuard;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

final class SessionBulkRevokeControllerTest extends TestCase
{
    public function testInvokeSuccess(): void
    {
        $revocationService = $this->createMock(SessionRevocationService::class);
        $authzService = $this->createMock(AuthorizationService::class);

        $validator = $this->createMock(\App\Modules\Validation\Contracts\ValidatorInterface::class);
        // Assuming validation result DTO is simple or just checking valid=true
        $validationResult = new \App\Modules\Validation\DTO\ValidationResultDTO(true, []);
        $validator->method('validate')->willReturn($validationResult);
        $validationGuard = new ValidationGuard($validator);

        $controller = new SessionBulkRevokeController(
            $revocationService,
            $authzService,
            $validationGuard,
        );

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $adminContext = new AdminContext(123);
        $requestContext = new RequestContext('req-1', '1.2.3.4', 'test');

        $request->method('getAttribute')->willReturnMap([
            [\App\Context\AdminContext::class, null, $adminContext],
            [RequestContext::class, null, $requestContext],
        ]);

        $request->method('getCookieParams')->willReturn(['auth_token' => 'token-123']);
        $request->method('getParsedBody')->willReturn(['session_ids' => ['hash-1', 'hash-2']]);

        $response->method('getBody')->willReturn($stream);
        $response->method('withHeader')->willReturn($response);
        $response->method('withStatus')->with(200)->willReturn($response);

        // Act
        $controller($request, $response);
    }
}
