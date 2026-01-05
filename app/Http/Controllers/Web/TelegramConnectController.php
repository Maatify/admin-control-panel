<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Contracts\VerificationCodeGeneratorInterface;
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
        $adminId = $request->getAttribute('admin_id');
        if (!is_int($adminId)) {
            // Should not happen if middleware is correct
            throw new RuntimeException('Admin ID not found in request');
        }

        // Generate OTP
        // Identity: admin, ID: adminId
        // Purpose: telegram_channel_link
        $code = $this->generator->generate('admin', (string)$adminId, 'telegram_channel_link');

        return $this->view->render($response, 'telegram-connect.twig', [
            'otp' => $code->plainCode
        ]);
    }
}
