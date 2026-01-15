<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Contracts\VerificationCodeGeneratorInterface;
use App\Domain\Enum\IdentityTypeEnum;
use App\Domain\Enum\VerificationPurposeEnum;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use RuntimeException;

readonly class TelegramConnectController
{
    public function __construct(
        private VerificationCodeGeneratorInterface $generator,
        private Twig $view
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        // Admin ID must be present (SessionGuardMiddleware ensures this for protected routes)
        $adminContext = $request->getAttribute(\App\Context\AdminContext::class);
        if (!$adminContext instanceof \App\Context\AdminContext) {
            // Fail-closed if AdminContext is missing
            throw new RuntimeException('AdminContext missing');
        }
        $adminId = $adminContext->adminId;

        // Generate OTP
        // Identity: admin, ID: adminId
        // Purpose: telegram_channel_link
        $code = $this->generator->generate(IdentityTypeEnum::Admin, (string)$adminId, VerificationPurposeEnum::TelegramChannelLink);

        return $this->view->render($response, 'telegram-connect.twig', [
            'otp' => $code->plainCode
        ]);
    }
}
