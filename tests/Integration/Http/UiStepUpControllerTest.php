<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use Maatify\AdminKernel\Http\Controllers\Ui\UiStepUpController;
use Maatify\AdminKernel\Http\Controllers\Web\TwoFactorController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

final class UiStepUpControllerTest extends TestCase
{
    private UiStepUpController $controller;
    private TwoFactorController&MockObject $twoFactorControllerMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->twoFactorControllerMock = $this->createMock(TwoFactorController::class);
        $this->controller = new UiStepUpController($this->twoFactorControllerMock);
    }

    public function testVerifyPropagatesAttributes(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/2fa/verify')
            ->withQueryParams(['scope' => 'security', 'return_to' => '/admins/create']);

        $response = new Response();

        $this->twoFactorControllerMock
            ->expects($this->once())
            ->method('verify')
            ->with(
                $this->callback(function (ServerRequestInterface $req) {
                    return $req->getAttribute('scope') === 'security'
                        && $req->getAttribute('return_to') === '/admins/create'
                        && $req->getAttribute('template') === 'pages/2fa_verify.twig';
                }),
                $response
            )
            ->willReturn($response);

        $this->controller->verify($request, $response);
    }

    public function testDoVerifyPropagatesAttributes(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/2fa/verify')
            ->withParsedBody(['code' => '123456', 'scope' => 'security', 'return_to' => '/admins/create']);

        $response = new Response();

        $this->twoFactorControllerMock
            ->expects($this->once())
            ->method('doVerify')
            ->with(
                $this->callback(function (ServerRequestInterface $req) {
                    return $req->getAttribute('scope') === 'security'
                        && $req->getAttribute('return_to') === '/admins/create'
                        && $req->getAttribute('template') === 'pages/2fa_verify.twig';
                }),
                $response
            )
            ->willReturn($response);

        $this->controller->doVerify($request, $response);
    }
}
